<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->decimal('amount', 12, 2);
            $table->date('due_date')->nullable();
            $table->decimal('paid_amount', 12, 2)->default(0.00);
            $table->date('paid_date')->nullable();
            $table->enum('status',['paid','unpaid','partial'])->default('unpaid');
            $table->string('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fees');
    }
};
