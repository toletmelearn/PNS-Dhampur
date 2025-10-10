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
        Schema::create('aadhaar_verifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->string('aadhaar_number', 12);
            $table->enum('verification_status', ['pending', 'verified', 'failed', 'expired'])->default('pending');
            $table->json('demographic_data')->nullable();
            $table->decimal('match_percentage', 5, 2)->nullable();
            $table->json('api_response')->nullable();
            $table->string('transaction_id')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            
            // Indexes
            $table->index(['student_id', 'verification_status']);
            $table->index('aadhaar_number');
            $table->index('verification_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('aadhaar_verifications');
    }
};