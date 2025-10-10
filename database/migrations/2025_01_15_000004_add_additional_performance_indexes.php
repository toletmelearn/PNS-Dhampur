<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add additional performance optimization indexes for frequently queried columns
     */
    public function up(): void
    {
        // Helper function to check if index exists
        $indexExists = function ($table, $indexName) {
            if (DB::getDriverName() === 'sqlite') {
                return false;
            }
            try {
                $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
                return !empty($indexes);
            } catch (\Exception $e) {
                return false;
            }
        };

        // Add indexes for students table - frequently queried columns
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) use ($indexExists) {
                // Index for admission_number (unique identifier searches)
                if (Schema::hasColumn('students', 'admission_number') && !$indexExists('students', 'idx_students_admission_number')) {
                    $table->index('admission_number', 'idx_students_admission_number');
                }
                
                // Index for roll_number (class-wise searches)
                if (Schema::hasColumn('students', 'roll_number') && !$indexExists('students', 'idx_students_roll_number')) {
                    $table->index('roll_number', 'idx_students_roll_number');
                }
                
                // Composite index for class_id and status (most common query pattern)
                if (Schema::hasColumn('students', 'class_id') && Schema::hasColumn('students', 'is_active') && !$indexExists('students', 'idx_students_class_is_active')) {
                $table->index(['class_id', 'is_active'], 'idx_students_class_is_active');
                }
                
                // Index for academic_year (year-wise filtering)
                if (Schema::hasColumn('students', 'academic_year') && !$indexExists('students', 'idx_students_academic_year')) {
                    $table->index('academic_year', 'idx_students_academic_year');
                }
                
                // Index for parent_contact (parent searches)
                if (Schema::hasColumn('students', 'parent_contact') && !$indexExists('students', 'idx_students_parent_contact')) {
                    $table->index('parent_contact', 'idx_students_parent_contact');
                }
            });
        }

        // Add indexes for attendances table - high-frequency queries
        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) use ($indexExists) {
                // Composite index for date and class_id (daily attendance reports)
                if (!$indexExists('attendances', 'idx_attendances_date_class')) {
                    $table->index(['date', 'class_id'], 'idx_attendances_date_class');
                }
                
                // Composite index for student_id and date (student attendance history)
                if (!$indexExists('attendances', 'idx_attendances_student_date')) {
                    $table->index(['student_id', 'date'], 'idx_attendances_student_date');
                }
                
                // Index for status (attendance status filtering)
                if (Schema::hasColumn('attendances', 'status') && !$indexExists('attendances', 'idx_attendances_status')) {
                    $table->index('status', 'idx_attendances_status');
                }
                
                // Composite index for date range queries with status
                if (Schema::hasColumn('attendances', 'status') && !$indexExists('attendances', 'idx_attendances_date_status')) {
                    $table->index(['date', 'status'], 'idx_attendances_date_status');
                }
            });
        }

        // Add indexes for fees table - payment tracking
        if (Schema::hasTable('fees')) {
            Schema::table('fees', function (Blueprint $table) use ($indexExists) {
                // Index for payment_status (pending/paid filtering)
                if (Schema::hasColumn('fees', 'payment_status') && !$indexExists('fees', 'idx_fees_payment_status')) {
                    $table->index('payment_status', 'idx_fees_payment_status');
                }
                
                // Index for due_date (overdue fees)
                if (Schema::hasColumn('fees', 'due_date') && !$indexExists('fees', 'idx_fees_due_date')) {
                    $table->index('due_date', 'idx_fees_due_date');
                }
                
                // Composite index for student_id and payment_status
                if (Schema::hasColumn('fees', 'payment_status') && !$indexExists('fees', 'idx_fees_student_status')) {
                    $table->index(['student_id', 'payment_status'], 'idx_fees_student_status');
                }
                
                // Index for fee_type (fee category filtering)
                if (Schema::hasColumn('fees', 'fee_type') && !$indexExists('fees', 'idx_fees_fee_type')) {
                    $table->index('fee_type', 'idx_fees_fee_type');
                }
                
                // Index for academic_year (yearly fee reports)
                if (Schema::hasColumn('fees', 'academic_year') && !$indexExists('fees', 'idx_fees_academic_year')) {
                    $table->index('academic_year', 'idx_fees_academic_year');
                }
            });
        }

        // Add indexes for results table - exam results
        if (Schema::hasTable('results')) {
            Schema::table('results', function (Blueprint $table) use ($indexExists) {
                // Composite index for exam_id and student_id (exam results lookup)
                if (!$indexExists('results', 'idx_results_exam_student')) {
                    $table->index(['exam_id', 'student_id'], 'idx_results_exam_student');
                }
                
                // Index for subject_id (subject-wise results)
                if (Schema::hasColumn('results', 'subject_id') && !$indexExists('results', 'idx_results_subject')) {
                    $table->index('subject_id', 'idx_results_subject');
                }
                
                // Index for marks (grade calculations)
                if (Schema::hasColumn('results', 'marks') && !$indexExists('results', 'idx_results_marks')) {
                    $table->index('marks', 'idx_results_marks');
                }
                
                // Composite index for class_id and exam_id (class results)
                if (Schema::hasColumn('results', 'class_id') && !$indexExists('results', 'idx_results_class_exam')) {
                    $table->index(['class_id', 'exam_id'], 'idx_results_class_exam');
                }
            });
        }

        // Add indexes for exams table - exam management
        if (Schema::hasTable('exams')) {
            Schema::table('exams', function (Blueprint $table) use ($indexExists) {
                // Index for exam_date (upcoming exams)
                if (Schema::hasColumn('exams', 'exam_date') && !$indexExists('exams', 'idx_exams_exam_date')) {
                    $table->index('exam_date', 'idx_exams_exam_date');
                }
                
                // Index for status (active/inactive exams)
                if (Schema::hasColumn('exams', 'status') && !$indexExists('exams', 'idx_exams_status')) {
                    $table->index('status', 'idx_exams_status');
                }
                
                // Composite index for class_id and exam_date
                if (Schema::hasColumn('exams', 'exam_date') && !$indexExists('exams', 'idx_exams_class_date')) {
                    $table->index(['class_id', 'exam_date'], 'idx_exams_class_date');
                }
                
                // Index for academic_year (yearly exam reports)
                if (Schema::hasColumn('exams', 'academic_year') && !$indexExists('exams', 'idx_exams_academic_year')) {
                    $table->index('academic_year', 'idx_exams_academic_year');
                }
            });
        }

        // Add indexes for assignments table - homework management
        if (Schema::hasTable('assignments')) {
            Schema::table('assignments', function (Blueprint $table) use ($indexExists) {
                // Index for due_date (upcoming assignments)
                if (Schema::hasColumn('assignments', 'due_date') && !$indexExists('assignments', 'idx_assignments_due_date')) {
                    $table->index('due_date', 'idx_assignments_due_date');
                }
                
                // Index for status (active/completed assignments)
                if (Schema::hasColumn('assignments', 'status') && !$indexExists('assignments', 'idx_assignments_status')) {
                    $table->index('status', 'idx_assignments_status');
                }
                
                // Composite index for class_id and due_date
                if (Schema::hasColumn('assignments', 'due_date') && !$indexExists('assignments', 'idx_assignments_class_due')) {
                    $table->index(['class_id', 'due_date'], 'idx_assignments_class_due');
                }
                
                // Index for subject_id (subject-wise assignments)
                if (Schema::hasColumn('assignments', 'subject_id') && !$indexExists('assignments', 'idx_assignments_subject')) {
                    $table->index('subject_id', 'idx_assignments_subject');
                }
                
                // Index for teacher_id (teacher's assignments)
                if (Schema::hasColumn('assignments', 'teacher_id') && !$indexExists('assignments', 'idx_assignments_teacher')) {
                    $table->index('teacher_id', 'idx_assignments_teacher');
                }
            });
        }

        // Add indexes for notifications table - messaging system
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) use ($indexExists) {
                // Index for recipient_id (user notifications)
                if (Schema::hasColumn('notifications', 'recipient_id') && !$indexExists('notifications', 'idx_notifications_recipient')) {
                    $table->index('recipient_id', 'idx_notifications_recipient');
                }
                
                // Index for is_read (unread notifications)
                if (Schema::hasColumn('notifications', 'is_read') && !$indexExists('notifications', 'idx_notifications_is_read')) {
                    $table->index('is_read', 'idx_notifications_is_read');
                }
                
                // Composite index for recipient_id and is_read
                if (Schema::hasColumn('notifications', 'recipient_id') && Schema::hasColumn('notifications', 'is_read') && !$indexExists('notifications', 'idx_notifications_recipient_read')) {
                    $table->index(['recipient_id', 'is_read'], 'idx_notifications_recipient_read');
                }
                
                // Index for notification_type (type-based filtering)
                if (Schema::hasColumn('notifications', 'notification_type') && !$indexExists('notifications', 'idx_notifications_type')) {
                    $table->index('notification_type', 'idx_notifications_type');
                }
                
                // Index for created_at (recent notifications)
                if (!$indexExists('notifications', 'idx_notifications_created_at')) {
                    $table->index('created_at', 'idx_notifications_created_at');
                }
            });
        }

        // Add indexes for class_models table - class management
        if (Schema::hasTable('class_models')) {
            Schema::table('class_models', function (Blueprint $table) use ($indexExists) {
                // Index for academic_year (yearly class data)
                if (Schema::hasColumn('class_models', 'academic_year') && !$indexExists('class_models', 'idx_class_models_academic_year')) {
                    $table->index('academic_year', 'idx_class_models_academic_year');
                }
                
                // Index for is_active (active classes)
                if (Schema::hasColumn('class_models', 'is_active') && !$indexExists('class_models', 'idx_class_models_is_active')) {
                    $table->index('is_active', 'idx_class_models_is_active');
                }
                
                // Index for class_teacher_id (teacher's classes)
                if (Schema::hasColumn('class_models', 'class_teacher_id') && !$indexExists('class_models', 'idx_class_models_teacher')) {
                    $table->index('class_teacher_id', 'idx_class_models_teacher');
                }
            });
        }

        // Add indexes for subjects table - subject management
        if (Schema::hasTable('subjects')) {
            Schema::table('subjects', function (Blueprint $table) use ($indexExists) {
                // Index for is_active (active subjects)
                if (Schema::hasColumn('subjects', 'is_active') && !$indexExists('subjects', 'idx_subjects_is_active')) {
                    $table->index('is_active', 'idx_subjects_is_active');
                }
                
                // Index for subject_code (unique identifier)
                if (Schema::hasColumn('subjects', 'subject_code') && !$indexExists('subjects', 'idx_subjects_code')) {
                    $table->index('subject_code', 'idx_subjects_code');
                }
                
                // Index for class_id (class subjects)
                if (Schema::hasColumn('subjects', 'class_id') && !$indexExists('subjects', 'idx_subjects_class')) {
                    $table->index('class_id', 'idx_subjects_class');
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
            if (DB::getDriverName() === 'sqlite') {
                return false;
            }
            try {
                $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
                return !empty($indexes);
            } catch (\Exception $e) {
                return false;
            }
        };

        // Drop indexes in reverse order
        $tables = [
            'subjects' => ['idx_subjects_class', 'idx_subjects_code', 'idx_subjects_is_active'],
            'class_models' => ['idx_class_models_teacher', 'idx_class_models_is_active', 'idx_class_models_academic_year'],
            'notifications' => ['idx_notifications_created_at', 'idx_notifications_type', 'idx_notifications_recipient_read', 'idx_notifications_is_read', 'idx_notifications_recipient'],
            'assignments' => ['idx_assignments_teacher', 'idx_assignments_subject', 'idx_assignments_class_due', 'idx_assignments_status', 'idx_assignments_due_date'],
            'exams' => ['idx_exams_academic_year', 'idx_exams_class_date', 'idx_exams_status', 'idx_exams_exam_date'],
            'results' => ['idx_results_class_exam', 'idx_results_marks', 'idx_results_subject', 'idx_results_exam_student'],
            'fees' => ['idx_fees_academic_year', 'idx_fees_fee_type', 'idx_fees_student_status', 'idx_fees_due_date', 'idx_fees_payment_status'],
            'attendances' => ['idx_attendances_date_status', 'idx_attendances_status', 'idx_attendances_student_date', 'idx_attendances_date_class'],
            'students' => ['idx_students_parent_contact', 'idx_students_academic_year', 'idx_students_class_is_active', 'idx_students_roll_number', 'idx_students_admission_number']
        ];

        foreach ($tables as $tableName => $indexes) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($indexes, $indexExists, $tableName) {
                    foreach ($indexes as $indexName) {
                        if ($indexExists($tableName, $indexName)) {
                            $table->dropIndex($indexName);
                        }
                    }
                });
            }
        }
    }
};