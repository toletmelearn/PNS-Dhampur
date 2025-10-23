<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('grading_systems', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('format')->default('percentage'); // percentage | gpa | cbse
            $table->json('rules'); // e.g., [{"min":90,"max":100,"grade":"A+","point":10}]
            $table->unsignedInteger('pass_mark')->nullable();
            $table->unsignedInteger('max_mark')->default(100);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grading_systems');
    }
};