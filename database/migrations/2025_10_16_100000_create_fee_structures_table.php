<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_model_id');
            $table->string('name');
            $table->string('academic_year')->index();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->foreign('class_model_id')->references('id')->on('class_models')->onDelete('cascade');
            $table->index(['class_model_id', 'is_active']);
        });

        Schema::create('fee_structure_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fee_structure_id');
            $table->string('item_name');
            $table->decimal('amount', 10, 2);
            $table->enum('frequency', ['monthly', 'annual', 'one_time'])->index();
            $table->unsignedTinyInteger('due_day')->nullable(); // For monthly frequency
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('fee_structure_id')->references('id')->on('fee_structures')->onDelete('cascade');
            $table->index(['fee_structure_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_structure_items');
        Schema::dropIfExists('fee_structures');
    }
};