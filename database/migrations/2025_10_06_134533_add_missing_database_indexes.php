<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Helper function to check if index exists
        $indexExists = function ($table, $indexName) {
            if (DB::getDriverName() === 'sqlite') {
                // For SQLite, check if index exists using pragma
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
            $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
            return !empty($indexes);
        };

        // Add indexes to users table
        Schema::table('users', function (Blueprint $table) use ($indexExists) {
            if (!$indexExists('users', 'idx_users_role')) {
                $table->index('role', 'idx_users_role');
            }
        });

        // Add indexes to students table
        Schema::table('students', function (Blueprint $table) use ($indexExists) {
            if (!$indexExists('students', 'idx_students_verification_status')) {
                $table->index('verification_status', 'idx_students_verification_status');
            }
            if (!$indexExists('students', 'idx_students_is_active')) {
                $table->index('is_active', 'idx_students_is_active');
            }
            if (!$indexExists('students', 'idx_students_dob')) {
                $table->index('dob', 'idx_students_dob');
            }
        });

        // Add indexes to attendances table
        Schema::table('attendances', function (Blueprint $table) use ($indexExists) {
            if (!$indexExists('attendances', 'idx_attendances_student_id')) {
                $table->index('student_id', 'idx_attendances_student_id');
            }
            if (!$indexExists('attendances', 'idx_attendances_status')) {
                $table->index('status', 'idx_attendances_status');
            }
            if (!$indexExists('attendances', 'idx_attendances_date_status')) {
                $table->index(['date', 'status'], 'idx_attendances_date_status');
            }
        });

        // Add indexes to fees table
        Schema::table('fees', function (Blueprint $table) use ($indexExists) {
            if (!$indexExists('fees', 'idx_fees_student_id')) {
                $table->index('student_id', 'idx_fees_student_id');
            }
            if (!$indexExists('fees', 'idx_fees_due_date')) {
                $table->index('due_date', 'idx_fees_due_date');
            }
            if (!$indexExists('fees', 'idx_fees_paid_date')) {
                $table->index('paid_date', 'idx_fees_paid_date');
            }
            if (!$indexExists('fees', 'idx_fees_status')) {
                $table->index('status', 'idx_fees_status');
            }
            if (!$indexExists('fees', 'idx_fees_student_status')) {
                $table->index(['student_id', 'status'], 'idx_fees_student_status');
            }
        });

        // Add indexes to teachers table
        Schema::table('teachers', function (Blueprint $table) use ($indexExists) {
            if (!$indexExists('teachers', 'idx_teachers_user_id')) {
                $table->index('user_id', 'idx_teachers_user_id');
            }
            if (!$indexExists('teachers', 'idx_teachers_joining_date')) {
                $table->index('joining_date', 'idx_teachers_joining_date');
            }
            if (!$indexExists('teachers', 'idx_teachers_salary')) {
                $table->index('salary', 'idx_teachers_salary');
            }
        });

        // Add indexes to exams table
        Schema::table('exams', function (Blueprint $table) use ($indexExists) {
            if (!$indexExists('exams', 'idx_exams_start_date')) {
                $table->index('start_date', 'idx_exams_start_date');
            }
            if (!$indexExists('exams', 'idx_exams_end_date')) {
                $table->index('end_date', 'idx_exams_end_date');
            }
            if (!$indexExists('exams', 'idx_exams_class_id')) {
                $table->index('class_id', 'idx_exams_class_id');
            }
            if (!$indexExists('exams', 'idx_exams_date_range')) {
                $table->index(['start_date', 'end_date'], 'idx_exams_date_range');
            }
        });

        // Add indexes to biometric_attendances table if it exists
        if (Schema::hasTable('biometric_attendances')) {
            Schema::table('biometric_attendances', function (Blueprint $table) use ($indexExists) {
                if (!$indexExists('biometric_attendances', 'idx_biometric_attendances_device_id')) {
                    $table->index('device_id', 'idx_biometric_attendances_device_id');
                }
                if (!$indexExists('biometric_attendances', 'idx_biometric_attendances_teacher_id')) {
                    $table->index('teacher_id', 'idx_biometric_attendances_teacher_id');
                }
                if (!$indexExists('biometric_attendances', 'idx_biometric_attendances_status')) {
                    $table->index('status', 'idx_biometric_attendances_status');
                }
            });
        }

        // Add indexes to results table if it exists
        if (Schema::hasTable('results')) {
            Schema::table('results', function (Blueprint $table) use ($indexExists) {
                if (!$indexExists('results', 'idx_results_student_id')) {
                    $table->index('student_id', 'idx_results_student_id');
                }
                if (!$indexExists('results', 'idx_results_exam_id')) {
                    $table->index('exam_id', 'idx_results_exam_id');
                }
                if (!$indexExists('results', 'idx_results_student_exam')) {
                    $table->index(['student_id', 'exam_id'], 'idx_results_student_exam');
                }
            });
        }

        // Add indexes to salaries table if it exists
        if (Schema::hasTable('salaries')) {
            Schema::table('salaries', function (Blueprint $table) use ($indexExists) {
                if (!$indexExists('salaries', 'idx_salaries_teacher_id')) {
                    $table->index('teacher_id', 'idx_salaries_teacher_id');
                }
                if (!$indexExists('salaries', 'idx_salaries_month')) {
                    $table->index('month', 'idx_salaries_month');
                }
                if (!$indexExists('salaries', 'idx_salaries_year')) {
                    $table->index('year', 'idx_salaries_year');
                }
                if (!$indexExists('salaries', 'idx_salaries_teacher_period')) {
                    $table->index(['teacher_id', 'month', 'year'], 'idx_salaries_teacher_period');
                }
            });
        }

        // Add indexes to class_models table if it exists
        if (Schema::hasTable('class_models')) {
            Schema::table('class_models', function (Blueprint $table) use ($indexExists) {
                if (Schema::hasColumn('class_models', 'is_active') && !$indexExists('class_models', 'idx_class_models_is_active')) {
                    $table->index('is_active', 'idx_class_models_is_active');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Helper function to check if index exists
        $indexExists = function ($table, $indexName) {
            // Skip index checking for SQLite as SHOW INDEX is not supported
            if (DB::getDriverName() === 'sqlite') {
                return true; // Always assume indexes exist in SQLite for dropping
            }
            $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
            return !empty($indexes);
        };

        // Drop indexes from users table
        Schema::table('users', function (Blueprint $table) use ($indexExists) {
            if ($indexExists('users', 'idx_users_role')) {
                $table->dropIndex('idx_users_role');
            }
        });

        // Drop indexes from students table
        Schema::table('students', function (Blueprint $table) use ($indexExists) {
            if ($indexExists('students', 'idx_students_verification_status')) {
                $table->dropIndex('idx_students_verification_status');
            }
            if ($indexExists('students', 'idx_students_is_active')) {
                $table->dropIndex('idx_students_is_active');
            }
            if ($indexExists('students', 'idx_students_dob')) {
                $table->dropIndex('idx_students_dob');
            }
        });

        // Drop indexes from attendances table
        Schema::table('attendances', function (Blueprint $table) use ($indexExists) {
            if ($indexExists('attendances', 'idx_attendances_student_id')) {
                $table->dropIndex('idx_attendances_student_id');
            }
            if ($indexExists('attendances', 'idx_attendances_status')) {
                $table->dropIndex('idx_attendances_status');
            }
            if ($indexExists('attendances', 'idx_attendances_date_status')) {
                $table->dropIndex('idx_attendances_date_status');
            }
        });

        // Drop indexes from fees table
        Schema::table('fees', function (Blueprint $table) use ($indexExists) {
            if ($indexExists('fees', 'idx_fees_student_id')) {
                $table->dropIndex('idx_fees_student_id');
            }
            if ($indexExists('fees', 'idx_fees_due_date')) {
                $table->dropIndex('idx_fees_due_date');
            }
            if ($indexExists('fees', 'idx_fees_paid_date')) {
                $table->dropIndex('idx_fees_paid_date');
            }
            if ($indexExists('fees', 'idx_fees_status')) {
                $table->dropIndex('idx_fees_status');
            }
            if ($indexExists('fees', 'idx_fees_student_status')) {
                $table->dropIndex('idx_fees_student_status');
            }
        });

        // Drop indexes from teachers table
        Schema::table('teachers', function (Blueprint $table) use ($indexExists) {
            if ($indexExists('teachers', 'idx_teachers_user_id')) {
                $table->dropIndex('idx_teachers_user_id');
            }
            if ($indexExists('teachers', 'idx_teachers_joining_date')) {
                $table->dropIndex('idx_teachers_joining_date');
            }
            if ($indexExists('teachers', 'idx_teachers_salary')) {
                $table->dropIndex('idx_teachers_salary');
            }
        });

        // Drop indexes from exams table
        Schema::table('exams', function (Blueprint $table) use ($indexExists) {
            if ($indexExists('exams', 'idx_exams_start_date')) {
                $table->dropIndex('idx_exams_start_date');
            }
            if ($indexExists('exams', 'idx_exams_end_date')) {
                $table->dropIndex('idx_exams_end_date');
            }
            if ($indexExists('exams', 'idx_exams_class_id')) {
                $table->dropIndex('idx_exams_class_id');
            }
            if ($indexExists('exams', 'idx_exams_date_range')) {
                $table->dropIndex('idx_exams_date_range');
            }
        });

        // Drop indexes from biometric_attendances table
        if (Schema::hasTable('biometric_attendances')) {
            Schema::table('biometric_attendances', function (Blueprint $table) use ($indexExists) {
                if ($indexExists('biometric_attendances', 'idx_biometric_attendances_device_id')) {
                    $table->dropIndex('idx_biometric_attendances_device_id');
                }
                if ($indexExists('biometric_attendances', 'idx_biometric_attendances_teacher_id')) {
                    $table->dropIndex('idx_biometric_attendances_teacher_id');
                }
                if ($indexExists('biometric_attendances', 'idx_biometric_attendances_status')) {
                    $table->dropIndex('idx_biometric_attendances_status');
                }
            });
        }

        // Drop indexes from results table
        if (Schema::hasTable('results')) {
            Schema::table('results', function (Blueprint $table) use ($indexExists) {
                if ($indexExists('results', 'idx_results_student_id')) {
                    $table->dropIndex('idx_results_student_id');
                }
                if ($indexExists('results', 'idx_results_exam_id')) {
                    $table->dropIndex('idx_results_exam_id');
                }
                if ($indexExists('results', 'idx_results_student_exam')) {
                    $table->dropIndex('idx_results_student_exam');
                }
            });
        }

        // Drop indexes from salaries table
        if (Schema::hasTable('salaries')) {
            Schema::table('salaries', function (Blueprint $table) use ($indexExists) {
                if ($indexExists('salaries', 'idx_salaries_teacher_id')) {
                    $table->dropIndex('idx_salaries_teacher_id');
                }
                if ($indexExists('salaries', 'idx_salaries_month')) {
                    $table->dropIndex('idx_salaries_month');
                }
                if ($indexExists('salaries', 'idx_salaries_year')) {
                    $table->dropIndex('idx_salaries_year');
                }
                if ($indexExists('salaries', 'idx_salaries_teacher_period')) {
                    $table->dropIndex('idx_salaries_teacher_period');
                }
            });
        }

        // Drop indexes from class_models table
        if (Schema::hasTable('class_models')) {
            Schema::table('class_models', function (Blueprint $table) use ($indexExists) {
                if ($indexExists('class_models', 'idx_class_models_is_active')) {
                    $table->dropIndex('idx_class_models_is_active');
                }
            });
        }
    }
};
