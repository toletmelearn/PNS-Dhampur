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
        if (!Schema::hasTable('teacher_absences')) {
            Schema::create('teacher_absences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
                $table->date('absence_date');
                $table->date('end_date')->nullable();

                // Simple reason used by tests; keep alongside detailed fields for future use
                $table->string('reason')->nullable();
                $table->string('reason_category')->nullable();
                $table->text('reason_details')->nullable();

                $table->string('status')->default('pending'); // e.g., pending, approved, rejected

                // Reporting and approval tracking
                $table->foreignId('reported_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('reported_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('approved_at')->nullable();

                // Impact details
                $table->json('periods_affected')->nullable();
                $table->json('classes_affected')->nullable();

                // Notifications and substitution requirement
                $table->boolean('notification_sent')->default(false);
                $table->boolean('substitute_required')->default(false);

                // Documents and notes
                $table->string('medical_certificate')->nullable();
                $table->text('notes')->nullable();

                $table->timestamps();

                // Indexes for common queries
                $table->index(['teacher_id', 'absence_date']);
                $table->index(['status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_absences');
    }
};