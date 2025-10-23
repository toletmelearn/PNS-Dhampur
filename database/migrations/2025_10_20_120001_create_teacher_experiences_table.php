<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('teacher_experiences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->unsignedInteger('total_years')->default(0);
            $table->string('primary_specialization')->nullable();
            $table->json('specializations')->nullable();
            $table->text('summary')->nullable();
            $table->longText('achievements')->nullable();
            $table->date('last_promotion_date')->nullable();
            $table->enum('portfolio_status', ['in_progress', 'complete'])->default('in_progress');
            $table->timestamps();

            $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('cascade');
            $table->index(['teacher_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_experiences');
    }
};