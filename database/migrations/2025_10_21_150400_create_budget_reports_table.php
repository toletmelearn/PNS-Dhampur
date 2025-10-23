<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('budget_reports', function (Blueprint $table) {
            $table->id();
            $table->integer('year')->index();
            $table->string('department')->nullable()->index();
            $table->enum('type', ['utilization','variance','forecast']);
            $table->json('data');
            $table->timestamp('generated_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_reports');
    }
};