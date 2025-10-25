<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('result_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('format')->default('percentage'); // percentage | gpa | cbse | custom
            $table->json('settings')->nullable(); // JSON for layout, subject order, weightages, remarks options
            $table->unsignedBigInteger('grading_system_id')->nullable(); // Will add foreign key later
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_templates');
    }
};