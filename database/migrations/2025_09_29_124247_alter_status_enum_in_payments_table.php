<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('initiated','pending','paid','settled','failed','reversed') DEFAULT 'initiated'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('pending','paid','settled','failed','reversed') DEFAULT 'pending'");
    }
};
