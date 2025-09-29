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
// database/migrations/xxxx_xx_xx_create_payments_table.php
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('merchant_order_id')->unique(); // used for bpPayRequest (your generated order id)
            $table->string('verify_order_id')->nullable()->unique(); // used for bpVerifyRequest
            $table->bigInteger('amount');
            $table->string('ref_id')->nullable(); // RefId returned from bpPayRequest
            $table->string('sale_order_id')->nullable(); // posted back by gateway
            $table->string('sale_reference_id')->nullable(); // posted back by gateway
            $table->enum('status', ['pending','paid','settled','failed','reversed'])->default('pending');
            $table->text('raw_response')->nullable(); // store raw XML / results for troubleshooting
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
