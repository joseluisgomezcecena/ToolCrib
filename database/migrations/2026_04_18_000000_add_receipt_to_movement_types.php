<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE movements MODIFY COLUMN type ENUM('checkout', 'checkin', 'transfer', 'consume', 'scrap', 'receipt') NOT NULL DEFAULT 'checkout'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE movements MODIFY COLUMN type ENUM('checkout', 'checkin', 'transfer', 'consume', 'scrap') NOT NULL DEFAULT 'checkout'");
    }
};
