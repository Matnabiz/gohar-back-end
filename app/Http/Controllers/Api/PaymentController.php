<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\MellatGateway;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    protected $gateway;

    public function __construct(MellatGateway $gateway)
    {
        $this->gateway = $gateway;
    }

    public function start(Request $request)
    {
        $request->validate(['order_id' => 'required|integer']);
        $order = Order::findOrFail($request->order_id);

        // Block if already paid / settled
        $finalStatuses = ['paid', 'settled', 'completed'];
        if (in_array($order->status, $finalStatuses, true)) {
            return response()->json([
                'status' => 'fail',
                'message' => 'این سفارش قبلاً پرداخت شده است.'
            ], 400);
        }

        // compute amount to send to gateway (Rials/Tomans)
        $mult = config('services.mellat.amount_multiplier', 1);
        $amount = (int) round($order->total * $mult);

        if ($amount <= 0) {
            return response()->json([
                'status' => 'fail',
                'message' => 'مبلغ تراکنش نامعتبر است.'
            ], 400);
        }

        // Generate unique merchant_order_id for each attempt
        // Format: {orderId}-{timestamp}-{random}
        $merchantOrderId = (string) ($order->id . str_pad(mt_rand(0,999999), 6, '0', STR_PAD_LEFT));

        // Create a new payment record for this attempt
        try {
            $payment = Payment::create([
                'order_id' => $order->id,
                'merchant_order_id' => $merchantOrderId,
                'amount' => $amount,
                'status' => 'initiated',
            ]);
        } catch (\Exception $e) {
            Log::error('Payment create failed', ['err' => $e->getMessage(), 'order_id' => $order->id]);
            return response()->json([
                'status' => 'fail',
                'message' => 'خطا در ایجاد رکورد پرداخت'
            ], 500);
        }

        $callbackUrl = config('services.mellat.callback'); // must be publicly accessible
        $startUrlConfig = config('services.mellat.startpay') ?: 'https://bpm.shaparak.ir/pgwchannel/startpay.mellat';

        // Call gateway
        try {
            $res = $this->gateway->bpPayRequest($merchantOrderId, $amount, $callbackUrl);
        } catch (\Exception $e) {
            // Save error on payment and return server error
            $payment->status = 'failed';
            $payment->raw_response = 'exception: ' . $e->getMessage();
            $payment->save();

            Log::error('Gateway call exception', ['err' => $e->getMessage(), 'payment_id' => $payment->id]);

            return response()->json([
                'status' => 'fail',
                'message' => 'Gateway error: ' . $e->getMessage()
            ], 500);
        }

        // If gateway wrapper returned an error array
        if (isset($res['error'])) {
            $payment->status = 'failed';
            $payment->raw_response = $res['message'] ?? json_encode($res);
            $payment->save();

            Log::warning('Gateway returned wrapper error', ['res' => $res, 'payment_id' => $payment->id]);

            return response()->json([
                'status' => 'fail',
                'message' => 'Gateway error: ' . ($res['message'] ?? 'نامشخص')
            ], 500);
        }

        // Normalize response fields (some client wrappers return array, some return ['code','ref'] etc.)
        $code = null;
        $ref = null;
        $raw = null;

        if (is_string($res)) {
            // e.g. "0,AF82..."
            $raw = $res;
            $parts = explode(',', trim($res), 2);
            $code = $parts[0] ?? null;
            $ref = $parts[1] ?? null;
        } elseif (is_array($res)) {
            // prefer named keys
            $code = $res['code'] ?? ($res[0] ?? null);
            $ref = $res['ref'] ?? ($res[1] ?? null);
            $raw = $res['raw'] ?? json_encode($res);
        } elseif (is_object($res)) {
            $raw = json_encode($res);
            $code = property_exists($res, 'code') ? $res->code : (property_exists($res, 'return') ? $res->return : null);
            // try parsing if return has comma
            if (is_string($code) && str_contains($code, ',')) {
                $parts = explode(',', $code, 2);
                $code = $parts[0] ?? $code;
                $ref = $parts[1] ?? $ref;
            }
        } else {
            $raw = json_encode($res);
        }

        // persist raw response at least
        $payment->raw_response = $raw ?? ($res['raw'] ?? json_encode($res));

        // success case — code '0' with a RefId
        if ((string)$code === '0' && !empty($ref)) {
            $payment->ref_id = (string)$ref;
            $payment->status = 'initiated';
            $payment->save();

            return response()->json([
                'status' => 'ok',
                'ref' => $ref,
                'start_url' => $startUrlConfig,
                'payment_id' => $payment->id,
            ]);
        }

        // otherwise treat as gateway-level failure
        $payment->status = 'failed';
        $payment->save();

        Log::warning('Gateway returned non-0 code', [
            'code' => $code,
            'ref' => $ref,
            'payment_id' => $payment->id,
            'raw' => $payment->raw_response,
        ]);

        return response()->json([
            'status' => 'fail',
            'message' => 'Gateway returned code ' . ($code ?? 'null')
        ], 400);
    }


    // CALLBACK endpoint that Mellat posts to. This must be public and on your domain.
    public function callback(Request $request){
        // Frontend base (set FRONTEND_URL in .env e.g. https://goharesadaf.ir)
        $frontendBase = env('FRONTEND_URL', config('app.url'));

        // Helper: small HTML page that client-side redirects to $url (200 OK)
        $redirectHtml = function (string $url, string $msg = '') {
            $escapedUrl = e($url);
            $escapedMsg = e($msg);
            return <<<HTML
<!doctype html>
<html lang="fa">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>در حال انتقال...</title>
  <meta http-equiv="refresh" content="0;url={$escapedUrl}">
  <script>window.location.replace("{$escapedUrl}");</script>
  <style>body{font-family:sans-serif;direction:rtl;text-align:center;padding:40px}</style>
</head>
<body>
  <h3>در حال انتقال به صفحه پرداخت...</h3>
  <p>{$escapedMsg}</p>
  <p><a href="{$escapedUrl}">در صورت عدم انتقال اینجا کلیک کنید</a></p>
</body>
</html>
HTML;
        };

        // Mellat posts: RefId, ResCode, SaleOrderId, SaleReferenceId, CardHolderInfo
        $resCode = (string) ($request->input('ResCode') ?? '');
        $refId = $request->input('RefId');
        $saleOrderId = $request->input('SaleOrderId');
        $saleReferenceId = $request->input('SaleReferenceId');

        // Optional IP whitelist check (config/services.php mellat.allowed_ips)
        $allowed = config('services.mellat.allowed_ips', []);
        if (!empty($allowed)) {
            $ip = $request->ip();
            if (!in_array($ip, $allowed)) {
                \Log::warning('Mellat callback from unknown IP', ['ip' => $ip, 'payload' => $request->all()]);
                // NOTE: we continue and return 200 to avoid bank retries; change to return 403 if you prefer strict blocking.
            }
        }

        // find payment by ref_id or merchant_order_id (SaleOrderId)
        $payment = null;
        if ($refId) {
            $payment = \App\Models\Payment::where('ref_id', $refId)->latest()->first();
        }
        if (!$payment && $saleOrderId) {
            $payment = \App\Models\Payment::where('merchant_order_id', $saleOrderId)->latest()->first();
        }
        if (!$payment) {
            \Log::warning('Mellat callback: payment not found', $request->all());
            // Redirect user to a generic frontend failure page (200 OK)
            $url = rtrim($frontendBase, '/') . '/payment/failure';
            return response($redirectHtml($url, 'پرداخت ناموفق — شناسه تراکنش یافت نشد'), 200)
                ->header('Content-Type', 'text/html');
        }

        // Save gateway fields
        $payment->sale_order_id = $saleOrderId;
        $payment->sale_reference_id = $saleReferenceId;
        $payment->raw_response = json_encode($request->all());
        $payment->save();

        // If the bank reports non-zero, mark failed and send to frontend failure page
        if ($resCode !== '0') {
            $payment->status = 'failed';
            $payment->save();

            $url = rtrim($frontendBase, '/') . '/payment/failure?order_id=' . $payment->order_id . '&code=' . urlencode($resCode);
            return response($redirectHtml($url, 'پرداخت ناموفق'), 200)->header('Content-Type', 'text/html');
        }

        // Create unique verify ID
        $verifyOrderId = time() + (int)$payment->id;
        $payment->verify_order_id = (string)$verifyOrderId;
        $payment->save();

        // 1) Verify
        $verifyRes = $this->gateway->bpVerifyRequest($payment->merchant_order_id, $saleOrderId, $saleReferenceId);
        if (isset($verifyRes['error'])) {
            \Log::error('Mellat verify error', ['err' => $verifyRes, 'payment' => $payment->id]);
            $payment->status = 'pending';
            $payment->raw_response .= "\nverify_error:" . ($verifyRes['message'] ?? '');
            $payment->save();

            $url = rtrim($frontendBase, '/') . '/payment/pending?order_id=' . $payment->order_id;
            return response($redirectHtml($url, 'پرداخت در حال بررسی'), 200)->header('Content-Type', 'text/html');
        }

        $verifyCode = $verifyRes['code'] ?? null;
        $payment->raw_response .= "\nverify_raw:" . ($verifyRes['raw'] ?? '');
        $payment->save();

        if ($verifyCode !== '0') {
            \Log::warning('Mellat verify failed', ['code' => $verifyCode, 'payment' => $payment->id]);
            $payment->status = 'failed';
            $payment->save();

            $url = rtrim($frontendBase, '/') . '/payment/failure?order_id=' . $payment->order_id . '&code=' . urlencode($verifyCode);
            return response($redirectHtml($url, 'خطا در تایید پرداخت'), 200)->header('Content-Type', 'text/html');
        }

        // 2) Settle
        $settleRes = $this->gateway->bpSettleRequest($verifyOrderId, $saleOrderId, $saleReferenceId);
        if (isset($settleRes['error'])) {
            \Log::error('Mellat settle error', ['err' => $settleRes, 'payment' => $payment->id]);
            $payment->status = 'pending';
            $payment->raw_response .= "\nsettle_error:" . ($settleRes['message'] ?? '');
            $payment->save();

            $url = rtrim($frontendBase, '/') . '/payment/pending?order_id=' . $payment->order_id;
            return response($redirectHtml($url, 'تسویه در حال بررسی'), 200)->header('Content-Type', 'text/html');
        }

        $settleCode = $settleRes['code'] ?? null;
        $payment->raw_response .= "\nsettle_raw:" . ($settleRes['raw'] ?? '');
        $payment->save();

        // success -> update order and redirect to frontend success
        if ($settleCode === '0' || $settleCode === '45') {
            $payment->status = 'settled';
            $payment->save();

            // update order
            $order = $payment->order;
            if ($order) {
                $order->status = 'paid';
                $order->transaction_id = $saleReferenceId;
                $order->save();
            }

            $url = rtrim($frontendBase, '/') . '/payment/success?order_id=' . ($order->id ?? $payment->order_id);
            return response($redirectHtml($url, 'پرداخت با موفقیت انجام شد'), 200)->header('Content-Type', 'text/html');
        }

        // otherwise treat as failure
        $payment->status = 'failed';
        $payment->save();

        $url = rtrim($frontendBase, '/') . '/payment/failure?order_id=' . $payment->order_id . '&code=' . urlencode($settleCode);
        return response($redirectHtml($url, 'خطا در تسویه پرداخت'), 200)->header('Content-Type', 'text/html');
    }

}
