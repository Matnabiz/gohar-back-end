<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(){
        Schema::create('cart_items', function(Blueprint $table){
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('price', 12, 2); // snapshot price
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }
    public function down(){ Schema::dropIfExists('cart_items'); }
};
