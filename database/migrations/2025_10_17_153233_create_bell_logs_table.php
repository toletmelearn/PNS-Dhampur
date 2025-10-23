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
        Schema::create('bell_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bell_timing_id')->nullable();
            $table->unsignedBigInteger('special_schedule_id')->nullable();
            $table->enum('schedule_type', ['regular', 'special'])->default('regular');
            $table->enum('ring_type', ['auto', 'manual'])->default('auto');
            $table->string('name');
            $table->time('time');
            $table->enum('season', ['winter', 'summer'])->nullable();
            $table->date('date');
            $table->boolean('suppressed')->default(false);
            $table->boolean('forced')->default(false);
            $table->string('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['date', 'season']);
            $table->index(['schedule_type', 'ring_type']);

            $table->foreign('bell_timing_id')
                ->references('id')
                ->on('bell_timings')
                ->onDelete('set null');

            $table->foreign('special_schedule_id')
                ->references('id')
                ->on('special_schedules')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bell_logs');
    }
};
