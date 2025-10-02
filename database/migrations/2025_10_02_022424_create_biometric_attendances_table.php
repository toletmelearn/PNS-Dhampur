<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biometric_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->date('date');
            $table->datetime('check_in_time')->nullable();
            $table->datetime('check_out_time')->nullable();
            $table->string('status')->default('present');
            $table->decimal('working_hours', 5, 2)->nullable();
            $table->boolean('is_late')->default(false);
            $table->boolean('is_early_departure')->default(false);
            $table->text('biometric_data')->nullable();
            $table->string('device_id')->nullable();
            $table->string('check_in_location')->nullable();
            $table->string('check_out_location')->nullable();
            $table->text('absence_reason')->nullable();
            $table->unsignedBigInteger('marked_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('cascade');
            $table->foreign('marked_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index(['teacher_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biometric_attendances');
    }
};
