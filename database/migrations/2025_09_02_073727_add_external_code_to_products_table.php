<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('external_code', 4)->nullable()->unique()->after('id');
        });

        // Optional: populate existing products
        \App\Models\Product::get()->each(function ($product) {
            $product->external_code = str_pad($product->id, 4, '0', STR_PAD_LEFT);
            $product->saveQuietly();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('external_code');
        });
    }
};
