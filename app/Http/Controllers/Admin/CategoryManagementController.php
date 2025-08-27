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
        $categories = Category::whereNull('parent_id')
            ->orderBy('sort_order', 'asc')
            ->with('children')
            ->get();
        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        // Generate a simple slug just for this category
        $slug = preg_match('/[a-zA-Z]/', $request->name)
            ? Str::slug($request->name, '-')
            : str_replace(' ', '-', trim($request->name));

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

        $slug = preg_match('/[a-zA-Z]/', $request->name)
            ? Str::slug($request->name, '-')
            : str_replace(' ', '-', trim($request->name));

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

    public function updateOrder(Request $request){
        $categories = $request->input('categories');

        foreach ($categories as $cat) {
            Category::where('id', $cat['id'])
                ->update(['sort_order' => $cat['sort_order']]);
        }

        return response()->json(['status' => 'success']);
    }

}

