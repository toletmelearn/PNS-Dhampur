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
        Schema::create('bell_schedules', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->enum('season', ['winter', 'summer']);
            $table->unsignedBigInteger('special_schedule_id')->nullable();
            $table->enum('source', ['computed', 'manual'])->default('computed');
            $table->json('effective_timings'); // snapshot of timings used that day
            $table->timestamps();

            $table->unique(['date', 'season']);
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
        Schema::dropIfExists('bell_schedules');
    }
};
