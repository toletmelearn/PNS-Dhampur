<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->tinyInteger('month');
            $table->smallInteger('year');
            $table->decimal('basic',12,2)->default(0);
            $table->json('allowances')->nullable();
            $table->json('deductions')->nullable();
            $table->decimal('net_salary',12,2)->default(0);
            $table->date('paid_date')->nullable();
            $table->timestamps();
            $table->unique(['teacher_id','month','year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salaries');
    }
};
