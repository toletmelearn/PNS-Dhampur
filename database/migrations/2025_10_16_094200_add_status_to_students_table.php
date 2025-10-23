<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('students')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'status')) {
                // Align with application expectations
                $table->enum('status', ['active', 'inactive', 'transferred', 'graduated', 'dropped'])
                      ->default('active');
                $table->index('status', 'idx_students_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('students')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'status')) {
                // Drop index if exists before dropping column
                try {
                    $table->dropIndex('idx_students_status');
                } catch (\Throwable $e) {
                    // Ignore if index doesn't exist (SQLite)
                }
                $table->dropColumn('status');
            }
        });
    }
};