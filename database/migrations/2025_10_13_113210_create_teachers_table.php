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
        // If the teachers table already exists from earlier migrations,
        // skip creating it again to prevent duplicate table errors.
        if (Schema::hasTable('teachers')) {
            return;
        }

        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->string('teacher_id')->unique();
            $table->string('employee_id')->unique();
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
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->nullable();
            
            // Contact Information
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('mobile');
            $table->text('address');
            $table->string('city');
            $table->string('state');
            $table->string('country')->default('India');
            $table->string('pincode');
            $table->text('permanent_address')->nullable();
            
            // Professional Information
            $table->string('designation');
            $table->string('department')->nullable();
            $table->date('joining_date');
            $table->enum('employment_type', ['permanent', 'temporary', 'contract', 'part_time'])->default('permanent');
            $table->decimal('salary', 10, 2);
            $table->string('salary_grade')->nullable();
            $table->json('subjects')->nullable(); // Array of subject IDs
            $table->json('classes')->nullable(); // Array of class IDs
            $table->boolean('is_class_teacher')->default(false);
            $table->foreignId('class_teacher_of')->nullable()->constrained('classes')->onDelete('set null');
            
            // Educational Qualifications
            $table->json('qualifications')->nullable(); // Array of qualification objects
            $table->json('certifications')->nullable(); // Array of certification objects
            $table->text('specializations')->nullable();
            $table->integer('experience_years')->default(0);
            $table->text('previous_experience')->nullable();
            
            // Documents
            $table->string('resume')->nullable();
            $table->string('photo')->nullable();
            $table->string('id_proof')->nullable();
            $table->string('address_proof')->nullable();
            $table->string('educational_certificates')->nullable();
            $table->string('experience_certificates')->nullable();
            $table->json('other_documents')->nullable();
            
            // Bank Details
            $table->string('bank_name')->nullable();
            $table->string('bank_branch')->nullable();
            $table->string('account_number')->nullable();
            $table->string('ifsc_code')->nullable();
            $table->string('pan_number')->nullable();
            $table->string('aadhar_number')->nullable();
            $table->string('pf_number')->nullable();
            $table->string('esi_number')->nullable();
            
            // Emergency Contact
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relation')->nullable();
            $table->text('emergency_contact_address')->nullable();
            
            // Performance and Attendance
            $table->decimal('performance_rating', 3, 2)->nullable();
            $table->integer('total_leaves')->default(0);
            $table->integer('used_leaves')->default(0);
            $table->json('leave_balance')->nullable(); // Different types of leaves
            $table->text('performance_notes')->nullable();
            
            // Schedule and Availability
            $table->json('weekly_schedule')->nullable(); // Weekly timetable
            $table->json('availability')->nullable(); // Available time slots
            $table->boolean('substitute_available')->default(true);
            $table->json('substitute_subjects')->nullable(); // Subjects can substitute for
            
            // Status and Tracking
            $table->enum('status', ['active', 'inactive', 'on_leave', 'terminated', 'retired'])->default('active');
            $table->date('probation_end_date')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->date('contract_end_date')->nullable();
            $table->text('termination_reason')->nullable();
            $table->date('termination_date')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['teacher_id', 'status']);
            $table->index(['employee_id', 'status']);
            $table->index(['designation', 'department']);
            $table->index(['first_name', 'last_name']);
            $table->index(['joining_date', 'status']);
            $table->index('status');
            $table->index('is_class_teacher');
            $table->index('substitute_available');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('teachers');
    }
};
