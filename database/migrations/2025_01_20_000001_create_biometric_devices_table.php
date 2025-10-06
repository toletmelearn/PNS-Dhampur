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
        Schema::create('biometric_devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_id')->unique(); // Unique device identifier
            $table->string('device_name'); // Human-readable device name
            $table->enum('device_type', ['fingerprint', 'face', 'iris', 'rfid', 'palm', 'hybrid'])->default('fingerprint');
            $table->string('ip_address')->nullable(); // Device IP address
            $table->integer('port')->nullable(); // Device port
            $table->string('location')->nullable(); // Physical location
            $table->string('manufacturer')->nullable(); // Device manufacturer
            $table->string('model')->nullable(); // Device model
            $table->string('firmware_version')->nullable(); // Firmware version
            $table->string('api_endpoint')->nullable(); // API endpoint for communication
            $table->string('api_key')->nullable(); // API key for authentication
            $table->enum('connection_type', ['tcp', 'http', 'websocket', 'serial', 'usb'])->default('tcp');
            $table->enum('status', ['online', 'offline', 'maintenance', 'error'])->default('offline');
            $table->timestamp('last_sync_at')->nullable(); // Last successful sync
            $table->timestamp('last_heartbeat_at')->nullable(); // Last heartbeat received
            $table->json('configuration')->nullable(); // Device configuration settings
            $table->boolean('is_active')->default(true); // Device is active
            $table->foreignId('registered_by')->constrained('users')->onDelete('cascade'); // Who registered the device
            $table->text('notes')->nullable(); // Additional notes
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['device_id', 'is_active']);
            $table->index(['status', 'is_active']);
            $table->index(['device_type', 'is_active']);
            $table->index('last_heartbeat_at');
            $table->index('last_sync_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('biometric_devices');
    }
};