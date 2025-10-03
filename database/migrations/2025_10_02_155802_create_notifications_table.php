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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // assignment_deadline, assignment_created, assignment_graded, syllabus_uploaded, etc.
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // Additional data like assignment_id, syllabus_id, etc.
            $table->unsignedBigInteger('user_id'); // Recipient
            $table->string('user_type')->default('student'); // student, teacher, admin
            $table->unsignedBigInteger('sender_id')->nullable(); // Who sent the notification
            $table->string('sender_type')->nullable(); // teacher, admin, system
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->string('channel')->default('web'); // web, email, sms
            $table->timestamp('scheduled_at')->nullable(); // For scheduled notifications
            $table->timestamp('expires_at')->nullable(); // When notification expires
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'is_read']);
            $table->index(['type', 'scheduled_at']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notifications');
    }
};
