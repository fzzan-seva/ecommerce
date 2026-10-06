<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY payment_method VARCHAR(20) NOT NULL DEFAULT 'bank_transfer'");
        }
    }

    public function down(): void
    {
        // Mirrors up(): the generic VARCHAR(20) shape is produced by migration
        // 2024_01_01_000006 on fresh installs, so there is nothing to revert to.
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY payment_method VARCHAR(20) NOT NULL DEFAULT 'bank_transfer'");
        }
    }
};
