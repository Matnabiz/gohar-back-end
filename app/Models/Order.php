<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    // You can either use $fillable or $guarded. Using guarded = [] allows mass assignment for all fields.
    protected $guarded = [];

    // Cast JSON/meta fields to array automatically.
    protected $casts = [
        'meta' => 'array',
        'total' => 'decimal:2',
    ];

    /**
     * Order belongs to a user (nullable for guest orders).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Order has many items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Convenience accessor: compute total from items if total missing.
     */
    public function getComputedTotalAttribute(): float
    {
        if ($this->total && $this->total > 0) {
            return (float) $this->total;
        }

        return (float) $this->items->sum(function (OrderItem $it) {
            return $it->price * $it->quantity;
        });
    }

    /**
     * Example: scope for recent orders
     */
    public function scopeRecent($q)
    {
        return $q->orderBy('created_at', 'desc');
    }
}
