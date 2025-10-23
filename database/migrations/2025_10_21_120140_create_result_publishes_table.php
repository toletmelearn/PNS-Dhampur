<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('result_publishes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('class_models');
            $table->foreignId('template_id')->nullable()->constrained('result_templates');
            $table->string('format')->default('percentage');
            $table->string('status')->default('draft'); // draft | published | archived
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique(['exam_id', 'class_id', 'format'], 'uniq_publish_scope');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_publishes');
    }
};