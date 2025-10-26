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
        Schema::create('exam_paper_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exam_paper_id');
            $table->unsignedBigInteger('version_id');
            $table->unsignedBigInteger('reviewed_by');
            $table->enum('approval_level', ['department_head', 'principal', 'academic_coordinator', 'external_reviewer']);
            $table->enum('status', ['pending', 'approved', 'rejected', 'delegated'])->default('pending');
            $table->text('comments')->nullable();
            $table->json('feedback')->nullable(); // Structured feedback as JSON
            $table->integer('priority')->default(1); // Approval priority/order
            $table->timestamp('submitted_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('deadline')->nullable();
            $table->boolean('is_required')->default(true);
            $table->boolean('can_delegate')->default(false);
            $table->foreignId('delegated_to')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('delegated_at')->nullable();
            $table->json('approval_criteria')->nullable(); // Criteria for approval
            $table->decimal('score', 5, 2)->nullable(); // Approval score if applicable
            $table->string('digital_signature', 500)->nullable(); // Digital signature hash
            $table->json('metadata')->nullable(); // Additional approval metadata
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['exam_paper_version_id', 'approval_level']);
            $table->index(['approver_id', 'status']);
            $table->index(['status', 'submitted_at']);
            $table->index('deadline');
            $table->index('priority');
            
            // Unique constraint to prevent duplicate approvals
            $table->unique(['exam_paper_version_id', 'approver_id', 'approval_level'], 'unique_approval');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('exam_paper_approvals');
    }
};
