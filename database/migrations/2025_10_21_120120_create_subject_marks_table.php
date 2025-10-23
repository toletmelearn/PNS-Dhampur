<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subject_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects');
            $table->foreignId('class_id')->constrained('class_models');
            $table->decimal('marks_obtained', 6, 2)->default(0);
            $table->decimal('total_marks', 6, 2)->default(100);
            $table->string('grade')->nullable();
            $table->decimal('grade_point', 4, 2)->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users');
            $table->foreignId('template_id')->nullable()->constrained('result_templates');
            $table->string('status')->default('draft'); // draft | finalized
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'exam_id', 'subject_id'], 'uniq_student_exam_subject');
            $table->index(['class_id', 'exam_id'], DB::getDriverName()==='sqlite' ? 'idx_subject_marks_class_exam' : 'idx_class_exam');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_marks');
    }
};