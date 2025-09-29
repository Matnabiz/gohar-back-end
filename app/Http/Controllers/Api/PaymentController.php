<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\MellatGateway;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    protected $gateway;

    public function __construct(MellatGateway $gateway)
    {
        $this->gateway = $gateway;
    }

    // START payment: call after order created (or pass order_id)
    public function start(Request $request)
    {
        $request->validate(['order_id' => 'required|integer']);
        $order = Order::findOrFail($request->order_id);

        // compute amount that should be sent to gateway (Rials/Tomans)
        $mult = config('services.mellat.amount_multiplier', 1);
        $amount = (int) ($order->total * $mult); // be sure order->total numeric

        // merchant_order_id must be unique for each bpPayRequest
        $merchantOrderId = (string) $order->id; // OK to use DB id (unique)
        $payment = Payment::create([
            'order_id' => $order->id,
            'merchant_order_id' => $merchantOrderId,
            'amount' => $amount,
            'status' => 'initiated',
        ]);

        $callbackUrl = config('services.mellat.callback'); // must be publicly accessible
        $res = $this->gateway->bpPayRequest($merchantOrderId, $amount, $callbackUrl);

        if (isset($res['error'])) {
            $payment->status = 'failed';
            $payment->raw_response = $res['message'] ?? json_encode($res);
            $payment->save();
            return response()->json(['status'=>'fail','message'=>'Gateway error: '.$res['message']], 500);
        }

        $code = $res['code'] ?? null;
        $ref = $res['ref'] ?? null;
        $payment->raw_response = $res['raw'] ?? null;

        if ($code === '0' && $ref) {
            $payment->ref_id = $ref;
            $payment->status = 'initiated';
            $payment->save();

            // return ref to frontend (frontend will POST RefId to startpay URL)
            return response()->json([
                'status' => 'ok',
                'ref' => $ref,
                'start_url' => config('services.mellat.startpay'),
                'payment_id' => $payment->id,
            ]);
        }

        $payment->status = 'failed';
        $payment->save();
        return response()->json(['status'=>'fail','message'=>'Gateway returned code '.$code], 400);
    }

    // CALLBACK endpoint that Mellat posts to. This must be public and on your domain.
    public function callback(Request $request)
    {
        // Mellat posts: RefId, ResCode, SaleOrderId, SaleReferenceId, CardHolderInfo
        $resCode = (string) ($request->input('ResCode') ?? '');
        $refId = $request->input('RefId');
        $saleOrderId = $request->input('SaleOrderId');
        $saleReferenceId = $request->input('SaleReferenceId');

        // Optional IP whitelist check
        $allowed = config('services.mellat.allowed_ips', []);
        if (!empty($allowed)) {
            $ip = $request->ip();
            if (!in_array($ip, $allowed)) {
                \Log::warning('Mellat callback from unknown IP', ['ip'=>$ip,'payload'=>$request->all()]);
                // reply 403 so we can investigate — but many banks expect 200; choose action by policy
                return response('Forbidden', 403);
            }
        }

        // find payment by ref_id or merchant_order_id (SaleOrderId)
        $payment = null;
        if ($refId) {
            $payment = Payment::where('ref_id', $refId)->latest()->first();
        }
        if (!$payment && $saleOrderId) {
            $payment = Payment::where('merchant_order_id', $saleOrderId)->latest()->first();
        }
        if (!$payment) {
            \Log::warning('Mellat callback: payment not found', $request->all());
            // respond 200 so bank isn't retried. But log for human check.
            return response('OK', 200);
        }

        $payment->sale_order_id = $saleOrderId;
        $payment->sale_reference_id = $saleReferenceId;
        $payment->raw_response = json_encode($request->all());
        $payment->save();

        if ($resCode !== '0') {
            $payment->status = 'failed';
            $payment->save();
            // redirect the user (bank will follow this response)
            $frontendFailUrl = config('app.url') . '/payment/failure?order_id=' . $payment->order_id . '&code=' . $resCode;
            return redirect($frontendFailUrl);
        }

        // For verify, create a new unique verify id (must be unique)
        $verifyOrderId = time() + $payment->id;
        $payment->verify_order_id = (string)$verifyOrderId;
        $payment->save();

        // 1) Verify
        $verifyRes = $this->gateway->bpVerifyRequest($verifyOrderId, $saleOrderId, $saleReferenceId);
        if (isset($verifyRes['error'])) {
            \Log::error('Mellat verify error', ['err'=>$verifyRes,'payment'=>$payment]);
            // Try inquiry later — mark pending
            $payment->status = 'pending';
            $payment->raw_response .= "\nverify_error:".($verifyRes['message'] ?? '');
            $payment->save();
            $frontendPending = config('app.url') . '/payment/pending?order_id=' . $payment->order_id;
            return redirect($frontendPending);
        }

        $verifyCode = $verifyRes['code'] ?? null;
        $payment->raw_response .= "\nverify_raw:" . ($verifyRes['raw'] ?? '');
        $payment->save();

        if ($verifyCode !== '0') {
            \Log::warning('Mellat verify failed', ['code'=>$verifyCode,'payment'=>$payment->id]);
            $payment->status = 'failed';
            $payment->save();
            $frontendFailUrl = config('app.url') . '/payment/failure?order_id=' . $payment->order_id . '&code=' . $verifyCode;
            return redirect($frontendFailUrl);
        }

        // 2) Settle (finalize)
        $settleRes = $this->gateway->bpSettleRequest($verifyOrderId, $saleOrderId, $saleReferenceId);
        if (isset($settleRes['error'])) {
            \Log::error('Mellat settle error', ['err'=>$settleRes,'payment'=>$payment]);
            $payment->status = 'pending';
            $payment->raw_response .= "\nsettle_error:".($settleRes['message'] ?? '');
            $payment->save();
            $frontendPending = config('app.url') . '/payment/pending?order_id=' . $payment->order_id;
            return redirect($frontendPending);
        }

        $settleCode = $settleRes['code'] ?? null;
        $payment->raw_response .= "\nsettle_raw:" . ($settleRes['raw'] ?? '');
        $payment->save();

        // According to docs, settle code 0 = success, 45 = already settled (treat as success)
        if ($settleCode === '0' || $settleCode === '45') {
            $payment->status = 'settled';
            $payment->save();

            // update order status (adjust for your order model)
            $order = $payment->order;
            $order->status = 'paid';
            $order->transaction_id = $saleReferenceId;
            $order->save();

            $frontendSuccess = config('app.url') . '/payment/success?order_id=' . $order->id;
            return redirect($frontendSuccess);
        }

        // otherwise treat as failure
        $payment->status = 'failed';
        $payment->save();
        $frontendFailUrl = config('app.url') . '/payment/failure?order_id=' . $payment->order_id . '&code=' . $settleCode;
        return redirect($frontendFailUrl);
    }
}
