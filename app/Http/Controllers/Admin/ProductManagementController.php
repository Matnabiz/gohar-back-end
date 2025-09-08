<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductManagementController extends Controller
{
    public function store(Request $request){
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'price'        => 'nullable|numeric|min:0',
            'description'  => 'nullable|string',
            'category_id'  => 'nullable|exists:categories,id',
            'active'       => 'nullable|boolean',
            'stock'        => 'required|integer|min:0',
            'main_image'   => 'required|image|mimes:jpg,jpeg,png|max:2048',
            'images'       => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png|max:2048',
            'color'        => 'nullable|string|max:255',
            'dimensions'   => 'nullable|string|max:255',
            'material'     => 'nullable|string|max:255',
        ]);

        // Handle main image upload
        if ($request->hasFile('main_image')) {
            $path = $request->file('main_image')->store('products', 'public');
            $validated['main_image'] = $path;
        }

        $product = Product::create($validated);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $file->store('products', 'public');
                $product->images()->create(['path' => $path]);
            }
        }


        return response()->json([
            'message' => 'Product created successfully',
            'data'    => $product->toArray() + [
                    // Return full image URL
                    'main_image_url' => asset('storage/' . $product->main_image),
                ]
        ], 201);
    }
    public function index()
    {
        return Product::orderBy('created_at', 'desc')->get();
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
            'price'        => 'sometimes|nullable|numeric|min:0',
            'description'  => 'nullable|string',
            'category_id'  => 'nullable|exists:categories,id',
            'active'       => 'nullable|boolean',
            'stock'        => 'sometimes|required|integer|min:0',
            'main_image'   => 'sometimes|file|image|mimes:jpg,jpeg,png|max:2048',
            'images'       => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png|max:2048',
            'color'        => 'nullable|string|max:255',
            'dimensions'   => 'nullable|string|max:255',
            'material'     => 'nullable|string|max:255',
        ]);

        // Handle main image upload if a new file is present
        if ($request->hasFile('main_image')) {
            // Delete old image if it exists
            if ($product->main_image) {
                Storage::disk('public')->delete($product->main_image);
            }
            // Store the new image
            $path = $request->file('main_image')->store('products', 'public');
            $validated['main_image'] = $path;
        }
        foreach ($request->file('images') as $file) {
            $path = $file->store('products', 'public');
            $product->images()->create(['path' => $path]);
        }
        $product->update($validated);

        return response()->json([
            'message' => 'Product updated successfully',
            'data'    => $product->toArray() + [
                    // Return full image URL
                    'main_image_url' => asset('storage/' . $product->main_image),
                ]
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

