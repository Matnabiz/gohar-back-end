<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->input('q', '');

        if (!$query) {
            return response()->json([], 200);
        }

        $products = Product::with('images', 'category.parent')
            ->where('title', 'like', '%' . $query . '%')
            ->orWhere('description', 'like', '%' . $query . '%')
            ->get();

        $formatted = $products->map(function ($product) {
            $imagesUrls = $product->images->map(function ($img) {
                return asset('storage/' . ltrim($img->path, '/'));
            });

            return [
                'id' => $product->id,
                'title' => $product->title,
                'price' => $product->price,
                'description' => $product->description,
                'main_image' => $product->main_image
                    ? asset('storage/' . ltrim($product->main_image, '/'))
                    : null,
                'images' => $imagesUrls,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                    'breadcrumb' => $product->category->breadcrumb,
                ] : null,
            ];
        });

        return response()->json($formatted, 200);
    }
}
