<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('annual_budgets', function (Blueprint $table) {
            $table->id();
            $table->integer('year')->index();
            $table->decimal('total_allocated', 12, 2)->default(0);
            $table->decimal('total_spent', 12, 2)->default(0);
            $table->enum('status', ['planned', 'approved', 'locked'])->default('planned');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annual_budgets');
    }
};