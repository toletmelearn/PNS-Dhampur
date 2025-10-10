<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Backup Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the automated backup system.
    | You can configure backup types, storage destinations, encryption,
    | compression, and retention policies.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Backup Settings
    |--------------------------------------------------------------------------
    */
    'default' => [
        'type' => env('BACKUP_DEFAULT_TYPE', 'full'), // full, database, files
        'encrypt' => env('BACKUP_ENCRYPT', true),
        'compress' => env('BACKUP_COMPRESS', true),
        'verify' => env('BACKUP_VERIFY', true),
        'retention_days' => env('BACKUP_RETENTION_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Schedules
    |--------------------------------------------------------------------------
    */
    'schedules' => [
        'daily_full' => [
            'enabled' => env('BACKUP_DAILY_ENABLED', true),
            'cron' => env('BACKUP_DAILY_CRON', '0 2 * * *'), // 2 AM daily
            'type' => 'full',
            'compress' => true,
            'cleanup' => true,
        ],
        
        'hourly_database' => [
            'enabled' => env('BACKUP_HOURLY_DB_ENABLED', false),
            'cron' => env('BACKUP_HOURLY_DB_CRON', '0 * * * *'), // Every hour
            'type' => 'database',
            'compress' => true,
            'cleanup' => false,
        ],
        
        'weekly_archive' => [
            'enabled' => env('BACKUP_WEEKLY_ENABLED', true),
            'cron' => env('BACKUP_WEEKLY_CRON', '0 1 * * 0'), // 1 AM on Sundays
            'type' => 'full',
            'compress' => true,
            'cleanup' => true,
            'archive' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'enabled' => env('BACKUP_NOTIFICATIONS_ENABLED', true),
        
        'channels' => [
            'email' => [
                'enabled' => env('BACKUP_EMAIL_NOTIFICATIONS', true),
                'recipients' => explode(',', env('BACKUP_EMAIL_RECIPIENTS', 'admin@example.com')),
                'on_success' => env('BACKUP_EMAIL_ON_SUCCESS', false),
                'on_failure' => env('BACKUP_EMAIL_ON_FAILURE', true),
            ],
            
            'slack' => [
                'enabled' => env('BACKUP_SLACK_NOTIFICATIONS', false),
                'webhook_url' => env('BACKUP_SLACK_WEBHOOK'),
                'channel' => env('BACKUP_SLACK_CHANNEL', '#backups'),
                'on_success' => env('BACKUP_SLACK_ON_SUCCESS', true),
                'on_failure' => env('BACKUP_SLACK_ON_FAILURE', true),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Encryption Settings
    |--------------------------------------------------------------------------
    */
    'encryption' => [
        'enabled' => env('BACKUP_ENCRYPTION_ENABLED', true),
        'key' => env('BACKUP_ENCRYPTION_KEY', env('APP_KEY')),
        'cipher' => 'AES-256-CBC',
        'key_derivation' => 'PBKDF2',
        'iterations' => 10000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Compression Settings
    |--------------------------------------------------------------------------
    */
    'compression' => [
        'enabled' => env('BACKUP_COMPRESSION_ENABLED', true),
        'level' => env('BACKUP_COMPRESSION_LEVEL', 9), // 1-9, 9 is maximum compression
        'method' => 'zip', // zip, gzip, bzip2
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Destinations
    |--------------------------------------------------------------------------
    */
    'storage' => [
        'destinations' => [
            'local' => [
                'enabled' => env('BACKUP_LOCAL_ENABLED', true),
                'path' => storage_path('app/backups'),
                'permissions' => 0755,
            ],
            
            's3' => [
                'enabled' => env('BACKUP_S3_ENABLED', false),
                'disk' => 's3',
                'path' => env('BACKUP_S3_PATH', 'backups'),
                'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
                'bucket' => env('AWS_BUCKET'),
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
            
            'ftp' => [
                'enabled' => env('BACKUP_FTP_ENABLED', false),
                'host' => env('BACKUP_FTP_HOST'),
                'port' => env('BACKUP_FTP_PORT', 21),
                'username' => env('BACKUP_FTP_USERNAME'),
                'password' => env('BACKUP_FTP_PASSWORD'),
                'path' => env('BACKUP_FTP_PATH', '/backups'),
                'passive' => env('BACKUP_FTP_PASSIVE', true),
                'ssl' => env('BACKUP_FTP_SSL', false),
            ],
            
            'google_drive' => [
                'enabled' => env('BACKUP_GOOGLE_DRIVE_ENABLED', false),
                'client_id' => env('GOOGLE_DRIVE_CLIENT_ID'),
                'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
                'refresh_token' => env('GOOGLE_DRIVE_REFRESH_TOKEN'),
                'folder_id' => env('GOOGLE_DRIVE_FOLDER_ID'),
            ],
            
            'dropbox' => [
                'enabled' => env('BACKUP_DROPBOX_ENABLED', false),
                'access_token' => env('DROPBOX_ACCESS_TOKEN'),
                'path' => env('BACKUP_DROPBOX_PATH', '/backups'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Backup Settings
    |--------------------------------------------------------------------------
    */
    'database' => [
        'connections' => [
            'mysql' => [
                'dump_command_path' => env('BACKUP_MYSQL_DUMP_PATH', 'mysqldump'),
                'dump_command_timeout' => 60 * 5, // 5 minutes
                'use_single_transaction' => true,
                'skip_comments' => true,
                'skip_lock_tables' => false,
                'exclude_tables' => [
                    'sessions',
                    'cache',
                    'jobs',
                    'failed_jobs',
                ],
            ],
            
            'postgresql' => [
                'dump_command_path' => env('BACKUP_POSTGRES_DUMP_PATH', 'pg_dump'),
                'dump_command_timeout' => 60 * 5, // 5 minutes
                'exclude_tables' => [
                    'sessions',
                    'cache',
                    'jobs',
                    'failed_jobs',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | File System Backup Settings
    |--------------------------------------------------------------------------
    */
    'files' => [
        'include' => [
            storage_path('app'),
            public_path('uploads'),
            public_path('images'),
            resource_path('views'),
            config_path(),
            base_path('.env.example'),
            base_path('composer.json'),
            base_path('package.json'),
            base_path('artisan'),
        ],
        
        'exclude' => [
            storage_path('app/backups'),
            storage_path('logs'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            public_path('css'),
            public_path('js'),
            base_path('node_modules'),
            base_path('vendor'),
            base_path('.git'),
        ],
        
        'exclude_extensions' => [
            'log',
            'tmp',
            'temp',
            'cache',
        ],
        
        'max_file_size' => 100 * 1024 * 1024, // 100MB
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Scheduling
    |--------------------------------------------------------------------------
    */
    'schedule' => [
        'enabled' => env('BACKUP_SCHEDULE_ENABLED', true),
        
        'jobs' => [
            'full_backup' => [
                'enabled' => env('BACKUP_FULL_SCHEDULE_ENABLED', true),
                'frequency' => env('BACKUP_FULL_FREQUENCY', 'daily'), // daily, weekly, monthly
                'time' => env('BACKUP_FULL_TIME', '02:00'), // HH:MM format
                'day' => env('BACKUP_FULL_DAY', 'sunday'), // For weekly backups
                'date' => env('BACKUP_FULL_DATE', 1), // For monthly backups (1-31)
                'options' => [
                    'type' => 'full',
                    'encrypt' => true,
                    'compress' => true,
                    'verify' => true,
                    'storage' => ['local', 's3'],
                ],
            ],
            
            'database_backup' => [
                'enabled' => env('BACKUP_DB_SCHEDULE_ENABLED', true),
                'frequency' => env('BACKUP_DB_FREQUENCY', 'daily'),
                'time' => env('BACKUP_DB_TIME', '01:00'),
                'options' => [
                    'type' => 'database',
                    'encrypt' => true,
                    'compress' => true,
                    'verify' => true,
                    'storage' => ['local'],
                ],
            ],
            
            'files_backup' => [
                'enabled' => env('BACKUP_FILES_SCHEDULE_ENABLED', false),
                'frequency' => env('BACKUP_FILES_FREQUENCY', 'weekly'),
                'time' => env('BACKUP_FILES_TIME', '03:00'),
                'day' => env('BACKUP_FILES_DAY', 'saturday'),
                'options' => [
                    'type' => 'files',
                    'encrypt' => true,
                    'compress' => true,
                    'verify' => false,
                    'storage' => ['local'],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Monitoring & Notifications
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'enabled' => env('BACKUP_MONITORING_ENABLED', true),
        
        'notifications' => [
            'channels' => ['mail', 'slack'], // mail, slack, discord, webhook
            
            'events' => [
                'backup_started' => false,
                'backup_completed' => true,
                'backup_failed' => true,
                'cleanup_completed' => false,
                'storage_full' => true,
            ],
            
            'mail' => [
                'to' => env('BACKUP_NOTIFICATION_MAIL_TO', env('MAIL_FROM_ADDRESS')),
                'subject_prefix' => '[Backup System]',
            ],
            
            'slack' => [
                'webhook_url' => env('BACKUP_SLACK_WEBHOOK_URL'),
                'channel' => env('BACKUP_SLACK_CHANNEL', '#backups'),
                'username' => env('BACKUP_SLACK_USERNAME', 'Backup Bot'),
                'icon' => env('BACKUP_SLACK_ICON', ':floppy_disk:'),
            ],
            
            'webhook' => [
                'url' => env('BACKUP_WEBHOOK_URL'),
                'method' => 'POST',
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . env('BACKUP_WEBHOOK_TOKEN'),
                ],
            ],
        ],
        
        'health_checks' => [
            'enabled' => true,
            'max_backup_age_hours' => 25, // Alert if last backup is older than this
            'min_backup_size_mb' => 1, // Alert if backup is smaller than this
            'check_frequency' => 'hourly', // hourly, daily
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Retention & Cleanup
    |--------------------------------------------------------------------------
    */
    'retention' => [
        'enabled' => env('BACKUP_CLEANUP_ENABLED', true),
        
        'policies' => [
            'daily' => [
                'keep_days' => env('BACKUP_KEEP_DAILY_DAYS', 7),
            ],
            'weekly' => [
                'keep_weeks' => env('BACKUP_KEEP_WEEKLY_WEEKS', 4),
            ],
            'monthly' => [
                'keep_months' => env('BACKUP_KEEP_MONTHLY_MONTHS', 12),
            ],
            'yearly' => [
                'keep_years' => env('BACKUP_KEEP_YEARLY_YEARS', 5),
            ],
        ],
        
        'cleanup_schedule' => [
            'frequency' => 'daily',
            'time' => '04:00',
        ],
        
        'storage_limits' => [
            'max_total_size_gb' => env('BACKUP_MAX_TOTAL_SIZE_GB', 50),
            'alert_threshold_percent' => 80,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Verification & Testing
    |--------------------------------------------------------------------------
    */
    'verification' => [
        'enabled' => env('BACKUP_VERIFICATION_ENABLED', true),
        
        'methods' => [
            'checksum' => true, // Generate and verify SHA256 checksums
            'file_integrity' => true, // Check if files can be opened/read
            'database_restore_test' => false, // Test database restore (resource intensive)
        ],
        
        'test_restore' => [
            'enabled' => env('BACKUP_TEST_RESTORE_ENABLED', false),
            'frequency' => 'weekly',
            'database_name' => env('BACKUP_TEST_DB_NAME', 'backup_test'),
            'cleanup_after_test' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance & Resource Management
    |--------------------------------------------------------------------------
    */
    'performance' => [
        'memory_limit' => env('BACKUP_MEMORY_LIMIT', '512M'),
        'execution_timeout' => env('BACKUP_EXECUTION_TIMEOUT', 3600), // 1 hour
        'chunk_size' => env('BACKUP_CHUNK_SIZE', 1024 * 1024), // 1MB
        'parallel_uploads' => env('BACKUP_PARALLEL_UPLOADS', false),
        'max_parallel_jobs' => env('BACKUP_MAX_PARALLEL_JOBS', 3),
        
        'throttling' => [
            'enabled' => env('BACKUP_THROTTLING_ENABLED', false),
            'max_bandwidth_mbps' => env('BACKUP_MAX_BANDWIDTH_MBPS', 10),
            'pause_between_files_ms' => env('BACKUP_PAUSE_BETWEEN_FILES_MS', 100),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    'security' => [
        'secure_delete' => env('BACKUP_SECURE_DELETE', true), // Overwrite files before deletion
        'access_control' => [
            'restrict_commands' => true,
            'allowed_users' => ['admin', 'backup'],
            'require_sudo' => false,
        ],
        
        'audit_logging' => [
            'enabled' => true,
            'log_channel' => 'backup_audit',
            'log_level' => 'info',
        ],
    ],
];