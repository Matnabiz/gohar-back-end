<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ProductManagementController extends Controller
{
    /**
     * Return all products (admin listing). Each product includes an "images" array of full URLs.
     */
    public function index()
    {
        $products = Product::with('images','category')->orderBy('created_at', 'desc')->get();

        $payload = $products->map(function ($p) {
            return $this->formatProductForAdmin($p);
        });

        return response()->json($payload);
    }

    /**
     * Create a product and optionally store multiple images.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
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
        ]);

        DB::beginTransaction();
        try {
            // Handle main image upload
            if ($request->hasFile('main_image')) {
                $path = $request->file('main_image')->store('products', 'public');
                $validated['main_image'] = $path;
            }

            $product = Product::create($validated);

            // Handle additional images (images[])
            $createdImages = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $path = $file->store('products', 'public');
                    $createdImages[] = $product->images()->create(['path' => $path]);
                }
            }

            DB::commit();

            $product->load('images','category');

            return response()->json([
                'message' => 'Product created successfully',
                'data' => $this->formatProductForAdmin($product),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            // cleanup any uploaded files if you want (optional)
            return response()->json(['message' => 'Failed to create product', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update a product, supporting:
     * - removing existing images via removed_images[] (array of full URLs or paths)
     * - uploading new images via images[] (multiple files)
     * - uploading a new main_image file (main_image)
     * - selecting main image by metadata main_image_choice_type & main_image_choice_index
     */
    public function update(Request $request, $id)
    {
        $product = Product::with('images')->find($id);

        if (! $product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $validated = $request->validate([
            'title'        => 'sometimes|required|string|max:255',
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
        ]);

        DB::beginTransaction();
        try {
            // Update basic fields
            $updateFields = $request->only(['title','price','stock','color','dimensions','material','category_id','active','description']);
            if (!empty($updateFields)) {
                $product->update($updateFields);
            }

            // 1) Delete removed images (if any). removed_images[] can be full URL or stored path.
            if ($request->filled('removed_images')) {
                foreach ($request->input('removed_images') as $pathOrUrl) {
                    $filename = basename(parse_url($pathOrUrl, PHP_URL_PATH));
                    // find a matching product_image by path containing filename
                    $img = $product->images()->where('path', 'like', "%{$filename}")->first();
                    if ($img) {
                        Storage::disk('public')->delete($img->path);
                        $img->delete();
                    }
                }
            }

            // 2) Handle main_image file upload (explicit new main file)
            $explicitMainUploaded = false;
            if ($request->hasFile('main_image')) {
                // delete old main image file if exists
                if ($product->main_image) {
                    Storage::disk('public')->delete($product->main_image);
                }
                $path = $request->file('main_image')->store('products', 'public');
                $product->main_image = $path;
                $product->save();
                $explicitMainUploaded = true;
            }

            // 3) Save newly uploaded images (images[])
            $newlyCreated = []; // collection of created ProductImage models in insertion order
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $path = $file->store('products', 'public');
                    $newlyCreated[] = $product->images()->create(['path' => $path]);
                }
            }

            // 4) Handle main_image_choice metadata (if explicit main_image file was not used)
            $choiceType = $request->input('main_image_choice_type');
            $choiceIndex = (int) $request->input('main_image_choice_index', 0);

            if (! $explicitMainUploaded && $choiceType) {
                if ($choiceType === 'existing') {
                    // refresh images to reflect deletions
                    $images = $product->images()->get()->values();
                    if (isset($images[$choiceIndex])) {
                        $product->main_image = $images[$choiceIndex]->path;
                        $product->save();
                    }
                } elseif ($choiceType === 'new') {
                    // choose from newly uploaded images (by index)
                    if (isset($newlyCreated[$choiceIndex])) {
                        $product->main_image = $newlyCreated[$choiceIndex]->path;
                        $product->save();
                    } else {
                        // fallback: if newlyCreated is empty or index out of range, do nothing
                    }
                }
            }

            DB::commit();

            $product->refresh();
            $product->load('images','category');

            return response()->json([
                'message' => 'Product updated successfully',
                'data' => $this->formatProductForAdmin($product),
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            // Optionally you can remove any newly uploaded files (not implemented here)
            return response()->json(['message' => 'Failed to update product', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete product and all associated images from storage & DB.
     */
    public function destroy($id)
    {
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
    private function formatProductForAdmin(Product $product)
    {
        $product->loadMissing('images','category');

        $images = $product->images->map(function ($img) {
            return asset('storage/' . ltrim($img->path, '/'));
        })->values()->toArray();

        return [
            'id' => $product->id,
            'title' => $product->title,
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
