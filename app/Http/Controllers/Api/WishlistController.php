<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;

class WishlistController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $wishlistItems = $user->wishlists()->get();

        $products = $wishlistItems->map(function ($product) {
            if (!$product) return null;
            return $this->formatProduct($product);
        })->filter();

        return response()->json($products->values());
    }


    private function formatProduct($product){
        $data = [
            'id' => $product->id,
            'title' => $product->title,
            'price' => $product->price,
            'description' => $product->description,
            'main_image' => $product->main_image ? asset('storage/' . $product->main_image) : null,
            'material' => $product->material,
            'color' => $product->color,
            'active' => $product->active,
            'stock' => $product->stock,
        ];

        if ($product->category) {
            $data['category'] = [
                'id' => $product->category->id,
                'name' => $product->category->name,
                'breadcrumb' => $product->category->breadcrumb,
            ];
        }

        return $data;
    }

    public function store(Request $request, $productId){
        $product = Product::findOrFail($productId);
        $request->user()->wishlists()->syncWithoutDetaching([$product->id]);

        return response()->json(['message' => 'Added to wishlist']);
    }

    public function destroy(Request $request, $productId){
        $request->user()->wishlists()->detach($productId);

        return response()->json(['message' => 'Removed from wishlist']);
    }
}
