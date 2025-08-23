<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(){
        Schema::create('orders', function(Blueprint $table){
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('pending'); // pending, confirmed, shipped...
            $table->decimal('total', 12, 2)->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }
    public function down(){ Schema::dropIfExists('orders'); }
};
