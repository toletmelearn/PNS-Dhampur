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
        Schema::create('bell_timings', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Morning Assembly", "Period 1", "Recess", etc.
            $table->time('time'); // Bell time
            $table->enum('season', ['winter', 'summer']); // Season type
            $table->enum('type', ['start', 'end', 'break']); // Bell type
            $table->string('description')->nullable(); // Optional description
            $table->boolean('is_active')->default(true); // Active status
            $table->integer('order')->default(0); // Display order
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bell_timings');
    }
};