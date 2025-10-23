<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_document_id')->constrained('teacher_documents')->onDelete('cascade');
            $table->unsignedInteger('version_number');
            $table->string('original_name');
            $table->string('file_path');
            $table->string('file_extension');
            $table->unsignedBigInteger('file_size');
            $table->string('mime_type');
            $table->enum('status', ['pending', 'approved', 'rejected', 'archived'])->default('pending');
            $table->text('change_summary')->nullable();
            $table->boolean('is_current_version')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('approval_notes')->nullable();
            $table->string('checksum')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['teacher_document_id', 'version_number']);
            $table->index(['status', 'is_current_version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_versions');
    }
};