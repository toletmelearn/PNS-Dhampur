<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('syllabus_progress', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('daily_syllabus_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('subject_id');
            $table->date('date');
            $table->json('planned_topics')->nullable();
            $table->json('completed_topics')->nullable();
            $table->decimal('completion_percentage', 5, 2)->default(0);
            $table->string('status')->default('pending'); // pending, in_progress, completed
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('marked_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['daily_syllabus_id', 'class_id', 'subject_id', 'date'], 'syllabus_progress_lookup_idx');
            $table->foreign('daily_syllabus_id')->references('id')->on('daily_syllabi')->onDelete('cascade');
            $table->foreign('class_id')->references('id')->on('class_models')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
            $table->foreign('marked_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('syllabus_progress');
    }
};
