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
                $table->unsignedBigInteger('class_id');
                $table->unsignedBigInteger('section_id');
                $table->unsignedBigInteger('subject_id');
                $table->date('date');
                $table->string('topic');
                $table->text('description')->nullable();
                $table->text('resources')->nullable();
                $table->text('homework')->nullable();
                $table->unsignedBigInteger('teacher_id');
                $table->enum('status', ['planned', 'completed', 'rescheduled']);
                $table->timestamps();
            });
            
            // Add foreign keys separately with error handling
            try {
                Schema::table('daily_syllabus', function (Blueprint $table) {
                    if (Schema::hasTable('classes')) {
                        $table->foreign('class_id')->references('id')->on('classes')
                            ->onDelete('cascade')->onUpdate('cascade');
                    }
                });
            } catch (\Exception $e) {
                \Log::warning('Could not add foreign key for classes in daily_syllabus: ' . $e->getMessage());
            }
            
            try {
                Schema::table('daily_syllabus', function (Blueprint $table) {
                    if (Schema::hasTable('sections')) {
                        $table->foreign('section_id')->references('id')->on('sections')
                            ->onDelete('cascade')->onUpdate('cascade');
                    }
                });
            } catch (\Exception $e) {
                \Log::warning('Could not add foreign key for sections in daily_syllabus: ' . $e->getMessage());
            }
            
            try {
                Schema::table('daily_syllabus', function (Blueprint $table) {
                    if (Schema::hasTable('subjects')) {
                        $table->foreign('subject_id')->references('id')->on('subjects')
                            ->onDelete('cascade')->onUpdate('cascade');
                    }
                });
            } catch (\Exception $e) {
                \Log::warning('Could not add foreign key for subjects in daily_syllabus: ' . $e->getMessage());
            }
            
            try {
                Schema::table('daily_syllabus', function (Blueprint $table) {
                    if (Schema::hasTable('teachers')) {
                        $table->foreign('teacher_id')->references('id')->on('teachers')
                            ->onDelete('cascade')->onUpdate('cascade');
                    }
                });
            } catch (\Exception $e) {
                \Log::warning('Could not add foreign key for teachers in daily_syllabus: ' . $e->getMessage());
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_syllabus');
    }
};