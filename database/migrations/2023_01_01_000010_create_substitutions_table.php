<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('substitutions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('absent_teacher_id');
            $table->unsignedBigInteger('substitute_teacher_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('section_id');
            $table->unsignedBigInteger('subject_id');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('assigned_by');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('substitutions');
    }
};