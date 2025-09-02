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
        Schema::table('users', function (Blueprint $table) {
            // Just make email nullable (don't re-add unique)
            $table->string('email')->nullable()->change();

            // Add phone column (nullable + unique)
            $table->string('phone')->nullable()->unique()->after('email');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone']); // drop phone unique index
            $table->dropColumn('phone');

            // Make email NOT NULL again
            $table->string('email')->nullable(false)->change();
        });

    }
};
