<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SystemSetting;
use App\Models\NotificationTemplate;
use App\Models\AcademicYear;
use Carbon\Carbon;

class ConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedSystemSettings();
        $this->seedNotificationTemplates();
        $this->seedAcademicYear();
    }

    /**
     * Seed default system settings
     */
    private function seedSystemSettings(): void
    {
        $settings = [
             // School Information
             [
                 'key' => 'school_name',
                 'label' => 'School Name',
                 'value' => 'PNS Dhampur',
                 'type' => 'string',
                 'category' => 'school',
                 'description' => 'Official name of the school',
                 'is_public' => true,
                 'is_editable' => true,
                 'sort_order' => 1
             ],
             [
                 'key' => 'school_address',
                 'label' => 'School Address',
                 'value' => 'Dhampur, Uttar Pradesh, India',
                 'type' => 'text',
                 'category' => 'school',
                 'description' => 'Complete address of the school',
                 'is_public' => true,
                 'is_editable' => true,
                 'sort_order' => 2
             ],
             [
                 'key' => 'school_phone',
                 'label' => 'School Phone',
                 'value' => '+91-1234567890',
                 'type' => 'string',
                 'category' => 'school',
                 'description' => 'Primary contact number',
                 'is_public' => true,
                 'is_editable' => true,
                 'sort_order' => 3
             ],
             [
                 'key' => 'school_email',
                 'label' => 'School Email',
                 'value' => 'info@pnsdhampur.edu.in',
                 'type' => 'string',
                 'category' => 'school',
                 'description' => 'Official email address',
                 'is_public' => true,
                 'is_editable' => true,
                 'sort_order' => 4
             ],
             [
                 'key' => 'school_website',
                 'label' => 'School Website',
                 'value' => 'https://pnsdhampur.edu.in',
                 'type' => 'string',
                 'category' => 'school',
                 'description' => 'Official website URL',
                 'is_public' => true,
                 'is_editable' => true,
                 'sort_order' => 5
             ],

             // Academic Settings
             [
                 'key' => 'academic_year_start_month',
                 'label' => 'Academic Year Start Month',
                 'value' => '4',
                 'type' => 'number',
                 'category' => 'academic',
                 'description' => 'Month when academic year starts (1-12)',
                 'is_public' => false,
                 'is_editable' => true,
                 'sort_order' => 10
             ],
             [
                 'key' => 'minimum_attendance_percentage',
                 'label' => 'Minimum Attendance Percentage',
                 'value' => '75',
                 'type' => 'number',
                 'category' => 'academic',
                 'description' => 'Minimum required attendance percentage',
                 'is_public' => true,
                 'is_editable' => true,
                 'sort_order' => 11
             ],
             [
                 'key' => 'working_days_per_week',
                 'label' => 'Working Days Per Week',
                 'value' => '6',
                 'type' => 'number',
                 'category' => 'academic',
                 'description' => 'Number of working days in a week',
                 'is_public' => true,
                 'is_editable' => true,
                 'sort_order' => 12
             ],
             [
                 'key' => 'class_duration_minutes',
                 'label' => 'Class Duration (Minutes)',
                 'value' => '45',
                 'type' => 'number',
                 'category' => 'academic',
                 'description' => 'Duration of each class period in minutes',
                 'is_public' => true,
                 'is_editable' => true,
                 'sort_order' => 13
             ],

             // System Settings
             [
                 'key' => 'system_timezone',
                 'label' => 'System Timezone',
                 'value' => 'Asia/Kolkata',
                 'type' => 'text',
                 'category' => 'system',
                 'description' => 'Default timezone for the system',
                 'is_public' => false,
                 'is_editable' => true,
                 'sort_order' => 20
             ],
             [
                 'key' => 'date_format',
                 'label' => 'Date Format',
                 'value' => 'd/m/Y',
                 'type' => 'text',
                 'category' => 'system',
                 'description' => 'Default date format for display',
                 'is_public' => true,
                 'is_editable' => true,
                 'sort_order' => 21
             ],
             [
                 'key' => 'currency_symbol',
                 'label' => 'Currency Symbol',
                 'value' => '₹',
                 'type' => 'text',
                 'category' => 'system',
                 'description' => 'Currency symbol for financial transactions',
                 'is_public' => true,
                 'is_editable' => true,
                 'sort_order' => 22
             ],
             [
                 'key' => 'enable_sms_notifications',
                 'label' => 'Enable SMS Notifications',
                 'value' => 'true',
                 'type' => 'boolean',
                 'category' => 'notifications',
                 'description' => 'Enable or disable SMS notifications',
                 'is_public' => false,
                 'is_editable' => true,
                 'sort_order' => 30
             ],
             [
                 'key' => 'enable_email_notifications',
                 'label' => 'Enable Email Notifications',
                 'value' => 'true',
                 'type' => 'boolean',
                 'category' => 'notifications',
                 'description' => 'Enable or disable email notifications',
                 'is_public' => false,
                 'is_editable' => true,
                 'sort_order' => 31
             ],

             // Fee Settings
             [
                 'key' => 'late_fee_percentage',
                 'label' => 'Late Fee Percentage',
                 'value' => '5',
                 'type' => 'number',
                 'category' => 'finance',
                 'description' => 'Percentage of late fee on overdue payments',
                 'is_public' => true,
                 'is_editable' => true,
                 'sort_order' => 40
             ],
             [
                 'key' => 'fee_due_days',
                 'label' => 'Fee Due Days',
                 'value' => '10',
                 'type' => 'number',
                 'category' => 'finance',
                 'description' => 'Number of days after which fee becomes overdue',
                 'is_public' => true,
                 'is_editable' => true,
                 'sort_order' => 41
             ],

             // Admission Settings
             [
                 'key' => 'admission_start_date',
                 'label' => 'Admission Start Date',
                 'value' => '2025-03-01',
                 'type' => 'text',
                 'category' => 'admission',
                 'description' => 'Date when admissions open',
                 'is_public' => true,
                 'is_editable' => true,
                 'sort_order' => 50
             ],
             [
                 'key' => 'admission_end_date',
                 'label' => 'Admission End Date',
                 'value' => '2025-03-31',
                 'type' => 'text',
                 'category' => 'admission',
                 'description' => 'Date when admissions close',
                 'is_public' => true,
                 'is_editable' => true,
                 'sort_order' => 51
             ],
             [
                 'key' => 'enable_online_admission',
                 'label' => 'Enable Online Admission',
                 'value' => 'true',
                 'type' => 'boolean',
                 'category' => 'admission',
                 'description' => 'Allow online admission applications',
                 'is_public' => true,
                 'is_editable' => true,
                 'sort_order' => 52
             ]
         ];

        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }

    /**
     * Seed default notification templates
     */
    private function seedNotificationTemplates(): void
    {
        $templates = [
            // Admission Templates
            [
                'name' => 'Student Admission Confirmation',
                'slug' => 'student-admission-confirmation',
                'type' => 'email',
                'category' => 'admission',
                'subject' => 'Welcome to {school_name} - Admission Confirmed',
                'body' => "Dear {parent_name},\n\nWe are pleased to inform you that your child {student_name} has been successfully admitted to {school_name}.\n\nAdmission Details:\n- Student ID: {student_id}\n- Class: {student_class}\n- Section: {student_section}\n- Academic Year: {academic_year}\n\nPlease visit our office to complete the admission formalities and fee payment.\n\nSchool Address: {school_address}\nContact: {school_phone}\n\nWelcome to the {school_name} family!\n\nBest regards,\nAdmission Office\n{school_name}",
                'variables' => ['student_name', 'parent_name', 'student_id', 'student_class', 'student_section', 'academic_year', 'school_name', 'school_address', 'school_phone'],
                'is_active' => true,
                'is_system' => true,
                'description' => 'Sent when a student admission is confirmed'
            ],
            [
                'name' => 'Admission Application Received',
                'slug' => 'admission-application-received',
                'type' => 'sms',
                'category' => 'admission',
                'body' => "Dear {parent_name}, your admission application for {student_name} has been received. Application ID: {application_id}. We will contact you soon. - {school_name}",
                'variables' => ['parent_name', 'student_name', 'application_id', 'school_name'],
                'is_active' => true,
                'is_system' => true,
                'description' => 'Sent when admission application is received'
            ],

            // Fee Templates
            [
                'name' => 'Fee Payment Reminder',
                'slug' => 'fee-payment-reminder',
                'type' => 'sms',
                'category' => 'finance',
                'body' => "Dear {parent_name}, this is a reminder that the school fee for {student_name} (Class {student_class}) is due. Amount: {currency_symbol}{fee_amount}. Due Date: {due_date}. Please make the payment at your earliest convenience. - {school_name}",
                'variables' => ['parent_name', 'student_name', 'student_class', 'currency_symbol', 'fee_amount', 'due_date', 'school_name'],
                'is_active' => true,
                'is_system' => true,
                'description' => 'Sent as fee payment reminder'
            ],
            [
                'name' => 'Fee Payment Confirmation',
                'slug' => 'fee-payment-confirmation',
                'type' => 'email',
                'category' => 'finance',
                'subject' => 'Fee Payment Confirmation - {student_name}',
                'body' => "Dear {parent_name},\n\nThis is to confirm that we have received the fee payment for {student_name}.\n\nPayment Details:\n- Receipt No: {receipt_number}\n- Amount Paid: {currency_symbol}{amount_paid}\n- Payment Date: {payment_date}\n- Payment Mode: {payment_mode}\n\nThank you for your prompt payment.\n\nBest regards,\nAccounts Department\n{school_name}",
                'variables' => ['parent_name', 'student_name', 'receipt_number', 'currency_symbol', 'amount_paid', 'payment_date', 'payment_mode', 'school_name'],
                'is_active' => true,
                'is_system' => true,
                'description' => 'Sent when fee payment is confirmed'
            ],

            // Attendance Templates
            [
                'name' => 'Low Attendance Alert',
                'slug' => 'low-attendance-alert',
                'type' => 'email',
                'category' => 'attendance',
                'subject' => 'Attendance Alert for {student_name}',
                'body' => "Dear {parent_name},\n\nThis is to inform you that your child {student_name} (Class {student_class}, Roll No: {student_roll}) has low attendance.\n\nCurrent Attendance: {attendance_percentage}%\nRequired Minimum: {minimum_attendance}%\n\nPlease ensure regular attendance to avoid any academic issues.\n\nFor any queries, please contact us at {school_phone}.\n\nBest regards,\n{school_name}",
                'variables' => ['parent_name', 'student_name', 'student_class', 'student_roll', 'attendance_percentage', 'minimum_attendance', 'school_phone', 'school_name'],
                'is_active' => true,
                'is_system' => true,
                'description' => 'Sent when student attendance falls below minimum'
            ],
            [
                'name' => 'Daily Attendance SMS',
                'slug' => 'daily-attendance-sms',
                'type' => 'sms',
                'category' => 'attendance',
                'body' => "Dear {parent_name}, {student_name} was {attendance_status} today ({date}). - {school_name}",
                'variables' => ['parent_name', 'student_name', 'attendance_status', 'date', 'school_name'],
                'is_active' => true,
                'is_system' => true,
                'description' => 'Daily attendance notification to parents'
            ],

            // Exam Templates
            [
                'name' => 'Exam Schedule Notification',
                'slug' => 'exam-schedule-notification',
                'type' => 'email',
                'category' => 'academic',
                'subject' => 'Exam Schedule - {exam_name}',
                'body' => "Dear {parent_name},\n\nThis is to inform you about the upcoming {exam_name} for {student_name} (Class {student_class}).\n\nExam Details:\n- Exam Start Date: {exam_start_date}\n- Exam End Date: {exam_end_date}\n- Reporting Time: {reporting_time}\n\nPlease ensure your child is well prepared and reaches school on time.\n\nBest regards,\nExamination Department\n{school_name}",
                'variables' => ['parent_name', 'exam_name', 'student_name', 'student_class', 'exam_start_date', 'exam_end_date', 'reporting_time', 'school_name'],
                'is_active' => true,
                'is_system' => true,
                'description' => 'Sent when exam schedule is announced'
            ],

            // General Templates
            [
                'name' => 'Holiday Announcement',
                'slug' => 'holiday-announcement',
                'type' => 'sms',
                'category' => 'general',
                'body' => "Dear Parents, {school_name} will remain closed on {holiday_date} due to {holiday_name}. Classes will resume on {resume_date}. - {school_name}",
                'variables' => ['holiday_date', 'holiday_name', 'resume_date', 'school_name'],
                'is_active' => true,
                'is_system' => true,
                'description' => 'Sent for holiday announcements'
            ],
            [
                'name' => 'Event Invitation',
                'slug' => 'event-invitation',
                'type' => 'email',
                'category' => 'events',
                'subject' => 'Invitation: {event_name}',
                'body' => "Dear {parent_name},\n\nYou are cordially invited to attend {event_name} at {school_name}.\n\nEvent Details:\n- Date: {event_date}\n- Time: {event_time}\n- Venue: {event_venue}\n\n{event_description}\n\nYour presence would be highly appreciated.\n\nBest regards,\nEvent Committee\n{school_name}",
                'variables' => ['parent_name', 'event_name', 'school_name', 'event_date', 'event_time', 'event_venue', 'event_description'],
                'is_active' => true,
                'is_system' => true,
                'description' => 'Sent for school event invitations'
            ]
        ];

        foreach ($templates as $template) {
            NotificationTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                $template
            );
        }
    }

    /**
     * Seed current academic year
     */
    private function seedAcademicYear(): void
    {
        $currentYear = Carbon::now()->year;
        $startYear = Carbon::now()->month >= 4 ? $currentYear : $currentYear - 1;
        $endYear = $startYear + 1;

        AcademicYear::updateOrCreate(
            ['name' => "{$startYear}-{$endYear}"],
            [
                'name' => "{$startYear}-{$endYear}",
                'description' => "Academic Year {$startYear}-{$endYear}",
                'start_date' => Carbon::create($startYear, 4, 1),
                'end_date' => Carbon::create($endYear, 3, 31),
                'is_active' => true,
                'is_current' => true,
                'settings' => json_encode([
                    'total_working_days' => 220,
                    'minimum_attendance' => 75,
                    'grace_period_days' => 15,
                    'allow_late_admissions' => true,
                    'auto_promote_students' => false,
                ])
            ]
        );
    }
}
