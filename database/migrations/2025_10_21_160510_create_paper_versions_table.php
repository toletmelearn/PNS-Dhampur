<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('paper_versions')) {
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
                
                // Check if the exam_papers table exists before adding foreign key
                if (Schema::hasTable('exam_papers')) {
                    $table->foreign('exam_paper_id')->references('id')->on('exam_papers')->onDelete('cascade');
                }
                
                // Check if the users table exists before adding foreign key
                if (Schema::hasTable('users')) {
                    $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('paper_versions');
    }
};