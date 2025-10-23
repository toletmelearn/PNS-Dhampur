<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddComprehensiveIndexesFixed extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Detect driver and guard MySQL-specific index checks
        $driver = Schema::getConnection()->getDriverName();

        // Helper function to check if index exists (MySQL/MariaDB only)
        $indexExists = function ($table, $indexName) use ($driver) {
            try {
                if (in_array($driver, ['mysql', 'mariadb'])) {
                    $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
                    return !empty($indexes);
                }
                if ($driver === 'sqlite') {
                    $indexes = DB::select(
                        "SELECT name FROM sqlite_master WHERE type='index' AND tbl_name = ? AND name = ?",
                        [$table, $indexName]
                    );
                    return !empty($indexes);
                }
                // Unsupported driver: assume index does not exist
                return false;
            } catch (\Throwable $e) {
                return false;
            }
        };

        // Students table indexes for performance optimization
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) use ($indexExists) {
                if (Schema::hasColumn('students', 'class_id') && Schema::hasColumn('students', 'status') && !$indexExists('students', 'idx_students_class_status')) {
                    $table->index(['class_id', 'status'], 'idx_students_class_status');
                }
                if (Schema::hasColumn('students', 'admission_no') && !$indexExists('students', 'idx_students_admission_no')) {
                    $table->index('admission_no', 'idx_students_admission_no');
                }
                if (Schema::hasColumn('students', 'aadhaar') && !$indexExists('students', 'idx_students_aadhaar')) {
                    $table->index('aadhaar', 'idx_students_aadhaar');
                }
                if (Schema::hasColumn('students', 'email') && !$indexExists('students', 'idx_students_email')) {
                    $table->index('email', 'idx_students_email');
                }
            });
        }

        // Attendance table indexes for efficient queries
        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) use ($indexExists) {
                if (Schema::hasColumn('attendances', 'student_id') && Schema::hasColumn('attendances', 'date') && !$indexExists('attendances', 'idx_attendance_student_date')) {
                    $table->index(['student_id', 'date'], 'idx_attendance_student_date');
                }
                if (Schema::hasColumn('attendances', 'class_id') && Schema::hasColumn('attendances', 'date') && !$indexExists('attendances', 'idx_attendance_class_date')) {
                    $table->index(['class_id', 'date'], 'idx_attendance_class_date');
                }
                if (Schema::hasColumn('attendances', 'date') && !$indexExists('attendances', 'idx_attendance_date')) {
                    $table->index('date', 'idx_attendance_date');
                }
            });
        }

        // Fees table indexes for financial queries
        if (Schema::hasTable('fees')) {
            Schema::table('fees', function (Blueprint $table) use ($indexExists) {
                if (Schema::hasColumn('fees', 'student_id') && Schema::hasColumn('fees', 'due_date') && !$indexExists('fees', 'idx_fees_student_due')) {
                    $table->index(['student_id', 'due_date'], 'idx_fees_student_due');
                }
                if (Schema::hasColumn('fees', 'status') && !$indexExists('fees', 'idx_fees_status')) {
                    $table->index('status', 'idx_fees_status');
                }
            });
        }

        // Users table indexes for authentication and search
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) use ($indexExists) {
                if (Schema::hasColumn('users', 'email') && !$indexExists('users', 'idx_users_email')) {
                    $table->index('email', 'idx_users_email');
                }
                if (Schema::hasColumn('users', 'phone') && !$indexExists('users', 'idx_users_phone')) {
                    $table->index('phone', 'idx_users_phone');
                }
                if (Schema::hasColumn('users', 'role') && !$indexExists('users', 'idx_users_role')) {
                    $table->index('role', 'idx_users_role');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop indexes if they exist
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropIndex(['idx_students_class_status', 'idx_students_admission_no', 'idx_students_aadhaar', 'idx_students_email']);
            });
        }

        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->dropIndex(['idx_attendance_student_date', 'idx_attendance_class_date', 'idx_attendance_date']);
            });
        }

        if (Schema::hasTable('fees')) {
            Schema::table('fees', function (Blueprint $table) {
                $table->dropIndex(['idx_fees_student_due', 'idx_fees_status']);
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['idx_users_email', 'idx_users_phone', 'idx_users_role']);
            });
        }
    }
}