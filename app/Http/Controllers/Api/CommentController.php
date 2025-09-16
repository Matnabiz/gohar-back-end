<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Comment;
use App\Models\Product;

class CommentController extends Controller
{
    /**
     * List comments for a given resource and include product rating if applicable.
     */
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

        $response = [
            'comments' => $comments,
        ];

        // Include product rating if resource is Product
        if ($request->commentable_type === Product::class) {
            $product = Product::find($request->commentable_id);
            if ($product) {
                $response['product'] = [
                    'id' => $product->id,
                    'rating_avg' => $product->rating_avg,    // e.g., 4.5
                    'rating_count' => $product->rating_count, // e.g., 12
                ];
            }
        }

        return response()->json($response);
    }

    /**
     * Store a new comment and let the observer handle rating recalculation.
     */
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

        // The observer automatically recalculates Product rating if applicable

        return response()->json([
            'comment' => $comment->load('user'),
        ]);
    }
}
