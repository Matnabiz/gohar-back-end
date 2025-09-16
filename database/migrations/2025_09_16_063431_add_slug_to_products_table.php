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

        // Backfill slugs with uniqueness check
        Product::chunk(100, function ($products) {
            foreach ($products as $product) {
                if (empty($product->slug)) {
                    $baseSlug = Str::slug($product->title, '-');
                    $slug = $baseSlug;
                    $counter = 1;

                    // ensure uniqueness
                    while (Product::where('slug', $slug)->exists()) {
                        $slug = $baseSlug . '-' . $counter++;
                    }

                    $product->slug = $slug;
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
