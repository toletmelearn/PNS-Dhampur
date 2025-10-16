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
        // If the exams table already exists from an earlier migration, skip creating it again.
        if (Schema::hasTable('exams')) {
            return;
        }

        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            
            // Exam Classification
            $table->enum('type', ['unit_test', 'mid_term', 'final', 'quarterly', 'half_yearly', 'annual', 'board', 'entrance', 'competitive', 'internal', 'practical'])->default('unit_test');
            $table->enum('category', ['formative', 'summative', 'diagnostic', 'benchmark'])->default('summative');
            $table->string('academic_year');
            $table->string('term')->nullable(); // First Term, Second Term, etc.
            
            // Scheduling
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->integer('duration_minutes')->default(180);
            
            // Scope and Applicability
            $table->json('applicable_classes')->nullable(); // Array of class IDs
            $table->json('applicable_sections')->nullable(); // Array of section IDs
            $table->json('subjects')->nullable(); // Array of subject IDs with details
            
            // Exam Configuration
            $table->integer('total_marks')->default(100);
            $table->integer('passing_marks')->default(35);
            $table->decimal('passing_percentage', 5, 2)->default(35.00);
            $table->json('marking_scheme')->nullable(); // Theory, Practical, Internal weightage
            $table->json('grading_scale')->nullable(); // A+, A, B+, etc.
            
            // Instructions and Rules
            $table->text('instructions')->nullable();
            $table->json('exam_rules')->nullable();
            $table->boolean('allow_calculator')->default(false);
            $table->boolean('allow_formula_sheet')->default(false);
            $table->text('allowed_materials')->nullable();
            
            // Result Configuration
            $table->boolean('show_marks')->default(true);
            $table->boolean('show_grade')->default(true);
            $table->boolean('show_rank')->default(false);
            $table->boolean('show_percentage')->default(true);
            $table->date('result_declaration_date')->nullable();
            $table->boolean('results_published')->default(false);
            $table->timestamp('results_published_at')->nullable();
            
            // Supervision and Invigilation
            $table->json('invigilators')->nullable(); // Array of teacher IDs
            $table->json('exam_centers')->nullable(); // Array of room/hall details
            $table->text('special_arrangements')->nullable();
            
            // Status and Workflow
            $table->enum('status', ['draft', 'scheduled', 'ongoing', 'completed', 'cancelled', 'postponed'])->default('draft');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            
            // Notifications
            $table->boolean('notify_students')->default(true);
            $table->boolean('notify_parents')->default(true);
            $table->boolean('notify_teachers')->default(true);
            $table->timestamp('notifications_sent_at')->nullable();
            
            // Additional Information
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['code', 'is_active']);
            $table->index(['type', 'category']);
            $table->index(['academic_year', 'term']);
            $table->index(['start_date', 'end_date']);
            $table->index(['status', 'is_active']);
            $table->index(['results_published', 'result_declaration_date']);
            $table->index(['created_by', 'approved_by']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('exams');
    }
};
