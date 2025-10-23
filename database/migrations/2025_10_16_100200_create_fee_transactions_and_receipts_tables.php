<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fee_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_fee_id');
            $table->decimal('amount', 10, 2);
            $table->string('transaction_id')->nullable()->index();
            $table->string('gateway')->nullable();
            $table->enum('status', ['success', 'pending', 'failed'])->default('pending')->index();
            $table->string('payment_method')->nullable();
            $table->dateTime('paid_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('receipt_id')->nullable();
            $table->timestamps();

            $table->foreign('student_fee_id')->references('id')->on('student_fees')->onDelete('cascade');
            $table->index(['student_fee_id', 'status']);
        });

        Schema::create('fee_receipts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_fee_id');
            $table->unsignedBigInteger('fee_transaction_id')->nullable();
            $table->string('receipt_number')->unique();
            $table->string('pdf_path')->nullable();
            $table->dateTime('issued_at')->nullable()->index();
            $table->timestamps();

            $table->foreign('student_fee_id')->references('id')->on('student_fees')->onDelete('cascade');
            $table->foreign('fee_transaction_id')->references('id')->on('fee_transactions')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_receipts');
        Schema::dropIfExists('fee_transactions');
    }
};