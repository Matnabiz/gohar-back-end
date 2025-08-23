<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model {
    protected $guarded = [];
    public function product(){ return $this->belongsTo(Product::class); }
    public function getUrlAttribute(){ return asset('storage/'.$this->path); } // convenience
}
