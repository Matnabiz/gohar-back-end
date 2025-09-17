<?php

namespace App\Observers;

use App\Models\Comment;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class CommentObserver
{
    public function created(Comment $comment): void
    {
        Log::info('Observer CREATED fired! Comment ID: ' . $comment->id);
        $this->updateProductRating($comment);
    }

    public function updated(Comment $comment): void
    {
        Log::info('Observer UPDATED fired! Comment ID: ' . $comment->id);
        $this->updateProductRating($comment);
    }

    public function deleted(Comment $comment): void
    {
        Log::info('Observer DELETED fired! Comment ID: ' . $comment->id);
        $this->updateProductRating($comment);
    }

    protected function updateProductRating(Comment $comment): void
    {
        Log::info('updateProductRating CALLED for commentable_type=' . $comment->commentable_type);

        if (! str_contains($comment->commentable_type, 'Product')) {
            Log::info('Skipped because not a Product comment.');
            return;
        }

        $product = Product::find($comment->commentable_id);
        if (! $product) {
            Log::info('No product found for ID ' . $comment->commentable_id);
            return;
        }

        $stats = Comment::where('commentable_type', $comment->commentable_type)
            ->where('commentable_id', $product->id)
            ->whereNotNull('rating')
            ->selectRaw('COUNT(*) as count, AVG(rating) as avg')
            ->first();

        Log::info('Stats calculated: count=' . ($stats->count ?? 0) . ', avg=' . ($stats->avg ?? 'null'));

        $product->rating_count = $stats->count ?? 0;
        $product->rating_avg   = $stats->avg ?? null;
        $product->save();

        Log::info('Product updated: ID=' . $product->id . ', rating_count=' . $product->rating_count . ', rating_avg=' . $product->rating_avg);
    }
}
