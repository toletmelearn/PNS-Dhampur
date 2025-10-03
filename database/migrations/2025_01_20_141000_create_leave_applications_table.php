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
        Schema::create('leave_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_number')->unique(); // Auto-generated unique number
            
            // Applicant information
            $table->unsignedBigInteger('employee_id'); // References users table (teacher/staff)
            $table->string('employee_type'); // teacher, admin_staff, support_staff
            $table->string('employee_name'); // Cached for performance
            $table->string('employee_designation')->nullable();
            $table->string('department')->nullable();
            
            // Leave details
            $table->enum('leave_type', [
                'casual_leave',
                'sick_leave', 
                'earned_leave',
                'maternity_leave',
                'paternity_leave',
                'emergency_leave',
                'compensatory_leave',
                'study_leave',
                'sabbatical_leave',
                'unpaid_leave'
            ]);
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('total_days'); // Calculated field
            $table->boolean('is_half_day')->default(false);
            $table->enum('half_day_period', ['first_half', 'second_half'])->nullable();
            
            // Application details
            $table->text('reason'); // Reason for leave
            $table->text('address_during_leave')->nullable(); // Contact address during leave
            $table->string('contact_number')->nullable(); // Contact number during leave
            $table->string('emergency_contact')->nullable(); // Emergency contact
            
            // Supporting documents
            $table->json('attachments')->nullable(); // File paths for supporting documents
            $table->boolean('medical_certificate_required')->default(false);
            $table->boolean('medical_certificate_attached')->default(false);
            
            // Leave balance information
            $table->integer('available_balance')->nullable(); // Available leave balance
            $table->integer('balance_after_leave')->nullable(); // Balance after this leave
            
            // Approval workflow
            $table->enum('status', [
                'draft',
                'submitted',
                'pending_hod_approval',
                'pending_principal_approval',
                'approved',
                'rejected',
                'cancelled',
                'withdrawn'
            ])->default('draft');
            
            // HOD/Supervisor approval
            $table->unsignedBigInteger('hod_id')->nullable(); // Head of Department
            $table->enum('hod_status', ['pending', 'approved', 'rejected'])->nullable();
            $table->text('hod_comments')->nullable();
            $table->timestamp('hod_reviewed_at')->nullable();
            
            // Principal approval
            $table->unsignedBigInteger('principal_id')->nullable();
            $table->enum('principal_status', ['pending', 'approved', 'rejected'])->nullable();
            $table->text('principal_comments')->nullable();
            $table->timestamp('principal_reviewed_at')->nullable();
            
            // Final approval details
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Substitute arrangement
            $table->unsignedBigInteger('substitute_teacher_id')->nullable();
            $table->text('substitute_arrangement')->nullable();
            $table->boolean('substitute_confirmed')->default(false);
            
            // Leave execution
            $table->date('actual_start_date')->nullable(); // Actual leave start (may differ from planned)
            $table->date('actual_end_date')->nullable(); // Actual leave end
            $table->integer('actual_days_taken')->nullable();
            $table->boolean('rejoined')->default(false);
            $table->date('rejoining_date')->nullable();
            $table->text('rejoining_remarks')->nullable();
            
            // Cancellation/Withdrawal
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            
            // Notifications and reminders
            $table->json('notification_log')->nullable(); // Track notifications sent
            $table->boolean('reminder_sent')->default(false);
            $table->timestamp('reminder_sent_at')->nullable();
            
            // Priority and urgency
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->boolean('is_emergency')->default(false);
            
            // Audit fields
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['employee_id', 'status']);
            $table->index(['start_date', 'end_date']);
            $table->index(['leave_type', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('application_number');
            
            // Foreign key constraints
            $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('hod_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('principal_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('rejected_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('substitute_teacher_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('cancelled_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('leave_applications');
    }
};