<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
        return Product::where('active', true)->paginate(10);
    }

    public function show($id)
    {
        $product = Product::with('category.parent')->findOrFail($id);

        return response()->json([
            'id' => $product->id,
            'title' => $product->title,
            'price' => $product->price,
            'description' => $product->description,
            'main_image' => $product->main_image,
            'category' => [
                'id' => $product->category->id,
                'name' => $product->category->name,
                'breadcrumb' => $product->category->breadcrumb,
            ]
        ]);
    }

    public function byCategory($path = null){
        // If no path is provided, return all products.
        if (!$path) {
            return response()->json(Product::all());
        }

        $segments = explode('/', $path);
        $parentCategory = null;
        $category = null;

        // Find the final category in the path.
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
            // Collect all category IDs, including the parent and its children.
            $categoryIds = $this->getDescendantIds($category);

            // Fetch all products that belong to any of these categories.
            $products = Product::whereIn('category_id', $categoryIds)->get();

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

        // Recursively get IDs of all child categories.
        $children = $category->children()->get();
        foreach ($children as $child) {
            $ids = array_merge($ids, $this->getDescendantIds($child));
        }

        return $ids;
    }
}

