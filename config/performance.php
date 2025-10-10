<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Performance Alert Thresholds
    |--------------------------------------------------------------------------
    |
    | Define the thresholds for various performance metrics. When these
    | thresholds are exceeded, alerts will be triggered.
    |
    */
    'alert_thresholds' => [
        // Response time threshold in milliseconds
        'response_time' => env('PERF_RESPONSE_TIME_THRESHOLD', 5000),
        
        // Memory usage threshold as percentage
        'memory_usage' => env('PERF_MEMORY_USAGE_THRESHOLD', 85),
        
        // CPU usage threshold as percentage
        'cpu_usage' => env('PERF_CPU_USAGE_THRESHOLD', 80),
        
        // Minimum disk space in GB
        'disk_space' => env('PERF_DISK_SPACE_THRESHOLD', 10),
        
        // Error rate threshold as percentage
        'error_rate' => env('PERF_ERROR_RATE_THRESHOLD', 5),
        
        // System load average threshold
        'system_load' => env('PERF_SYSTEM_LOAD_THRESHOLD', 10),
        
        // Database query time threshold in milliseconds
        'database_query_time' => env('PERF_DB_QUERY_TIME_THRESHOLD', 1000),
        
        // Queue size threshold
        'queue_size' => env('PERF_QUEUE_SIZE_THRESHOLD', 1000),
        
        // Active sessions threshold
        'active_sessions' => env('PERF_ACTIVE_SESSIONS_THRESHOLD', 500),
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Cooldown Periods
    |--------------------------------------------------------------------------
    |
    | Define cooldown periods (in seconds) for different alert severities
    | to prevent spam notifications.
    |
    */
    'alert_cooldowns' => [
        'default' => env('PERF_ALERT_COOLDOWN_DEFAULT', 300), // 5 minutes
        'warning' => env('PERF_ALERT_COOLDOWN_WARNING', 300), // 5 minutes
        'critical' => env('PERF_ALERT_COOLDOWN_CRITICAL', 600), // 10 minutes
        'emergency' => env('PERF_ALERT_COOLDOWN_EMERGENCY', 1800), // 30 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring Settings
    |--------------------------------------------------------------------------
    |
    | Configure various monitoring behaviors and features.
    |
    */
    'monitoring' => [
        // Enable/disable performance monitoring
        'enabled' => env('PERF_MONITORING_ENABLED', true),
        
        // How often to run performance checks (in minutes)
        'check_interval' => env('PERF_CHECK_INTERVAL', 5),
        
        // How long to retain performance metrics (in days)
        'retention_days' => env('PERF_RETENTION_DAYS', 30),
        
        // Enable detailed logging
        'detailed_logging' => env('PERF_DETAILED_LOGGING', false),
        
        // Enable real-time monitoring
        'realtime_monitoring' => env('PERF_REALTIME_MONITORING', true),
        
        // Maximum number of alerts to store
        'max_alerts' => env('PERF_MAX_ALERTS', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Channels
    |--------------------------------------------------------------------------
    |
    | Configure which notification channels to use for different alert types.
    |
    */
    'notification_channels' => [
        'warning' => ['database'],
        'critical' => ['database', 'mail'],
        'emergency' => ['database', 'mail', 'sms'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Metrics Collection
    |--------------------------------------------------------------------------
    |
    | Configure which metrics to collect and how often.
    |
    */
    'metrics_collection' => [
        // System metrics
        'system' => [
            'enabled' => env('PERF_COLLECT_SYSTEM_METRICS', true),
            'interval' => env('PERF_SYSTEM_METRICS_INTERVAL', 60), // seconds
        ],
        
        // Application metrics
        'application' => [
            'enabled' => env('PERF_COLLECT_APP_METRICS', true),
            'interval' => env('PERF_APP_METRICS_INTERVAL', 30), // seconds
        ],
        
        // Database metrics
        'database' => [
            'enabled' => env('PERF_COLLECT_DB_METRICS', true),
            'interval' => env('PERF_DB_METRICS_INTERVAL', 60), // seconds
            'slow_query_threshold' => env('PERF_SLOW_QUERY_THRESHOLD', 1000), // milliseconds
        ],
        
        // Cache metrics
        'cache' => [
            'enabled' => env('PERF_COLLECT_CACHE_METRICS', true),
            'interval' => env('PERF_CACHE_METRICS_INTERVAL', 60), // seconds
        ],
        
        // Queue metrics
        'queue' => [
            'enabled' => env('PERF_COLLECT_QUEUE_METRICS', true),
            'interval' => env('PERF_QUEUE_METRICS_INTERVAL', 30), // seconds
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Recipients
    |--------------------------------------------------------------------------
    |
    | Define who should receive performance alerts.
    |
    */
    'alert_recipients' => [
        // Email addresses for critical alerts
        'critical_emails' => env('PERF_CRITICAL_EMAILS', 'admin@pnsdhampur.edu.in'),
        
        // SMS numbers for emergency alerts
        'emergency_sms' => env('PERF_EMERGENCY_SMS', ''),
        
        // Slack webhook for team notifications
        'slack_webhook' => env('PERF_SLACK_WEBHOOK', ''),
        
        // Discord webhook for team notifications
        'discord_webhook' => env('PERF_DISCORD_WEBHOOK', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Optimization
    |--------------------------------------------------------------------------
    |
    | Settings for automatic performance optimization features.
    |
    */
    'optimization' => [
        // Enable automatic cache clearing when memory is high
        'auto_cache_clear' => env('PERF_AUTO_CACHE_CLEAR', true),
        
        // Memory threshold for auto cache clearing (percentage)
        'cache_clear_threshold' => env('PERF_CACHE_CLEAR_THRESHOLD', 90),
        
        // Enable automatic log rotation
        'auto_log_rotation' => env('PERF_AUTO_LOG_ROTATION', true),
        
        // Log file size threshold for rotation (MB)
        'log_rotation_threshold' => env('PERF_LOG_ROTATION_THRESHOLD', 100),
        
        // Enable automatic database optimization
        'auto_db_optimization' => env('PERF_AUTO_DB_OPTIMIZATION', false),
        
        // Enable automatic queue processing scaling
        'auto_queue_scaling' => env('PERF_AUTO_QUEUE_SCALING', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Settings
    |--------------------------------------------------------------------------
    |
    | Configure the performance monitoring dashboard.
    |
    */
    'dashboard' => [
        // Refresh interval for real-time data (seconds)
        'refresh_interval' => env('PERF_DASHBOARD_REFRESH', 30),
        
        // Number of data points to show in charts
        'chart_data_points' => env('PERF_CHART_DATA_POINTS', 24),
        
        // Enable dashboard caching
        'enable_caching' => env('PERF_DASHBOARD_CACHING', true),
        
        // Dashboard cache TTL (seconds)
        'cache_ttl' => env('PERF_DASHBOARD_CACHE_TTL', 60),
        
        // Show detailed metrics
        'show_detailed_metrics' => env('PERF_SHOW_DETAILED_METRICS', true),
        
        // Enable export functionality
        'enable_export' => env('PERF_ENABLE_EXPORT', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    |
    | Configure integrations with external monitoring services.
    |
    */
    'integrations' => [
        // New Relic integration
        'newrelic' => [
            'enabled' => env('PERF_NEWRELIC_ENABLED', false),
            'api_key' => env('PERF_NEWRELIC_API_KEY', ''),
        ],
        
        // DataDog integration
        'datadog' => [
            'enabled' => env('PERF_DATADOG_ENABLED', false),
            'api_key' => env('PERF_DATADOG_API_KEY', ''),
        ],
        
        // Prometheus integration
        'prometheus' => [
            'enabled' => env('PERF_PROMETHEUS_ENABLED', false),
            'endpoint' => env('PERF_PROMETHEUS_ENDPOINT', '/metrics'),
        ],
        
        // Grafana integration
        'grafana' => [
            'enabled' => env('PERF_GRAFANA_ENABLED', false),
            'url' => env('PERF_GRAFANA_URL', ''),
            'api_key' => env('PERF_GRAFANA_API_KEY', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Security-related performance monitoring settings.
    |
    */
    'security' => [
        // Monitor failed login attempts
        'monitor_failed_logins' => env('PERF_MONITOR_FAILED_LOGINS', true),
        
        // Failed login threshold before alert
        'failed_login_threshold' => env('PERF_FAILED_LOGIN_THRESHOLD', 10),
        
        // Monitor suspicious activity
        'monitor_suspicious_activity' => env('PERF_MONITOR_SUSPICIOUS_ACTIVITY', true),
        
        // Rate limiting monitoring
        'monitor_rate_limiting' => env('PERF_MONITOR_RATE_LIMITING', true),
        
        // DDoS detection
        'ddos_detection' => env('PERF_DDOS_DETECTION', true),
        
        // Request rate threshold for DDoS detection
        'ddos_threshold' => env('PERF_DDOS_THRESHOLD', 1000), // requests per minute
    ],
];