<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('results')) {
            Schema::create('results', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('exam_id');
                $table->string('subject_id');
                $table->decimal('marks_obtained', 8, 2)->nullable();
                $table->decimal('max_marks', 8, 2)->nullable();
                $table->string('grade')->nullable();
                $table->text('remarks')->nullable();
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->timestamps();

                $table->foreign('student_id')->references('id')->on('students')
                    ->onDelete('cascade')->onUpdate('cascade');
                $table->foreign('exam_id')->references('id')->on('exams')
                    ->onDelete('cascade')->onUpdate('cascade');
                $table->foreign('subject_id')->references('id')->on('subjects')
                    ->onDelete('cascade')->onUpdate('cascade');
                
                if (Schema::hasTable('users')) {
                    $table->foreign('uploaded_by')->references('id')->on('users')
                        ->onDelete('set null')->onUpdate('cascade');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};
