<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'price',
        'description',
        'category_id',
        'active',
        'stock',
        'main_image',
        'images',
        'dimensions',
        'material',
        'color'
    ];

    protected $casts = [
        'images' => 'array'
    ];

    public function category() {
        return $this->belongsTo(Category::class);
    }
    public function wishedBy(){
        return $this->belongsToMany(User::class, 'wishlists')->withTimestamps();
    }

    protected static function booted()
    {
        static::created(function ($product) {
            $product->external_code = str_pad($product->id, 4, '0', STR_PAD_LEFT);
            $product->saveQuietly();
        });
    }

}

