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
        if (!Schema::hasTable('teacher_documents')) {
            Schema::create('teacher_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->enum('document_type', [
                'resume', 
                'certificate', 
                'degree', 
                'id_proof', 
                'experience_letter'
            ]);
            $table->string('original_name');
            $table->string('file_path');
            $table->string('file_extension');
            $table->unsignedBigInteger('file_size'); // in bytes
            $table->string('mime_type');
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->text('admin_comments')->nullable();
            $table->date('expiry_date')->nullable(); // for certificates
            $table->boolean('is_expired')->default(false);
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->json('metadata')->nullable(); // additional document info
            $table->timestamps();

            // Indexes for better performance
            $table->index(['teacher_id', 'document_type']);
            $table->index(['status', 'created_at']);
            $table->index(['expiry_date', 'is_expired']);
            $table->index('reviewed_at');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_documents');
    }
};