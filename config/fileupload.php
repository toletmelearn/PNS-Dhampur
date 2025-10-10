<?php

return [
    /*
    |--------------------------------------------------------------------------
    | File Upload Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all the configuration options for file uploads
    | across the application. You can customize file size limits, allowed
    | file types, and other upload-related settings here.
    |
    */

    'max_file_sizes' => [
        'default' => 10240, // 10MB in KB
        'image' => 5120,    // 5MB for images
        'document' => 20480, // 20MB for documents
        'assignment' => 25600, // 25MB for assignments
        'bulk_upload' => 51200, // 50MB for bulk uploads
    ],

    'allowed_file_types' => [
        'image' => [
            'mimes' => 'jpg,jpeg,png,gif,webp',
            'extensions' => ['.jpg', '.jpeg', '.png', '.gif', '.webp'],
        ],
        'document' => [
            'mimes' => 'pdf,doc,docx,txt,rtf',
            'extensions' => ['.pdf', '.doc', '.docx', '.txt', '.rtf'],
        ],
        'assignment' => [
            'mimes' => 'pdf,doc,docx,txt,jpg,jpeg,png,zip,rar,7z',
            'extensions' => ['.pdf', '.doc', '.docx', '.txt', '.jpg', '.jpeg', '.png', '.zip', '.rar', '.7z'],
        ],
        'verification' => [
            'mimes' => 'pdf,jpg,jpeg,png,gif',
            'extensions' => ['.pdf', '.jpg', '.jpeg', '.png', '.gif'],
        ],
        'teacher_documents' => [
            'mimes' => 'pdf,jpg,jpeg,png,doc,docx',
            'extensions' => ['.pdf', '.jpg', '.jpeg', '.png', '.doc', '.docx'],
        ],
    ],

    'upload_paths' => [
        'students' => 'uploads/students',
        'teachers' => 'uploads/teachers',
        'assignments' => 'uploads/assignments',
        'documents' => 'uploads/documents',
        'verifications' => 'uploads/verifications',
        'inventory' => 'uploads/inventory',
        'temp' => 'uploads/temp',
    ],

    'preview_settings' => [
        'enable_preview' => true,
        'preview_types' => ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'],
        'thumbnail_size' => [
            'width' => 200,
            'height' => 200,
        ],
    ],

    'progress_settings' => [
        'enable_progress' => true,
        'chunk_size' => 1024 * 1024, // 1MB chunks
        'show_speed' => true,
        'show_eta' => true,
    ],

    'drag_drop_settings' => [
        'enable_drag_drop' => true,
        'multiple_files' => true,
        'highlight_on_drag' => true,
    ],

    'validation_messages' => [
        'max_size' => 'The :attribute file size must not exceed :max MB.',
        'invalid_type' => 'The :attribute must be a file of type: :types.',
        'upload_failed' => 'Failed to upload :attribute. Please try again.',
        'too_many_files' => 'You can upload a maximum of :max files.',
    ],

    'security_settings' => [
        'enable_virus_scan' => env('ENABLE_VIRUS_SCAN', true),
        'quarantine_suspicious_files' => env('QUARANTINE_SUSPICIOUS_FILES', true),
        'max_scan_size' => 100 * 1024 * 1024, // 100MB
        'scan_timeout' => 30, // seconds
        'enable_signature_validation' => true,
        'enable_content_analysis' => true,
        'strict_mime_validation' => true,
    ],

    'blocked_extensions' => [
        'exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'vbe', 'js', 'jse',
        'wsf', 'wsh', 'msi', 'msp', 'hta', 'cpl', 'jar', 'app', 'deb', 'rpm',
        'dmg', 'pkg', 'run', 'bin', 'sh', 'bash', 'ps1', 'psm1', 'psd1'
    ],

    'rate_limiting' => [
        'enable_upload_rate_limit' => true,
        'max_uploads_per_minute' => 10,
        'max_uploads_per_hour' => 100,
        'max_total_size_per_hour' => 500 * 1024 * 1024, // 500MB
    ],
];