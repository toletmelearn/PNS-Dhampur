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
        Schema::create('biometric_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->onDelete('cascade');
            $table->string('device_id');
            $table->string('biometric_type')->default('fingerprint'); // fingerprint, face, card
            $table->text('biometric_template'); // Encrypted biometric template data
            $table->string('template_format'); // Format of the template (e.g., ISO, ANSI)
            $table->integer('template_quality')->nullable(); // Quality score of the template
            $table->json('device_info')->nullable(); // Device information and settings
            $table->timestamp('enrolled_at');
            $table->foreignId('enrolled_by')->constrained('users')->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->integer('usage_count')->default(0);
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['teacher_id', 'device_id']);
            $table->index(['device_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('biometric_data');
    }
};
