<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Check if foreign key constraints already exist before adding them
        
        // Add foreign key constraints for exams table (only if not exists)
        if (!$this->foreignKeyExists('exams', 'exams_class_id_foreign')) {
            Schema::table('exams', function (Blueprint $table) {
                $table->foreign('class_id')->references('id')->on('class_models')->onDelete('set null');
            });
        }

        // Add foreign key constraints for results table (only if not exists)
        if (!$this->foreignKeyExists('results', 'results_student_id_foreign')) {
            Schema::table('results', function (Blueprint $table) {
                $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            });
        }
        
        if (!$this->foreignKeyExists('results', 'results_exam_id_foreign')) {
            Schema::table('results', function (Blueprint $table) {
                $table->foreign('exam_id')->references('id')->on('exams')->onDelete('cascade');
            });
        }
        
        // Check if the uploaded_by column exists before adding foreign key
        if (Schema::hasColumn('results', 'uploaded_by') && !$this->foreignKeyExists('results', 'results_uploaded_by_foreign')) {
            Schema::table('results', function (Blueprint $table) {
                $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('set null');
            });
        }

        // Add foreign key constraints for salaries table (only if not exists)
        if (!$this->foreignKeyExists('salaries', 'salaries_teacher_id_foreign')) {
            Schema::table('salaries', function (Blueprint $table) {
                $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('cascade');
            });
        }

        // Add foreign key constraints for syllabus table (only if not exists)
        if (!$this->foreignKeyExists('syllabus', 'syllabus_class_id_foreign')) {
            Schema::table('syllabus', function (Blueprint $table) {
                $table->foreign('class_id')->references('id')->on('class_models')->onDelete('cascade');
            });
        }
        
        if (!$this->foreignKeyExists('syllabus', 'syllabus_teacher_id_foreign')) {
            Schema::table('syllabus', function (Blueprint $table) {
                $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('set null');
            });
        }

        // Add foreign key constraints for audit_trails table (only if not exists)
        if (!$this->foreignKeyExists('audit_trails', 'audit_trails_student_id_foreign')) {
            Schema::table('audit_trails', function (Blueprint $table) {
                $table->foreign('student_id')->references('id')->on('students')->onDelete('set null');
            });
        }
        
        if (!$this->foreignKeyExists('audit_trails', 'audit_trails_class_id_foreign')) {
            Schema::table('audit_trails', function (Blueprint $table) {
                $table->foreign('class_id')->references('id')->on('class_models')->onDelete('set null');
            });
        }
        
        if (!$this->foreignKeyExists('audit_trails', 'audit_trails_subject_id_foreign')) {
            Schema::table('audit_trails', function (Blueprint $table) {
                $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('set null');
            });
        }

        // Add foreign key constraints for class_teacher_permissions table (only if not exists)
        if (!$this->foreignKeyExists('class_teacher_permissions', 'class_teacher_permissions_subject_id_foreign')) {
            Schema::table('class_teacher_permissions', function (Blueprint $table) {
                $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('set null');
            });
        }

        // Add foreign key constraints for notifications table (sender_id) (only if not exists)
        if (!$this->foreignKeyExists('notifications', 'notifications_sender_id_foreign')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->foreign('sender_id')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    /**
     * Check if a foreign key constraint exists
     */
    private function foreignKeyExists($table, $constraintName)
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql') {
            $result = DB::select("
                SELECT COUNT(*) as count 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE CONSTRAINT_SCHEMA = DATABASE() 
                AND TABLE_NAME = ? 
                AND CONSTRAINT_NAME = ?
            ", [$table, $constraintName]);
            
            return $result[0]->count > 0;
        }
        
        // For SQLite and other databases, assume constraint doesn't exist to allow creation
        return false;
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop foreign key constraints (skip for SQLite)
        if (DB::getDriverName() !== 'sqlite') {
            if ($this->foreignKeyExists('notifications', 'notifications_sender_id_foreign')) {
                Schema::table('notifications', function (Blueprint $table) {
                    $table->dropForeign(['sender_id']);
                });
            }

            if ($this->foreignKeyExists('class_teacher_permissions', 'class_teacher_permissions_subject_id_foreign')) {
                Schema::table('class_teacher_permissions', function (Blueprint $table) {
                    $table->dropForeign(['subject_id']);
                });
            }

            if ($this->foreignKeyExists('audit_trails', 'audit_trails_student_id_foreign')) {
                Schema::table('audit_trails', function (Blueprint $table) {
                    $table->dropForeign(['student_id']);
                });
            }
            
            if ($this->foreignKeyExists('audit_trails', 'audit_trails_class_id_foreign')) {
                Schema::table('audit_trails', function (Blueprint $table) {
                    $table->dropForeign(['class_id']);
                });
            }
            
            if ($this->foreignKeyExists('audit_trails', 'audit_trails_subject_id_foreign')) {
                Schema::table('audit_trails', function (Blueprint $table) {
                    $table->dropForeign(['subject_id']);
                });
            }

            if ($this->foreignKeyExists('syllabus', 'syllabus_class_id_foreign')) {
                Schema::table('syllabus', function (Blueprint $table) {
                    $table->dropForeign(['class_id']);
                });
            }
            
            if ($this->foreignKeyExists('syllabus', 'syllabus_teacher_id_foreign')) {
                Schema::table('syllabus', function (Blueprint $table) {
                    $table->dropForeign(['teacher_id']);
                });
            }

            if ($this->foreignKeyExists('salaries', 'salaries_teacher_id_foreign')) {
                Schema::table('salaries', function (Blueprint $table) {
                    $table->dropForeign(['teacher_id']);
                });
            }

            if ($this->foreignKeyExists('results', 'results_student_id_foreign')) {
                Schema::table('results', function (Blueprint $table) {
                    $table->dropForeign(['student_id']);
                });
            }
            
            if ($this->foreignKeyExists('results', 'results_exam_id_foreign')) {
                Schema::table('results', function (Blueprint $table) {
                    $table->dropForeign(['exam_id']);
                });
            }
            
            if (Schema::hasColumn('results', 'uploaded_by') && $this->foreignKeyExists('results', 'results_uploaded_by_foreign')) {
                Schema::table('results', function (Blueprint $table) {
                    $table->dropForeign(['uploaded_by']);
                });
            }

            if ($this->foreignKeyExists('exams', 'exams_class_id_foreign')) {
                Schema::table('exams', function (Blueprint $table) {
                    $table->dropForeign(['class_id']);
                });
            }
        }
    }
};