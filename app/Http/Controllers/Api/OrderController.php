<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::with('items.product', 'user')->latest()->get();
        return response()->json($orders);
    }

    public function show(Request $request, $id)
    {
        $order = Order::with('items.product', 'user')->findOrFail($id);
        return response()->json($order);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $cart = $user->cart()->with('items.product')->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart empty'], 422);
        }

        // --- validate request ---
        $data = $request->validate([
            'gift_id' => 'nullable|integer',
            'delivery_option' => 'required|string|in:snapp,post,express',
            'shipping_cost' => 'required|numeric|min:0',
        ]);

        // --- validate gift if provided ---
        $gift = null;
        if (!empty($data['gift_id'])) {
            $gift = Product::where('id', $data['gift_id'])
                ->where('active', true)
                ->where('price', '<', 300000)
                ->first();

            if (!$gift) {
                return response()->json(['message' => 'Selected gift is invalid'], 422);
            }
        }

        // --- calculate subtotal (excluding gift) ---
        $subtotal = $cart->items->sum(fn($it) => $it->price * $it->quantity);

        // --- final total = subtotal + shipping ---
        $total = $subtotal + (float)$data['shipping_cost'];

        // --- create order ---
        $order = Order::create([
            'user_id'         => $user->id,
            'subtotal'        => $subtotal,
            'total'           => $total,
            'shipping_cost'   => $data['shipping_cost'],
            'delivery_method' => $data['delivery_option'],
            'status'          => 'pending',
            'gift_id'         => $gift?->id, // null if no gift selected
        ]);

        // --- create order items ---
        foreach ($cart->items as $it) {
            $order->items()->create([
                'product_id' => $it->product_id,
                'quantity'   => $it->quantity,
                'price'      => $it->price,
                'meta'       => null,
            ]);
        }

        // --- add gift as an order item with price 0 ---
        if ($gift) {
            $order->items()->create([
                'product_id' => $gift->id,
                'quantity'   => 1,
                'price'      => 0,
                'meta'       => 'هدیه رایگان',
            ]);
        }

        // --- clear cart ---
        $cart->items()->delete();

        return response()->json(
            $order->load('items.product', 'gift')
        );
    }

    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        if (!$request->user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status' => 'required|in:pending,paid,shipped,completed,canceled'
        ]);

        $order->update([
            'status' => $request->status
        ]);

        return response()->json($order->load('items.product'));
    }

    public function destroy(Request $request, $id){
        $user = $request->user();

        $order = Order::where('id', $id)
            ->where('user_id', $user->id)
            ->where('status', 'pending') // only pending orders
            ->first();

        if (!$order) {
            return response()->json([
                'message' => 'Order not found or cannot be deleted.'
            ], 404);
        }

        // Delete the order and its items
        $order->items()->delete();
        $order->delete();

        return response()->json([
            'message' => 'Order deleted successfully.'
        ]);
    }

    public function myOrders(Request $request)
    {
        $orders = $request->user()->orders()->with('items.product')->latest()->get();
        return response()->json($orders);
    }

    public function status(Order $order){
        return response()->json([
            'id'             => $order->id,
            'total'          => $order->total,
            'status'         => $order->status,
            'transaction_id' => $order->transaction_id,
            'created_at'     => $order->created_at,
        ]);
    }

    public function unpaidCount(Request $request){
        $user = $request->user();

        // Adjust statuses according to your app.
        // Use all statuses that mean "not yet paid" (e.g. 'pending','initiated')
        $unpaidStatuses = ['pending', 'initiated'];

        $count = Order::where('user_id', $user->id)
            ->whereIn('status', $unpaidStatuses)
            ->count();

        return response()->json(['count' => $count]);
    }

    public function unpaidList(Request $request){
        $user = $request->user();
        $unpaidStatuses = ['pending', 'initiated'];

        $orders = Order::where('user_id', $user->id)
            ->whereIn('status', $unpaidStatuses)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['id','total','status','created_at','transaction_id']);

        return response()->json(['orders' => $orders]);
    }

}
