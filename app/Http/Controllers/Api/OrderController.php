<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->is_admin) {
            // admin: see all orders
            $orders = Order::with('items.product', 'user')->latest()->get();
        } else {
            // user: only see their own
            $orders = $user->orders()->with('items.product')->latest()->get();
        }

        return response()->json($orders);
    }

    public function show(Request $request, $id)
    {
        $order = Order::with('items.product', 'user')->findOrFail($id);

        // if not admin, restrict to own order
        if (!$request->user()->is_admin && $order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($order);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $cart = $user->cart()->with('items.product')->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart empty'], 422);
        }

        $total = $cart->items->sum(fn($it) => $it->price * $it->quantity);

        $order = Order::create([
            'user_id' => $user->id,
            'total' => $total,
            'status' => 'pending'
        ]);

        foreach ($cart->items as $it) {
            $order->items()->create([
                'product_id' => $it->product_id,
                'quantity' => $it->quantity,
                'price' => $it->price,
                'meta' => null
            ]);
        }

        // clear cart
        $cart->items()->delete();

        return response()->json($order->load('items.product'));
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
}
