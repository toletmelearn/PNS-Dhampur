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
        if (!Schema::hasTable('bell_schedules')) {
            Schema::create('bell_schedules', function (Blueprint $table) {
                $table->id();
                $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
                $table->integer('period_number');
                $table->time('start_time');
                $table->time('end_time');
                $table->boolean('is_break');
                $table->string('description')->nullable();
                $table->boolean('is_active');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bell_schedules');
    }
};
