<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('paper_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('submission_id');
            $table->unsignedBigInteger('approved_by');
            $table->enum('status', ['approved', 'rejected'])->default('approved');
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('submission_id')->references('id')->on('paper_submissions')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('cascade');
            $table->index(['submission_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paper_approvals');
    }
};