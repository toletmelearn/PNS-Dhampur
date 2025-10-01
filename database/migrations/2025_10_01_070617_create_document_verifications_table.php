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
        Schema::create('document_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->string('document_type'); // birth_certificate, transfer_certificate, caste_certificate, etc.
            $table->string('document_name');
            $table->string('file_path');
            $table->string('file_hash'); // For integrity verification
            $table->enum('verification_status', ['pending', 'verified', 'rejected', 'expired'])->default('pending');
            $table->text('verification_notes')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();
            $table->date('expiry_date')->nullable(); // For documents that expire
            $table->json('metadata')->nullable(); // Additional document metadata
            $table->boolean('is_mandatory')->default(false);
            $table->integer('verification_attempts')->default(0);
            $table->timestamp('last_verification_attempt')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('document_verifications');
    }
};
