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
        Schema::create('verification_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('verification_id')->nullable(); // Nullable for bulk operations
            $table->unsignedBigInteger('student_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable(); // Nullable for system actions
            $table->string('action'); // e.g., 'verification_created', 'status_changed', 'ocr_processed'
            $table->json('details')->nullable(); // Additional details about the action
            $table->json('old_data')->nullable(); // Previous state data
            $table->json('new_data')->nullable(); // New state data
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['verification_id', 'created_at']);
            $table->index(['student_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index('created_at');
            
            // Foreign key constraints
            $table->foreign('verification_id')->references('id')->on('student_verifications')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('verification_audit_logs');
    }
};
