<?php

namespace App\Observers;

use App\Models\Comment;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class CommentObserver
{
    public function created(Comment $comment): void
    {
        $this->updateProductRating($comment);
    }

    public function updated(Comment $comment): void
    {
        $this->updateProductRating($comment);
    }

    public function deleted(Comment $comment): void
    {
        $this->updateProductRating($comment);
    }

    protected function updateProductRating(Comment $comment): void
    {

        if (! str_contains($comment->commentable_type, 'Product')) {
            return;
        }

        $product = Product::find($comment->commentable_id);
        if (! $product) {
            return;
        }

        $stats = Comment::where('commentable_type', $comment->commentable_type)
            ->where('commentable_id', $product->id)
            ->whereNotNull('rating')
            ->selectRaw('COUNT(*) as count, AVG(rating) as avg')
            ->first();


        $product->rating_count = $stats->count ?? 0;
        $product->rating_avg   = $stats->avg ?? null;
        $product->save();

    }
}
