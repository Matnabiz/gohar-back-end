<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('products', 'images')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('images');
            });
        }
    }

    public function down(): void
    {
        // Add the column back as nullable text - won't repopulate data
        if (! Schema::hasColumn('products', 'images')) {
            Schema::table('products', function (Blueprint $table) {
                $table->text('images')->nullable();
            });
        }
    }
};
