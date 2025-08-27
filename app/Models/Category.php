<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name', 'parent_id', 'slug', 'sort_order'];

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')
            ->orderBy('sort_order', 'asc')
            ->with('children');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function getFullSlugAttribute()
    {
        if ($this->parent) {
            return $this->parent->full_slug . '/' . $this->slug;
        }
        return $this->slug;
    }

    public function getBreadcrumbAttribute(){
        $breadcrumb = [];
        $category = $this;

        while ($category) {
            array_unshift($breadcrumb, $category->name);
            $category = $category->parent;
        }

        return implode(' > ', $breadcrumb);
    }
}


