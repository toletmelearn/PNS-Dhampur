<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fee_id');
            $table->decimal('amount_paid', 12, 2);
            $table->date('payment_date');
            $table->string('payment_mode')->default('cash'); // cash, online, card
            $table->string('receipt_no')->nullable()->unique();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('fee_id')->references('id')->on('fees')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_payments');
    }
};
