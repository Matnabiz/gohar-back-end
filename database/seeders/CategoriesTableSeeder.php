<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoriesTableSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');  // Disable FK checks
        Category::truncate();

        $categories = [
            'Electronics' => ['Phones', 'Laptops', 'Cameras'],
            'Books'       => ['Fiction', 'Non-Fiction', 'Comics'],
            'Clothing'    => ['Men', 'Women', 'Kids'],
            'Home'        => ['Furniture', 'Kitchen', 'Decor'],
            'Sports'      => ['Football', 'Basketball', 'Tennis'],
        ];

        foreach ($categories as $parent => $children) {
            // Parent category slug
            $parentSlug = Str::slug($parent, '-');

            $parentCategory = Category::create([
                'name' => $parent,
                'parent_id' => null,
                'slug' => $parentSlug,
            ]);

            foreach ($children as $child) {
                $childSlug = $parentSlug . '-' . Str::slug($child, '-'); // prepend parent slug

                Category::create([
                    'name' => $child,
                    'parent_id' => $parentCategory->id,
                    'slug' => $childSlug,
                ]);
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');  // Enable FK checks
    }
}
