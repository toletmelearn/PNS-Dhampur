<?php

return [
    /*
    |--------------------------------------------------------------------------
    | File Upload Security Settings
    |--------------------------------------------------------------------------
    |
    | These settings control the security aspects of file uploads in the
    | school management system.
    |
    */

    'security' => [
        'scan_viruses' => env('SCAN_UPLOADS_FOR_VIRUSES', true),
        'check_file_content' => env('CHECK_FILE_CONTENT', true),
        'block_suspicious_ips' => env('BLOCK_SUSPICIOUS_IPS', true),
        'log_all_uploads' => env('LOG_ALL_UPLOADS', true),
        'quarantine_suspicious_files' => env('QUARANTINE_SUSPICIOUS_FILES', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Limits
    |--------------------------------------------------------------------------
    |
    | Configure the limits for file uploads to prevent abuse and ensure
    | system stability.
    |
    */

    'limits' => [
        'max_files_per_request' => env('MAX_FILES_PER_REQUEST', 10),
        'max_total_size_per_request' => env('MAX_TOTAL_SIZE_PER_REQUEST', 52428800), // 50MB
        'max_uploads_per_minute_per_ip' => env('MAX_UPLOADS_PER_MINUTE_PER_IP', 10),
        'max_uploads_per_hour_per_user' => env('MAX_UPLOADS_PER_HOUR_PER_USER', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Type Configurations
    |--------------------------------------------------------------------------
    |
    | Define allowed file types and their specific configurations for
    | different contexts in the school management system.
    |
    */

    'types' => [
        'student_documents' => [
            'max_size' => 10485760, // 10MB
            'allowed_extensions' => ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'],
            'allowed_mime_types' => [
                'application/pdf',
                'image/jpeg',
                'image/png',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ],
            'scan_viruses' => true,
            'check_content' => true,
            'resize_images' => true,
            'max_width' => 2000,
            'max_height' => 2000,
        ],

        'teacher_documents' => [
            'max_size' => 15728640, // 15MB
            'allowed_extensions' => ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'txt'],
            'allowed_mime_types' => [
                'application/pdf',
                'image/jpeg',
                'image/png',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'text/plain'
            ],
            'scan_viruses' => true,
            'check_content' => true,
            'resize_images' => true,
            'max_width' => 3000,
            'max_height' => 3000,
        ],

        'profile_photos' => [
            'max_size' => 2097152, // 2MB
            'allowed_extensions' => ['jpg', 'jpeg', 'png'],
            'allowed_mime_types' => [
                'image/jpeg',
                'image/png'
            ],
            'scan_viruses' => true,
            'check_content' => true,
            'resize_images' => true,
            'max_width' => 1000,
            'max_height' => 1000,
            'min_width' => 100,
            'min_height' => 100,
            'aspect_ratio' => 1.0, // Square images
            'aspect_ratio_tolerance' => 0.1,
        ],

        'school_documents' => [
            'max_size' => 20971520, // 20MB
            'allowed_extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'],
            'allowed_mime_types' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation'
            ],
            'scan_viruses' => true,
            'check_content' => true,
        ],

        'certificates' => [
            'max_size' => 5242880, // 5MB
            'allowed_extensions' => ['pdf', 'jpg', 'jpeg', 'png'],
            'allowed_mime_types' => [
                'application/pdf',
                'image/jpeg',
                'image/png'
            ],
            'scan_viruses' => true,
            'check_content' => true,
            'resize_images' => true,
            'max_width' => 2000,
            'max_height' => 2000,
        ],

        'fee_receipts' => [
            'max_size' => 5242880, // 5MB
            'allowed_extensions' => ['pdf', 'jpg', 'jpeg', 'png'],
            'allowed_mime_types' => [
                'application/pdf',
                'image/jpeg',
                'image/png'
            ],
            'scan_viruses' => true,
            'check_content' => true,
            'watermark' => true,
        ],

        'bulk_imports' => [
            'max_size' => 10485760, // 10MB
            'allowed_extensions' => ['csv', 'xlsx', 'xls'],
            'allowed_mime_types' => [
                'text/csv',
                'application/csv',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ],
            'scan_viruses' => true,
            'check_content' => true,
            'validate_structure' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Configurations
    |--------------------------------------------------------------------------
    |
    | Configure where different types of files should be stored and how
    | they should be organized.
    |
    */

    'storage' => [
        'disk' => env('FILE_UPLOAD_DISK', 'public'),
        'directories' => [
            'student_documents' => 'uploads/students/documents',
            'teacher_documents' => 'uploads/teachers/documents',
            'profile_photos' => 'uploads/profiles/photos',
            'school_documents' => 'uploads/school/documents',
            'certificates' => 'uploads/certificates',
            'fee_receipts' => 'uploads/fees/receipts',
            'bulk_imports' => 'uploads/bulk/imports',
            'temporary' => 'uploads/temp',
            'quarantine' => 'uploads/quarantine',
        ],
        'organize_by_date' => true,
        'create_thumbnails' => true,
        'thumbnail_sizes' => [
            'small' => [150, 150],
            'medium' => [300, 300],
            'large' => [600, 600],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | Additional validation rules that can be applied to file uploads
    | based on context and user roles.
    |
    */

    'validation' => [
        'check_duplicates' => env('CHECK_DUPLICATE_FILES', true),
        'max_file_age_days' => env('MAX_FILE_AGE_DAYS', 365),
        'min_file_size' => env('MIN_FILE_SIZE', 100), // bytes
        'require_authentication' => true,
        'require_authorization' => true,
        'log_validation_failures' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Processing
    |--------------------------------------------------------------------------
    |
    | Settings for automatic image processing during upload.
    |
    */

    'image_processing' => [
        'auto_orient' => true,
        'strip_metadata' => true,
        'compress_quality' => 85,
        'convert_to_webp' => env('CONVERT_IMAGES_TO_WEBP', false),
        'generate_thumbnails' => true,
        'watermark_images' => env('WATERMARK_IMAGES', false),
        'watermark_text' => env('WATERMARK_TEXT', config('app.name')),
        'watermark_opacity' => 0.3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Virus Scanning
    |--------------------------------------------------------------------------
    |
    | Configuration for virus scanning integration.
    |
    */

    'virus_scanning' => [
        'enabled' => env('VIRUS_SCANNING_ENABLED', true),
        'engine' => env('VIRUS_SCANNER_ENGINE', 'clamav'), // clamav, windows_defender, custom
        'quarantine_infected' => true,
        'notify_admin' => true,
        'scan_timeout' => 30, // seconds
        'max_scan_size' => 104857600, // 100MB
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Analysis
    |--------------------------------------------------------------------------
    |
    | Settings for analyzing file content for security threats.
    |
    */

    'content_analysis' => [
        'check_embedded_files' => true,
        'check_macros' => true,
        'check_scripts' => true,
        'check_external_links' => true,
        'entropy_threshold' => 7.5,
        'suspicious_patterns' => [
            'php_code' => '/<\?php/i',
            'javascript' => '/<script[^>]*>/i',
            'vbscript' => '/vbscript:/i',
            'eval_function' => '/eval\s*\(/i',
            'base64_decode' => '/base64_decode\s*\(/i',
            'shell_exec' => '/shell_exec\s*\(/i',
            'system_call' => '/system\s*\(/i',
            'file_inclusion' => '/(include|require)(_once)?\s*\(/i',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for file uploads to prevent abuse.
    |
    */

    'rate_limiting' => [
        'enabled' => true,
        'per_ip' => [
            'uploads_per_minute' => 10,
            'uploads_per_hour' => 100,
            'total_size_per_hour' => 524288000, // 500MB
        ],
        'per_user' => [
            'uploads_per_minute' => 20,
            'uploads_per_hour' => 200,
            'total_size_per_hour' => 1073741824, // 1GB
        ],
        'block_duration' => 3600, // 1 hour in seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Cleanup Settings
    |--------------------------------------------------------------------------
    |
    | Configure automatic cleanup of temporary and old files.
    |
    */

    'cleanup' => [
        'temp_files_lifetime' => 3600, // 1 hour in seconds
        'quarantine_files_lifetime' => 2592000, // 30 days in seconds
        'log_files_lifetime' => 7776000, // 90 days in seconds
        'auto_cleanup_enabled' => true,
        'cleanup_schedule' => 'daily',
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configure notifications for file upload events.
    |
    */

    'notifications' => [
        'notify_on_virus' => true,
        'notify_on_suspicious_activity' => true,
        'notify_on_large_uploads' => true,
        'notify_on_failed_uploads' => false,
        'admin_email' => env('ADMIN_EMAIL', 'admin@school.com'),
        'notification_channels' => ['mail', 'database'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Settings
    |--------------------------------------------------------------------------
    |
    | Configure backup settings for uploaded files.
    |
    */

    'backup' => [
        'enabled' => env('BACKUP_UPLOADS', true),
        'backup_disk' => env('BACKUP_DISK', 'backup'),
        'backup_schedule' => 'daily',
        'retention_days' => 90,
        'compress_backups' => true,
        'encrypt_backups' => true,
    ],
];