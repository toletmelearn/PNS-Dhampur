<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('exam_paper_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exam_paper_id');
            $table->integer('version_number');
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('questions'); // Store questions as JSON
            $table->json('marking_scheme')->nullable(); // Store marking scheme as JSON
            $table->integer('total_marks');
            $table->integer('duration_minutes');
            $table->enum('difficulty_level', ['easy', 'medium', 'hard']);
            $table->json('instructions')->nullable(); // Exam instructions as JSON
            $table->json('metadata')->nullable(); // Additional metadata
            $table->enum('status', ['draft', 'review', 'approved', 'rejected', 'archived'])->default('draft');
            $table->text('change_summary')->nullable(); // Summary of changes in this version
            $table->unsignedBigInteger('created_by');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->text('approval_notes')->nullable();
            $table->boolean('is_current_version')->default(false);
            $table->string('checksum', 64)->nullable(); // For integrity verification
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            // Indexes for performance
            $table->index(['exam_paper_id', 'version_number']);
            $table->index(['status', 'created_at']);
            $table->index('is_current_version');
            $table->index('created_by');
            
            // Unique constraint to ensure only one current version per exam paper
            $table->unique(['exam_paper_id', 'is_current_version'], 'unique_current_version');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('exam_paper_versions');
    }
};
