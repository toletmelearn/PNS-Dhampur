<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('monthly_expenses', function (Blueprint $table) {
            $table->id();
            $table->integer('year')->index();
            $table->unsignedTinyInteger('month')->index();
            $table->string('department')->index();
            $table->string('category')->nullable()->index();
            $table->decimal('amount', 12, 2)->default(0);
            $table->unsignedInteger('transaction_count')->default(0);
            $table->timestamp('snapshot_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['year','month','department','category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_expenses');
    }
};