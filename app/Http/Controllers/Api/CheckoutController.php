<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\User;

class CheckoutController extends Controller
{
    /**
     * Show checkout data (cart, user info, totals).
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Fetch cart with products
        $cart = Cart::with('items.product')->where('user_id', $user->id)->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 400);
        }

        // Calculate totals
        $subtotal = $cart->items->sum(fn($item) => $item->product->price * $item->quantity);
        $shipping = $subtotal > 100 ? 0 : 10; // free shipping if subtotal > 100
        $total = $subtotal + $shipping;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'address' => $user->address,
            ],
            'cart' => $cart->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->product->title,
                    'quantity' => $item->quantity,
                    'price' => $item->product->price,
                    'subtotal' => $item->product->price * $item->quantity,
                    'image' => $item->product->main_image
                        ? asset('storage/' . ltrim($item->product->main_image, '/'))
                        : null,
                ];
            }),
            'summary' => [
                'subtotal' => $subtotal,
                'shipping' => $shipping,
                'total' => $total,
            ]
        ]);
    }

    /**
     * Save / update shipping info before placing order
     */
    public function saveAddress(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'address' => 'required|string|max:255',
        ]);

        $user->address = $request->address;
        $user->save();

        return response()->json(['message' => 'Address saved successfully']);
    }

    public function eligibleGifts(Request $request)
    {
        // Return a small randomized list for the checkout UI (adjust limit as you see fit)
        $gifts = Product::query()
            ->where('active', true)
            ->where('price', '<', 300000)
            ->inRandomOrder()
            ->limit(10)
            ->get(['id','title','slug','price','main_image','rating_avg']);

        // If you store only path in main_image, convert to full URL here:
        $gifts = $gifts->map(function($p) {
            return [
                'id' => $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
                'price' => $p->price,
                'main_image' => $p->main_image ? asset('storage/' . ltrim($p->main_image, '/')) : null,
                'rating_avg' => $p->rating_avg,
            ];
        });

        return response()->json($gifts);
    }

}
