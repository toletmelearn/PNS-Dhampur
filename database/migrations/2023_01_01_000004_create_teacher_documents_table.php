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
                $table->unsignedBigInteger('teacher_id');
                $table->enum('document_type', ['resume', 'certificate', 'degree', 'id_proof', 'experience_letter']);
                $table->string('original_name');
                $table->string('file_path');
                $table->string('file_extension');
                $table->unsignedBigInteger('file_size');
                $table->string('mime_type');
                $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
                $table->text('admin_comments')->nullable();
                $table->date('expiry_date')->nullable();
                $table->boolean('is_expired')->default(false);
                $table->unsignedBigInteger('uploaded_by');
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
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