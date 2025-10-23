<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('result_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('class_models');
            $table->foreignId('template_id')->nullable()->constrained('result_templates');
            $table->string('format')->default('percentage');
            $table->decimal('total_marks', 8, 2)->default(0);
            $table->decimal('max_marks', 8, 2)->default(0);
            $table->decimal('percentage', 5, 2)->nullable();
            $table->string('grade')->nullable();
            $table->unsignedInteger('position')->nullable();
            $table->json('card_data'); // snapshot of computed details
            $table->string('pdf_path')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'exam_id'], 'uniq_student_exam');
            $table->index(['class_id', 'exam_id'], DB::getDriverName()==='sqlite' ? 'idx_result_cards_class_exam' : 'idx_class_exam');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_cards');
    }
};