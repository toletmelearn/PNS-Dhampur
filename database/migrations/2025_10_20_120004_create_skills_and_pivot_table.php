<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique('name');
        });

        Schema::create('skill_teacher', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->unsignedBigInteger('skill_id');
            $table->unsignedTinyInteger('proficiency_level')->default(0); // 0-10 scale
            $table->unsignedInteger('years_experience')->default(0);
            $table->boolean('verified')->default(false);
            $table->unsignedInteger('endorsements_count')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('cascade');
            $table->foreign('skill_id')->references('id')->on('skills')->onDelete('cascade');
            $table->unique(['teacher_id', 'skill_id']);
            $table->index(['verified']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_teacher');
        Schema::dropIfExists('skills');
    }
};