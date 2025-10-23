<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admit_cards')) {
            return;
        }
        if (DB::getDriverName() === 'sqlite') {
            // SQLite does not support MODIFY syntax; skip in testing environment
            return;
        }
        DB::statement('ALTER TABLE `admit_cards` MODIFY `qr_code` TEXT NULL');
    }

    public function down(): void
    {
        if (!Schema::hasTable('admit_cards')) {
            return;
        }
        if (DB::getDriverName() === 'sqlite') {
            // SQLite does not support MODIFY syntax; skip in testing environment
            return;
        }
        DB::statement('ALTER TABLE `admit_cards` MODIFY `qr_code` VARCHAR(191) NULL');
    }
};