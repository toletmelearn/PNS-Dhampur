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
        Schema::create('transport_routes', function (Blueprint $table) {
            $table->id();
            $table->string('route_name');
            $table->string('route_code')->unique();
            $table->text('description')->nullable();
            
            // Route Information
            $table->string('start_location');
            $table->string('end_location');
            $table->decimal('total_distance', 8, 2)->nullable(); // in kilometers
            $table->integer('estimated_duration')->nullable(); // in minutes
            $table->json('stops')->nullable(); // Array of stop locations with details
            $table->json('coordinates')->nullable(); // GPS coordinates for route mapping
            
            // Vehicle Assignment
            $table->string('vehicle_number')->nullable();
            $table->string('vehicle_type')->nullable(); // Bus, Van, Car
            $table->integer('vehicle_capacity')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('driver_phone')->nullable();
            $table->string('driver_license')->nullable();
            $table->string('conductor_name')->nullable();
            $table->string('conductor_phone')->nullable();
            
            // Schedule Information
            $table->time('morning_start_time')->nullable();
            $table->time('morning_end_time')->nullable();
            $table->time('evening_start_time')->nullable();
            $table->time('evening_end_time')->nullable();
            $table->json('schedule_details')->nullable(); // Detailed stop-wise timings
            
            // Fee and Pricing
            $table->decimal('monthly_fee', 8, 2)->default(0);
            $table->decimal('quarterly_fee', 8, 2)->default(0);
            $table->decimal('yearly_fee', 8, 2)->default(0);
            $table->decimal('per_km_rate', 5, 2)->nullable();
            $table->json('distance_based_pricing')->nullable();
            
            // Operational Information
            $table->boolean('is_active')->default(true);
            $table->date('operational_from')->nullable();
            $table->date('operational_to')->nullable();
            $table->json('operational_days')->nullable(); // Array of weekdays
            $table->boolean('holiday_service')->default(false);
            $table->text('special_instructions')->nullable();
            
            // Safety and Compliance
            $table->boolean('gps_enabled')->default(false);
            $table->string('gps_device_id')->nullable();
            $table->boolean('cctv_enabled')->default(false);
            $table->boolean('first_aid_kit')->default(false);
            $table->boolean('fire_extinguisher')->default(false);
            $table->date('last_safety_check')->nullable();
            $table->date('next_safety_check')->nullable();
            
            // Student Information
            $table->integer('current_students')->default(0);
            $table->integer('max_students')->nullable();
            $table->json('pickup_points')->nullable(); // Student pickup/drop points
            $table->boolean('attendance_tracking')->default(false);
            
            // Emergency Information
            $table->string('emergency_contact')->nullable();
            $table->string('emergency_phone')->nullable();
            $table->text('emergency_procedures')->nullable();
            $table->json('hospital_contacts')->nullable();
            
            // Maintenance and Records
            $table->date('last_maintenance')->nullable();
            $table->date('next_maintenance')->nullable();
            $table->decimal('maintenance_cost', 10, 2)->default(0);
            $table->text('maintenance_notes')->nullable();
            $table->json('fuel_records')->nullable();
            
            // Additional Information
            $table->text('remarks')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['route_code', 'is_active']);
            $table->index(['vehicle_number', 'driver_name']);
            $table->index(['is_active', 'operational_from', 'operational_to']);
            $table->index(['current_students', 'max_students']);
            $table->index(['gps_enabled', 'gps_device_id']);
            $table->index(['last_maintenance', 'next_maintenance']);
            $table->index(['created_by', 'updated_by']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transport_routes');
    }
};
