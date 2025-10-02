<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_papers', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('paper_code')->unique();
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('exam_id')->nullable();
            $table->unsignedBigInteger('teacher_id');
            $table->integer('duration_minutes');
            $table->decimal('total_marks', 8, 2);
            $table->text('instructions')->nullable();
            $table->enum('paper_type', ['theory', 'practical', 'both'])->default('theory');
            $table->enum('difficulty_level', ['easy', 'medium', 'hard'])->default('medium');
            $table->datetime('submission_deadline')->nullable();
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'published'])->default('draft');
            $table->datetime('published_at')->nullable();
            $table->unsignedBigInteger('published_by')->nullable();
            $table->datetime('submitted_at')->nullable();
            $table->datetime('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->datetime('rejected_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
            $table->foreign('class_id')->references('id')->on('class_models')->onDelete('cascade');
            $table->foreign('exam_id')->references('id')->on('exams')->onDelete('set null');
            $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('cascade');
            $table->foreign('published_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('rejected_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index(['subject_id', 'class_id']);
            $table->index('status');
            $table->index('teacher_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_papers');
    }
};
