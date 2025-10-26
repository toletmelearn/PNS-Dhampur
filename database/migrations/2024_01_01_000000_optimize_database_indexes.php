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
        // Optimize students table indexes
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                $table->index(['class_id', 'section_id']);
                $table->index(['status', 'created_at']);
                $table->index('admission_no');
                $table->index('roll_number');
                $table->index('email');
            });
        }

        // Optimize teachers table indexes
        if (Schema::hasTable('teachers')) {
            Schema::table('teachers', function (Blueprint $table) {
                $table->index(['department', 'status']);
                $table->index('employee_id');
                $table->index('email');
                $table->index('created_at');
            });
        }

        // Optimize attendance table indexes
        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->index(['student_id', 'date']);
                $table->index(['class_id', 'date']);
                $table->index('status');
                $table->index('created_at');
            });
        }

        // Optimize exam_results table indexes
        if (Schema::hasTable('exam_results')) {
            Schema::table('exam_results', function (Blueprint $table) {
                $table->index(['student_id', 'exam_id']);
                $table->index(['subject_id', 'marks_obtained']);
                $table->index('created_at');
            });
        }

        // Optimize fee_transactions table indexes - commented out due to missing column
        /*
        if (Schema::hasTable('fee_transactions')) {
            Schema::table('fee_transactions', function (Blueprint $table) {
                $table->index(['student_id', 'month']);
                $table->index(['status', 'created_at']);
                $table->index('transaction_id');
            });
        }
        */

        // Optimize users table indexes
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                // Check if status column exists before creating index
                if (Schema::hasColumn('users', 'status')) {
                    $table->index(['role', 'status']);
                } else {
                    $table->index('role');
                }
                $table->index('email');
                $table->index('created_at');
            });
        }

        // Optimize classes table indexes
        if (Schema::hasTable('classes')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->index('name');
                $table->index('status');
            });
        }

        // Optimize subjects table indexes
        if (Schema::hasTable('subjects')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->index(['class_id', 'name']);
                $table->index('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes from students table
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropIndex(['class_id', 'section_id']);
                $table->dropIndex(['status', 'created_at']);
                $table->dropIndex(['admission_no']);
                $table->dropIndex(['roll_number']);
                $table->dropIndex(['email']);
            });
        }

        // Remove indexes from teachers table
        if (Schema::hasTable('teachers')) {
            Schema::table('teachers', function (Blueprint $table) {
                $table->dropIndex(['department', 'status']);
                $table->dropIndex(['employee_id']);
                $table->dropIndex(['email']);
                $table->dropIndex(['created_at']);
            });
        }

        // Remove indexes from attendance table
        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->dropIndex(['student_id', 'date']);
                $table->dropIndex(['class_id', 'date']);
                $table->dropIndex(['status']);
                $table->dropIndex(['created_at']);
            });
        }

        // Remove indexes from exam_results table
        if (Schema::hasTable('exam_results')) {
            Schema::table('exam_results', function (Blueprint $table) {
                $table->dropIndex(['student_id', 'exam_id']);
                $table->dropIndex(['subject_id', 'marks_obtained']);
                $table->dropIndex(['created_at']);
            });
        }

        // Remove indexes from fee_transactions table - commented out due to missing column
        /*
        if (Schema::hasTable('fee_transactions')) {
            Schema::table('fee_transactions', function (Blueprint $table) {
                $table->dropIndex(['student_id', 'month']);
                $table->dropIndex(['status', 'created_at']);
                $table->dropIndex(['transaction_id']);
            });
        }
        */

        // Remove indexes from users table
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['role', 'status']);
                $table->dropIndex(['email']);
                $table->dropIndex(['created_at']);
            });
        }

        // Remove indexes from classes table
        if (Schema::hasTable('classes')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->dropIndex(['name']);
                $table->dropIndex(['status']);
            });
        }

        // Remove indexes from subjects table
        if (Schema::hasTable('subjects')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->dropIndex(['class_id', 'name']);
                $table->dropIndex(['status']);
            });
        }
    }
};