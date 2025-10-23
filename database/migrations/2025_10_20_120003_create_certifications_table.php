<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('certifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->string('name');
            $table->string('issuing_organization');
            $table->date('issue_date');
            $table->date('expiry_date')->nullable();
            $table->string('certificate_code')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->unsignedBigInteger('teacher_document_id')->nullable();
            $table->string('document_path')->nullable();
            $table->boolean('verified')->default(false);
            $table->string('license_number')->nullable();
            $table->timestamps();

            $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('cascade');
            $table->foreign('teacher_document_id')->references('id')->on('teacher_documents')->onDelete('set null');
            $table->index(['teacher_id', 'name']);
            $table->index(['verified']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certifications');
    }
};