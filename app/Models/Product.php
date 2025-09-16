<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'price',
        'description',
        'category_id',
        'active',
        'stock',
        'main_image',
        'dimensions',
        'material',
        'color'
    ];
    public function images(){
        return $this->hasMany(ProductImage::class);
    }
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

        static::saving(function ($product) {
            if (empty($product->slug)) {
                $baseSlug = Str::slug($product->title, '-');
                $slug = $baseSlug;
                $count = 1;

                // Ensure uniqueness
                while (Product::where('slug', $slug)
                    ->where('id', '!=', $product->id)
                    ->exists()) {
                    $slug = $baseSlug . '-' . $count;
                    $count++;
                }

                $product->slug = $slug;
            }
        });
    }


}

