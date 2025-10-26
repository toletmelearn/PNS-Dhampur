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
        if (!Schema::hasTable('student_verifications')) {
            Schema::create('student_verifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
                $table->string('document_type'); // 'birth_certificate', 'aadhar_card', 'transfer_certificate', etc.
                $table->string('original_file_path'); // Path to original uploaded document
                $table->string('processed_file_path')->nullable(); // Path to processed/OCR'd document
                $table->enum('verification_status', ['pending', 'processing', 'verified', 'failed', 'manual_review'])->default('pending');
                $table->enum('verification_method', ['automatic', 'manual', 'hybrid'])->default('automatic');
                
                // OCR and extracted data
                $table->json('extracted_data')->nullable(); // OCR extracted text and data
                $table->json('verification_results')->nullable(); // Detailed verification results
                $table->decimal('confidence_score', 5, 2)->nullable(); // 0.00 to 100.00
                
                // Document validation fields
                $table->boolean('format_valid')->default(false);
                $table->boolean('quality_check_passed')->default(false);
                $table->boolean('data_consistency_check')->default(false);
                $table->boolean('cross_reference_check')->default(false);
                
                // Manual review fields
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
                $table->text('reviewer_comments')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                
                // Audit trail
                $table->json('verification_log')->nullable(); // Step-by-step verification process
                $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
                $table->timestamp('verification_started_at')->nullable();
                $table->timestamp('verification_completed_at')->nullable();
                
                $table->timestamps();
                
                // Indexes for performance
                $table->index(['student_id', 'document_type']);
                $table->index('verification_status');
                $table->index('verification_method');
                $table->index('confidence_score');
                $table->index('reviewed_by');
                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_verifications');
    }
};