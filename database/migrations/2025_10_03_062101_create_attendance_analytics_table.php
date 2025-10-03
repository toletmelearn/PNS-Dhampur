<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->integer('month');
            $table->integer('year');
            $table->integer('total_working_days');
            $table->integer('present_days');
            $table->integer('absent_days');
            $table->integer('late_arrivals');
            $table->integer('early_departures');
            $table->decimal('total_working_hours', 8, 2);
            $table->decimal('average_daily_hours', 8, 2);
            $table->decimal('punctuality_score', 5, 2);
            $table->decimal('attendance_percentage', 5, 2);
            $table->json('leave_pattern_analysis');
            $table->json('performance_metrics');
            $table->timestamp('calculated_at');
            $table->timestamps();
            
            // Unique constraint to prevent duplicate analytics for same teacher/month/year
            $table->unique(['teacher_id', 'month', 'year']);
            
            // Indexes for better query performance
            $table->index(['year', 'month']);
            $table->index('punctuality_score');
            $table->index('attendance_percentage');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendance_analytics');
    }
};
