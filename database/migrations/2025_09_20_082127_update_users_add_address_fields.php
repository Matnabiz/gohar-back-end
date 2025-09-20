<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // add new structured fields
            $table->string('province')->nullable();
            $table->string('city')->nullable();
            $table->string('street')->nullable(); // street/alley/boulevard
            $table->string('postal_code', 20)->nullable();

            // keep old 'address' for now (don’t drop yet if data exists)
            // later you can migrate data and drop it safely
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['province', 'city', 'street', 'postal_code']);
        });
    }
};

