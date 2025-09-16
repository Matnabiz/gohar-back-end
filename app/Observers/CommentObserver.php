<?php

namespace App\Observers;

use App\Models\Comment;
use App\Models\Product;

class CommentObserver
{
    /**
     * Handle events after a comment is created or updated.
     * 'saved' covers both create and update.
     */
    public function saved(Comment $comment): void
    {
        $this->maybeRecalcProduct($comment);
    }

    /**
     * Handle deleted (permanent) comments.
     */
    public function deleted(Comment $comment): void
    {
        $this->maybeRecalcProduct($comment);
    }

    /**
     * If your Comment model uses soft deletes, handle restore as well.
     */
    public function restored(Comment $comment): void
    {
        $this->maybeRecalcProduct($comment);
    }

    /**
     * Centralized logic: only recalc if the comment belongs to a Product.
     */
    protected function maybeRecalcProduct(Comment $comment): void
    {
        // If your commentable_type stores class names (default morphTo), compare to Product::class
        if ($comment->commentable_type !== Product::class) {
            return;
        }

        // find product by id (safer than $comment->commentable in case relation is not loaded)
        $product = Product::find($comment->commentable_id);

        if (! $product) {
            return;
        }

        // Recalculate stats (fast)
        $product->updateRatingStats();
    }
}
