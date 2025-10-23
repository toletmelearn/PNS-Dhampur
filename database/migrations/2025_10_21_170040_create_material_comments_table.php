<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('material_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->text('comment');
            $table->boolean('is_resolved')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['material_id', 'user_id']);
            $table->foreign('material_id')->references('id')->on('subject_materials')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('material_comments')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_comments');
    }
};
