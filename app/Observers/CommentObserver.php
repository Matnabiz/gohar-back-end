<?php

namespace App\Observers;

use App\Models\Comment;
use App\Models\Product;

class CommentObserver
{
    /**
     * Handle events after a comment is created.
     */
    public function created(Comment $comment): void
    {
        $this->updateProductRating($comment);
    }

    /**
     * Handle events after a comment is updated.
     */
    public function updated(Comment $comment): void
    {
        $this->updateProductRating($comment);
    }

    /**
     * Handle events after a comment is deleted.
     */
    public function deleted(Comment $comment): void
    {
        $this->updateProductRating($comment);
    }

    /**
     * Recalculate and persist product rating/count.
     */
    protected function updateProductRating(Comment $comment): void
    {
        // Only update if the comment is for a Product
        if (! str_contains($comment->commentable_type, 'Product')) {
            return;
        }

        // Get the product
        $product = Product::find($comment->commentable_id);
        if (! $product) {
            return;
        }

        // Calculate rating count + avg
        $stats = Comment::where('commentable_type', Product::class)
            ->where('commentable_id', $product->id)
            ->whereNotNull('rating')
            ->selectRaw('COUNT(*) as count, AVG(rating) as avg')
            ->first();

        $product->rating_count = $stats->count ?? 0;
        $product->rating_avg   = $stats->avg ?? null;
        $product->save();
    }
}
