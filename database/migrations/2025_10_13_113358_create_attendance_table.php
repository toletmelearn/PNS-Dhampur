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
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('class_id')->constrained()->onDelete('cascade');
            $table->foreignId('section_id')->nullable()->constrained()->onDelete('set null');
            $table->date('attendance_date');
            $table->string('academic_year');
            
            // Attendance Status
            $table->enum('status', ['present', 'absent', 'late', 'half_day', 'excused', 'medical_leave'])->default('present');
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->integer('total_periods')->default(0);
            $table->integer('present_periods')->default(0);
            $table->integer('absent_periods')->default(0);
            
            // Period-wise attendance
            $table->json('period_attendance')->nullable(); // Array of period-wise status
            $table->json('subject_attendance')->nullable(); // Subject-wise attendance
            
            // Marking Information
            $table->foreignId('marked_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('marked_at')->nullable();
            $table->enum('marking_method', ['manual', 'biometric', 'rfid', 'mobile_app', 'bulk_import'])->default('manual');
            $table->string('device_id')->nullable(); // For biometric/RFID devices
            
            // Leave Information
            $table->boolean('is_leave')->default(false);
            $table->enum('leave_type', ['sick', 'casual', 'emergency', 'festival', 'family', 'medical', 'other'])->nullable();
            $table->text('leave_reason')->nullable();
            $table->string('leave_application_id')->nullable();
            $table->boolean('leave_approved')->nullable();
            $table->foreignId('leave_approved_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Late/Early Information
            $table->boolean('is_late')->default(false);
            $table->integer('late_minutes')->default(0);
            $table->text('late_reason')->nullable();
            $table->boolean('early_departure')->default(false);
            $table->time('departure_time')->nullable();
            $table->text('departure_reason')->nullable();
            
            // Parent Notification
            $table->boolean('parent_notified')->default(false);
            $table->timestamp('parent_notified_at')->nullable();
            $table->enum('notification_method', ['sms', 'email', 'app', 'call'])->nullable();
            $table->text('notification_response')->nullable();
            
            // Additional Information
            $table->text('remarks')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['student_id', 'attendance_date']);
            $table->index(['class_id', 'section_id', 'attendance_date']);
            $table->index(['attendance_date', 'status']);
            $table->index(['academic_year', 'status']);
            $table->index(['marked_by', 'marked_at']);
            $table->index('is_leave');
            $table->index('is_late');
            $table->index('parent_notified');
            
            // Unique constraint to prevent duplicate entries
            $table->unique(['student_id', 'attendance_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendance');
    }
};
