<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\Product;

return new class extends Migration {
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('title');
        });

        // Backfill slugs from unique titles
        Product::chunk(100, function ($products) {
            foreach ($products as $product) {
                if (empty($product->slug)) {
                    $product->slug = Str::slug($product->title, '-');
                    $product->saveQuietly();
                }
            }
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};

