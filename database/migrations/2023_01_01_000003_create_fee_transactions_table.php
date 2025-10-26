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
        Schema::create('fee_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('student_fee_id')->nullable();
            $table->string('transaction_id')->unique();
            $table->decimal('amount', 10, 2);
            $table->enum('payment_method', ['cash', 'cheque', 'online', 'bank_transfer', 'upi']);
            $table->string('reference_number')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('completed');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('collected_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_transactions');
    }
};