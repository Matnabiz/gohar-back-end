<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Comment;

class CommentManagementController extends Controller
{
    // List all comments, optionally filtered by resource type/id
    public function index(Request $request)
    {
        $request->validate([
            'commentable_type' => 'nullable|string',
            'commentable_id' => 'nullable|integer',
        ]);

        $query = Comment::with('user');

        if ($request->filled('commentable_type')) {
            $query->where('commentable_type', $request->commentable_type);
        }
        if ($request->filled('commentable_id')) {
            $query->where('commentable_id', $request->commentable_id);
        }

        $comments = $query->latest()->paginate(20);

        return response()->json($comments);
    }

    // Update a comment's state (shown/hidden)
    public function updateState(Request $request, $id)
    {
        $request->validate([
            'state' => 'required|in:shown,hidden',
        ]);

        $comment = Comment::findOrFail($id);
        $comment->state = $request->state;
        $comment->save();

        return response()->json([
            'message' => 'Comment state updated successfully',
            'comment' => $comment,
        ]);
    }

    // Delete a comment
    public function destroy($id)
    {
        $comment = Comment::findOrFail($id);
        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted successfully',
        ]);
    }
}
