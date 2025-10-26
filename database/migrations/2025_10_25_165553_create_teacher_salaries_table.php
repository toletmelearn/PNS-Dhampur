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
        if (!Schema::hasTable('teacher_salaries')) {
            Schema::create('teacher_salaries', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('teacher_id');
                $table->string('month');
                $table->year('year');
                $table->decimal('basic_salary', 10, 2);
                $table->decimal('allowances', 10, 2);
                $table->decimal('deductions', 10, 2);
                $table->decimal('net_salary', 10, 2);
                $table->date('payment_date')->nullable();
                $table->string('payment_method')->nullable();
                $table->string('transaction_id')->nullable();
                $table->enum('status', ['pending', 'paid', 'cancelled']);
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->foreign('teacher_id')->references('id')->on('teachers')
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
        Schema::dropIfExists('teacher_salaries');
    }
};
