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
];