<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_access_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // student user id
            $table->unsignedBigInteger('material_id');
            $table->timestamp('accessed_at')->useCurrent();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('device_info')->nullable();
            $table->boolean('success')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'material_id', 'accessed_at']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('material_id')->references('id')->on('subject_materials')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_access_logs');
    }
};
