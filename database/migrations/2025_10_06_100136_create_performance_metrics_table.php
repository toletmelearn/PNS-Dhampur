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
        Schema::create('performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('endpoint')->nullable(); // API endpoint or page URL
            $table->string('method')->default('GET'); // HTTP method
            $table->string('user_agent')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->decimal('response_time', 8, 2); // in milliseconds
            $table->integer('memory_usage')->nullable(); // in bytes
            $table->integer('cpu_usage')->nullable(); // percentage
            $table->integer('database_queries')->default(0);
            $table->decimal('database_time', 8, 2)->default(0); // in milliseconds
            $table->integer('status_code')->nullable();
            $table->json('additional_data')->nullable(); // Custom metrics
            $table->timestamp('recorded_at');
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['endpoint', 'recorded_at']);
            $table->index(['user_id', 'recorded_at']);
            $table->index(['status_code', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('performance_metrics');
    }
};
