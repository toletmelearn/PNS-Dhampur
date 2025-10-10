<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Production Environment Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file contains production-specific settings that
    | override default Laravel configurations for optimal performance,
    | security, and monitoring in production environments.
    |
    */

    'app' => [
        'debug' => false,
        'env' => 'production',
        'log_level' => 'error',
    ],

    'cache' => [
        'default' => 'redis',
        'prefix' => env('CACHE_PREFIX', 'pns_dhampur_cache'),
        'ttl' => [
            'default' => 3600, // 1 hour
            'user_sessions' => 7200, // 2 hours
            'static_data' => 86400, // 24 hours
            'reports' => 1800, // 30 minutes
        ],
    ],

    'session' => [
        'driver' => 'redis',
        'lifetime' => 120, // 2 hours
        'expire_on_close' => true,
        'encrypt' => true,
        'http_only' => true,
        'secure' => true,
        'same_site' => 'strict',
    ],

    'queue' => [
        'default' => 'redis',
        'connections' => [
            'redis' => [
                'driver' => 'redis',
                'connection' => 'default',
                'queue' => env('REDIS_QUEUE', 'default'),
                'retry_after' => 90,
                'block_for' => null,
            ],
        ],
    ],

    'logging' => [
        'default' => 'production_stack',
        'channels' => [
            'production_stack' => [
                'driver' => 'stack',
                'channels' => ['daily', 'slack'],
                'ignore_exceptions' => false,
            ],
        ],
    ],

    'database' => [
        'connections' => [
            'mysql' => [
                'options' => [
                    PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
                    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
                ],
                'strict' => true,
                'engine' => 'InnoDB',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
            ],
        ],
    ],

    'security' => [
        'bcrypt_rounds' => 12,
        'password_timeout' => 10800, // 3 hours
        'max_login_attempts' => 5,
        'lockout_duration' => 900, // 15 minutes
        'force_https' => true,
        'hsts_max_age' => 31536000, // 1 year
    ],

    'file_upload' => [
        'max_size' => 10240, // 10MB in KB
        'virus_scan_enabled' => true,
        'allowed_mime_types' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'image/jpeg',
            'image/png',
            'image/jpg',
        ],
        'quarantine_suspicious_files' => true,
        'log_all_uploads' => true,
    ],

    'rate_limiting' => [
        'api' => [
            'per_minute' => 60,
            'per_hour' => 1000,
        ],
        'login' => [
            'per_minute' => 5,
            'per_hour' => 20,
        ],
        'upload' => [
            'per_minute' => 10,
            'per_hour' => 100,
        ],
        'password_reset' => [
            'per_minute' => 2,
            'per_hour' => 10,
        ],
    ],

    'monitoring' => [
        'error_reporting' => E_ALL & ~E_DEPRECATED & ~E_STRICT,
        'log_queries' => false,
        'log_slow_queries' => true,
        'slow_query_threshold' => 2000, // 2 seconds
        'memory_limit' => '512M',
        'max_execution_time' => 300, // 5 minutes
    ],

    'backup' => [
        'enabled' => true,
        'frequency' => 'daily',
        'retention_days' => 30,
        'compress' => true,
        'encrypt' => true,
        'destinations' => ['s3', 'local'],
    ],

    'mail' => [
        'queue_emails' => true,
        'rate_limit' => [
            'per_minute' => 10,
            'per_hour' => 100,
        ],
    ],

    'performance' => [
        'opcache_enabled' => true,
        'view_cache' => true,
        'route_cache' => true,
        'config_cache' => true,
        'compress_responses' => true,
        'minify_html' => true,
    ],
];