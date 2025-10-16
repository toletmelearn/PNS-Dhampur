<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('exam_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('class_id')->constrained()->onDelete('cascade');
            $table->foreignId('section_id')->nullable()->constrained()->onDelete('set null');
            
            // Marks Information
            $table->decimal('theory_marks', 8, 2)->default(0);
            $table->decimal('practical_marks', 8, 2)->default(0);
            $table->decimal('internal_marks', 8, 2)->default(0);
            $table->decimal('total_marks_obtained', 8, 2)->default(0);
            $table->decimal('total_marks_possible', 8, 2)->default(100);
            $table->decimal('percentage', 5, 2)->default(0);
            
            // Grading Information
            $table->string('grade')->nullable();
            $table->decimal('grade_points', 4, 2)->nullable();
            $table->string('remarks')->nullable();
            $table->enum('result_status', ['pass', 'fail', 'absent', 'exempted', 'withheld', 'cancelled'])->default('pass');
            
            // Attendance and Participation
            $table->boolean('was_present')->default(true);
            $table->text('absence_reason')->nullable();
            $table->decimal('attendance_percentage', 5, 2)->nullable();
            $table->boolean('eligible_for_exam')->default(true);
            $table->text('ineligibility_reason')->nullable();
            
            // Additional Assessment Components
            $table->json('component_marks')->nullable(); // Breakdown of different components
            $table->decimal('assignment_marks', 8, 2)->default(0);
            $table->decimal('project_marks', 8, 2)->default(0);
            $table->decimal('viva_marks', 8, 2)->default(0);
            $table->decimal('lab_marks', 8, 2)->default(0);
            
            // Ranking and Position
            $table->integer('class_rank')->nullable();
            $table->integer('section_rank')->nullable();
            $table->integer('subject_rank')->nullable();
            $table->integer('overall_rank')->nullable();
            
            // Evaluation Details
            $table->foreignId('evaluated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('evaluated_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();
            $table->boolean('is_final')->default(false);
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            
            // Improvement and Supplementary
            $table->boolean('is_improvement')->default(false);
            $table->boolean('is_supplementary')->default(false);
            $table->integer('attempt_number')->default(1);
            $table->foreignId('original_result_id')->nullable()->constrained('exam_results')->onDelete('set null');
            
            // Parent and Student Acknowledgment
            $table->boolean('parent_viewed')->default(false);
            $table->timestamp('parent_viewed_at')->nullable();
            $table->boolean('student_viewed')->default(false);
            $table->timestamp('student_viewed_at')->nullable();
            $table->text('parent_comments')->nullable();
            $table->text('student_comments')->nullable();
            
            // Additional Information
            $table->json('metadata')->nullable();
            $table->text('teacher_comments')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['exam_id', 'student_id', 'subject_id']);
            $table->index(['student_id', 'exam_id']);
            $table->index(['class_id', 'section_id', 'exam_id']);
            $table->index(['result_status', 'is_published']);
            $table->index(['grade', 'percentage']);
            $table->index(['class_rank', 'section_rank']);
            $table->index(['evaluated_by', 'verified_by']);
            $table->index(['is_improvement', 'is_supplementary']);
            $table->index(['parent_viewed', 'student_viewed']);
            
            // Unique constraint to prevent duplicate results
            $table->unique(['exam_id', 'student_id', 'subject_id', 'attempt_number'], 'unique_exam_student_subject_attempt');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('exam_results');
    }
};
