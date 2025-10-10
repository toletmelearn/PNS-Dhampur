<?php

return [
    /*
    |--------------------------------------------------------------------------
    | System Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for system monitoring, alerting,
    | and performance tracking in the production environment.
    |
    */

    'enabled' => env('MONITORING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Health Check Configuration
    |--------------------------------------------------------------------------
    */
    'health_checks' => [
        'database' => [
            'enabled' => true,
            'timeout' => 5, // seconds
            'critical_threshold' => 10, // seconds
        ],
        'cache' => [
            'enabled' => true,
            'timeout' => 3,
            'critical_threshold' => 5,
        ],
        'storage' => [
            'enabled' => true,
            'disk_space_threshold' => 85, // percentage
            'critical_threshold' => 95,
        ],
        'queue' => [
            'enabled' => true,
            'failed_jobs_threshold' => 10,
            'pending_jobs_threshold' => 100,
        ],
        'external_services' => [
            'biometric_api' => [
                'enabled' => env('BIOMETRIC_ENABLED', false),
                'url' => env('BIOMETRIC_API_URL'),
                'timeout' => 10,
            ],
            'mail_service' => [
                'enabled' => true,
                'timeout' => 15,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    */
    'performance' => [
        'memory_threshold' => 80, // percentage
        'cpu_threshold' => 85, // percentage
        'response_time_threshold' => 2000, // milliseconds
        'slow_query_threshold' => 1000, // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Alerting Configuration
    |--------------------------------------------------------------------------
    */
    'alerts' => [
        'enabled' => env('ALERTS_ENABLED', true),
        'channels' => [
            'email' => [
                'enabled' => env('EMAIL_ALERTS_ENABLED', true),
                'recipients' => explode(',', env('ALERT_EMAIL_RECIPIENTS', '')),
                'from' => env('MAIL_FROM_ADDRESS'),
            ],
            'slack' => [
                'enabled' => env('SLACK_ALERTS_ENABLED', false),
                'webhook_url' => env('SLACK_WEBHOOK_URL'),
                'channel' => env('SLACK_ALERT_CHANNEL', '#alerts'),
            ],
            'sms' => [
                'enabled' => env('SMS_ALERTS_ENABLED', false),
                'service' => env('SMS_SERVICE', 'twilio'),
                'recipients' => explode(',', env('SMS_ALERT_RECIPIENTS', '')),
            ],
        ],
        'severity_levels' => [
            'critical' => [
                'channels' => ['email', 'slack', 'sms'],
                'immediate' => true,
            ],
            'warning' => [
                'channels' => ['email', 'slack'],
                'immediate' => false,
                'throttle' => 300, // seconds
            ],
            'info' => [
                'channels' => ['slack'],
                'immediate' => false,
                'throttle' => 600,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'health_checks' => [
            'enabled' => true,
            'channel' => 'monitoring',
            'level' => 'info',
        ],
        'performance' => [
            'enabled' => true,
            'channel' => 'performance',
            'level' => 'debug',
        ],
        'security' => [
            'enabled' => true,
            'channel' => 'security',
            'level' => 'warning',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics Collection
    |--------------------------------------------------------------------------
    */
    'metrics' => [
        'enabled' => env('METRICS_ENABLED', true),
        'retention_days' => 30,
        'collection_interval' => 60, // seconds
        'endpoints' => [
            'prometheus' => env('PROMETHEUS_ENDPOINT'),
            'grafana' => env('GRAFANA_ENDPOINT'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Uptime Monitoring
    |--------------------------------------------------------------------------
    */
    'uptime' => [
        'enabled' => true,
        'check_interval' => 60, // seconds
        'timeout' => 10,
        'endpoints' => [
            'main' => env('APP_URL'),
            'api' => env('APP_URL') . '/api/v1/status',
            'health' => env('APP_URL') . '/health',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Monitoring
    |--------------------------------------------------------------------------
    */
    'security' => [
        'failed_login_threshold' => 5,
        'suspicious_activity_threshold' => 10,
        'rate_limit_violations_threshold' => 20,
        'monitor_file_changes' => true,
        'critical_files' => [
            '.env',
            'config/',
            'app/Http/Middleware/',
            'routes/',
        ],
    ],
];