<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'meta' => 'array',
        'price' => 'decimal:2',
    ];

    /**
     * Each order item belongs to an order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Each order item belongs to a product snapshot.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Convenience accessor: product main image url (if images table exists)
     * Usage: $orderItem->main_image_url
     */
    public function getMainImageUrlAttribute(): ?string
    {
        if ($this->product && $this->product->images && $this->product->images->count()) {
            $img = $this->product->images->first();
            return asset('storage/' . $img->path);
        }
        return null;
    }

    /**
     * Snapshot helpers (if you store a JSON snapshot in meta).
     * Example meta: { "title": "...", "sku": "..."}
     */
    public function getSnapshotTitleAttribute(): ?string
    {
        return $this->meta['title'] ?? null;
    }
}
