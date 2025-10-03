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
        Schema::create('bell_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bell_timing_id')->constrained()->onDelete('cascade');
            $table->enum('notification_type', ['visual', 'audio', 'push', 'email', 'sms']);
            $table->string('title');
            $table->text('message');
            $table->string('sound_file')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->time('notification_time')->nullable();
            $table->json('target_audience')->nullable(); // ['teachers', 'students', 'staff']
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->boolean('auto_dismiss')->default(true);
            $table->integer('dismiss_after_seconds')->default(10);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bell_notifications');
    }
};
