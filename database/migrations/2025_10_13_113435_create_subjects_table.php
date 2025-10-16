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
        // Avoid duplicate creation if an earlier subjects table already exists.
        if (Schema::hasTable('subjects')) {
            return;
        }

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            
            // Academic Information
            $table->enum('type', ['core', 'elective', 'optional', 'extra_curricular', 'vocational'])->default('core');
            $table->enum('category', ['language', 'mathematics', 'science', 'social_science', 'arts', 'physical_education', 'computer_science', 'vocational', 'other'])->default('other');
            $table->integer('credits')->default(1);
            $table->integer('theory_marks')->default(100);
            $table->integer('practical_marks')->default(0);
            $table->integer('internal_marks')->default(0);
            $table->integer('total_marks')->default(100);
            $table->integer('passing_marks')->default(35);
            
            // Class and Grade Information
            $table->json('applicable_classes')->nullable(); // Array of class IDs
            $table->json('applicable_grades')->nullable(); // Array of grade levels
            $table->string('academic_year');
            
            // Teaching Information
            $table->integer('periods_per_week')->default(5);
            $table->integer('duration_minutes')->default(45);
            $table->boolean('has_practical')->default(false);
            $table->boolean('has_lab')->default(false);
            $table->string('lab_requirements')->nullable();
            
            // Syllabus and Resources
            $table->text('syllabus_overview')->nullable();
            $table->json('learning_objectives')->nullable();
            $table->json('textbooks')->nullable();
            $table->json('reference_books')->nullable();
            $table->json('online_resources')->nullable();
            
            // Assessment Information
            $table->json('assessment_pattern')->nullable(); // Exam pattern, weightage
            $table->boolean('continuous_assessment')->default(true);
            $table->json('grading_scheme')->nullable();
            $table->boolean('has_project')->default(false);
            $table->boolean('has_assignment')->default(true);
            
            // Prerequisites and Dependencies
            $table->json('prerequisites')->nullable(); // Required subjects
            $table->json('corequisites')->nullable(); // Concurrent subjects
            $table->integer('difficulty_level')->default(1); // 1-5 scale
            
            // Status and Settings
            $table->boolean('is_active')->default(true);
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('allow_exemption')->default(false);
            $table->text('exemption_criteria')->nullable();
            $table->integer('sort_order')->default(0);
            
            // Additional Information
            $table->string('department')->nullable();
            $table->string('board_code')->nullable(); // For board exam subjects
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['code', 'is_active']);
            $table->index(['type', 'category']);
            $table->index(['academic_year', 'is_active']);
            $table->index(['is_mandatory', 'is_active']);
            $table->index('sort_order');
            $table->index('department');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subjects');
    }
};
