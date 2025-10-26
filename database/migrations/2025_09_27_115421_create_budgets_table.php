<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('budgets')) {
            Schema::create('budgets', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('fiscal_year');
                $table->string('category');
                $table->decimal('amount_allocated', 12, 2);
                $table->decimal('amount_spent', 12, 2);
                $table->date('start_date');
                $table->date('end_date');
                $table->enum('status', ['draft', 'approved', 'closed']);
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->text('remarks')->nullable();
                $table->integer('year');
                $table->decimal('total_budget',12,2)->default(0);
                $table->decimal('spent_amount',12,2)->default(0);
                $table->timestamps();

                $table->foreign('approved_by')->references('id')->on('users')
                    ->onDelete('cascade')->onUpdate('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
