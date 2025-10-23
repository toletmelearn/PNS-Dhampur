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
        if (Schema::hasTable('admit_templates')) {
            return;
        }

        Schema::create('admit_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('format')->default('A4'); // A4, A5, card
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admit_templates');
    }
};