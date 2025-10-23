<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('attendances')) {
            return;
        }

        // Recreate the attendances table with extended status enum (add 'excused').
        Schema::create('attendances_tmp', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_id')->nullable();
            $table->date('date');
            $table->enum('status', ['present', 'absent', 'late', 'excused'])->default('present');
            $table->unsignedBigInteger('marked_by')->nullable();

            // Additional columns introduced by later migrations
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->integer('late_minutes')->default(0);
            $table->integer('early_departure_minutes')->default(0);
            $table->text('remarks')->nullable();
            $table->string('academic_year', 20)->nullable();
            $table->integer('month')->nullable();
            $table->integer('week_number')->nullable();
            $table->boolean('is_holiday')->default(false);
            $table->enum('attendance_type', ['regular', 'biometric', 'manual'])->default('regular');

            $table->timestamps();
            $table->softDeletes();

            // Unique constraint to prevent duplicates
            $table->unique(['student_id', 'date']);
        });

        // Copy data from existing table
        DB::statement('INSERT INTO attendances_tmp (id, student_id, class_id, date, status, marked_by, check_in_time, check_out_time, late_minutes, early_departure_minutes, remarks, academic_year, month, week_number, is_holiday, attendance_type, created_at, updated_at)
                       SELECT id, student_id, class_id, date, status, marked_by, check_in_time, check_out_time, late_minutes, early_departure_minutes, remarks, academic_year, month, week_number, is_holiday, attendance_type, created_at, updated_at FROM attendances');

        // Replace old table with the new one
        Schema::drop('attendances');
        Schema::rename('attendances_tmp', 'attendances');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('attendances')) {
            return;
        }

        // Recreate table reverting status enum to original values
        Schema::create('attendances_tmp', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_id')->nullable();
            $table->date('date');
            $table->enum('status', ['present', 'absent', 'late'])->default('present');
            $table->unsignedBigInteger('marked_by')->nullable();

            // Preserve additional columns
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->integer('late_minutes')->default(0);
            $table->integer('early_departure_minutes')->default(0);
            $table->text('remarks')->nullable();
            $table->string('academic_year', 20)->nullable();
            $table->integer('month')->nullable();
            $table->integer('week_number')->nullable();
            $table->boolean('is_holiday')->default(false);
            $table->enum('attendance_type', ['regular', 'biometric', 'manual'])->default('regular');

            $table->timestamps();
            $table->softDeletes();
            $table->unique(['student_id', 'date']);
        });

        DB::statement('INSERT INTO attendances_tmp (id, student_id, class_id, date, status, marked_by, check_in_time, check_out_time, late_minutes, early_departure_minutes, remarks, academic_year, month, week_number, is_holiday, attendance_type, created_at, updated_at)
                       SELECT id, student_id, class_id, date, status, marked_by, check_in_time, check_out_time, late_minutes, early_departure_minutes, remarks, academic_year, month, week_number, is_holiday, attendance_type, created_at, updated_at FROM attendances');

        Schema::drop('attendances');
        Schema::rename('attendances_tmp', 'attendances');
    }
};