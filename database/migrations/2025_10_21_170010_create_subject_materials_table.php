<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_materials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('daily_syllabus_id');
            $table->unsignedBigInteger('uploaded_by');
            $table->string('type')->default('other'); // pdf, doc, docx, image, video, other
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('original_filename')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('storage_disk')->default('public');
            $table->string('visibility')->default('class_only');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('download_count')->default(0);
            $table->unsignedBigInteger('view_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['daily_syllabus_id', 'type']);
            $table->foreign('daily_syllabus_id')->references('id')->on('daily_syllabi')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_materials');
    }
};
