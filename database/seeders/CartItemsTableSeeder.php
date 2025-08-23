<?php

namespace Database\Seeders;

use App\Models\Cart;
use Illuminate\Database\Seeder;
use App\Models\CartItem;
use App\Models\User;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class CartItemsTableSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');  // Disable FK checks

        CartItem::truncate();

        $carts = Cart::all();
        $products = Product::all();

        foreach ($carts as $cart) {
            // Each user gets 2-4 cart items
            $cartProducts = $products->random(rand(2, 4));

            foreach ($cartProducts as $product) {
                CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'quantity' => rand(1, 3),
                ]);
            }
        }
    }
}
