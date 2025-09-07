<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;

class WishlistController extends Controller
{
    public function index(Request $request)
    {
        return response()->json($request->user()->wishlist()->get());
    }

    public function store(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);
        $request->user()->wishlist()->syncWithoutDetaching([$product->id]);

        return response()->json(['message' => 'Added to wishlist']);
    }

    public function destroy(Request $request, $productId)
    {
        $request->user()->wishlist()->detach($productId);

        return response()->json(['message' => 'Removed from wishlist']);
    }
}
