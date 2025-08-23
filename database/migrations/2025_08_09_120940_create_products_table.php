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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('price', 12, 2)->default(0);
            $table->boolean('active')->default(true);
            $table->integer('stock')->default(0);
            $table->string('main_image')->nullable(); // path in storage
            $table->json('images')->nullable(); // extra image paths
            $table->string('dimensions')->nullable(); // extra image paths
            $table->string('material')->nullable(); // extra image paths
            $table->string('color')->nullable(); // extra image paths

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
