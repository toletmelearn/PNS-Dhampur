<?php

namespace App\Services;

class Constants
{
    // Performance Alert Thresholds
    const RESPONSE_TIME_THRESHOLD = 2000; // milliseconds
    const MEMORY_USAGE_THRESHOLD = 80; // percentage
    const CPU_USAGE_THRESHOLD = 85; // percentage
    const DISK_SPACE_THRESHOLD = 5; // GB remaining
    const ERROR_RATE_THRESHOLD = 5; // percentage
    const SYSTEM_LOAD_THRESHOLD = 2.0; // load average
    const DATABASE_QUERY_TIME_THRESHOLD = 1000; // milliseconds

    // Alert Cooldowns (in minutes)
    const ALERT_COOLDOWN_DEFAULT = 5;
    const ALERT_COOLDOWN_CRITICAL = 10;
    const ALERT_COOLDOWN_EMERGENCY = 30;

    // Cache Durations (in seconds)
    const CACHE_DURATION_SHORT = 300; // 5 minutes
    const CACHE_DURATION_MEDIUM = 1800; // 30 minutes
    const CACHE_DURATION_LONG = 3600; // 1 hour
    const CACHE_DURATION_DAILY = 86400; // 24 hours

    // Security Constants
    const MAX_LOGIN_ATTEMPTS = 5;
    const LOGIN_LOCKOUT_DURATION = 900; // 15 minutes
    const PASSWORD_MIN_LENGTH = 8;
    const SESSION_TIMEOUT = 7200; // 2 hours

