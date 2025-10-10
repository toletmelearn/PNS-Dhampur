<?php

namespace App\Support;

/**
 * System Constants Helper
 * 
 * Provides easy access to system-wide constants defined in config/constants.php
 * This class eliminates magic numbers and centralizes configuration values.
 */
class Constants
{
    /**
     * Get a constant value by key path
     * 
     * @param string $key Dot notation key (e.g., 'time.seconds_per_minute')
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        return config("constants.{$key}", $default);
    }

    // Time and Date Constants
    public static function secondsPerMinute(): int
    {
        return self::get('time.seconds_per_minute', 60);
    }

    public static function minutesPerHour(): int
    {
        return self::get('time.minutes_per_hour', 60);
    }

    public static function hoursPerDay(): int
    {
        return self::get('time.hours_per_day', 24);
    }

    public static function daysPerWeek(): int
    {
        return self::get('time.days_per_week', 7);
    }

    public static function daysPerMonth(): int
    {
        return self::get('time.days_per_month', 30);
    }

    public static function monthsPerYear(): int
    {
        return self::get('time.months_per_year', 12);
    }

    public static function sundayDayOfWeek(): int
    {
        return self::get('time.sunday_day_of_week', 0);
    }

    // Cache TTL Constants
    public static function shortCacheTtl(): int
    {
        return self::get('cache.short_ttl', 300);
    }

    public static function mediumCacheTtl(): int
    {
        return self::get('cache.medium_ttl', 1800);
    }

    public static function longCacheTtl(): int
    {
        return self::get('cache.long_ttl', 3600);
    }

    public static function dailyCacheTtl(): int
    {
        return self::get('cache.daily_ttl', 86400);
    }

    // Performance Thresholds
    public static function responseTimeThreshold(): int
    {
        return self::get('performance.response_time_threshold', 5000);
    }

    public static function memoryUsageThreshold(): int
    {
        return self::get('performance.memory_usage_threshold', 85);
    }

    public static function cpuUsageThreshold(): int
    {
        return self::get('performance.cpu_usage_threshold', 80);
    }

    public static function slowQueryThreshold(): int
    {
        return self::get('performance.slow_query_threshold', 400);
    }

    // File Upload Constants
    public static function maxFileSizeMb(): int
    {
        return self::get('file_upload.max_file_size_mb', 5);
    }

    public static function maxProfilePhotoSizeMb(): int
    {
        return self::get('file_upload.max_profile_photo_size_mb', 2);
    }

    public static function maxDocumentSizeMb(): int
    {
        return self::get('file_upload.max_document_size_mb', 10);
    }

    public static function virusScanTimeout(): int
    {
        return self::get('file_upload.virus_scan_timeout', 30);
    }

    // Attendance Constants
    public static function overtimeThresholdHours(): float
    {
        return self::get('attendance.overtime_threshold_hours', 8.5);
    }

    public static function lunchBreakMinutes(): int
    {
        return self::get('attendance.lunch_break_minutes', 60);
    }

    public static function minimumWorkingMinutes(): int
    {
        return self::get('attendance.minimum_working_minutes', 240);
    }

    public static function fullDayMinutes(): int
    {
        return self::get('attendance.full_day_minutes', 360);
    }

    public static function lateThresholdMinutes(): int
    {
        return self::get('attendance.late_threshold_minutes', 30);
    }

    // Academic Constants
    public static function passingMarks(): int
    {
        return self::get('academic.passing_marks', 40);
    }

    public static function excellentThreshold(): int
    {
        return self::get('academic.excellent_threshold', 90);
    }

    public static function minimumAttendanceRate(): int
    {
        return self::get('academic.minimum_attendance_rate', 75);
    }

    // Financial Constants
    public static function budgetVarianceWarning(): int
    {
        return self::get('financial.budget_variance_warning', 20);
    }

    public static function budgetVarianceCritical(): int
    {
        return self::get('financial.budget_variance_critical', 30);
    }

    public static function approvalLimitSupervisor(): int
    {
        return self::get('financial.approval_limit_supervisor', 50000);
    }

    public static function autoApprovalThreshold(): int
    {
        return self::get('financial.auto_approval_threshold', 10000);
    }

    // Security Constants
    public static function maxLoginAttempts(): int
    {
        return self::get('security.max_login_attempts', 5);
    }

    public static function lockoutDurationMinutes(): int
    {
        return self::get('security.lockout_duration_minutes', 30);
    }

    public static function passwordMinLength(): int
    {
        return self::get('security.password_min_length', 8);
    }

    public static function sessionTimeoutMinutes(): int
    {
        return self::get('security.session_timeout_minutes', 120);
    }

    public static function rateLimitPerMinute(): int
    {
        return self::get('security.rate_limit_per_minute', 60);
    }

    // Notification Constants
    public static function documentExpiryCritical(): int
    {
        return self::get('notifications.document_expiry_critical', 7);
    }

    public static function documentExpiryWarning(): int
    {
        return self::get('notifications.document_expiry_warning', 30);
    }

    public static function maxNotificationsPerUser(): int
    {
        return self::get('notifications.max_notifications_per_user', 50);
    }

    // Inventory Constants
    public static function lowStockThreshold(): float
    {
        return self::get('inventory.low_stock_threshold', 0.5);
    }

    public static function reorderPointMultiplier(): int
    {
        return self::get('inventory.reorder_point_multiplier', 2);
    }

    // Verification Constants
    public static function similarityThresholdHigh(): int
    {
        return self::get('verification.similarity_threshold_high', 90);
    }

    public static function confidenceThresholdAccept(): int
    {
        return self::get('verification.confidence_threshold_accept', 95);
    }

    public static function ocrQualityThreshold(): int
    {
        return self::get('verification.ocr_quality_threshold', 70);
    }

    // Data Processing Constants
    public static function batchSize(): int
    {
        return self::get('data_processing.batch_size', 100);
    }

    public static function paginationDefault(): int
    {
        return self::get('data_processing.pagination_default', 25);
    }

    public static function maxExportRecords(): int
    {
        return self::get('data_processing.max_export_records', 10000);
    }

    // API Constants
    public static function apiTimeoutSeconds(): int
    {
        return self::get('api.timeout_seconds', 30);
    }

    public static function apiRetryAttempts(): int
    {
        return self::get('api.retry_attempts', 3);
    }

    public static function mockSuccessRate(): int
    {
        return self::get('api.mock_success_rate', 85);
    }

    // Regional Constants
    public static function defaultTimezone(): string
    {
        return self::get('regional.default_timezone', 'Asia/Kolkata');
    }

    public static function phoneNumberLength(): int
    {
        return self::get('regional.phone_number_length', 10);
    }

    public static function aadharNumberLength(): int
    {
        return self::get('regional.aadhar_number_length', 12);
    }

    // Quality Assurance Constants
    public static function testCoverageMinimum(): int
    {
        return self::get('quality.test_coverage_minimum', 80);
    }

    public static function securityScoreMinimum(): int
    {
        return self::get('quality.security_score_minimum', 90);
    }

    // Month Constants
    public const JANUARY_MONTH = 1;
    public const FEBRUARY_MONTH = 2;
    public const MARCH_MONTH = 3;
    public const APRIL_MONTH = 4;
    public const MAY_MONTH = 5;
    public const JUNE_MONTH = 6;
    public const JULY_MONTH = 7;
    public const AUGUST_MONTH = 8;
    public const SEPTEMBER_MONTH = 9;
    public const OCTOBER_MONTH = 10;
    public const NOVEMBER_MONTH = 11;
    public const DECEMBER_MONTH = 12;

    // Day Constants
    public const FIRST_DAY_OF_MONTH = 1;
    public const MARCH_LAST_DAY = 31;
    public const SEPTEMBER_LAST_DAY = 30;

    // Count Constants
    public const ZERO_COUNT = 0;

    // Season Constants
    public const SEASON_CACHE_DAYS = 30;

    // Virus Scan Constants
    public const VIRUS_SCAN_MAX_SIZE_MB = 100;
    public const VIRUS_SCAN_HEADER_BYTES = 32;
    public const CLAMAV_SUCCESS_CODE = 0;
    public const CLAMAV_MALWARE_CODE = 1;

    // Purchase Order Automation
    public const PO_CACHE_DURATION = 300; // 5 minutes
    public const DEFAULT_LEAD_TIME_DAYS = 7;
    public const VENDOR_RECOMMENDATION_LIMIT = 3;
    public const RECENT_ACTIVITY_LIMIT = 10;
    public const LOW_STOCK_ITEMS_LIMIT = 10;
    public const TOP_VENDORS_LIMIT = 5;
    public const PENDING_APPROVALS_LIMIT = 10;
    public const TIME_SAVED_PER_AUTO_PO = 0.5; // hours
    public const MIN_REORDER_QUANTITY = 10;
    public const REORDER_POINT_THRESHOLD = 0.5;
    public const MIN_VENDOR_RATING = 3.0;
    public const AUTO_SEND_AMOUNT_THRESHOLD = 10000;
    public const AUTO_SEND_VENDOR_RATING = 4.5;
    public const DEFAULT_SYSTEM_USER_ID = 1;
    
    // Approval Limits by Role
    public const APPROVAL_LIMIT_EMPLOYEE = 5000;
    public const APPROVAL_LIMIT_TEAM_LEAD = 25000;
    public const APPROVAL_LIMIT_SUPERVISOR = 50000;
    public const APPROVAL_LIMIT_MANAGER = 100000;

    // Performance Alert Thresholds
    public const RESPONSE_TIME_THRESHOLD = 5000; // milliseconds
    public const MEMORY_USAGE_THRESHOLD = 85; // percentage
    public const CPU_USAGE_THRESHOLD = 80; // percentage
    public const DISK_SPACE_THRESHOLD = 10; // GB remaining
    public const ERROR_RATE_THRESHOLD = 5; // percentage
    public const SYSTEM_LOAD_THRESHOLD = 10; // load average
    public const DATABASE_QUERY_TIME_THRESHOLD = 1000; // milliseconds
    
    // Alert Cooldown Periods (seconds)
    public const ALERT_COOLDOWN_DEFAULT = 300; // 5 minutes
    public const ALERT_COOLDOWN_CRITICAL = 600; // 10 minutes
    public const ALERT_COOLDOWN_EMERGENCY = 1800; // 30 minutes
    
    // Critical Severity Thresholds
    public const CRITICAL_MEMORY_THRESHOLD = 95; // percentage
    public const CRITICAL_CPU_THRESHOLD = 95; // percentage
    public const CRITICAL_DISK_SPACE = 5; // GB
    public const CRITICAL_ERROR_RATE = 15; // percentage
    public const CRITICAL_SYSTEM_LOAD = 20; // load average
    
    // Performance Monitoring
    public const PERFORMANCE_CHECK_MINUTES = 5;
    public const RECENT_ALERTS_HOURS = 24;
    public const RECENT_ALERTS_LIMIT = 10;
    public const CACHE_HEALTH_CHECK_SECONDS = 1;
    public const PERFORMANCE_CACHE_HOURS = 24;
    public const MILLISECONDS_MULTIPLIER = 1000;
    public const BYTES_TO_GB_DIVISOR = 1073741824; // 1024^3
    public const CPU_CORES_DEFAULT = 4;

    // Performance metrics constants
    public const PERFORMANCE_CACHE_TIMEOUT = 3600;
    public const SINGLE_TEACHER_COUNT = 1;
    public const OVERTIME_HOURS_THRESHOLD = 8;
    public const DECIMAL_PLACES = 2;
    public const SCHOOL_START_TIME = '08:00:00';
    public const WORKING_HOUR_RANGES = [4, 6, 8, 10];
    public const PERFORMANCE_SCORE_WEIGHTS = [0.4, 0.3, 0.3]; // attendance, punctuality, consistency
    public const TOP_PERFORMERS_LIMIT = 10;
    public const BOTTOM_PERFORMERS_LIMIT = 10;
    public const LOW_PERFORMANCE_THRESHOLD = 70;
    public const LOW_ATTENDANCE_THRESHOLD = 85;
    public const LOW_PUNCTUALITY_THRESHOLD = 80;
    public const MINUTES_PER_HOUR = 60;
    public const CONSISTENCY_SCORE_MULTIPLIER = 10;
    public const TREND_CHANGE_THRESHOLD = 5;
}