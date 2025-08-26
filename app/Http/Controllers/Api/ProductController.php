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
    public function index()
    {
        $products = Product::where('active', true)->paginate(10);
        $products->getCollection()->transform(function ($product) {
            return $this->formatProduct($product);
        });

        return $products;
    }

    /**
     * Display the specified product.
     */
    public function show($id)
    {
        $product = Product::with('category.parent')->findOrFail($id);

        return response()->json($this->formatProduct($product));
    }

    /**
     * Display products by category slug.
     */
    public function byCategory($path = null)
    {
        // If no path is provided, return all products.
        if (!$path) {
            $products = Product::all();
            $products->transform(function ($product) {
                return $this->formatProduct($product);
            });
            return response()->json($products);
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
            return response()->json($products);
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
    private function getDescendantIds($category)
    {
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
    private function formatProduct(Product $product)
    {
        $data = [
            'id' => $product->id,
            'title' => $product->title,
            'price' => $product->price,
            'description' => $product->description,
            'main_image' => $product->main_image ? asset('storage/' . $product->main_image) : null,
            'material' => $product->material,
            'color' => $product->color,
        ];

        // Add category data if available
        if ($product->category) {
            $data['category'] = [
                'id' => $product->category->id,
                'name' => $product->category->name,
                'breadcrumb' => $product->category->breadcrumb,
            ];
        }

        return $data;
    }
}
