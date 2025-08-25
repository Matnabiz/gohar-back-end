<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryManagementController extends Controller
{
    public function index()
    {
        $categories = Category::whereNull('parent_id')->with('children')->get();
        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        // Generate a simple slug just for this category
        $slug = Str::slug($request->name, '--');

        // Ensure slug is unique among siblings
        $query = Category::where('slug', $slug);
        if ($request->parent_id) {
            $query->where('parent_id', $request->parent_id);
        } else {
            $query->whereNull('parent_id');
        }

        if ($query->exists()) {
            return response()->json([
                'message' => 'A category with this slug already exists under the selected parent.'
            ], 422);
        }

        $category = Category::create([
            'name' => $request->name,
            'parent_id' => $request->parent_id,
            'slug' => $slug
        ]);

        return response()->json($category, 201);
    }

    public function update(Request $request, $id){
        $category = Category::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id|not_in:' . $id, // prevent setting itself as parent
        ]);

        $slug = Str::slug($request->name, '-');

        $query = Category::where('slug', $slug)->where('id', '!=', $id);
        if ($request->parent_id) {
            $query->where('parent_id', $request->parent_id);
        } else {
            $query->whereNull('parent_id');
        }

        if ($query->exists()) {
            return response()->json([
                'message' => 'A category with this slug already exists under the selected parent.'
            ], 422);
        }

        $category->update([
            'name' => $request->name,
            'parent_id' => $request->parent_id,
            'slug' => $slug,
        ]);

        return response()->json($category);
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json(null, 204);
    }

    public function breadcrumb($id)
    {
        $category = Category::findOrFail($id);

        return response()->json([
            'id' => $category->id,
            'breadcrumb' => $category->breadcrumb
        ]);
    }

}

