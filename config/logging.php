<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Processor\PsrLogMessageProcessor;
use Monolog\Formatter\LineFormatter;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => env('LOG_STACK_CHANNELS', 'single,security,performance'),
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 14),
            'replace_placeholders' => true,
        ],

        // Production logging with rotation
        'production' => [
            'driver' => 'monolog',
            'handler' => RotatingFileHandler::class,
            'handler_with' => [
                'filename' => storage_path('logs/production.log'),
                'maxFiles' => 30,
            ],
            'level' => env('LOG_LEVEL', 'warning'),
            'formatter' => LineFormatter::class,
            'formatter_with' => [
                'format' => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                'dateFormat' => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => true,
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        // Security-specific logging
        'security' => [
            'driver' => 'monolog',
            'handler' => RotatingFileHandler::class,
            'handler_with' => [
                'filename' => storage_path('logs/security.log'),
                'maxFiles' => 90, // Keep security logs longer
            ],
            'level' => 'info',
            'formatter' => LineFormatter::class,
            'formatter_with' => [
                'format' => "[%datetime%] SECURITY.%level_name%: %message% %context%\n",
                'dateFormat' => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => true,
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        // Performance monitoring
        'performance' => [
            'driver' => 'monolog',
            'handler' => RotatingFileHandler::class,
            'handler_with' => [
                'filename' => storage_path('logs/performance.log'),
                'maxFiles' => 7, // Keep performance logs for a week
            ],
            'level' => 'info',
            'formatter' => LineFormatter::class,
            'formatter_with' => [
                'format' => "[%datetime%] PERF.%level_name%: %message% %context%\n",
                'dateFormat' => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => false,
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        // Database query logging
        'database' => [
            'driver' => 'monolog',
            'handler' => RotatingFileHandler::class,
            'handler_with' => [
                'filename' => storage_path('logs/database.log'),
                'maxFiles' => 7,
            ],
            'level' => env('DB_LOG_LEVEL', 'debug'),
            'formatter' => LineFormatter::class,
            'formatter_with' => [
                'format' => "[%datetime%] DB.%level_name%: %message% %context%\n",
                'dateFormat' => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => true,
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        // API request logging
        'api' => [
            'driver' => 'monolog',
            'handler' => RotatingFileHandler::class,
            'handler_with' => [
                'filename' => storage_path('logs/api.log'),
                'maxFiles' => 14,
            ],
            'level' => 'info',
            'formatter' => LineFormatter::class,
            'formatter_with' => [
                'format' => "[%datetime%] API.%level_name%: %message% %context%\n",
                'dateFormat' => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => true,
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        // Authentication and authorization logging
        'auth' => [
            'driver' => 'monolog',
            'handler' => RotatingFileHandler::class,
            'handler_with' => [
                'filename' => storage_path('logs/auth.log'),
                'maxFiles' => 30,
            ],
            'level' => 'info',
            'formatter' => LineFormatter::class,
            'formatter_with' => [
                'format' => "[%datetime%] AUTH.%level_name%: %message% %context%\n",
                'dateFormat' => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => true,
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        // Error-only logging for critical issues
        'errors' => [
            'driver' => 'monolog',
            'handler' => RotatingFileHandler::class,
            'handler_with' => [
                'filename' => storage_path('logs/errors.log'),
                'maxFiles' => 60,
            ],
            'level' => 'error',
            'formatter' => LineFormatter::class,
            'formatter_with' => [
                'format' => "[%datetime%] ERROR: %message% %context% %extra%\n",
                'dateFormat' => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => true,
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'PNS Dhampur Logger',
            'emoji' => ':warning:',
            'level' => env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => LOG_USER,
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Context
    |--------------------------------------------------------------------------
    |
    | Additional context to include in all log messages
    |
    */
    'context' => [
        'application' => env('APP_NAME', 'PNS-Dhampur'),
        'environment' => env('APP_ENV', 'production'),
        'version' => env('APP_VERSION', '1.0.0'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Processors
    |--------------------------------------------------------------------------
    |
    | Additional processors to add context to log messages
    |
    */
    'processors' => [
        // Add request ID to all logs
        'request_id' => [
            'enabled' => true,
            'header' => 'X-Request-ID',
        ],
        
        // Add user context to logs
        'user_context' => [
            'enabled' => true,
            'include_ip' => true,
            'include_user_agent' => true,
        ],
        
        // Add memory usage to logs
        'memory_usage' => [
            'enabled' => env('LOG_MEMORY_USAGE', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for security-related logging
    |
    */
    'security' => [
        'log_failed_logins' => true,
        'log_successful_logins' => env('LOG_SUCCESSFUL_LOGINS', false),
        'log_permission_denials' => true,
        'log_suspicious_activity' => true,
        'log_file_uploads' => true,
        'log_data_exports' => true,
        'log_admin_actions' => true,
        'log_password_changes' => true,
        'log_account_lockouts' => true,
        'log_csrf_failures' => true,
        'log_rate_limit_hits' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for performance monitoring
    |
    */
    'performance' => [
        'log_slow_queries' => env('LOG_SLOW_QUERIES', true),
        'slow_query_threshold' => env('SLOW_QUERY_THRESHOLD', 1000), // milliseconds
        'log_slow_requests' => env('LOG_SLOW_REQUESTS', true),
        'slow_request_threshold' => env('SLOW_REQUEST_THRESHOLD', 2000), // milliseconds
        'log_memory_usage' => env('LOG_MEMORY_USAGE', false),
        'memory_threshold' => env('MEMORY_THRESHOLD', 128), // MB
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Retention Policy
    |--------------------------------------------------------------------------
    |
    | How long to keep different types of logs
    |
    */
    'retention' => [
        'security' => 90, // days
        'errors' => 60,   // days
        'performance' => 7, // days
        'api' => 14,      // days
        'auth' => 30,     // days
        'database' => 7,  // days
        'general' => 14,  // days
    ],
];
