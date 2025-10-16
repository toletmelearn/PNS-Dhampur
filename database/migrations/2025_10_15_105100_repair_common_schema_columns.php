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
        $tables = [
            'schools',
            'users',
            'attendances',
            'class_data_approvals',
            'exam_paper_approvals',
            'attendance_regularizations',
        ];

        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                // Add deleted_at for soft deletes if missing
                if (!Schema::hasColumn($tableName, 'deleted_at')) {
                    $table->softDeletes();
                }

                // Add timestamps if missing
                if (!Schema::hasColumn($tableName, 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }
                if (!Schema::hasColumn($tableName, 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'schools',
            'users',
            'attendances',
            'class_data_approvals',
            'exam_paper_approvals',
            'attendance_regularizations',
        ];

        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                // Drop columns only if they exist
                if (Schema::hasColumn($tableName, 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
                if (Schema::hasColumn($tableName, 'created_at')) {
                    $table->dropColumn('created_at');
                }
                if (Schema::hasColumn($tableName, 'updated_at')) {
                    $table->dropColumn('updated_at');
                }
            });
        }
    }
};