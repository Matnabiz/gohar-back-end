<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class ProductsTableSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');  // Disable FK checks

        Product::truncate();

        $categories = Category::all();

        foreach ($categories as $category) {
            Product::factory()->count(5)->create([
                'category_id' => $category->id,
            ]);
        }
    }
}
