<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductManagementController extends Controller
{
    public function store(Request $request){
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'price'        => 'required|numeric|min:0',
            'description'  => 'nullable|string',
            'category_id'  => 'nullable|exists:categories,id',
            'active'       => 'boolean',
            'stock'        => 'required|integer|min:0',
            'main_image' => 'required|image|mimes:jpg,jpeg,png|max:2048',
            'images'       => 'nullable|array',
            'images.*'     => 'string|max:255',
            'color'     => 'string|max:255',
            'dimensions'     => 'string|max:255',
            'material'     => 'string|max:255',

        ]);

        if ($request->hasFile('main_image')) {
            $path = $request->file('main_image')->store('products', 'public');
        }

        $product = Product::create([
            'title' => $request->title,
            'price' => $request->price,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'stock' => $request->stock,
            'color' => $request->color,
            'dimensions' => $request->dimensions,
            'material' => $request->material,
            'main_image' => $path ?? null,
        ]);

        return response()->json([
            'message' => 'Product created successfully',
            'data'    => $product
        ], 201);
    }
    public function index()
    {
        return Product::all();
    }
    public function update(Request $request, $id){
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        }

        $validated = $request->validate([
            'title'        => 'sometimes|required|string|max:255',
            'price'        => 'sometimes|required|numeric|min:0',
            'description'  => 'nullable|string',
            'category_id'  => 'nullable|exists:categories,id',
            'active'       => 'boolean',
            'stock'        => 'sometimes|required|integer|min:0',
            'main_image'   => 'nullable|string|max:255',
            'images'       => 'nullable|array',
            'images.*'     => 'string|max:255',
            'color'        => 'nullable|string|max:255',
            'dimensions'   => 'nullable|string|max:255',
            'material'     => 'nullable|string|max:255',
        ]);

        $product->update($validated);

        return response()->json([
            'message' => 'Product updated successfully',
            'data'    => $product
        ], 200);
    }
    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        }

        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully'
        ], 200);
    }
}

