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
        Schema::create('audit_trails', function (Blueprint $table) {
            $table->id();
            
            // User and session information
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('user_type')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('url')->nullable();
            
            // Auditable model information
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->string('event'); // created, updated, deleted, viewed, exported, etc.
            
            // Change tracking
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('changes')->nullable(); // Computed differences
            
            // Educational context
            $table->foreignId('student_id')->nullable()->constrained('students')->onDelete('set null');
            $table->unsignedBigInteger('class_id')->nullable(); // Remove foreign key constraint for now
            $table->unsignedBigInteger('subject_id')->nullable(); // Remove foreign key constraint for now
            $table->string('academic_year', 20)->nullable();
            $table->string('term', 20)->nullable();
            
            // Correction and approval workflow
            $table->text('correction_reason')->nullable();
            $table->enum('status', ['normal', 'pending_approval', 'approved', 'rejected'])->default('normal');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Additional metadata
            $table->json('tags')->nullable(); // For categorization
            $table->text('description')->nullable();
            $table->boolean('is_sensitive')->default(false);
            $table->boolean('requires_approval')->default(false);
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['user_id', 'created_at']);
            $table->index(['event', 'created_at']);
            $table->index(['student_id', 'created_at']);
            $table->index(['class_id', 'subject_id']);
            $table->index(['academic_year', 'term']);
            $table->index(['status', 'created_at']);
            $table->index(['requires_approval', 'status']);
            $table->index('created_at');
            $table->index('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_trails');
    }
};