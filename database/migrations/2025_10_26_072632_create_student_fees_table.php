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
        if (!Schema::hasTable('student_fees')) {
            Schema::create('student_fees', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('fee_structure_id');
                $table->decimal('amount_paid', 10, 2);
                $table->decimal('amount_due', 10, 2);
                $table->date('payment_date');
                $table->string('payment_method')->nullable();
                $table->string('transaction_id')->nullable();
                $table->string('receipt_number')->nullable();
                $table->enum('status', ['paid', 'partial', 'due', 'waived']);
                $table->text('remarks')->nullable();
                $table->timestamps();
                
                $table->foreign('student_id')->references('id')->on('students')
                    ->onDelete('cascade')->onUpdate('cascade');
                $table->foreign('fee_structure_id')->references('id')->on('fee_structures')
                    ->onDelete('cascade')->onUpdate('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('student_fees');
    }
};
