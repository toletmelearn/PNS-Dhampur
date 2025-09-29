<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_id')->nullable();
            $table->date('date');
            $table->enum('status',['present','absent','late'])->default('present');
            $table->unsignedBigInteger('marked_by')->nullable();
            $table->timestamps();
            $table->unique(['student_id','date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
