<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // If the notifications table already exists from an earlier migration,
        // skip re-creation to prevent duplicate table errors.
        if (Schema::hasTable('notifications')) {
            return;
        }

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('message');
            $table->text('description')->nullable();
            
            // Notification Classification
            $table->enum('type', ['announcement', 'alert', 'reminder', 'emergency', 'academic', 'fee', 'attendance', 'exam', 'transport', 'library', 'event', 'system'])->default('announcement');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent', 'critical'])->default('medium');
            $table->enum('category', ['general', 'academic', 'administrative', 'emergency', 'event', 'maintenance'])->default('general');
            
            // Sender Information
            $table->foreignId('sender_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('sender_name')->nullable();
            $table->string('sender_role')->nullable();
            $table->string('department')->nullable();
            
            // Target Audience
            $table->json('target_roles')->nullable(); // Array of roles: admin, teacher, student, parent
            $table->json('target_classes')->nullable(); // Array of class IDs
            $table->json('target_sections')->nullable(); // Array of section IDs
            $table->json('target_users')->nullable(); // Array of specific user IDs
            $table->boolean('send_to_all')->default(false);
            
            // Delivery Channels
            $table->boolean('send_email')->default(false);
            $table->boolean('send_sms')->default(false);
            $table->boolean('send_push')->default(true);
            $table->boolean('send_whatsapp')->default(false);
            $table->boolean('show_on_dashboard')->default(true);
            $table->boolean('show_on_mobile')->default(true);
            
            // Scheduling
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_scheduled')->default(false);
            $table->boolean('is_recurring')->default(false);
            $table->json('recurrence_pattern')->nullable(); // Daily, Weekly, Monthly pattern
            
            // Status and Tracking
            $table->enum('status', ['draft', 'scheduled', 'sending', 'sent', 'failed', 'cancelled', 'expired'])->default('draft');
            $table->integer('total_recipients')->default(0);
            $table->integer('sent_count')->default(0);
            $table->integer('delivered_count')->default(0);
            $table->integer('read_count')->default(0);
            $table->integer('failed_count')->default(0);
            
            // Content and Media
            $table->json('attachments')->nullable(); // Array of file paths
            $table->string('image_url')->nullable();
            $table->string('action_url')->nullable(); // URL for call-to-action
            $table->string('action_text')->nullable(); // Text for action button
            $table->json('custom_data')->nullable(); // Additional data for mobile apps
            
            // Approval Workflow
            $table->boolean('requires_approval')->default(false);
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->nullable();
            
            // Analytics and Engagement
            $table->integer('view_count')->default(0);
            $table->integer('click_count')->default(0);
            $table->decimal('engagement_rate', 5, 2)->default(0);
            $table->json('delivery_stats')->nullable(); // Detailed delivery statistics
            $table->json('engagement_stats')->nullable(); // Click, view, interaction stats
            
            // Emergency and Special Features
            $table->boolean('is_emergency')->default(false);
            $table->boolean('requires_acknowledgment')->default(false);
            $table->integer('acknowledgment_count')->default(0);
            $table->boolean('auto_translate')->default(false);
            $table->json('translations')->nullable(); // Multi-language support
            
            // System and Administrative
            $table->boolean('is_system_generated')->default(false);
            $table->string('system_source')->nullable(); // attendance, fees, exams, etc.
            $table->string('reference_id')->nullable(); // Reference to related record
            $table->string('reference_type')->nullable(); // Type of related record
            
            // Additional Information
            $table->json('metadata')->nullable();
            $table->text('admin_notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['type', 'priority', 'status']);
            $table->index(['sender_id', 'created_at']);
            $table->index(['scheduled_at', 'is_scheduled']);
            $table->index(['status', 'sent_at']);
            $table->index(['expires_at', 'is_active']);
            $table->index(['is_emergency', 'requires_acknowledgment']);
            $table->index(['approval_status', 'requires_approval']);
            $table->index(['system_source', 'reference_id']);
            $table->index(['send_to_all', 'target_roles']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notifications');
    }
};
