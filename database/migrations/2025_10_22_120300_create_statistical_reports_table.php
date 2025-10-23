<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('statistical_reports', function (Blueprint $table) {
            $table->id();
            $table->string('context')->index();
            $table->json('parameters')->nullable();
            $table->json('metrics')->nullable();
            $table->dateTime('generated_at')->index();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cache_key')->nullable()->index();
            $table->dateTime('expires_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statistical_reports');
    }
};
