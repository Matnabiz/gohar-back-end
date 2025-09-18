<?php

namespace App\Http\Controllers\Api;
use App\Models\Product;
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

    public function showById($id){
        $blog = Blog::findOrFail($id);
        return response()->json($blog);
    }

    // GET single blog by slug
    public function show($slug)
    {
        $blog = Blog::with('category')->where('slug', $slug)->firstOrFail();

        // related blogs (same category)
        $relatedBlogs = [];
        if ($blog->category_id) {
            $relatedBlogs = Blog::where('category_id', $blog->category_id)
                ->where('id', '!=', $blog->id)
                ->limit(6)
                ->get(['id','title','slug','image','created_at']);
        }

        // related products (same category or children categories, or random if no category)
        $relatedProducts = [];
        if ($blog->category_id) {
            $category = $blog->category;

            // get all descendant IDs (including current)
            $categoryIds = [$category->id];
            $categoryIds = array_merge($categoryIds, $this->getDescendantIds($category));

            $relatedProductsQuery = Product::whereIn('category_id', $categoryIds)
                ->where('active', true)
                ->whereNotNull('main_image');
        } else {
            // no category: pick 6 random active products
            $relatedProductsQuery = Product::where('active', true)
                ->whereNotNull('main_image')
                ->inRandomOrder();
        }

        $relatedProducts = $relatedProductsQuery
            ->limit(6)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'title' => $p->title,
                    'slug' => $p->slug ?? null,
                    'price' => $p->price,
                    'main_image' => $p->main_image ? asset('storage/' . ltrim($p->main_image, '/')) : null,
                    'rating_avg' => $p->rating_avg ?? null,
                ];
            });

        return response()->json([
            'blog' => $blog,
            'relatedBlogs' => $relatedBlogs,
            'relatedProducts' => $relatedProducts,
        ]);
    }


    /**
     * Recursively get all child category IDs.
     * Can be copied from ProductController.
     */
    private function getDescendantIds($category){
        $ids = [];
        $children = $category->children()->get();

        foreach ($children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->getDescendantIds($child));
        }

        return $ids;
    }


    // POST create a blog
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
            'image'   => 'required|image|mimes:jpg,jpeg,png,gif,webp|max:5120', // file required
            'category_id' => 'nullable|integer|exists:categories,id',
        ]);

        // Sanitize content
        $cleanContent = Purifier::clean($validatedData['content'], 'full_html');

        // Handle file upload
        $file = $request->file('image');
        $path = $file->store('blogs', 'public'); // storage/app/public/blogs
        $imageUrl = asset('storage/' . $path);

        // Make slug unique
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
            'category_id' => $request->category_id,
        ]);

        return response()->json($blog, 201);
    }

    // PUT update blog
    public function update(Request $request, $id){
        $blog = Blog::findOrFail($id);

        $validatedData = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'content'     => 'sometimes|string',
            'cover_image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'slug'        => 'sometimes|string|max:255|unique:blogs,slug,' . $id, // optional direct slug change
            'category_id' => 'nullable|integer|exists:categories,id',
        ]);

        // Sanitize content
        if (isset($validatedData['content'])) {
            $validatedData['content'] = Purifier::clean($validatedData['content'], 'full_html');
        }

        // Handle new cover image
        if ($request->hasFile('cover_image')) {
            $file = $request->file('cover_image');
            $path = $file->store('blogs', 'public');
            $validatedData['image'] = asset('storage/' . $path);
        }

        // Update slug if title changed OR if slug provided manually
        if (isset($validatedData['title']) || isset($validatedData['slug'])) {
            if (!isset($validatedData['slug'])) {
                $slug = Str::slug($validatedData['title']);
            } else {
                $slug = Str::slug($validatedData['slug']);
            }

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
