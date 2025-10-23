<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('paper_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exam_paper_id');
            $table->unsignedInteger('version_number');
            $table->longText('content_text')->nullable();
            $table->string('file_path')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['exam_paper_id', 'version_number']);
            $table->foreign('exam_paper_id')->references('id')->on('exam_papers')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paper_versions');
    }
};