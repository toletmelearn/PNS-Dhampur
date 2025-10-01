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
        Schema::create('teacher_availability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('status', ['available', 'busy', 'absent', 'leave'])->default('available');
            $table->json('subject_expertise')->nullable(); // Subjects teacher can substitute for
            $table->text('notes')->nullable();
            $table->boolean('can_substitute')->default(true);
            $table->integer('max_substitutions_per_day')->default(3);
            $table->timestamps();

            // Indexes
            $table->index(['teacher_id', 'date']);
            $table->index(['date', 'status']);
            $table->index(['can_substitute', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_availability');
    }
};