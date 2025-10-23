<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('alumni_batches', function (Blueprint $table) {
            $table->id();
            $table->string('label')->index();
            $table->unsignedInteger('year_start')->nullable()->index();
            $table->unsignedInteger('year_end')->nullable()->index();
            $table->text('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni_batches');
    }
};
