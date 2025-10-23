<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hostel_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained('hostel_buildings')->cascadeOnDelete();
            $table->string('room_number');
            $table->unsignedInteger('bed_count')->default(1);
            $table->enum('gender', ['male','female','mixed'])->default('mixed');
            $table->enum('status', ['available','full','maintenance'])->default('available');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hostel_rooms');
    }
};
