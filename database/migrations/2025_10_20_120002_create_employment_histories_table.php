<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('employment_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->string('organization_name');
            $table->string('role_title');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->text('responsibilities')->nullable();
            $table->json('subjects_taught')->nullable();
            $table->text('achievements')->nullable();
            $table->unsignedBigInteger('teacher_document_id')->nullable();
            $table->string('document_path')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->boolean('verified')->default(false);
            $table->text('verification_notes')->nullable();
            $table->timestamps();

            $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('cascade');
            $table->foreign('teacher_document_id')->references('id')->on('teacher_documents')->onDelete('set null');
            $table->index(['teacher_id', 'organization_name']);
            $table->index(['verified']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employment_histories');
    }
};