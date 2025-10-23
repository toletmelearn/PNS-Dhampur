<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_progress', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_model_id');
            $table->unsignedBigInteger('class_data_id')->nullable();

            // Progress payload
            $table->json('progress')->nullable();

            // Term/period
            $table->string('term')->nullable();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();

            // Audit references
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            // Approval workflow
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('approved');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // FKs
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('class_model_id')->references('id')->on('class_models')->onDelete('cascade');
            $table->foreign('class_data_id')->references('id')->on('class_data')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index(['student_id', 'class_model_id']);
            $table->index(['term']);
            $table->index(['approval_status', 'approved_at']);
            $table->index(['created_by', 'created_at']);
            $table->index(['updated_by', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_progress');
    }
};
