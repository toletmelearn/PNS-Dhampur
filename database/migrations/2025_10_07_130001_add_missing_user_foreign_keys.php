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
        // Helper function to check if foreign key already exists
        if (!function_exists('foreignKeyExists')) {
            function foreignKeyExists($table, $column) {
                $schema = Schema::getConnection()->getDoctrineSchemaManager();
                $tableDetails = $schema->listTableDetails($table);
                return $tableDetails->hasForeignKey($column);
            }
        }

        // Add foreign key for students.user_id
        if (Schema::hasTable('students') && Schema::hasColumn('students', 'user_id') && !foreignKeyExists('students', 'students_user_id_foreign')) {
            Schema::table('students', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            });
        }

        // Add foreign key for teachers.user_id
        if (Schema::hasTable('teachers') && Schema::hasColumn('teachers', 'user_id') && !foreignKeyExists('teachers', 'teachers_user_id_foreign')) {
            Schema::table('teachers', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            });
        }

        // Add foreign key for security_events.user_id
        if (Schema::hasTable('security_events') && Schema::hasColumn('security_events', 'user_id') && !foreignKeyExists('security_events', 'security_events_user_id_foreign')) {
            Schema::table('security_events', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            });
        }

        // Add foreign key for class_data_audits.user_id
        if (Schema::hasTable('class_data_audits') && Schema::hasColumn('class_data_audits', 'user_id') && !foreignKeyExists('class_data_audits', 'class_data_audits_user_id_foreign')) {
            Schema::table('class_data_audits', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            });
        }

        // Add foreign key for audit_logs.user_id
        if (Schema::hasTable('audit_logs') && Schema::hasColumn('audit_logs', 'user_id') && !foreignKeyExists('audit_logs', 'audit_logs_user_id_foreign')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            });
        }

        // Add foreign key for verification_audit_logs.user_id
        if (Schema::hasTable('verification_audit_logs') && Schema::hasColumn('verification_audit_logs', 'user_id') && !foreignKeyExists('verification_audit_logs', 'verification_audit_logs_user_id_foreign')) {
            Schema::table('verification_audit_logs', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            });
        }

        // Add foreign key for error_logs.user_id
        if (Schema::hasTable('error_logs') && Schema::hasColumn('error_logs', 'user_id') && !foreignKeyExists('error_logs', 'error_logs_user_id_foreign')) {
            Schema::table('error_logs', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            });
        }

        // Add foreign key for performance_metrics.user_id
        if (Schema::hasTable('performance_metrics') && Schema::hasColumn('performance_metrics', 'user_id') && !foreignKeyExists('performance_metrics', 'performance_metrics_user_id_foreign')) {
            Schema::table('performance_metrics', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys in reverse order
        if (Schema::hasTable('performance_metrics') && Schema::hasColumn('performance_metrics', 'user_id')) {
            Schema::table('performance_metrics', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        }

        if (Schema::hasTable('error_logs') && Schema::hasColumn('error_logs', 'user_id')) {
            Schema::table('error_logs', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        }

        if (Schema::hasTable('verification_audit_logs') && Schema::hasColumn('verification_audit_logs', 'user_id')) {
            Schema::table('verification_audit_logs', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        }

        if (Schema::hasTable('audit_logs') && Schema::hasColumn('audit_logs', 'user_id')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        }

        if (Schema::hasTable('class_data_audits') && Schema::hasColumn('class_data_audits', 'user_id')) {
            Schema::table('class_data_audits', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        }

        if (Schema::hasTable('security_events') && Schema::hasColumn('security_events', 'user_id')) {
            Schema::table('security_events', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        }

        if (Schema::hasTable('teachers') && Schema::hasColumn('teachers', 'user_id')) {
            Schema::table('teachers', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        }

        if (Schema::hasTable('students') && Schema::hasColumn('students', 'user_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        }
    }
};