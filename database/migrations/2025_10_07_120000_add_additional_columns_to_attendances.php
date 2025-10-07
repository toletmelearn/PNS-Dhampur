<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->time('check_in_time')->nullable()->after('status');
            $table->time('check_out_time')->nullable()->after('check_in_time');
            $table->integer('late_minutes')->default(0)->after('check_out_time');
            $table->integer('early_departure_minutes')->default(0)->after('late_minutes');
            $table->text('remarks')->nullable()->after('early_departure_minutes');
            $table->string('academic_year', 20)->nullable()->after('remarks');
            $table->integer('month')->nullable()->after('academic_year');
            $table->integer('week_number')->nullable()->after('month');
            $table->boolean('is_holiday')->default(false)->after('week_number');
            $table->enum('attendance_type', ['regular', 'biometric', 'manual'])->default('regular')->after('is_holiday');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'check_in_time',
                'check_out_time',
                'late_minutes',
                'early_departure_minutes',
                'remarks',
                'academic_year',
                'month',
                'week_number',
                'is_holiday',
                'attendance_type'
            ]);
        });
    }
};