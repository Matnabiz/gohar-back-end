<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Comment;
use App\Models\Product; // make sure to import Product

class CommentController extends Controller
{
    // List comments for any resource
    public function index(Request $request)
    {
        $request->validate([
            'commentable_type' => 'required|string',
            'commentable_id' => 'required|integer',
        ]);

        $comments = Comment::where('commentable_type', $request->commentable_type)
            ->where('commentable_id', $request->commentable_id)
            ->with('user')
            ->latest()
            ->get();

        $product_rating = null;

        // Only compute rating if the resource is a Product
        if ($request->commentable_type === 'App\Models\Product') {
            $product = Product::find($request->commentable_id);
            if ($product) {
                // average rating (nullable if no ratings)
                $product_rating = $product->comments()->avg('rating');
            }
        }

        return response()->json([
            'comments' => $comments,
            'product_rating' => $product_rating ?? "-",
        ]);
    }

    // Add new comment
    public function store(Request $request)
    {
        $request->validate([
            'commentable_type' => 'required|string',
            'commentable_id' => 'required|integer',
            'content' => 'required|string|max:1000',
            'rating' => 'nullable|integer|min:1|max:5',
        ]);

        $comment = Comment::create([
            'user_id' => auth()->id(),
            'commentable_type' => $request->commentable_type,
            'commentable_id' => $request->commentable_id,
            'content' => $request->input('content'),
            'rating' => $request->rating,
        ]);

        return response()->json($comment->load('user'));
    }
}
