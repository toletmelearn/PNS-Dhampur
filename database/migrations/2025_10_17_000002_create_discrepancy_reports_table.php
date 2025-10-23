<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('discrepancy_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('verification_id');
            $table->unsignedBigInteger('student_id')->nullable();
            $table->integer('mismatches_count')->default(0);
            $table->decimal('overall_confidence', 5, 2)->default(0.0);
            $table->string('recommendation')->nullable(); // approve, reject, manual_review
            $table->boolean('auto_resolvable')->default(false);
            $table->string('status')->default('open'); // open, resolved, superseded
            $table->json('analysis')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('verification_id')
                  ->references('id')->on('student_verifications')
                  ->onDelete('cascade');

            $table->foreign('student_id')
                  ->references('id')->on('students')
                  ->onDelete('cascade');

            $table->index(['verification_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discrepancy_reports');
    }
};