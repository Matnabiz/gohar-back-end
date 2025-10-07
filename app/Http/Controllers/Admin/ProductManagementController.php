<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductManagementController extends Controller
{
    /**
     * Return all products (admin listing). Each product includes an "images" array of full URLs.
     */
    public function index(){
        $products = Product::with('images','category')->orderBy('created_at', 'desc')->get();

        $payload = $products->map(function ($p) {
            return $this->formatProductForAdmin($p);
        });

        return response()->json($payload);
    }

    /**
     * Create a product and optionally store multiple images.
     */
    public function store(Request $request){
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'slug'         => 'nullable|string|max:255|unique:products,slug',
            'price'        => 'nullable|numeric|min:0',
            'description'  => 'nullable|string',
            'category_id'  => 'nullable|exists:categories,id',
            'active'       => 'nullable|boolean',
            'stock'        => 'required|integer|min:0',
            'main_image'   => 'required|image|mimes:jpg,jpeg,png|max:2048',
            'images'       => 'nullable|array',
            'images.*'     => 'image|mimes:jpg,jpeg,png|max:2048',
            'color'        => 'nullable|string|max:255',
            'dimensions'   => 'nullable|string|max:255',
            'material'     => 'nullable|string|max:255',
            'on_sale' => 'sometimes|boolean',
            'discount_percentage' => 'sometimes|integer|min:0|max:100',
        ]);

        // Auto-generate slug if empty
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title'], '-');
        }

        // Normalize defaults
        $validated['on_sale'] = $request->boolean('on_sale', false);
        $validated['discount_percentage'] = (int) ($request->input('discount_percentage', 0));

        // Business rule: if on_sale=true but no discount → error
        if ($validated['on_sale'] && $validated['discount_percentage'] <= 0) {
            return response()->json(['message' => 'If on_sale is true, discount_percentage must be greater than 0.'], 422);
        }

        // Optional auto-enable on_sale when discount > 0
        if ($validated['discount_percentage'] > 0) {
            $validated['on_sale'] = true;
        }

        DB::beginTransaction();
        try {
            // Handle main image upload
            if ($request->hasFile('main_image')) {
                $path = $request->file('main_image')->store('products', 'public');
                $validated['main_image'] = $path;
            }

            $product = Product::create($validated);

            // Handle additional images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $path = $file->store('products', 'public');
                    $product->images()->create(['path' => $path]);
                }
            }

            DB::commit();
            $product->load('images', 'category');

            return response()->json([
                'message' => 'Product created successfully',
                'data' => $this->formatProductForAdmin($product),
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a product, supporting:
     * - removing existing images via removed_images[] (array of full URLs or paths)
     * - uploading new images via images[] (multiple files)
     * - uploading a new main_image file (main_image)
     * - selecting main image by metadata main_image_choice_type & main_image_choice_index
     */
    public function update(Request $request, $id){
        $product = Product::with('images')->find($id);
        if (! $product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $validated = $request->validate([
            'title'        => 'sometimes|required|string|max:255',
            'slug'         => 'nullable|string|max:255|unique:products,slug,' . $product->id,
            'price'        => 'sometimes|nullable|numeric|min:0',
            'description'  => 'nullable|string',
            'category_id'  => 'nullable|exists:categories,id',
            'active'       => 'nullable|boolean',
            'stock'        => 'sometimes|required|integer|min:0',
            'main_image'   => 'sometimes|file|image|mimes:jpg,jpeg,png|max:4096',
            'images'       => 'nullable|array',
            'images.*'     => 'nullable|image|mimes:jpg,jpeg,png|max:4096',
            'removed_images' => 'nullable|array',
            'removed_images.*' => 'nullable|string',
            'main_image_choice_type' => 'nullable|string|in:existing,new',
            'main_image_choice_index' => 'nullable|integer',
            'color'        => 'nullable|string|max:255',
            'dimensions'   => 'nullable|string|max:255',
            'material'     => 'nullable|string|max:255',
            'on_sale' => 'sometimes|boolean',
            'discount_percentage' => 'sometimes|integer|min:0|max:100',
        ]);

        // Update slug
        if (!empty($validated['slug'])) {
            $product->slug = $validated['slug'];
        } elseif (isset($validated['title'])) {
            $product->slug = Str::slug($validated['title'], '-');
        }

        // Normalize fields
        $discount = (int) ($request->input('discount_percentage', $product->discount_percentage ?? 0));
        $onSale = $request->boolean('on_sale', $product->on_sale);

        // Validation rule
        if ($onSale && $discount <= 0) {
            return response()->json(['message' => 'If on_sale is true, discount_percentage must be greater than 0.'], 422);
        }

        // Optional auto-enable on_sale when discount > 0
        if ($discount > 0) {
            $onSale = true;
        }

        DB::beginTransaction();
        try {
            // Update basic fields
            $updateFields = $request->only(['title','price','stock','color','dimensions','material','category_id','active','description']);
            $updateFields['discount_percentage'] = $discount;
            $updateFields['on_sale'] = $onSale;

            $product->update($updateFields);

            // Handle removed images
            if ($request->filled('removed_images')) {
                foreach ($request->input('removed_images') as $pathOrUrl) {
                    $filename = basename(parse_url($pathOrUrl, PHP_URL_PATH));
                    $img = $product->images()->where('path', 'like', "%{$filename}")->first();
                    if ($img) {
                        Storage::disk('public')->delete($img->path);
                        $img->delete();
                    }
                }
            }

            // Handle main_image upload
            $explicitMainUploaded = false;
            if ($request->hasFile('main_image')) {
                if ($product->main_image) {
                    Storage::disk('public')->delete($product->main_image);
                }
                $path = $request->file('main_image')->store('products', 'public');
                $product->main_image = $path;
                $product->save();
                $explicitMainUploaded = true;
            }

            // Handle newly uploaded images
            $newlyCreated = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $path = $file->store('products', 'public');
                    $newlyCreated[] = $product->images()->create(['path' => $path]);
                }
            }

            // Handle main_image_choice if provided
            $choiceType = $request->input('main_image_choice_type');
            $choiceIndex = (int) $request->input('main_image_choice_index', 0);

            if (!$explicitMainUploaded && $choiceType) {
                if ($choiceType === 'existing') {
                    $images = $product->images()->get()->values();
                    if (isset($images[$choiceIndex])) {
                        $product->main_image = $images[$choiceIndex]->path;
                        $product->save();
                    }
                } elseif ($choiceType === 'new' && isset($newlyCreated[$choiceIndex])) {
                    $product->main_image = $newlyCreated[$choiceIndex]->path;
                    $product->save();
                }
            }

            DB::commit();

            $product->refresh();
            $product->load('images', 'category');

            return response()->json([
                'message' => 'Product updated successfully',
                'data' => $this->formatProductForAdmin($product),
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete product and all associated images from storage & DB.
     */
    public function destroy($id){
        $product = Product::with('images')->find($id);

        if (! $product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        DB::beginTransaction();
        try {
            // delete main image file if exists
            if ($product->main_image) {
                Storage::disk('public')->delete($product->main_image);
            }

            // delete product_images files and rows
            foreach ($product->images as $img) {
                Storage::disk('public')->delete($img->path);
                $img->delete();
            }

            $product->delete();

            DB::commit();

            return response()->json(['message' => 'Product deleted successfully'], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete product', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Helper: format product for admin responses (include images as full URLs).
     */
    private function formatProductForAdmin(Product $product){
        $product->loadMissing('images','category');

        $images = $product->images->map(function ($img) {
            return asset('storage/' . ltrim($img->path, '/'));
        })->values()->toArray();

        return [
            'id' => $product->id,
            'title' => $product->title,
            'slug' => $product->slug,
            'price' => $product->price,
            'description' => $product->description,
            'category_id' => $product->category_id,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name,
            ] : null,
            'active' => $product->active,
            'stock' => $product->stock,
            'color' => $product->color,
            'dimensions' => $product->dimensions,
            'material' => $product->material,
            'main_image' => $product->main_image ? asset('storage/' . ltrim($product->main_image, '/')) : null,
            'images' => $images,
            'created_at' => $product->created_at,
            'updated_at' => $product->updated_at,
        ];
    }
}
