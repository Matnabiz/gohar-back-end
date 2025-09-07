<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;
    use HasFactory;
    public function wishlist(){
        return $this->belongsToMany(Product::class, 'wishlist', 'user_id', 'product_id')
            ->withTimestamps();
    }
    public function orders() { return $this->hasMany(Order::class); }
    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}
