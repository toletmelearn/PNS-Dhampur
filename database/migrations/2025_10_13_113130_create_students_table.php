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
        // Make idempotent: if students table already exists from earlier migration,
        // skip creating it again to avoid duplicate table errors.
        if (Schema::hasTable('students')) {
            return;
        }

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_id')->unique();
            $table->string('admission_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            
            // Personal Information
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('blood_group')->nullable();
            $table->string('religion')->nullable();
            $table->string('caste')->nullable();
            $table->string('category')->nullable();
            $table->string('nationality')->default('Indian');
            $table->string('mother_tongue')->nullable();
            
            // Contact Information
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->text('address');
            $table->string('city');
            $table->string('state');
            $table->string('country')->default('India');
            $table->string('pincode');
            
            // Academic Information
            $table->foreignId('class_id')->constrained()->onDelete('cascade');
            $table->foreignId('section_id')->nullable()->constrained()->onDelete('set null');
            $table->string('roll_number')->nullable();
            $table->date('admission_date');
            $table->string('academic_year');
            $table->enum('admission_type', ['new', 'transfer', 'readmission'])->default('new');
            $table->string('previous_school')->nullable();
            $table->text('previous_school_address')->nullable();
            
            // Parent/Guardian Information
            $table->string('father_name');
            $table->string('father_occupation')->nullable();
            $table->string('father_phone')->nullable();
            $table->string('father_email')->nullable();
            $table->decimal('father_income', 10, 2)->nullable();
            
            $table->string('mother_name');
            $table->string('mother_occupation')->nullable();
            $table->string('mother_phone')->nullable();
            $table->string('mother_email')->nullable();
            $table->decimal('mother_income', 10, 2)->nullable();
            
            $table->string('guardian_name')->nullable();
            $table->string('guardian_relation')->nullable();
            $table->string('guardian_phone')->nullable();
            $table->string('guardian_email')->nullable();
            $table->text('guardian_address')->nullable();
            
            // Documents
            $table->string('birth_certificate')->nullable();
            $table->string('transfer_certificate')->nullable();
            $table->string('character_certificate')->nullable();
            $table->string('caste_certificate')->nullable();
            $table->string('income_certificate')->nullable();
            $table->string('photo')->nullable();
            $table->json('other_documents')->nullable();
            
            // Medical Information
            $table->text('medical_conditions')->nullable();
            $table->text('allergies')->nullable();
            $table->text('medications')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relation')->nullable();
            
            // Transport Information
            $table->boolean('transport_required')->default(false);
            $table->foreignId('route_id')->nullable()->constrained('transport_routes')->onDelete('set null');
            $table->string('pickup_point')->nullable();
            $table->string('drop_point')->nullable();
            
            // Fee Information
            $table->decimal('fee_amount', 10, 2)->nullable();
            $table->enum('fee_type', ['monthly', 'quarterly', 'yearly'])->default('monthly');
            $table->boolean('fee_concession')->default(false);
            $table->decimal('concession_amount', 10, 2)->nullable();
            $table->string('concession_reason')->nullable();
            
            // Status and Tracking
            $table->enum('status', ['active', 'inactive', 'transferred', 'graduated', 'dropped'])->default('active');
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['student_id', 'status']);
            $table->index(['class_id', 'section_id', 'status']);
            $table->index(['admission_number', 'academic_year']);
            $table->index(['first_name', 'last_name']);
            $table->index(['father_name', 'mother_name']);
            $table->index('status');
            $table->index('academic_year');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('students');
    }
};
