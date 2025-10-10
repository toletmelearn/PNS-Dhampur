<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Input Sanitization Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for input sanitization
    | across the application. These settings help protect against
    | XSS, SQL injection, and other security threats.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Sanitization Mode
    |--------------------------------------------------------------------------
    |
    | The default sanitization mode to use when no specific mode is provided.
    | Available modes: standard, strict, html, text, name, email, url, phone,
    | numeric, alphanumeric, filename, sql
    |
    */
    'default_mode' => env('SANITIZATION_DEFAULT_MODE', 'standard'),

    /*
    |--------------------------------------------------------------------------
    | HTML Purifier Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for HTML Purifier library used for HTML sanitization.
    |
    */
    'html_purifier' => [
        'allowed_tags' => env('SANITIZATION_ALLOWED_TAGS', 'p,br,strong,em,u,ol,ul,li,a[href],blockquote'),
        'allowed_attributes' => env('SANITIZATION_ALLOWED_ATTRIBUTES', 'a.href'),
        'allowed_protocols' => env('SANITIZATION_ALLOWED_PROTOCOLS', 'http,https,mailto'),
        'cache_path' => storage_path('app/htmlpurifier'),
        'auto_format' => [
            'remove_empty' => true,
            'linkify' => false,
        ],
        'html' => [
            'nofollow' => true,
            'target_blank' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Field Type Mappings
    |--------------------------------------------------------------------------
    |
    | Define sanitization modes for different field types across the application.
    |
    */
    'field_types' => [
        // Personal Information
        'name' => 'name',
        'first_name' => 'name',
        'last_name' => 'name',
        'middle_name' => 'name',
        'father_name' => 'name',
        'mother_name' => 'name',
        'guardian_name' => 'name',
        
        // Contact Information
        'email' => 'email',
        'phone' => 'phone',
        'mobile' => 'phone',
        'telephone' => 'phone',
        'emergency_contact' => 'phone',
        
        // Address Fields
        'address' => 'text',
        'street_address' => 'text',
        'city' => 'name',
        'state' => 'name',
        'country' => 'name',
        'pincode' => 'numeric',
        'postal_code' => 'numeric',
        
        // Identification
        'aadhaar' => 'numeric',
        'pan' => 'alphanumeric',
        'passport' => 'alphanumeric',
        'driving_license' => 'alphanumeric',
        'voter_id' => 'alphanumeric',
        
        // Academic Fields
        'roll_number' => 'alphanumeric',
        'admission_number' => 'alphanumeric',
        'registration_number' => 'alphanumeric',
        'employee_id' => 'alphanumeric',
        'student_id' => 'alphanumeric',
        'teacher_id' => 'alphanumeric',
        
        // Financial Fields
        'amount' => 'numeric',
        'fee' => 'numeric',
        'salary' => 'numeric',
        'fine' => 'numeric',
        'discount' => 'numeric',
        'tax' => 'numeric',
        'total' => 'numeric',
        'balance' => 'numeric',
        
        // Text Fields
        'description' => 'html',
        'remarks' => 'html',
        'notes' => 'html',
        'comments' => 'html',
        'reason' => 'text',
        'qualification' => 'text',
        'experience' => 'text',
        'skills' => 'text',
        'bio' => 'html',
        
        // URLs and Files
        'url' => 'url',
        'website' => 'url',
        'filename' => 'filename',
        'file_name' => 'filename',
        
        // Dates and Times (handled as text for validation)
        'date' => 'text',
        'time' => 'text',
        'datetime' => 'text',
        'birth_date' => 'text',
        'joining_date' => 'text',
        'admission_date' => 'text',
        
        // Authentication
        'username' => 'alphanumeric',
        'password' => 'strict',
        'password_confirmation' => 'strict',
        'current_password' => 'strict',
        'new_password' => 'strict',
        
        // System Fields
        'slug' => 'alphanumeric',
        'code' => 'alphanumeric',
        'reference' => 'alphanumeric',
        'transaction_id' => 'alphanumeric',
        'receipt_number' => 'alphanumeric',
        'invoice_number' => 'alphanumeric',
    ],

    /*
    |--------------------------------------------------------------------------
    | Context-Specific Field Mappings
    |--------------------------------------------------------------------------
    |
    | Define field type mappings for specific contexts or forms.
    |
    */
    'contexts' => [
        'student_form' => [
            'name' => 'name',
            'father_name' => 'name',
            'mother_name' => 'name',
            'email' => 'email',
            'phone' => 'phone',
            'address' => 'text',
            'roll_number' => 'alphanumeric',
            'admission_number' => 'alphanumeric',
            'aadhaar' => 'numeric',
            'remarks' => 'html',
            'medical_conditions' => 'html',
            'emergency_contact' => 'phone',
        ],
        
        'teacher_form' => [
            'name' => 'name',
            'email' => 'email',
            'phone' => 'phone',
            'address' => 'text',
            'employee_id' => 'alphanumeric',
            'qualification' => 'text',
            'experience' => 'text',
            'salary' => 'numeric',
            'pan' => 'alphanumeric',
            'aadhaar' => 'numeric',
            'remarks' => 'html',
            'skills' => 'text',
            'bio' => 'html',
        ],
        
        'fee_form' => [
            'amount' => 'numeric',
            'discount' => 'numeric',
            'fine' => 'numeric',
            'tax' => 'numeric',
            'total' => 'numeric',
            'description' => 'text',
            'remarks' => 'html',
            'transaction_id' => 'alphanumeric',
            'receipt_number' => 'alphanumeric',
        ],
        
        'attendance_form' => [
            'remarks' => 'html',
            'reason' => 'text',
            'medical_reason' => 'html',
        ],
        
        'user_profile' => [
            'name' => 'name',
            'email' => 'email',
            'phone' => 'phone',
            'address' => 'text',
            'bio' => 'html',
            'website' => 'url',
        ],
        
        'settings' => [
            'school_name' => 'text',
            'school_address' => 'text',
            'school_phone' => 'phone',
            'school_email' => 'email',
            'school_website' => 'url',
            'principal_name' => 'name',
            'description' => 'html',
            'value' => 'text',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Suspicious Pattern Detection
    |--------------------------------------------------------------------------
    |
    | Configuration for detecting and logging suspicious input patterns.
    |
    */
    'suspicious_patterns' => [
        'enabled' => env('SANITIZATION_SUSPICIOUS_DETECTION', true),
        'log_channel' => env('SANITIZATION_LOG_CHANNEL', 'security'),
        'alert_threshold' => env('SANITIZATION_ALERT_THRESHOLD', 5), // Number of attempts before alerting
        'block_threshold' => env('SANITIZATION_BLOCK_THRESHOLD', 10), // Number of attempts before blocking IP
        'block_duration' => env('SANITIZATION_BLOCK_DURATION', 3600), // Block duration in seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | XSS Protection Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for XSS (Cross-Site Scripting) protection.
    |
    */
    'xss_protection' => [
        'enabled' => env('SANITIZATION_XSS_PROTECTION', true),
        'strict_mode' => env('SANITIZATION_XSS_STRICT', false),
        'log_attempts' => env('SANITIZATION_XSS_LOG', true),
        'block_requests' => env('SANITIZATION_XSS_BLOCK', false),
        'allowed_domains' => explode(',', env('SANITIZATION_XSS_ALLOWED_DOMAINS', '')),
    ],

    /*
    |--------------------------------------------------------------------------
    | SQL Injection Protection Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for SQL injection protection.
    |
    */
    'sql_injection_protection' => [
        'enabled' => env('SANITIZATION_SQL_PROTECTION', true),
        'strict_mode' => env('SANITIZATION_SQL_STRICT', true),
        'log_attempts' => env('SANITIZATION_SQL_LOG', true),
        'block_requests' => env('SANITIZATION_SQL_BLOCK', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Sanitization
    |--------------------------------------------------------------------------
    |
    | Configuration for sanitizing file upload metadata and names.
    |
    */
    'file_upload' => [
        'sanitize_filenames' => env('SANITIZATION_FILE_NAMES', true),
        'sanitize_metadata' => env('SANITIZATION_FILE_METADATA', true),
        'max_filename_length' => env('SANITIZATION_MAX_FILENAME_LENGTH', 255),
        'allowed_filename_chars' => env('SANITIZATION_FILENAME_CHARS', 'a-zA-Z0-9._-'),
        'preserve_extensions' => env('SANITIZATION_PRESERVE_EXTENSIONS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for sanitization performance optimization.
    |
    */
    'performance' => [
        'cache_enabled' => env('SANITIZATION_CACHE_ENABLED', true),
        'cache_ttl' => env('SANITIZATION_CACHE_TTL', 3600), // Cache TTL in seconds
        'batch_size' => env('SANITIZATION_BATCH_SIZE', 100), // Batch size for bulk operations
        'memory_limit' => env('SANITIZATION_MEMORY_LIMIT', '128M'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Whitelist Settings
    |--------------------------------------------------------------------------
    |
    | Define whitelisted patterns or values that should bypass sanitization.
    |
    */
    'whitelist' => [
        'routes' => [
            'api/webhook/*',
            'admin/system/backup',
            'admin/system/restore',
        ],
        'fields' => [
            'csrf_token',
            '_token',
            '_method',
        ],
        'patterns' => [
            // Add regex patterns for values that should be whitelisted
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Sanitization Rules
    |--------------------------------------------------------------------------
    |
    | Define custom sanitization rules for specific use cases.
    |
    */
    'custom_rules' => [
        'indian_phone' => [
            'pattern' => '/[^0-9\+\-\(\)\s]/',
            'replacement' => '',
            'format' => 'phone',
        ],
        'indian_pincode' => [
            'pattern' => '/[^0-9]/',
            'replacement' => '',
            'length' => 6,
        ],
        'school_code' => [
            'pattern' => '/[^A-Z0-9]/',
            'replacement' => '',
            'case' => 'upper',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for logging sanitization activities.
    |
    */
    'logging' => [
        'enabled' => env('SANITIZATION_LOGGING', true),
        'level' => env('SANITIZATION_LOG_LEVEL', 'warning'),
        'channel' => env('SANITIZATION_LOG_CHANNEL', 'security'),
        'include_user_data' => env('SANITIZATION_LOG_USER_DATA', false),
        'include_request_data' => env('SANITIZATION_LOG_REQUEST_DATA', true),
        'max_input_length' => env('SANITIZATION_LOG_MAX_INPUT', 200),
    ],
];