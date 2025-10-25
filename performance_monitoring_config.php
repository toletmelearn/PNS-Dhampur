<?php
/**
 * Performance Monitoring Configuration
 * 
 * This file configures monitoring tools for performance testing of the PNS-Dhampur application.
 * It integrates with the existing performance.php and monitoring.php configuration files.
 */

return [
    // Enable performance monitoring during tests
    'enabled' => env('PERFORMANCE_MONITORING_ENABLED', true),
    
    // JMeter integration settings
    'jmeter' => [
        'enabled' => true,
        'results_dir' => storage_path('performance/jmeter'),
        'metrics_endpoint' => '/api/metrics/jmeter',
        'auth_token' => env('JMETER_AUTH_TOKEN', 'performance_test_token'),
    ],
    
    // Database query monitoring
    'database' => [
        'log_slow_queries' => true,
        'slow_query_threshold' => env('SLOW_QUERY_THRESHOLD', 100), // milliseconds
        'log_all_queries' => env('LOG_ALL_QUERIES', false),
        'explain_queries' => env('EXPLAIN_QUERIES', true),
    ],
    
    // Resource monitoring
    'resources' => [
        'cpu' => [
            'enabled' => true,
            'warning_threshold' => env('CPU_WARNING_THRESHOLD', 70), // percentage
            'critical_threshold' => env('CPU_CRITICAL_THRESHOLD', 90), // percentage
        ],
        'memory' => [
            'enabled' => true,
            'warning_threshold' => env('MEMORY_WARNING_THRESHOLD', 70), // percentage
            'critical_threshold' => env('MEMORY_CRITICAL_THRESHOLD', 90), // percentage
        ],
        'disk' => [
            'enabled' => true,
            'warning_threshold' => env('DISK_WARNING_THRESHOLD', 80), // percentage
            'critical_threshold' => env('DISK_CRITICAL_THRESHOLD', 95), // percentage
        ],
    ],
    
    // Response time monitoring
    'response_time' => [
        'enabled' => true,
        'warning_threshold' => env('RESPONSE_TIME_WARNING', 500), // milliseconds
        'critical_threshold' => env('RESPONSE_TIME_CRITICAL', 1000), // milliseconds
        'log_slow_responses' => true,
    ],
    
    // Endpoint-specific thresholds
    'endpoints' => [
        // Attendance endpoints
        'attendance' => [
            'record' => [
                'warning_threshold' => 800, // milliseconds
                'critical_threshold' => 1500, // milliseconds
            ],
            'store' => [
                'warning_threshold' => 1000, // milliseconds
                'critical_threshold' => 2000, // milliseconds
            ],
        ],
        // Student registration endpoints
        'students' => [
            'register' => [
                'warning_threshold' => 1000, // milliseconds
                'critical_threshold' => 2000, // milliseconds
            ],
        ],
        // Report generation endpoints
        'reports' => [
            'generate' => [
                'warning_threshold' => 3000, // milliseconds
                'critical_threshold' => 5000, // milliseconds
            ],
        ],
    ],
    
    // Logging configuration
    'logging' => [
        'enabled' => true,
        'channel' => env('PERFORMANCE_LOG_CHANNEL', 'performance'),
        'level' => env('PERFORMANCE_LOG_LEVEL', 'debug'),
        'separate_files' => true,
    ],
    
    // Prometheus metrics export
    'prometheus' => [
        'enabled' => env('PROMETHEUS_ENABLED', true),
        'namespace' => 'pns_dhampur',
        'metrics_path' => '/metrics',
    ],
    
    // Test result storage
    'results' => [
        'storage' => [
            'driver' => env('PERFORMANCE_RESULTS_DRIVER', 'file'),
            'retention_days' => env('PERFORMANCE_RESULTS_RETENTION', 30),
        ],
        'compare_with_baseline' => true,
        'baseline_path' => storage_path('performance/baseline'),
    ],
];