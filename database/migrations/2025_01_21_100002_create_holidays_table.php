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
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('type')->default('holiday'); // holiday, event, exam, vacation
            $table->string('category')->default('general'); // general, religious, national, school
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_pattern')->nullable(); // yearly, monthly, etc.
            $table->boolean('is_active')->default(true);
            $table->string('color', 7)->default('#dc3545'); // Hex color for calendar display
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['start_date', 'end_date', 'is_active']);
            $table->index(['type', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};