<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('admit_cards')) {
            return;
        }

        Schema::create('admit_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('template_id')->nullable()->constrained('admit_templates')->onDelete('set null');
            $table->foreignId('seat_allocation_id')->nullable()->constrained('exam_seat_allocations')->onDelete('set null');
            $table->string('admit_card_no')->unique();
            $table->string('barcode')->nullable();
            $table->string('qr_code')->nullable();
            $table->string('pdf_path')->nullable();
            $table->text('html_snapshot')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index(['exam_id', 'class_id']);
            $table->unique(['exam_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admit_cards');
    }
};