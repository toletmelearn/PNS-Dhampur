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
        Schema::create('class_data_audits', function (Blueprint $table) {
            $table->id();
            
            // Auditable entity information
            $table->string('auditable_type'); // Model class name
            $table->unsignedBigInteger('auditable_id'); // Model ID
            $table->index(['auditable_type', 'auditable_id'], 'auditable_index');
            
            // Event information
            $table->enum('event_type', ['created', 'updated', 'deleted', 'restored', 'bulk_update', 'bulk_delete', 'import', 'export', 'merge', 'split', 'rollback']);
            $table->json('old_values')->nullable(); // Previous data state
            $table->json('new_values')->nullable(); // New data state
            $table->json('changed_fields')->nullable(); // List of changed field names
            
            // User and session information
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_type')->nullable(); // User model type
            $table->string('user_name')->nullable(); // Cached user name
            $table->string('user_role')->nullable(); // User role at time of change
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_id')->nullable();
            $table->string('request_id')->nullable(); // Unique request identifier
            
            // Change description and metadata
            $table->text('description')->nullable(); // Human-readable description
            $table->json('metadata')->nullable(); // Additional context data
            
            // Risk and approval information
            $table->enum('risk_level', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->boolean('requires_approval')->default(false);
            $table->enum('approval_status', ['pending', 'approved', 'rejected', 'auto_approved'])->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            
            // Batch and relationship information
            $table->string('batch_id')->nullable(); // Group related changes
            $table->unsignedBigInteger('parent_audit_id')->nullable(); // For nested changes
            
            // Data integrity
            $table->string('checksum', 64)->nullable(); // SHA-256 hash for integrity
            $table->json('tags')->nullable(); // Searchable tags
            
            $table->timestamps();
            $table->softDeletes(); // Add soft deletes support
            
            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('parent_audit_id')->references('id')->on('class_data_audits')->onDelete('cascade');
            
            // Indexes for performance
            $table->index('event_type');
            $table->index('user_id');
            $table->index('risk_level');
            $table->index('approval_status');
            $table->index('batch_id');
            $table->index('created_at');
            $table->index('checksum');
            
            // Composite indexes
            $table->index(['user_id', 'created_at'], 'user_activity_index');
            $table->index(['risk_level', 'approval_status'], 'risk_approval_index');
            $table->index(['event_type', 'created_at'], 'event_timeline_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_data_audits');
    }
};