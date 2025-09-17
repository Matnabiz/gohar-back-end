<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Comment;

class CommentController extends Controller
{
    // List comments for any resource
    public function index(Request $request)
    {
        $request->validate([
            'commentable_type' => 'required|string',
            'commentable_id' => 'required|integer',
        ]);

        // Get comments for the resource, including the user
        $comments = Comment::where('commentable_type', $request->commentable_type)
            ->where('commentable_id', $request->commentable_id)
            ->with('user')
            ->latest()
            ->get();

        // Return only the comments array
        return response()->json($comments);
    }

    // Add new comment
    public function store(Request $request){
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
            'state' => 'hidden'
        ]);

        // Load user relationship
        return response()->json($comment->load('user'));
    }
}
