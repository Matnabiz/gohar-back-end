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
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120', // file upload
        ]);

        // sanitize HTML content
        $cleanContent = Purifier::clean($validatedData['content']);

        // Handle cover image file upload
        $imageUrl = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $path = $file->store('blogs', 'public');
            $imageUrl = asset('storage/' . $path);
        }

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
            'image'   => $imageUrl,
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
            'cover_image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
        ]);

        // sanitize content
        if (isset($validatedData['content'])) {
            $validatedData['content'] = Purifier::clean($validatedData['content']);
        }

        // handle new cover image
        if ($request->hasFile('cover_image')) {
            $file = $request->file('cover_image');
            $path = $file->store('blogs', 'public');
            $validatedData['image'] = asset('storage/' . $path);
        }

        // update slug if title changed
        if (isset($validatedData['title'])) {
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
