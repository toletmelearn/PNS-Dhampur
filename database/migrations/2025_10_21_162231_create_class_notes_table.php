<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_notes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('class_model_id');
            $table->unsignedBigInteger('user_id');

            $table->text('note');
            $table->enum('visibility', ['private', 'teachers', 'admin', 'public'])->default('teachers');
            $table->json('tags')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('class_model_id')->references('id')->on('class_models')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->index(['class_model_id', 'visibility']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_notes');
    }
};
