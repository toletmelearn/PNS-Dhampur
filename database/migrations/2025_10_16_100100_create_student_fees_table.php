<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_fees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('fee_structure_id')->nullable();
            $table->unsignedInteger('installment_no')->default(1);
            $table->string('item_name');
            $table->decimal('amount', 10, 2);
            $table->date('due_date')->index();
            $table->enum('status', ['pending', 'paid', 'overdue'])->default('pending')->index();
            $table->decimal('late_fee', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->dateTime('paid_date')->nullable();
            $table->unsignedBigInteger('receipt_id')->nullable();
            $table->string('academic_year')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('fee_structure_id')->references('id')->on('fee_structures')->onDelete('set null');
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_fees');
    }
};