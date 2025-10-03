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
        Schema::create('exam_paper_security_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_paper_id')->nullable()->constrained('exam_papers')->onDelete('cascade');
            $table->foreignId('exam_paper_version_id')->nullable()->constrained('exam_paper_versions')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('action', [
                'created', 'viewed', 'edited', 'deleted', 'submitted', 'approved', 'rejected',
                'downloaded', 'printed', 'exported', 'copied', 'shared', 'accessed_unauthorized',
                'login_attempt', 'permission_denied', 'data_breach_attempt', 'suspicious_activity'
            ]);
            $table->string('resource_type', 50); // exam_paper, exam_paper_version, etc.
            $table->string('resource_id', 50)->nullable();
            $table->text('description');
            $table->json('old_values')->nullable(); // Previous values for edit actions
            $table->json('new_values')->nullable(); // New values for edit actions
            $table->string('ip_address', 45); // IPv4 and IPv6 support
            $table->text('user_agent')->nullable();
            $table->string('session_id', 100)->nullable();
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('risk_level', ['none', 'low', 'medium', 'high', 'critical'])->default('none');
            $table->boolean('is_suspicious')->default(false);
            $table->boolean('requires_investigation')->default(false);
            $table->timestamp('investigated_at')->nullable();
            $table->foreignId('investigated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('investigation_notes')->nullable();
            $table->json('metadata')->nullable(); // Additional security metadata
            $table->string('checksum', 64)->nullable(); // Log integrity verification
            $table->timestamp('created_at')->useCurrent();
            
            // Indexes for performance and security queries
            $table->index(['exam_paper_id', 'action', 'created_at']);
            $table->index(['user_id', 'action', 'created_at']);
            $table->index(['action', 'severity', 'created_at']);
            $table->index(['ip_address', 'created_at']);
            $table->index(['is_suspicious', 'requires_investigation'], 'security_logs_suspicious_investigation_idx');
            $table->index(['risk_level', 'created_at']);
            $table->index('session_id');
            
            // Full-text index for description search
            // Fulltext index removed for compatibility with SQLite testing
            // $table->fullText(['description', 'investigation_notes']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('exam_paper_security_logs');
    }
};
