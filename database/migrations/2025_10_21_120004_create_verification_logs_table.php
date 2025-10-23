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
        if (Schema::hasTable('verification_logs')) {
            return;
        }

        Schema::create('verification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admit_card_id')->constrained('admit_cards')->onDelete('cascade');
            $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->enum('method', ['qr', 'barcode', 'manual'])->default('qr');
            $table->boolean('success')->default(true);
            $table->timestamp('scanned_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('location')->nullable();
            $table->json('payload')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['exam_id', 'student_id', 'scanned_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_logs');
    }
};