<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Blog extends Model
{
    protected $fillable = ['title', 'content', 'slug', 'image'];
    public function comments(){
        return $this->morphMany(Comment::class, 'commentable')->latest();
    }
    public static function boot(){
        parent::boot();

        static::creating(function ($blog) {
            if (empty($blog->slug)) {
                $blog->slug = Str::slug($blog->title, '-');
            }
        });
    }
}
