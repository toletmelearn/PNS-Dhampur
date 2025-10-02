<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sr_registers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('subject_id');
            $table->string('academic_year');
            $table->string('term');
            $table->decimal('attendance_percentage', 5, 2)->nullable();
            $table->decimal('theory_marks', 8, 2)->nullable();
            $table->decimal('practical_marks', 8, 2)->nullable();
            $table->decimal('internal_assessment', 8, 2)->nullable();
            $table->decimal('project_marks', 8, 2)->nullable();
            $table->decimal('total_marks', 8, 2)->nullable();
            $table->string('grade')->nullable();
            $table->text('remarks')->nullable();
            $table->string('conduct_grade')->nullable();
            $table->text('discipline_remarks')->nullable();
            $table->text('co_curricular_activities')->nullable();
            $table->text('sports_achievements')->nullable();
            $table->text('special_achievements')->nullable();
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('class_id')->references('id')->on('class_models')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
            $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sr_registers');
    }
};
