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
        Schema::create('performance_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('alert_type'); // high_response_time, memory_usage, cpu_usage, etc.
            $table->enum('severity', ['warning', 'critical', 'emergency']);
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // alert-specific data
            $table->decimal('threshold_value', 10, 4)->nullable();
            $table->decimal('current_value', 10, 4)->nullable();
            $table->string('unit')->nullable();
            $table->enum('status', ['active', 'acknowledged', 'resolved'])->default('active');
            $table->timestamp('triggered_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('acknowledged_by')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('acknowledged_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('resolved_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes for better query performance
            $table->index(['alert_type', 'severity']);
            $table->index('status');
            $table->index('triggered_at');
            $table->index(['status', 'triggered_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('performance_alerts');
    }
};
