<?php

/**
 * Report Generation Performance Monitoring Configuration
 * 
 * This file configures monitoring for report generation performance testing
 * to ensure the system can handle 1,000 report generations per day.
 */

return [
    // General monitoring settings
    'enabled' => env('PERFORMANCE_MONITORING_ENABLED', true),
    'log_channel' => env('PERFORMANCE_LOG_CHANNEL', 'performance'),
    
    // Report generation specific thresholds
    'report_generation' => [
        'response_time' => [
            'warning_threshold' => 3000, // milliseconds
            'critical_threshold' => 5000, // milliseconds
        ],
        'memory_usage' => [
            'warning_threshold' => 128, // MB
            'critical_threshold' => 256, // MB
        ],
        'concurrent_reports' => [
            'warning_threshold' => 20,
            'critical_threshold' => 30,
        ],
        'daily_capacity' => 1000, // Required reports per day
        'hourly_peak' => 150,     // Expected peak reports per hour
    ],
    
    // Report types with specific thresholds
    'report_types' => [
        'attendance' => [
            'response_time' => 2000, // milliseconds
            'memory_usage' => 64,    // MB
        ],
        'academic' => [
            'response_time' => 3000, // milliseconds
            'memory_usage' => 96,    // MB
        ],
        'financial' => [
            'response_time' => 2500, // milliseconds
            'memory_usage' => 80,    // MB
        ],
        'exam' => [
            'response_time' => 4000, // milliseconds
            'memory_usage' => 128,   // MB
        ],
        'behavior' => [
            'response_time' => 2000, // milliseconds
            'memory_usage' => 64,    // MB
        ],
    ],
    
    // Database monitoring
    'database' => [
        'slow_query_threshold' => 1000, // milliseconds
        'max_connections' => 100,
        'connection_timeout' => 5000, // milliseconds
    ],
    
    // System resource monitoring
    'system' => [
        'cpu_usage' => [
            'warning_threshold' => 70, // percent
            'critical_threshold' => 90, // percent
        ],
        'memory_usage' => [
            'warning_threshold' => 70, // percent
            'critical_threshold' => 90, // percent
        ],
        'disk_usage' => [
            'warning_threshold' => 80, // percent
            'critical_threshold' => 90, // percent
        ],
    ],
    
    // Test result storage
    'test_results' => [
        'store_path' => storage_path('performance/reports'),
        'retention_days' => 30,
        'compare_with_baseline' => true,
        'baseline_path' => storage_path('performance/baseline'),
    ],
    
    // Alerting configuration
    'alerts' => [
        'enabled' => true,
        'channels' => ['email', 'log'],
        'recipients' => [
            'email' => ['admin@pnsdhampur.edu', 'tech@pnsdhampur.edu'],
        ],
    ],
    
    // JMeter integration
    'jmeter' => [
        'results_path' => storage_path('performance/jmeter'),
        'report_path' => storage_path('performance/jmeter/reports'),
    ],
];