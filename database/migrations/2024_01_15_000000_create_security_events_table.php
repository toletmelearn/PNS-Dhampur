<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 100)->index();
            $table->tinyInteger('severity')->default(2)->index();
            $table->json('context')->nullable();
            $table->ipAddress('ip_address')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->string('route', 255)->nullable()->index();
            $table->string('method', 10)->nullable();
            $table->timestamps();
            
            // Add foreign key constraint for user_id
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            
            // Add composite indexes for common queries
            $table->index(['event_type', 'created_at']);
            $table->index(['severity', 'created_at']);
            $table->index(['ip_address', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_events');
    }
};