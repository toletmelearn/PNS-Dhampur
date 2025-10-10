<?php

return [
    /*
    |--------------------------------------------------------------------------
    | System Constants
    |--------------------------------------------------------------------------
    |
    | These constants define system-wide values used throughout the application.
    | Centralizing these values improves maintainability and reduces magic numbers.
    |
    */

    // Time and Date Constants
    'time' => [
        'seconds_per_minute' => 60,
        'minutes_per_hour' => 60,
        'hours_per_day' => 24,
        'days_per_week' => 7,
        'days_per_month' => 30,
        'months_per_year' => 12,
        'days_per_year' => 365,
        'sunday_day_of_week' => 0,
    ],

    // Cache TTL (Time To Live) Constants
    'cache' => [
        'short_ttl' => 300,      // 5 minutes
        'medium_ttl' => 1800,    // 30 minutes
        'long_ttl' => 3600,      // 1 hour
        'daily_ttl' => 86400,    // 24 hours
        'weekly_ttl' => 604800,  // 7 days
    ],

    // Performance Thresholds
    'performance' => [
        'response_time_threshold' => 5000,    // milliseconds
        'memory_usage_threshold' => 85,       // percentage
        'cpu_usage_threshold' => 80,          // percentage
        'critical_memory_threshold' => 95,    // percentage
        'critical_cpu_threshold' => 95,       // percentage
        'system_load_threshold' => 20,        // load average
        'slow_query_threshold' => 400,        // milliseconds
    ],

    // File Upload Constants
    'file_upload' => [
        'max_file_size_mb' => 5,              // 5MB default
        'max_profile_photo_size_mb' => 2,     // 2MB for profile photos
        'max_document_size_mb' => 10,         // 10MB for documents
        'max_image_width' => 4000,            // pixels
        'max_image_height' => 4000,           // pixels
        'min_image_width' => 100,             // pixels
        'min_image_height' => 100,            // pixels
        'aspect_ratio_tolerance' => 0.1,      // 10% tolerance
        'max_filename_length' => 255,         // characters
        'virus_scan_timeout' => 30,           // seconds
    ],

    // Attendance and Working Hours
    'attendance' => [
        'overtime_threshold_hours' => 8.5,    // hours
        'lunch_break_minutes' => 60,          // minutes
        'tea_break_minutes' => 30,            // minutes
        'minimum_working_minutes' => 240,     // 4 hours
        'full_day_minutes' => 360,            // 6 hours
        'late_threshold_minutes' => 30,       // minutes
        'session_duration_minutes' => 45,     // minutes
        'minimum_session_gap_minutes' => 30,  // minutes
    ],

    // Academic and Grading
    'academic' => [
        'passing_marks' => 40,                // percentage
        'excellent_threshold' => 90,          // percentage
        'good_threshold' => 80,               // percentage
        'average_threshold' => 70,            // percentage
        'below_average_threshold' => 60,      // percentage
        'minimum_attendance_rate' => 75,      // percentage
        'punctuality_threshold' => 80,        // percentage
    ],

    // Financial and Budget
    'financial' => [
        'budget_variance_warning' => 20,      // percentage
        'budget_variance_critical' => 30,     // percentage
        'high_utilization_threshold' => 90,   // percentage
        'medium_utilization_threshold' => 75, // percentage
        'low_utilization_threshold' => 50,    // percentage
        'approval_limit_supervisor' => 50000, // INR
        'approval_limit_team_lead' => 25000,  // INR
        'approval_limit_employee' => 5000,    // INR
        'auto_approval_threshold' => 10000,   // INR
    ],

    // Notification and Alert Thresholds
    'notifications' => [
        'document_expiry_critical' => 7,      // days
        'document_expiry_warning' => 30,      // days
        'document_expiry_notice' => 90,       // days
        'alert_cooldown_minutes' => 30,       // minutes
        'max_notifications_per_user' => 50,   // count
        'notification_retention_days' => 30,  // days
    ],

    // Security and Validation
    'security' => [
        'max_login_attempts' => 5,            // attempts
        'lockout_duration_minutes' => 30,     // minutes
        'password_min_length' => 8,           // characters
        'session_timeout_minutes' => 120,     // minutes
        'csrf_token_lifetime' => 3600,        // seconds
        'rate_limit_per_minute' => 60,        // requests
        'max_input_length' => 255,            // characters
        'max_text_length' => 1000,            // characters
    ],

    // Inventory and Asset Management
    'inventory' => [
        'low_stock_threshold' => 0.5,         // 50% of reorder point
        'reorder_point_multiplier' => 2,      // times minimum stock
        'depreciation_threshold' => 90,       // percentage
        'maintenance_due_days' => 30,         // days
        'warranty_expiry_days' => 60,         // days
        'asset_audit_frequency_days' => 365,  // days
    ],

    // Scoring and Rating Systems
    'scoring' => [
        'max_teacher_score' => 100,           // points
        'base_availability_score' => 50,      // points
        'experience_multiplier' => 2,         // points per year
        'max_experience_points' => 25,        // points
        'subject_compatibility_score' => 60,  // points
        'class_familiarity_score' => 50,      // points
        'workload_balance_score' => 30,       // points
        'performance_rating_multiplier' => 6, // points per rating
        'confidence_threshold_high' => 95,    // percentage
        'confidence_threshold_medium' => 85,  // percentage
        'confidence_threshold_low' => 75,     // percentage
    ],

    // Data Processing and Limits
    'data_processing' => [
        'batch_size' => 100,                  // records
        'max_export_records' => 10000,       // records
        'pagination_default' => 25,          // records per page
        'search_results_limit' => 50,        // results
        'bulk_operation_limit' => 1000,      // operations
        'report_cache_minutes' => 60,        // minutes
        'cleanup_retention_days' => 365,     // days
    ],

    // API and External Services
    'api' => [
        'timeout_seconds' => 30,              // seconds
        'retry_attempts' => 3,                // attempts
        'rate_limit_per_hour' => 1000,       // requests
        'mock_success_rate' => 85,            // percentage for testing
        'uptime_threshold' => 95,             // percentage
        'response_time_sla' => 2000,          // milliseconds
    ],

    // Verification and Validation Thresholds
    'verification' => [
        'similarity_threshold_high' => 90,    // percentage
        'similarity_threshold_medium' => 80,  // percentage
        'similarity_threshold_low' => 70,     // percentage
        'confidence_threshold_accept' => 95,  // percentage
        'confidence_threshold_review' => 70,  // percentage
        'confidence_threshold_reject' => 30,  // percentage
        'ocr_quality_threshold' => 70,        // percentage
        'document_validation_threshold' => 60, // percentage
    ],

    // System Maintenance
    'maintenance' => [
        'log_retention_days' => 90,           // days
        'backup_retention_days' => 30,        // days
        'temp_file_cleanup_hours' => 24,      // hours
        'session_cleanup_minutes' => 60,      // minutes
        'cache_cleanup_hours' => 6,           // hours
        'database_optimization_days' => 7,    // days
    ],

    // Communication and Messaging
    'communication' => [
        'sms_character_limit' => 160,         // characters
        'email_subject_limit' => 100,        // characters
        'notification_title_limit' => 50,    // characters
        'message_body_limit' => 1000,        // characters
        'attachment_count_limit' => 5,       // files
        'broadcast_batch_size' => 100,       // recipients
    ],

    // Quality Assurance
    'quality' => [
        'test_coverage_minimum' => 80,        // percentage
        'code_quality_threshold' => 85,      // percentage
        'performance_score_minimum' => 75,   // percentage
        'security_score_minimum' => 90,      // percentage
        'documentation_coverage' => 70,      // percentage
    ],

    // Regional and Localization
    'regional' => [
        'default_timezone' => 'Asia/Kolkata',
        'default_currency' => 'INR',
        'default_locale' => 'en_IN',
        'phone_number_length' => 10,         // digits
        'pincode_length' => 6,               // digits
        'aadhar_number_length' => 12,        // digits
    ],
];