<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('paper_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exam_paper_id');
            $table->unsignedBigInteger('submitted_by');
            $table->longText('content_text')->nullable();
            $table->string('file_path')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->enum('status', ['pending', 'under_review', 'approved', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('exam_paper_id')->references('id')->on('exam_papers')->onDelete('cascade');
            $table->foreign('submitted_by')->references('id')->on('users')->onDelete('cascade');
            $table->index(['exam_paper_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paper_submissions');
    }
};