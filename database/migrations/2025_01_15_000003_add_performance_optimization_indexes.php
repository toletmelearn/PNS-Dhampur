<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add additional performance optimization indexes for N+1 query prevention
     */
    public function up(): void
    {
        // Helper function to check if index exists
        $indexExists = function ($table, $indexName) {
            if (DB::getDriverName() === 'sqlite') {
                try {
                    $indexes = DB::select("PRAGMA index_list({$table})");
                    foreach ($indexes as $index) {
                        if ($index->name === $indexName) {
                            return true;
                        }
                    }
                    return false;
                } catch (\Exception $e) {
                    return false;
                }
            }
            try {
                $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
                return !empty($indexes);
            } catch (\Exception $e) {
                return false;
            }
        };

        // Add indexes for teachers table optimization
        if (Schema::hasTable('teachers')) {
            Schema::table('teachers', function (Blueprint $table) use ($indexExists) {
                // Index for status column (frequently filtered)
                if (Schema::hasColumn('teachers', 'status') && !$indexExists('teachers', 'idx_teachers_status')) {
                    $table->index('status', 'idx_teachers_status');
                }
                
                // Composite index for user_id and status
                if (!$indexExists('teachers', 'idx_teachers_user_status')) {
                    $table->index(['user_id', 'status'], 'idx_teachers_user_status');
                }
            });
        }

        // Add indexes for salaries table optimization
        if (Schema::hasTable('salaries')) {
            Schema::table('salaries', function (Blueprint $table) use ($indexExists) {
                // Index for teacher_id foreign key
                if (!$indexExists('salaries', 'idx_salaries_teacher_id')) {
                    $table->index('teacher_id', 'idx_salaries_teacher_id');
                }
                
                // Index for payment_date (for recent salary queries)
                if (Schema::hasColumn('salaries', 'payment_date') && !$indexExists('salaries', 'idx_salaries_payment_date')) {
                    $table->index('payment_date', 'idx_salaries_payment_date');
                }
                
                // Index for status column
                if (Schema::hasColumn('salaries', 'status') && !$indexExists('salaries', 'idx_salaries_status')) {
                    $table->index('status', 'idx_salaries_status');
                }
                
                // Composite index for teacher_id and payment_date
                if (Schema::hasColumn('salaries', 'payment_date') && !$indexExists('salaries', 'idx_salaries_teacher_payment')) {
                    $table->index(['teacher_id', 'payment_date'], 'idx_salaries_teacher_payment');
                }
            });
        }

        // Add indexes for attendances table optimization (additional to existing ones)
        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) use ($indexExists) {
                // Index for class_id foreign key
                if (!$indexExists('attendances', 'idx_attendances_class_id')) {
                    $table->index('class_id', 'idx_attendances_class_id');
                }
                
                // Composite index for class_id and date
                if (!$indexExists('attendances', 'idx_attendances_class_date')) {
                    $table->index(['class_id', 'date'], 'idx_attendances_class_date');
                }
                
                // Index for marked_by (user who marked attendance)
                if (Schema::hasColumn('attendances', 'marked_by') && !$indexExists('attendances', 'idx_attendances_marked_by')) {
                    $table->index('marked_by', 'idx_attendances_marked_by');
                }
                
                // Composite index for student_id, date, and status (for analytics)
                if (!$indexExists('attendances', 'idx_attendances_student_date_status')) {
                    $table->index(['student_id', 'date', 'status'], 'idx_attendances_student_date_status');
                }
            });
        }

        // Add indexes for students table optimization (additional to existing ones)
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) use ($indexExists) {
                // Index for user_id foreign key
                if (!$indexExists('students', 'idx_students_user_id')) {
                    $table->index('user_id', 'idx_students_user_id');
                }
                
                // Index for gender column (for filtering)
                if (Schema::hasColumn('students', 'gender') && !$indexExists('students', 'idx_students_gender')) {
                    $table->index('gender', 'idx_students_gender');
                }
                
                // Composite index for class_id and status (common query pattern)
                if (!$indexExists('students', 'idx_students_class_is_active')) {
                $table->index(['class_id', 'is_active'], 'idx_students_class_is_active');
                }
                
                // Index for created_at (for date range queries)
                if (!$indexExists('students', 'idx_students_created_at')) {
                    $table->index('created_at', 'idx_students_created_at');
                }
            });
        }

        // Add indexes for fees table optimization (additional to existing ones)
        if (Schema::hasTable('fees')) {
            Schema::table('fees', function (Blueprint $table) use ($indexExists) {
                // Index for amount column (for sum calculations)
                if (Schema::hasColumn('fees', 'amount') && !$indexExists('fees', 'idx_fees_amount')) {
                    $table->index('amount', 'idx_fees_amount');
                }
                
                // Index for paid_amount column (for sum calculations)
                if (Schema::hasColumn('fees', 'paid_amount') && !$indexExists('fees', 'idx_fees_paid_amount')) {
                    $table->index('paid_amount', 'idx_fees_paid_amount');
                }
                
                // Composite index for student_id and amount
                if (Schema::hasColumn('fees', 'amount') && !$indexExists('fees', 'idx_fees_student_amount')) {
                    $table->index(['student_id', 'amount'], 'idx_fees_student_amount');
                }
            });
        }

        // Add indexes for class_models table optimization
        if (Schema::hasTable('class_models')) {
            Schema::table('class_models', function (Blueprint $table) use ($indexExists) {
                // Index for section column
                if (Schema::hasColumn('class_models', 'section') && !$indexExists('class_models', 'idx_class_models_section')) {
                    $table->index('section', 'idx_class_models_section');
                }
                
                // Composite index for name and section
                if (Schema::hasColumn('class_models', 'section') && !$indexExists('class_models', 'idx_class_models_name_section')) {
                    $table->index(['name', 'section'], 'idx_class_models_name_section');
                }
                
                // Index for is_active column (if exists)
                if (Schema::hasColumn('class_models', 'is_active') && !$indexExists('class_models', 'idx_class_models_is_active')) {
                    $table->index('is_active', 'idx_class_models_is_active');
                }
            });
        }

        // Add indexes for users table optimization (additional to existing ones)
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) use ($indexExists) {
                // Index for status column
                if (Schema::hasColumn('users', 'status') && !$indexExists('users', 'idx_users_status')) {
                    $table->index('status', 'idx_users_status');
                }
                
                // Index for email_verified_at column
                if (Schema::hasColumn('users', 'email_verified_at') && !$indexExists('users', 'idx_users_email_verified')) {
                    $table->index('email_verified_at', 'idx_users_email_verified');
                }
                
                // Composite index for role and status
                if (Schema::hasColumn('users', 'status') && !$indexExists('users', 'idx_users_role_status')) {
                    $table->index(['role', 'status'], 'idx_users_role_status');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes in reverse order
        $tables = [
            'users' => ['idx_users_role_status', 'idx_users_email_verified', 'idx_users_status'],
            'class_models' => ['idx_class_models_is_active', 'idx_class_models_name_section', 'idx_class_models_section'],
            'fees' => ['idx_fees_student_amount', 'idx_fees_paid_amount', 'idx_fees_amount'],
            'students' => ['idx_students_created_at', 'idx_students_class_is_active', 'idx_students_gender', 'idx_students_user_id'],
            'attendances' => ['idx_attendances_student_date_status', 'idx_attendances_marked_by', 'idx_attendances_class_date', 'idx_attendances_class_id'],
            'salaries' => ['idx_salaries_teacher_payment', 'idx_salaries_status', 'idx_salaries_payment_date', 'idx_salaries_teacher_id'],
            'teachers' => ['idx_teachers_user_status', 'idx_teachers_status']
        ];

        foreach ($tables as $tableName => $indexes) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($indexes, $tableName) {
                    foreach ($indexes as $indexName) {
                        try {
                            // Check if index exists before dropping
                            $indexExists = function ($table, $indexName) {
                                if (DB::getDriverName() === 'sqlite') {
                                    try {
                                        $indexes = DB::select("PRAGMA index_list({$table})");
                                        foreach ($indexes as $index) {
                                            if ($index->name === $indexName) {
                                                return true;
                                            }
                                        }
                                        return false;
                                    } catch (\Exception $e) {
                                        return false;
                                    }
                                }
                                try {
                                    $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
                                    return !empty($indexes);
                                } catch (\Exception $e) {
                                    return false;
                                }
                            };
                            
                            if ($indexExists($tableName, $indexName)) {
                                $table->dropIndex($indexName);
                            }
                        } catch (\Exception $e) {
                            // Index might not exist, continue
                        }
                    }
                });
            }
        }
    }
};