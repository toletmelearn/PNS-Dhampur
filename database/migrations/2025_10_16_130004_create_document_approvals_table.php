<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_version_id')->constrained('document_versions')->onDelete('cascade');
            $table->foreignId('approver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('approval_level')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'delegated'])->default('pending');
            $table->text('comments')->nullable();
            $table->json('feedback')->nullable();
            $table->unsignedInteger('priority')->default(1);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('deadline')->nullable();
            $table->boolean('is_required')->default(true);
            $table->foreignId('delegated_to')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('delegated_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['document_version_id', 'status']);
            $table->index(['approver_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_approvals');
    }
};