<?php

namespace App\Observers;

use App\Models\Comment;
use App\Models\Product;

class CommentObserver
{
    public function created(Comment $comment)
    {
        $this->updateProductRating($comment);
    }

    public function updated(Comment $comment)
    {
        $this->updateProductRating($comment);
    }

    public function deleted(Comment $comment)
    {
        $this->updateProductRating($comment);
    }

    protected function updateProductRating(Comment $comment)
    {
        if (class_basename($comment->commentable_type) === 'Product') {
            $product = Product::find($comment->commentable_id);

            if ($product) {
                $stats = $product->comments()
                    ->whereNotNull('rating')
                    ->selectRaw('COUNT(*) as count, AVG(rating) as avg')
                    ->first();

                $product->rating_count = $stats->count ?? 0;
                $product->rating_avg   = $stats->avg ?? 0;
                $product->save();
            }
        }
    }

}
