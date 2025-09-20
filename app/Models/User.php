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
    public function wishlists(){
        return $this->belongsToMany(Product::class, 'wishlists', 'user_id', 'product_id')
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
        'province',
        'city',
        'street',
        'postal_code',
        'password',
        'is_admin'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}
