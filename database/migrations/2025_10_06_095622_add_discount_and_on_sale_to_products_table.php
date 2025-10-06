<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDiscountAndOnSaleToProductsTable extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedTinyInteger('discount_percentage')->default(0)->after('price');
            $table->boolean('on_sale')->default(false)->after('discount_percentage');
            $table->index('on_sale');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['on_sale']);
            $table->dropColumn(['discount_percentage', 'on_sale']);
        });
    }
}
