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
        Schema::create('teacher_substitutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('absent_teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->foreignId('substitute_teacher_id')->nullable()->constrained('teachers')->onDelete('set null');
            $table->foreignId('class_id')->constrained('class_models')->onDelete('cascade');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('subject')->nullable();
            $table->enum('status', ['pending', 'assigned', 'completed', 'cancelled'])->default('pending');
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->boolean('is_emergency')->default(false);
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes for better performance
            $table->index(['date', 'status']);
            $table->index(['absent_teacher_id', 'date']);
            $table->index(['substitute_teacher_id', 'date']);
            $table->index(['class_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_substitutions');
    }
};