    // File Upload Constants
    const MAX_FILE_SIZE = 10485760; // 10MB in bytes
    const ALLOWED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    const ALLOWED_DOCUMENT_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];

    // Database Constants
    const MAX_QUERY_TIME = 5000; // milliseconds
    const MAX_CONNECTIONS = 100;
    const SLOW_QUERY_THRESHOLD = 1000; // milliseconds

    // System Health Constants
    const HEALTH_CHECK_INTERVAL = 300; // 5 minutes
    const MAINTENANCE_MODE_THRESHOLD = 95; // percentage
    const BACKUP_RETENTION_DAYS = 30;

    // Notification Constants
    const NOTIFICATION_BATCH_SIZE = 100;
    const EMAIL_QUEUE_DELAY = 60; // seconds
    const SMS_QUEUE_DELAY = 30; // seconds

    // API Rate Limiting
    const API_RATE_LIMIT_PER_MINUTE = 60;
    const API_RATE_LIMIT_PER_HOUR = 1000;
    const API_RATE_LIMIT_PER_DAY = 10000;

    // Audit Trail Constants
    const AUDIT_RETENTION_DAYS = 90;
    const AUDIT_BATCH_SIZE = 1000;
    const AUDIT_CLEANUP_INTERVAL = 86400; // 24 hours

    // Performance Monitoring
    const PERFORMANCE_SAMPLE_RATE = 0.1; // 10% sampling
    const PERFORMANCE_RETENTION_DAYS = 7;
    const PERFORMANCE_ALERT_THRESHOLD = 3; // consecutive failures

    // Security Monitoring
    const SECURITY_SCAN_INTERVAL = 3600; // 1 hour
    const VULNERABILITY_SCAN_INTERVAL = 86400; // 24 hours
    const SECURITY_LOG_RETENTION_DAYS = 180;

    // Backup Constants
    const BACKUP_COMPRESSION_LEVEL = 6;
    const BACKUP_ENCRYPTION_ENABLED = true;
    const BACKUP_VERIFICATION_ENABLED = true;

    // System Maintenance
    const MAINTENANCE_WINDOW_START = '02:00';
    const MAINTENANCE_WINDOW_END = '04:00';
    const MAINTENANCE_NOTIFICATION_HOURS = 24;

    // Error Handling
    const ERROR_LOG_MAX_SIZE = 104857600; // 100MB
    const ERROR_LOG_ROTATION_COUNT = 5;
    const ERROR_NOTIFICATION_THRESHOLD = 10; // errors per minute

    // Queue Constants
    const QUEUE_RETRY_ATTEMPTS = 3;
    const QUEUE_RETRY_DELAY = 60; // seconds
    const QUEUE_TIMEOUT = 300; // 5 minutes

    // Search Constants
    const SEARCH_RESULTS_PER_PAGE = 20;
    const SEARCH_MAX_RESULTS = 1000;
    const SEARCH_CACHE_DURATION = 1800; // 30 minutes

    // Export Constants
    const EXPORT_MAX_RECORDS = 10000;
    const EXPORT_TIMEOUT = 300; // 5 minutes
    const EXPORT_RETENTION_HOURS = 24;

    // Import Constants
    const IMPORT_MAX_FILE_SIZE = 52428800; // 50MB
    const IMPORT_BATCH_SIZE = 1000;
    const IMPORT_TIMEOUT = 600; // 10 minutes

    // Validation Constants
    const VALIDATION_MAX_STRING_LENGTH = 255;
    const VALIDATION_MAX_TEXT_LENGTH = 65535;
    const VALIDATION_MAX_ARRAY_SIZE = 1000;

    // Pagination Constants
    const DEFAULT_PAGE_SIZE = 15;
    const MAX_PAGE_SIZE = 100;
    const MIN_PAGE_SIZE = 5;

    // Date Format Constants
    const DATE_FORMAT = 'Y-m-d';
    const DATETIME_FORMAT = 'Y-m-d H:i:s';
    const TIME_FORMAT = 'H:i:s';
    const DISPLAY_DATE_FORMAT = 'd/m/Y';
    const DISPLAY_DATETIME_FORMAT = 'd/m/Y H:i:s';

    // Status Constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_DELETED = 'deleted';

    // Priority Constants
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_CRITICAL = 'critical';
    const PRIORITY_EMERGENCY = 'emergency';

    // Log Levels
    const LOG_LEVEL_DEBUG = 'debug';
    const LOG_LEVEL_INFO = 'info';
    const LOG_LEVEL_WARNING = 'warning';
    const LOG_LEVEL_ERROR = 'error';
    const LOG_LEVEL_CRITICAL = 'critical';

    // HTTP Status Codes
    const HTTP_OK = 200;
    const HTTP_CREATED = 201;
    const HTTP_BAD_REQUEST = 400;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_FORBIDDEN = 403;
    const HTTP_NOT_FOUND = 404;
    const HTTP_UNPROCESSABLE_ENTITY = 422;
    const HTTP_INTERNAL_SERVER_ERROR = 500;

    // Academic Year Constants
    const ACADEMIC_YEAR_START_MONTH = 4; // April
    const ACADEMIC_YEAR_END_MONTH = 3; // March
    const ACADEMIC_YEAR_FORMAT = 'Y-Y';

    // School Constants
    const SCHOOL_WORKING_DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    const SCHOOL_START_TIME = '08:00';
    const SCHOOL_END_TIME = '15:00';
    const BREAK_DURATION = 30; // minutes
    const PERIOD_DURATION = 45; // minutes

    // Attendance Constants
    const ATTENDANCE_PRESENT = 'present';
    const ATTENDANCE_ABSENT = 'absent';
    const ATTENDANCE_LATE = 'late';
    const ATTENDANCE_EXCUSED = 'excused';
    const ATTENDANCE_HALF_DAY = 'half_day';

    // Grade Constants
    const GRADE_A_PLUS = 'A+';
    const GRADE_A = 'A';
    const GRADE_B_PLUS = 'B+';
    const GRADE_B = 'B';
    const GRADE_C_PLUS = 'C+';
    const GRADE_C = 'C';
    const GRADE_D = 'D';
    const GRADE_F = 'F';

    // Fee Constants
    const FEE_STATUS_PAID = 'paid';
    const FEE_STATUS_PENDING = 'pending';
    const FEE_STATUS_OVERDUE = 'overdue';
    const FEE_STATUS_PARTIAL = 'partial';
    const FEE_STATUS_WAIVED = 'waived';

    // Communication Constants
    const NOTIFICATION_TYPE_EMAIL = 'email';
    const NOTIFICATION_TYPE_SMS = 'sms';
    const NOTIFICATION_TYPE_PUSH = 'push';
    const NOTIFICATION_TYPE_IN_APP = 'in_app';

    // Report Constants
    const REPORT_FORMAT_PDF = 'pdf';
    const REPORT_FORMAT_EXCEL = 'excel';
    const REPORT_FORMAT_CSV = 'csv';
    const REPORT_FORMAT_HTML = 'html';

    // User Role Constants
    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_ADMIN = 'admin';
    const ROLE_PRINCIPAL = 'principal';
    const ROLE_TEACHER = 'teacher';
    const ROLE_STUDENT = 'student';
    const ROLE_PARENT = 'parent';
    const ROLE_STAFF = 'staff';

    // Permission Constants
    const PERMISSION_CREATE = 'create';
    const PERMISSION_READ = 'read';
    const PERMISSION_UPDATE = 'update';
    const PERMISSION_DELETE = 'delete';
    const PERMISSION_MANAGE = 'manage';
    const PERMISSION_VIEW_ALL = 'view_all';
    const PERMISSION_EXPORT = 'export';
    const PERMISSION_IMPORT = 'import';
}