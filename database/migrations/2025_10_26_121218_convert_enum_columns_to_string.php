<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ConvertEnumColumnsToString extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Convert all ENUM columns to string to avoid Doctrine DBAL issues
        $this->convertEnumsToString();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: We cannot easily convert back to ENUM, so this is one-way
    }

    protected function convertEnumsToString()
    {
        // Convert ENUM columns to VARCHAR to avoid Doctrine DBAL issues
        // Users table
        if (Schema::hasTable('users')) {
            if (Schema::hasColumn('users', 'role')) {
                DB::statement('ALTER TABLE `users` MODIFY `role` VARCHAR(20) NOT NULL');
            }
            if (Schema::hasColumn('users', 'status')) {
                DB::statement('ALTER TABLE `users` MODIFY `status` VARCHAR(20) NOT NULL');
            }
        }
        
        // Students table
        if (Schema::hasTable('students') && Schema::hasColumn('students', 'gender')) {
            DB::statement('ALTER TABLE `students` MODIFY `gender` VARCHAR(10) NULL');
        }
        
        // Skip teachers table gender column as it doesn't exist
        
        // Other tables with ENUM columns
        if (Schema::hasTable('attendances') && Schema::hasColumn('attendances', 'status')) {
            DB::statement('ALTER TABLE `attendances` MODIFY `status` VARCHAR(20) NOT NULL DEFAULT "present"');
        }
        
        if (Schema::hasTable('exams') && Schema::hasColumn('exams', 'status')) {
            DB::statement('ALTER TABLE `exams` MODIFY `status` VARCHAR(20) NOT NULL DEFAULT "pending"');
        }
     }
}
