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

        // --- validate optional gift_id ---
        $giftId = $request->input('gift_id');
        $gift = null;
        if ($giftId) {
            $gift = Product::where('id', $giftId)
                ->where('active', true)
                ->where('price', '<', 300000)
                ->first();

            if (!$gift) {
                return response()->json(['message' => 'Selected gift is invalid'], 422);
            }
        }

        // calculate total (excluding gift)
        $total = $cart->items->sum(fn($it) => $it->price * $it->quantity);

        // create order with gift_id
        $order = Order::create([
            'user_id' => $user->id,
            'total' => $total,
            'status' => 'pending',
            'gift_id' => $gift?->id, // null if no gift selected
        ]);

        // create order items
        foreach ($cart->items as $it) {
            $order->items()->create([
                'product_id' => $it->product_id,
                'quantity' => $it->quantity,
                'price' => $it->price,
                'meta' => null
            ]);
        }

        // add gift as an order item with price 0
        if ($gift) {
            $order->items()->create([
                'product_id' => $gift->id,
                'quantity' => 1,
                'price' => 0,
                'meta' => 'هدیه رایگان'
            ]);
        }

        // clear cart
        $cart->items()->delete();

        return response()->json($order->load('items.product', 'gift'));
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

    public function destroy(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        if (!$request->user()->is_admin && $order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // If you want soft delete, make sure Order model uses SoftDeletes
        $order->delete();

        return response()->json(['message' => 'Order deleted']);
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

}
