<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('change_logs', function (Blueprint $table) {
            $table->id();

            // Polymorphic target of change (class_data, student_progress, class_note, etc.)
            $table->string('changeable_type');
            $table->unsignedBigInteger('changeable_id');

            // Change actor and context
            $table->unsignedBigInteger('user_id');
            $table->string('action'); // create, update, delete, approve, reject, rollback

            // Field-level diffs
            $table->json('changed_fields')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            // Significance and approval context
            $table->boolean('significant')->default(false);
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();

            // Link to audit record if present
            $table->unsignedBigInteger('audit_id')->nullable();

            // Misc
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('session_id')->nullable();
            $table->string('batch_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('audit_id')->references('id')->on('class_data_audits')->onDelete('set null');

            // Indexes
            $table->index(['changeable_type', 'changeable_id']);
            $table->index(['user_id', 'created_at']);
            $table->index(['significant']);
            $table->index(['approved_by', 'approved_at']);
            $table->index(['batch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_logs');
    }
};
