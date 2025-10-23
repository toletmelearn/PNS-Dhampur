<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hostel_buildings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('warden_name')->nullable();
            $table->enum('gender', ['male','female','mixed'])->default('mixed');
            $table->unsignedInteger('capacity')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hostel_buildings');
    }
};
