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
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type'); // email, sms, push, system
            $table->string('category'); // student, teacher, parent, admin, system
            $table->string('subject')->nullable(); // For email templates
            $table->text('body');
            $table->json('variables')->nullable(); // Available template variables
            $table->json('settings')->nullable(); // Template specific settings
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false); // System templates cannot be deleted
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index(['type', 'category', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};