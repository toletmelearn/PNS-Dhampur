<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
                if (!Schema::hasTable('daily_syllabus')) {
            Schema::create('daily_syllabus', function (Blueprint $table) {
                $table->id();
                $table->string('class_id');
                $table->string('section_id');
                $table->string('subject_id');
                $table->date('date');
                $table->string('topic');
                $table->text('description')->nullable();
                $table->text('resources')->nullable();
                $table->text('homework')->nullable();
                $table->unsignedBigInteger('teacher_id');
                $table->enum('status', 'planned', 'completed', 'rescheduled');
                $table->timestamps();

                $table->foreign('class_id')->references('id')->on('classes')
                    ->onDelete('cascade')->onUpdate('cascade');
                $table->foreign('section_id')->references('id')->on('sections')
                    ->onDelete('cascade')->onUpdate('cascade');
                $table->foreign('subject_id')->references('id')->on('subjects')
                    ->onDelete('cascade')->onUpdate('cascade');
                $table->foreign('teacher_id')->references('id')->on('teachers')
                    ->onDelete('cascade')->onUpdate('cascade');
            });
        }
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('section_id');
            $table->unsignedBigInteger('subject_id');
            $table->date('date');
            $table->text('topics_covered');
            $table->text('homework')->nullable();
            $table->text('resources')->nullable();
            $table->unsignedBigInteger('teacher_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_syllabus');
    }
};