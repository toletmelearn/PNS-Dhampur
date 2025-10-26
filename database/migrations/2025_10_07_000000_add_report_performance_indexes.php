<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add indexes for report generation performance optimization
     */
    public function up()
    {
        // Add indexes for students table
        Schema::table('students', function (Blueprint $table) {
            // Index for class_id foreign key (if not already exists)
            if (!$this->indexExists('students', 'students_class_id_index')) {
                $table->index('class_id', 'students_class_id_index');
            }
            
            // Composite index for status and class_id (common filter combination)
            if (!$this->indexExists('students', 'students_is_active_class_id_index')) {
                $table->index(['is_active', 'class_id'], 'students_is_active_class_id_index');
            }
            
            // Index for admission_number (often used in searches)
            if (!$this->indexExists('students', 'students_admission_number_index')) {
                $table->index('admission_number', 'students_admission_number_index');
            }
        });

        // Add indexes for attendances table
        Schema::table('attendances', function (Blueprint $table) {
            // Index for student_id foreign key (if not already exists)
            if (!$this->indexExists('attendances', 'attendances_student_id_index')) {
                $table->index('student_id', 'attendances_student_id_index');
            }
            
            // Index for date column (frequently filtered)
            if (!$this->indexExists('attendances', 'attendances_date_index')) {
                $table->index('date', 'attendances_date_index');
            }
            
            // Composite index for student_id and date (common query pattern)
            if (!$this->indexExists('attendances', 'attendances_student_id_date_index')) {
                $table->index(['student_id', 'date'], 'attendances_student_id_date_index');
            }
            
            // Composite index for date and status (for attendance rate calculations)
            if (!$this->indexExists('attendances', 'attendances_date_status_index')) {
                $table->index(['date', 'status'], 'attendances_date_status_index');
            }
            
            // Index for status column
            if (!$this->indexExists('attendances', 'attendances_status_index')) {
                $table->index('status', 'attendances_status_index');
            }
        });

        // Add indexes for fees table
        Schema::table('fees', function (Blueprint $table) {
            // Index for student_id foreign key (if not already exists)
            if (!$this->indexExists('fees', 'fees_student_id_index')) {
                $table->index('student_id', 'fees_student_id_index');
            }
            
            // Index for status column (frequently filtered)
            if (!$this->indexExists('fees', 'fees_status_index')) {
                $table->index('status', 'fees_status_index');
            }
            
            // Composite index for student_id and status
            if (!$this->indexExists('fees', 'fees_student_id_status_index')) {
                $table->index(['student_id', 'status'], 'fees_student_id_status_index');
            }
            
            // Index for created_at (for date range queries)
            if (!$this->indexExists('fees', 'fees_created_at_index')) {
                $table->index('created_at', 'fees_created_at_index');
            }
            
            // Index for due_date (if exists)
            if (Schema::hasColumn('fees', 'due_date') && !$this->indexExists('fees', 'fees_due_date_index')) {
                $table->index('due_date', 'fees_due_date_index');
            }
        });

        // Add indexes for class_models table
        Schema::table('class_models', function (Blueprint $table) {
            // Index for name column (for sorting and searching)
            if (!$this->indexExists('class_models', 'class_models_name_index')) {
                $table->index('name', 'class_models_name_index');
            }
        });

        // Add indexes for results table (if exists)
        if (Schema::hasTable('results')) {
            Schema::table('results', function (Blueprint $table) {
                // Index for student_id foreign key
                if (!$this->indexExists('results', 'results_student_id_index')) {
                    $table->index('student_id', 'results_student_id_index');
                }
                
                // Check if subject column exists before adding index
                if (Schema::hasColumn('results', 'subject') && !$this->indexExists('results', 'results_subject_index')) {
                    $table->index('subject', 'results_subject_index');
                }
                
                // Check if subject column exists before adding composite index
                if (Schema::hasColumn('results', 'subject') && !$this->indexExists('results', 'results_student_id_subject_index')) {
                    $table->index(['student_id', 'subject'], 'results_student_id_subject_index');
                } else if (!$this->indexExists('results', 'results_student_id_subject_id_index') && Schema::hasColumn('results', 'subject_id')) {
                    // Alternative index if subject_id exists instead of subject
                    $table->index(['student_id', 'subject_id'], 'results_student_id_subject_id_index');
                }
                
                // Index for created_at (for date range queries)
                if (!$this->indexExists('results', 'results_created_at_index')) {
                    $table->index('created_at', 'results_created_at_index');
                }
                
                // Index for marks_obtained (for performance analysis)
                if (!$this->indexExists('results', 'results_marks_obtained_index')) {
                    $table->index('marks_obtained', 'results_marks_obtained_index');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // Drop indexes for students table
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex('students_class_id_index');
            $table->dropIndex('students_is_active_class_id_index');
            $table->dropIndex('students_admission_number_index');
        });

        // Drop indexes for attendances table
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('attendances_student_id_index');
            $table->dropIndex('attendances_date_index');
            $table->dropIndex('attendances_student_id_date_index');
            $table->dropIndex('attendances_date_status_index');
            $table->dropIndex('attendances_status_index');
        });

        // Drop indexes for fees table
        Schema::table('fees', function (Blueprint $table) {
            $table->dropIndex('fees_student_id_index');
            $table->dropIndex('fees_status_index');
            $table->dropIndex('fees_student_id_status_index');
            $table->dropIndex('fees_created_at_index');
            
            if (Schema::hasColumn('fees', 'due_date')) {
                $table->dropIndex('fees_due_date_index');
            }
        });

        // Drop indexes for class_models table
        Schema::table('class_models', function (Blueprint $table) {
            $table->dropIndex('class_models_name_index');
        });

        // Drop indexes for results table (if exists)
        if (Schema::hasTable('results')) {
            Schema::table('results', function (Blueprint $table) {
                $table->dropIndex('results_student_id_index');
                $table->dropIndex('results_subject_index');
                $table->dropIndex('results_student_id_subject_index');
                $table->dropIndex('results_created_at_index');
                $table->dropIndex('results_marks_obtained_index');
            });
        }
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists($table, $indexName)
    {
        // For SQLite, use a different approach to check index existence
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
                return false; // If we can't check, assume it doesn't exist
            }
        }
        
        $indexes = Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes($table);
        return array_key_exists($indexName, $indexes);
    }
};