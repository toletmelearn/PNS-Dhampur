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
        Schema::create('class_data_approvals', function (Blueprint $table) {
            $table->id();
            
            // Relationship to audit record
            $table->unsignedBigInteger('audit_id');
            $table->foreign('audit_id')->references('id')->on('class_data_audits')->onDelete('cascade');
            
            // Approval type and status
            $table->enum('approval_type', ['data_change', 'bulk_operation', 'critical_update', 'deletion', 'restoration', 'import', 'export', 'merge', 'split'])->default('data_change');
            $table->enum('status', ['pending', 'approved', 'rejected', 'delegated', 'escalated', 'expired', 'cancelled'])->default('pending');
            
            // User relationships
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->string('requester_name')->nullable(); // Cached requester name
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->string('assignee_name')->nullable(); // Cached assignee name
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->string('approver_name')->nullable(); // Cached approver name
            $table->unsignedBigInteger('approver_id')->nullable(); // For compatibility with factory
            
            // Priority and timing
            $table->enum('priority', ['low', 'normal', 'high', 'urgent', 'critical'])->default('normal');
            $table->integer('level')->default(1); // For compatibility with factory
            $table->timestamp('deadline')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // For compatibility with factory
            
            // Approval details
            $table->text('request_reason')->nullable(); // Why approval is needed
            $table->text('approval_reason')->nullable(); // Why it was approved/rejected
            $table->text('comments')->nullable(); // For compatibility with factory
            $table->json('approval_conditions')->nullable(); // Conditions for approval
            
            // Digital signature support
            $table->text('digital_signature')->nullable(); // Encrypted signature
            $table->string('signature_algorithm')->nullable(); // Signature algorithm used
            $table->timestamp('signature_timestamp')->nullable();
            $table->boolean('signature_verified')->default(false);
            
            // Workflow management
            $table->json('workflow_steps')->nullable(); // Multi-step approval workflow
            $table->integer('current_step')->default(1);
            $table->json('delegation_history')->nullable(); // Track delegations
            
            // Metadata and tracking
            $table->json('metadata')->nullable(); // Additional approval data
            $table->json('notification_settings')->nullable(); // Notification preferences
            $table->integer('escalation_level')->default(0);
            $table->timestamp('last_escalated_at')->nullable();
            
            // Auto-approval rules
            $table->boolean('auto_approval_eligible')->default(false);
            $table->json('auto_approval_rules')->nullable(); // Rules that triggered auto-approval
            
            // Compliance and audit
            $table->text('compliance_notes')->nullable(); // Compliance-related notes
            $table->json('risk_assessment')->nullable(); // Risk assessment data
            $table->unsignedInteger('approval_duration_minutes')->nullable(); // Time taken to approve
            
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approver_id')->references('id')->on('users')->onDelete('set null');
            
            // Indexes for performance
            $table->index('audit_id');
            $table->index('approval_type');
            $table->index('status');
            $table->index('requested_by');
            $table->index('assigned_to');
            $table->index('approved_by');
            $table->index('approver_id');
            $table->index('priority');
            $table->index('deadline');
            $table->index('approved_at');
            $table->index('created_at');
            $table->index('escalation_level');
            $table->index('auto_approval_eligible');
            
            // Composite indexes
            $table->index(['status', 'priority'], 'status_priority_index');
            $table->index(['assigned_to', 'status'], 'assignee_status_index');
            $table->index(['deadline', 'status'], 'deadline_status_index');
            $table->index(['approval_type', 'status'], 'type_status_index');
            $table->index(['created_at', 'status'], 'timeline_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_data_approvals');
    }
};