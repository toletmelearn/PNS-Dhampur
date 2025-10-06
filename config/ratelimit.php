<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all the rate limiting configurations for different
    | types of requests in the application. You can adjust these values
    | based on your application's requirements.
    |
    */

    'login' => [
        'ip_limit' => env('RATE_LIMIT_LOGIN_IP', 5),
        'ip_window' => env('RATE_LIMIT_LOGIN_IP_WINDOW', 15), // minutes
        
        'email_limit' => env('RATE_LIMIT_LOGIN_EMAIL', 3),
        'email_window' => env('RATE_LIMIT_LOGIN_EMAIL_WINDOW', 10), // minutes
        
        'global_limit' => env('RATE_LIMIT_LOGIN_GLOBAL', 100),
        'global_window' => env('RATE_LIMIT_LOGIN_GLOBAL_WINDOW', 1), // minutes
        
        'rapid_limit' => env('RATE_LIMIT_LOGIN_RAPID', 10),
        'rapid_window' => env('RATE_LIMIT_LOGIN_RAPID_WINDOW', 30), // seconds
        
        'progressive_lockout' => [
            'enabled' => env('RATE_LIMIT_LOGIN_PROGRESSIVE', true),
            'multiplier' => env('RATE_LIMIT_LOGIN_MULTIPLIER', 2),
            'max_lockout' => env('RATE_LIMIT_LOGIN_MAX_LOCKOUT', 1440), // minutes (24 hours)
        ],
    ],

    'api' => [
        'role_limits' => [
            'super_admin' => env('RATE_LIMIT_API_SUPER_ADMIN', 1000),
            'admin' => env('RATE_LIMIT_API_ADMIN', 500),
            'principal' => env('RATE_LIMIT_API_PRINCIPAL', 300),
            'teacher' => env('RATE_LIMIT_API_TEACHER', 200),
            'student' => env('RATE_LIMIT_API_STUDENT', 100),
            'parent' => env('RATE_LIMIT_API_PARENT', 50),
            'guest' => env('RATE_LIMIT_API_GUEST', 20),
        ],
        
        'endpoint_limits' => [
            'auth.login' => env('RATE_LIMIT_API_LOGIN', 5),
            'auth.register' => env('RATE_LIMIT_API_REGISTER', 3),
            'auth.logout' => env('RATE_LIMIT_API_LOGOUT', 10),
            'users.create' => env('RATE_LIMIT_API_USER_CREATE', 10),
            'users.update' => env('RATE_LIMIT_API_USER_UPDATE', 20),
            'users.delete' => env('RATE_LIMIT_API_USER_DELETE', 5),
            'password.reset' => env('RATE_LIMIT_API_PASSWORD_RESET', 3),
            'data.export' => env('RATE_LIMIT_API_DATA_EXPORT', 5),
        ],
        
        'window' => env('RATE_LIMIT_API_WINDOW', 1), // minutes
        
        'burst_detection' => [
            'enabled' => env('RATE_LIMIT_API_BURST_DETECTION', true),
            'threshold' => env('RATE_LIMIT_API_BURST_THRESHOLD', 50),
            'window' => env('RATE_LIMIT_API_BURST_WINDOW', 10), // seconds
        ],
        
        'global_limit' => env('RATE_LIMIT_API_GLOBAL', 10000),
        'overload_threshold' => env('RATE_LIMIT_API_OVERLOAD', 0.8), // 80%
    ],

    'form' => [
        'default_limit' => env('RATE_LIMIT_FORM_DEFAULT', 30),
        'default_window' => env('RATE_LIMIT_FORM_WINDOW', 10), // minutes
        
        'critical_forms' => [
            'password.reset' => env('RATE_LIMIT_FORM_PASSWORD_RESET', 5),
            'user.create' => env('RATE_LIMIT_FORM_USER_CREATE', 10),
            'user.update' => env('RATE_LIMIT_FORM_USER_UPDATE', 15),
            'settings.update' => env('RATE_LIMIT_FORM_SETTINGS', 20),
            'profile.update' => env('RATE_LIMIT_FORM_PROFILE', 10),
        ],
        
        'role_multipliers' => [
            'super_admin' => env('RATE_LIMIT_FORM_SUPER_ADMIN_MULT', 3.0),
            'admin' => env('RATE_LIMIT_FORM_ADMIN_MULT', 2.5),
            'principal' => env('RATE_LIMIT_FORM_PRINCIPAL_MULT', 2.0),
            'teacher' => env('RATE_LIMIT_FORM_TEACHER_MULT', 1.5),
            'student' => env('RATE_LIMIT_FORM_STUDENT_MULT', 1.0),
            'parent' => env('RATE_LIMIT_FORM_PARENT_MULT', 0.8),
            'guest' => env('RATE_LIMIT_FORM_GUEST_MULT', 0.5),
        ],
        
        'rapid_detection' => [
            'enabled' => env('RATE_LIMIT_FORM_RAPID_DETECTION', true),
            'limit' => env('RATE_LIMIT_FORM_RAPID_LIMIT', 5),
            'window' => env('RATE_LIMIT_FORM_RAPID_WINDOW', 30), // seconds
        ],
        
        'global_critical_limit' => env('RATE_LIMIT_FORM_GLOBAL_CRITICAL', 500),
    ],

    'download' => [
        'role_limits' => [
            'super_admin' => [
                'count' => env('RATE_LIMIT_DOWNLOAD_SUPER_ADMIN_COUNT', 1000),
                'bandwidth' => env('RATE_LIMIT_DOWNLOAD_SUPER_ADMIN_BW', 10737418240), // 10GB in bytes
            ],
            'admin' => [
                'count' => env('RATE_LIMIT_DOWNLOAD_ADMIN_COUNT', 500),
                'bandwidth' => env('RATE_LIMIT_DOWNLOAD_ADMIN_BW', 5368709120), // 5GB in bytes
            ],
            'principal' => [
                'count' => env('RATE_LIMIT_DOWNLOAD_PRINCIPAL_COUNT', 300),
                'bandwidth' => env('RATE_LIMIT_DOWNLOAD_PRINCIPAL_BW', 3221225472), // 3GB in bytes
            ],
            'teacher' => [
                'count' => env('RATE_LIMIT_DOWNLOAD_TEACHER_COUNT', 200),
                'bandwidth' => env('RATE_LIMIT_DOWNLOAD_TEACHER_BW', 2147483648), // 2GB in bytes
            ],
            'student' => [
                'count' => env('RATE_LIMIT_DOWNLOAD_STUDENT_COUNT', 50),
                'bandwidth' => env('RATE_LIMIT_DOWNLOAD_STUDENT_BW', 536870912), // 512MB in bytes
            ],
            'parent' => [
                'count' => env('RATE_LIMIT_DOWNLOAD_PARENT_COUNT', 30),
                'bandwidth' => env('RATE_LIMIT_DOWNLOAD_PARENT_BW', 268435456), // 256MB in bytes
            ],
            'guest' => [
                'count' => env('RATE_LIMIT_DOWNLOAD_GUEST_COUNT', 10),
                'bandwidth' => env('RATE_LIMIT_DOWNLOAD_GUEST_BW', 104857600), // 100MB in bytes
            ],
        ],
        
        'window' => env('RATE_LIMIT_DOWNLOAD_WINDOW', 60), // minutes
        
        'file_type_multipliers' => [
            'video' => env('RATE_LIMIT_DOWNLOAD_VIDEO_MULT', 3),
            'audio' => env('RATE_LIMIT_DOWNLOAD_AUDIO_MULT', 2),
            'image' => env('RATE_LIMIT_DOWNLOAD_IMAGE_MULT', 1),
            'document' => env('RATE_LIMIT_DOWNLOAD_DOCUMENT_MULT', 1),
            'archive' => env('RATE_LIMIT_DOWNLOAD_ARCHIVE_MULT', 2),
            'export' => env('RATE_LIMIT_DOWNLOAD_EXPORT_MULT', 1.5),
        ],
        
        'rapid_detection' => [
            'enabled' => env('RATE_LIMIT_DOWNLOAD_RAPID_DETECTION', true),
            'limit' => env('RATE_LIMIT_DOWNLOAD_RAPID_LIMIT', 10),
            'window' => env('RATE_LIMIT_DOWNLOAD_RAPID_WINDOW', 60), // seconds
        ],
        
        'global_limit' => env('RATE_LIMIT_DOWNLOAD_GLOBAL', 1000),
        'global_bandwidth' => env('RATE_LIMIT_DOWNLOAD_GLOBAL_BW', 107374182400), // 100GB in bytes
    ],

    'monitoring' => [
        'enabled' => env('RATE_LIMIT_MONITORING', true),
        'log_channel' => env('RATE_LIMIT_LOG_CHANNEL', 'daily'),
        'dashboard_refresh' => env('RATE_LIMIT_DASHBOARD_REFRESH', 30), // seconds
        'cleanup_interval' => env('RATE_LIMIT_CLEANUP_INTERVAL', 1440), // minutes (24 hours)
        'retention_days' => env('RATE_LIMIT_RETENTION_DAYS', 30),
    ],

    'cache' => [
        'prefix' => env('RATE_LIMIT_CACHE_PREFIX', 'rate_limit'),
        'store' => env('RATE_LIMIT_CACHE_STORE', 'redis'),
        'ttl' => env('RATE_LIMIT_CACHE_TTL', 3600), // seconds
    ],

    'responses' => [
        'login_blocked' => 'Too many login attempts. Please try again in :minutes minutes.',
        'api_blocked' => 'API rate limit exceeded. Please try again in :seconds seconds.',
        'form_blocked' => 'Too many form submissions. Please try again in :minutes minutes.',
        'download_blocked' => 'Download limit exceeded. Please try again in :minutes minutes.',
        'rapid_requests' => 'Too many rapid requests detected. Please slow down.',
        'global_limit' => 'System is currently overloaded. Please try again later.',
    ],

    'headers' => [
        'include_headers' => env('RATE_LIMIT_INCLUDE_HEADERS', true),
        'limit_header' => 'X-RateLimit-Limit',
        'remaining_header' => 'X-RateLimit-Remaining',
        'reset_header' => 'X-RateLimit-Reset',
        'retry_after_header' => 'Retry-After',
    ],
];