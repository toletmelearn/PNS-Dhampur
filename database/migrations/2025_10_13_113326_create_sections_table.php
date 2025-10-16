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
        // If the sections table already exists from earlier migration,
        // skip creating it again to avoid duplicate table errors.
        if (Schema::hasTable('sections')) {
            return;
        }

        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained()->onDelete('cascade');
            $table->string('section_name'); // A, B, C, etc.
            $table->string('section_code')->unique();
            $table->text('description')->nullable();
            $table->integer('max_students')->default(40);
            $table->integer('current_students')->default(0);
            
            // Section Teacher
            $table->foreignId('section_teacher_id')->nullable()->constrained('teachers')->onDelete('set null');
            
            // Classroom Information
            $table->string('classroom')->nullable();
            $table->string('building')->nullable();
            $table->integer('floor')->nullable();
            $table->integer('room_capacity')->nullable();
            
            // Schedule
            $table->json('weekly_schedule')->nullable(); // Section-specific timetable
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            
            // Academic Settings
            $table->json('subjects')->nullable(); // Section-specific subjects if different from class
            $table->json('subject_teachers')->nullable(); // Subject-wise teacher assignments
            
            // Status
            $table->enum('status', ['active', 'inactive', 'merged', 'split'])->default('active');
            $table->string('academic_year');
            $table->boolean('admission_open')->default(true);
            
            // Tracking
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['class_id', 'section_name']);
            $table->index(['section_teacher_id', 'status']);
            $table->index(['status', 'academic_year']);
            $table->index('admission_open');
            
            // Unique constraint
            $table->unique(['class_id', 'section_name', 'academic_year']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sections');
    }
};
