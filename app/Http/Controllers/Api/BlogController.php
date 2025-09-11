<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Mews\Purifier\Facades\Purifier;

class BlogController extends Controller
{
    // GET all blogs
    public function index()
    {
        return response()->json(Blog::latest()->get());
    }

    // GET single blog by slug
    public function show($slug)
    {
        $blog = Blog::where('slug', $slug)->firstOrFail();
        return response()->json($blog);
    }

    // POST create a blog
    public function store(Request $request){
        $validatedData = $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
            'image'   => 'nullable|string',
        ]);

        // sanitize HTML content using Purifier
        $cleanContent = Purifier::clean($validatedData['content']);

        // make slug unique
        $slug = Str::slug($validatedData['title']);
        $original = $slug;
        $i = 1;
        while (Blog::where('slug', $slug)->exists()) {
            $slug = $original . '-' . $i++;
        }

        $blog = Blog::create([
            'title'   => $validatedData['title'],
            'content' => $cleanContent,
            'slug'    => $slug,
            'image'   => $validatedData['image'] ?? null,
        ]);

        return response()->json($blog, 201);
    }

    // PUT update blog
    public function update(Request $request, $id)
    {
        $blog = Blog::findOrFail($id);

        $validatedData = $request->validate([
            'title'   => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'image'   => 'nullable|string',
        ]);

        if (isset($validatedData['content'])) {
            $validatedData['content'] = Purifier::clean($validatedData['content']);
        }

        if (isset($validatedData['title'])) {
            // optionally update slug if title changed
            $slug = Str::slug($validatedData['title']);
            $original = $slug;
            $i = 1;
            while (Blog::where('slug', $slug)->where('id', '!=', $blog->id)->exists()) {
                $slug = $original . '-' . $i++;
            }
            $validatedData['slug'] = $slug;
        }

        $blog->update($validatedData);
        return response()->json($blog);
    }

    // DELETE blog
    public function destroy($id)
    {
        $blog = Blog::findOrFail($id);
        $blog->delete();

        return response()->json(null, 204);
    }

    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,gif,webp|max:5120', // 5MB
        ]);

        $file = $request->file('image');
        $path = $file->store('blogs', 'public'); // storage/app/public/blogs
        $url = asset('storage/' . $path);

        return response()->json(['url' => $url], 201);
    }
}
