<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;

class ProductController extends Controller
{
    /**
     * Display a listing of the products.
     */
    public function index(){
        $products = Product::with('images','category.parent')
            ->where('active', true)
            ->paginate(10);

        $products->getCollection()->transform(function ($product) {
            return $this->formatProduct($product);
        });

        return $products;
    }

    public function related($productId){
        $product = Product::findOrFail($productId);

        $related = Product::with('images','category.parent')
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('active', true)
            ->limit(6)
            ->get()
            ->map(fn($p) => $this->formatProduct($p));

        return response()->json($related);
    }


    /**
     * Display the specified product.
     */
    public function show($id){
        $product = Product::with('images', 'category.parent')->findOrFail($id);
        return response()->json($this->formatProduct($product));
    }

    public function showBySlug($slug){
        $product = Product::with('images', 'category.parent')
            ->where('slug', $slug)
            ->first();

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json($this->formatProduct($product));
    }



    /**
     * Display products by category slug.
     */

    public function allProducts(){
        $products = Product::with('images','category.parent')->get();
        $products->transform(fn($product) => $this->formatProduct($product));
        return response()->json($products);
    }

    // inside ProductController

    public function byCategory($path = null){
        if (!$path) {
            $products = Product::all();
            $products->transform(function ($product) {
                return $this->formatProduct($product);
            });

            return response()->json([
                'category' => null,
                'products' => $products,
            ]);
        }

        $segments = explode('/', $path);
        $parentCategory = null;
        $category = null;

        foreach ($segments as $slug) {
            $query = Category::where('slug', $slug);

            if ($parentCategory) {
                $query->where('parent_id', $parentCategory->id);
            } else {
                $query->whereNull('parent_id');
            }

            $category = $query->first();

            if (!$category) {
                return response()->json([
                    'message' => 'Category not found'
                ], 404);
            }

            $parentCategory = $category;
        }

        if ($category) {
            $categoryIds = $this->getDescendantIds($category);
            $products = Product::whereIn('category_id', $categoryIds)->get();
            $products->transform(function ($product) {
                return $this->formatProduct($product);
            });

            // Build category metadata for the frontend
            // put ancestors from root -> leaf, using parent relation
            $tmp = [];
            $cur = $category;
            while ($cur) {
                $tmp[] = [
                    'id' => $cur->id,
                    'name' => $cur->name,
                    'slug' => $cur->slug,
                ];
                $cur = $cur->parent;
            }
            $ancestors = array_reverse($tmp);
            $path_segments = array_map(fn($a) => $a['slug'], $ancestors);
            $category_path = implode('/', $path_segments);

            $categoryData = [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'breadcrumb' => $category->breadcrumb,
                'ancestors' => $ancestors,
                'category_path' => $category_path,
            ];

            return response()->json([
                'category' => $categoryData,
                'products' => $products,
            ]);
        }

        return response()->json([
            'message' => 'Category not found'
        ], 404);
    }


    /**
     * A helper function to get the ID of a category and all its children.
     *
     * @param \App\Models\Category $category
     * @return array
     */
    private function getDescendantIds($category){
        $ids = [$category->id];

        $children = $category->children()->get();
        foreach ($children as $child) {
            $ids = array_merge($ids, $this->getDescendantIds($child));
        }

        return $ids;
    }

    /**
     * A helper function to format a product with the full image URL.
     *
     * @param \App\Models\Product $product
     * @return array
     */
    private function formatProduct(Product $product){
        // ensure images relation is loaded or fetch it
        $imagesCollection = $product->relationLoaded('images') ? $product->images : $product->images()->get();

        $imagesUrls = $imagesCollection->map(function ($img) {
            // img->path is stored like "products/abc.jpg", ensure we trim slashes
            return asset('storage/' . ltrim($img->path, '/'));
        })->values()->toArray();

        // Build category info with ancestors (root -> ... -> current)
        $categoryData = null;
        if ($product->category) {
            $cat = $product->category;

            // collect ancestors from current up to root, then reverse to get root-first
            $tmp = [];
            $cur = $cat;
            while ($cur) {
                $tmp[] = [
                    'id' => $cur->id,
                    'name' => $cur->name,
                    'slug' => $cur->slug,
                ];
                $cur = $cur->parent; // requires parent relation on Category
            }
            $ancestors = array_reverse($tmp);

            // build category path (joined slugs) e.g. "carpet/pictorial-carpet"
            $path_segments = array_map(fn($a) => $a['slug'], $ancestors);
            $category_path = implode('/', $path_segments);

            $categoryData = [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'breadcrumb' => $cat->breadcrumb,
                'ancestors' => $ancestors,
                'category_path' => $category_path,
            ];
        }

        return [
            'id' => $product->id,
            'title' => $product->title,
            'slug' => $product->slug,
            'price' => $product->price,
            'description' => $product->description,
            'main_image' => $product->main_image ? asset('storage/' . ltrim($product->main_image, '/')) : null,
            'images' => $imagesUrls,
            'material' => $product->material,
            'color' => $product->color,
            'active' => $product->active,
            'stock' => $product->stock,
            'category' => $categoryData,
        ];
    }


}
