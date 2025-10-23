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
        Schema::create('class_data', function (Blueprint $table) {
            $table->id();

            // Link to class model
            $table->unsignedBigInteger('class_model_id');

            // Current data payload and baseline/original
            $table->longText('data')->nullable();
            $table->longText('original_data')->nullable();

            // Status and approval tracking
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('approved');
            $table->boolean('approval_required')->default(false);
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();

            // Version linkage
            $table->unsignedBigInteger('last_version_id')->nullable();

            // Change indicators
            $table->boolean('significant_change')->default(false);
            $table->string('change_reason')->nullable();

            // Ownership and audit references
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            // Soft state
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // FKs
            $table->foreign('class_model_id')->references('id')->on('class_models')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('last_version_id')->references('id')->on('class_data_versions')->onDelete('set null');

            // Indexes
            $table->index(['class_model_id', 'is_active']);
            $table->index(['approval_status', 'approved_at']);
            $table->index(['created_by', 'created_at']);
            $table->index(['updated_by', 'updated_at']);
            $table->index('significant_change');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_data');
    }
};
