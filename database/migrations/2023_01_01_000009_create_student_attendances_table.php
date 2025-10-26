<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('student_attendances')) {
            Schema::create('student_attendances', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('class_id');
                $table->unsignedBigInteger('section_id');
                $table->date('date');
                $table->enum('status', ['present', 'absent', 'late', 'half_day', 'leave']);
                $table->time('in_time')->nullable();
                $table->time('out_time')->nullable();
                $table->text('remarks')->nullable();
                $table->unsignedBigInteger('marked_by');
                $table->timestamps();

                $table->foreign('student_id')->references('id')->on('students')
                    ->onDelete('cascade')->onUpdate('cascade');
                $table->foreign('class_id')->references('id')->on('classes')
                    ->onDelete('cascade')->onUpdate('cascade');
                $table->foreign('section_id')->references('id')->on('sections')
                    ->onDelete('cascade')->onUpdate('cascade');
                $table->foreign('marked_by')->references('id')->on('users')
                    ->onDelete('cascade')->onUpdate('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_attendances');
    }
};