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
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('class_name');
            $table->string('class_code')->unique();
            $table->text('description')->nullable();
            $table->integer('grade_level');
            $table->string('academic_year');
            $table->integer('max_students')->default(40);
            $table->integer('current_students')->default(0);
            
            // Class Teacher
            $table->foreignId('class_teacher_id')->nullable()->constrained('teachers')->onDelete('set null');
            
            // Schedule Information
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->json('weekly_schedule')->nullable(); // Timetable
            $table->string('classroom')->nullable();
            $table->string('building')->nullable();
            $table->integer('floor')->nullable();
            
            // Academic Information
            $table->json('subjects')->nullable(); // Array of subject IDs
            $table->decimal('pass_percentage', 5, 2)->default(40.00);
            $table->json('grading_system')->nullable();
            $table->text('syllabus')->nullable();
            $table->json('exam_schedule')->nullable();
            
            // Fee Structure
            $table->decimal('monthly_fee', 10, 2)->nullable();
            $table->decimal('admission_fee', 10, 2)->nullable();
            $table->decimal('annual_fee', 10, 2)->nullable();
            $table->json('additional_fees')->nullable(); // Lab, library, transport, etc.
            
            // Status and Settings
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            $table->boolean('is_promoted')->default(false);
            $table->foreignId('promoted_to')->nullable()->constrained('classes')->onDelete('set null');
            $table->date('promotion_date')->nullable();
            $table->boolean('admission_open')->default(true);
            $table->date('admission_start_date')->nullable();
            $table->date('admission_end_date')->nullable();
            
            // Tracking
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['class_name', 'academic_year']);
            $table->index(['grade_level', 'academic_year']);
            $table->index(['class_teacher_id', 'status']);
            $table->index('status');
            $table->index('academic_year');
            $table->index('admission_open');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('classes');
    }
};
