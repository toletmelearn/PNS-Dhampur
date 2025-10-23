<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedBigInteger('reviewer_id')->nullable(); // users table
            $table->json('ratings')->nullable(); // e.g. {"pedagogy":9,"classroom_management":8,...}
            $table->decimal('overall_score', 5, 2)->nullable();
            $table->longText('comments')->nullable();
            $table->json('recommendations')->nullable();
            $table->boolean('promotion_recommended')->default(false);
            $table->string('promotion_title')->nullable();
            $table->boolean('increment_recommended')->default(false);
            $table->decimal('increment_amount', 10, 2)->nullable();
            $table->unsignedBigInteger('teacher_document_id')->nullable();
            $table->timestamps();

            $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('cascade');
            $table->foreign('reviewer_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('teacher_document_id')->references('id')->on('teacher_documents')->onDelete('set null');
            $table->index(['teacher_id', 'period_start', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_reviews');
    }
};