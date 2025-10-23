<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transport_buses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('number_plate')->unique();
            $table->string('driver_name')->nullable();
            $table->string('driver_phone')->nullable();
            $table->string('route_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_buses');
    }
};
