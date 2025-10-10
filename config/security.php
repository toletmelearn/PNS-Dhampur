<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains security-related configuration options for the
    | PNS-Dhampur School Management System.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Authentication Security
    |--------------------------------------------------------------------------
    */
    'auth' => [
        'max_login_attempts' => env('AUTH_MAX_LOGIN_ATTEMPTS', 5),
        'lockout_duration' => env('AUTH_LOCKOUT_DURATION', 900), // 15 minutes
        'password_min_length' => env('AUTH_PASSWORD_MIN_LENGTH', 8),
        'password_require_mixed_case' => env('AUTH_PASSWORD_REQUIRE_MIXED_CASE', true),
        'password_require_numbers' => env('AUTH_PASSWORD_REQUIRE_NUMBERS', true),
        'password_require_symbols' => env('AUTH_PASSWORD_REQUIRE_SYMBOLS', true),
        'session_timeout' => env('AUTH_SESSION_TIMEOUT', 7200), // 2 hours
        'force_password_change_days' => env('AUTH_FORCE_PASSWORD_CHANGE_DAYS', 90),
        'password_history_count' => env('AUTH_PASSWORD_HISTORY_COUNT', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Input Validation Security
    |--------------------------------------------------------------------------
    */
    'input' => [
        'max_string_length' => env('SECURITY_MAX_STRING_LENGTH', 255),
        'max_text_length' => env('SECURITY_MAX_TEXT_LENGTH', 65535),
        'max_array_size' => env('SECURITY_MAX_ARRAY_SIZE', 1000),
        'allowed_html_tags' => env('SECURITY_ALLOWED_HTML_TAGS', '<p><br><strong><em><ul><ol><li>'),
        'strip_dangerous_attributes' => env('SECURITY_STRIP_DANGEROUS_ATTRIBUTES', true),
        'sanitize_file_names' => env('SECURITY_SANITIZE_FILE_NAMES', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Security
    |--------------------------------------------------------------------------
    */
    'uploads' => [
        'max_file_size' => env('SECURITY_MAX_FILE_SIZE', 10485760), // 10MB
        'allowed_image_extensions' => explode(',', env('SECURITY_ALLOWED_IMAGE_EXTENSIONS', 'jpg,jpeg,png,gif,webp')),
        'allowed_document_extensions' => explode(',', env('SECURITY_ALLOWED_DOCUMENT_EXTENSIONS', 'pdf,doc,docx,xls,xlsx,ppt,pptx')),
        'scan_uploads_for_malware' => env('SECURITY_SCAN_UPLOADS_FOR_MALWARE', true),
        'quarantine_suspicious_files' => env('SECURITY_QUARANTINE_SUSPICIOUS_FILES', true),
        'upload_path_outside_webroot' => env('SECURITY_UPLOAD_PATH_OUTSIDE_WEBROOT', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Security
    |--------------------------------------------------------------------------
    */
    'database' => [
        'enable_query_logging' => env('SECURITY_ENABLE_QUERY_LOGGING', false),
        'log_slow_queries' => env('SECURITY_LOG_SLOW_QUERIES', true),
        'slow_query_threshold' => env('SECURITY_SLOW_QUERY_THRESHOLD', 1000), // milliseconds
        'enable_sql_injection_detection' => env('SECURITY_ENABLE_SQL_INJECTION_DETECTION', true),
        'max_query_execution_time' => env('SECURITY_MAX_QUERY_EXECUTION_TIME', 30), // seconds
        'enable_prepared_statements' => env('SECURITY_ENABLE_PREPARED_STATEMENTS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Encryption Security
    |--------------------------------------------------------------------------
    */
    'encryption' => [
        'encrypt_sensitive_fields' => env('SECURITY_ENCRYPT_SENSITIVE_FIELDS', true),
        'sensitive_fields' => [
            'users' => ['employee_id', 'phone', 'address'],
            'students' => ['phone', 'address', 'parent_phone', 'parent_email', 'emergency_contact'],
            'teachers' => ['phone', 'address', 'emergency_contact'],
            'salaries' => ['basic_salary', 'allowances', 'deductions', 'net_salary'],
            'fees' => ['amount', 'paid_amount', 'balance'],
        ],
        'key_rotation_days' => env('SECURITY_KEY_ROTATION_DAYS', 365),
        'backup_encryption' => env('SECURITY_BACKUP_ENCRYPTION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Security
    |--------------------------------------------------------------------------
    */
    'session' => [
        'secure_cookies' => env('SESSION_SECURE_COOKIE', false),
        'http_only_cookies' => env('SESSION_HTTP_ONLY', true),
        'same_site_cookies' => env('SESSION_SAME_SITE', 'lax'),
        'encrypt_session_data' => env('SESSION_ENCRYPT', false),
        'regenerate_on_login' => env('SECURITY_REGENERATE_SESSION_ON_LOGIN', true),
        'invalidate_on_logout' => env('SECURITY_INVALIDATE_SESSION_ON_LOGOUT', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Security
    |--------------------------------------------------------------------------
    */
    'rate_limiting' => [
        'login_attempts_per_minute' => env('SECURITY_LOGIN_ATTEMPTS_PER_MINUTE', 3),
        'api_requests_per_minute' => env('SECURITY_API_REQUESTS_PER_MINUTE', 60),
        'api_requests_per_hour' => env('SECURITY_API_REQUESTS_PER_HOUR', 1000),
        'password_reset_attempts_per_hour' => env('SECURITY_PASSWORD_RESET_ATTEMPTS_PER_HOUR', 5),
        'file_upload_attempts_per_minute' => env('SECURITY_FILE_UPLOAD_ATTEMPTS_PER_MINUTE', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging and Monitoring Security
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'log_failed_logins' => env('SECURITY_LOG_FAILED_LOGINS', true),
        'log_successful_logins' => env('SECURITY_LOG_SUCCESSFUL_LOGINS', true),
        'log_privilege_escalations' => env('SECURITY_LOG_PRIVILEGE_ESCALATIONS', true),
        'log_data_access' => env('SECURITY_LOG_DATA_ACCESS', false),
        'log_file_uploads' => env('SECURITY_LOG_FILE_UPLOADS', true),
        'log_suspicious_activity' => env('SECURITY_LOG_SUSPICIOUS_ACTIVITY', true),
        'alert_on_multiple_failed_logins' => env('SECURITY_ALERT_ON_MULTIPLE_FAILED_LOGINS', true),
        'alert_threshold' => env('SECURITY_ALERT_THRESHOLD', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy
    |--------------------------------------------------------------------------
    */
    'csp' => [
        'enabled' => env('SECURITY_CSP_ENABLED', true),
        'default_src' => env('SECURITY_CSP_DEFAULT_SRC', "'self'"),
        'script_src' => env('SECURITY_CSP_SCRIPT_SRC', "'self' 'unsafe-inline'"),
        'style_src' => env('SECURITY_CSP_STYLE_SRC', "'self' 'unsafe-inline'"),
        'img_src' => env('SECURITY_CSP_IMG_SRC', "'self' data: https:"),
        'font_src' => env('SECURITY_CSP_FONT_SRC', "'self'"),
        'connect_src' => env('SECURITY_CSP_CONNECT_SRC', "'self'"),
        'report_uri' => env('SECURITY_CSP_REPORT_URI', '/csp-report'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Headers
    |--------------------------------------------------------------------------
    */
    'headers' => [
        'x_content_type_options' => env('SECURITY_X_CONTENT_TYPE_OPTIONS', 'nosniff'),
        'x_frame_options' => env('SECURITY_X_FRAME_OPTIONS', 'DENY'),
        'x_xss_protection' => env('SECURITY_X_XSS_PROTECTION', '1; mode=block'),
        'referrer_policy' => env('SECURITY_REFERRER_POLICY', 'strict-origin-when-cross-origin'),
        'strict_transport_security' => env('SECURITY_STRICT_TRANSPORT_SECURITY', 'max-age=31536000; includeSubDomains'),
        'permissions_policy' => env('SECURITY_PERMISSIONS_POLICY', 'geolocation=(), microphone=(), camera=()'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Security
    |--------------------------------------------------------------------------
    */
    'backup' => [
        'encrypt_backups' => env('SECURITY_ENCRYPT_BACKUPS', true),
        'backup_retention_days' => env('SECURITY_BACKUP_RETENTION_DAYS', 30),
        'verify_backup_integrity' => env('SECURITY_VERIFY_BACKUP_INTEGRITY', true),
        'secure_backup_location' => env('SECURITY_SECURE_BACKUP_LOCATION', true),
        'backup_access_logging' => env('SECURITY_BACKUP_ACCESS_LOGGING', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Security
    |--------------------------------------------------------------------------
    */
    'api' => [
        'require_authentication' => env('SECURITY_API_REQUIRE_AUTHENTICATION', true),
        'enable_cors' => env('SECURITY_API_ENABLE_CORS', false),
        'allowed_origins' => explode(',', env('SECURITY_API_ALLOWED_ORIGINS', '')),
        'api_key_required' => env('SECURITY_API_KEY_REQUIRED', false),
        'jwt_expiration_minutes' => env('SECURITY_JWT_EXPIRATION_MINUTES', 60),
        'refresh_token_expiration_days' => env('SECURITY_REFRESH_TOKEN_EXPIRATION_DAYS', 7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Trail Security
    |--------------------------------------------------------------------------
    */
    'audit' => [
        'enabled' => env('SECURITY_AUDIT_ENABLED', true),
        'log_user_actions' => env('SECURITY_AUDIT_LOG_USER_ACTIONS', true),
        'log_data_changes' => env('SECURITY_AUDIT_LOG_DATA_CHANGES', true),
        'log_system_events' => env('SECURITY_AUDIT_LOG_SYSTEM_EVENTS', true),
        'retention_days' => env('SECURITY_AUDIT_RETENTION_DAYS', 365),
        'compress_old_logs' => env('SECURITY_AUDIT_COMPRESS_OLD_LOGS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Scanning
    |--------------------------------------------------------------------------
    */
    'scanning' => [
        'enable_vulnerability_scanning' => env('SECURITY_ENABLE_VULNERABILITY_SCANNING', true),
        'scan_frequency_hours' => env('SECURITY_SCAN_FREQUENCY_HOURS', 24),
        'enable_dependency_scanning' => env('SECURITY_ENABLE_DEPENDENCY_SCANNING', true),
        'enable_code_scanning' => env('SECURITY_ENABLE_CODE_SCANNING', false),
        'quarantine_suspicious_activity' => env('SECURITY_QUARANTINE_SUSPICIOUS_ACTIVITY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Emergency Response
    |--------------------------------------------------------------------------
    */
    'emergency' => [
        'enable_emergency_mode' => env('SECURITY_ENABLE_EMERGENCY_MODE', false),
        'emergency_contacts' => explode(',', env('SECURITY_EMERGENCY_CONTACTS', '')),
        'auto_lockdown_threshold' => env('SECURITY_AUTO_LOCKDOWN_THRESHOLD', 10),
        'incident_response_email' => env('SECURITY_INCIDENT_RESPONSE_EMAIL', ''),
        'emergency_backup_frequency_minutes' => env('SECURITY_EMERGENCY_BACKUP_FREQUENCY_MINUTES', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compliance Settings
    |--------------------------------------------------------------------------
    */
    'compliance' => [
        'gdpr_compliance' => env('SECURITY_GDPR_COMPLIANCE', true),
        'data_retention_policy_days' => env('SECURITY_DATA_RETENTION_POLICY_DAYS', 2555), // 7 years
        'right_to_be_forgotten' => env('SECURITY_RIGHT_TO_BE_FORGOTTEN', true),
        'data_portability' => env('SECURITY_DATA_PORTABILITY', true),
        'consent_management' => env('SECURITY_CONSENT_MANAGEMENT', true),
    ],

];