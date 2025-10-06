<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('activity_type'); // login, logout, create, update, delete, view, etc.
            $table->string('description');
            $table->string('subject_type')->nullable(); // Model class name
            $table->unsignedBigInteger('subject_id')->nullable(); // Model ID
            $table->json('properties')->nullable(); // Changed attributes, old/new values
            $table->string('url')->nullable();
            $table->string('method')->nullable(); // HTTP method
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('session_id')->nullable();
            $table->json('request_data')->nullable(); // Request parameters
            $table->decimal('response_time', 8, 2)->nullable(); // in milliseconds
            $table->integer('status_code')->nullable();
            $table->timestamp('performed_at');
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'performed_at']);
            $table->index(['activity_type', 'performed_at']);
            $table->index(['subject_type', 'subject_id']);
            $table->index(['ip_address', 'performed_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_activities');
    }
};
