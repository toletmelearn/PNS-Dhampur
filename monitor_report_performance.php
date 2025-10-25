<?php

/**
 * Report Generation Performance Monitoring Script
 * 
 * This script monitors performance metrics during report generation tests
 * and logs them for analysis.
 */

// Load configuration
$config = require_once('report_monitoring_config.php');

// Initialize monitoring
class ReportPerformanceMonitor {
    private $startTime;
    private $metrics = [];
    private $config;
    private $outputFile;
    
    public function __construct($config) {
        $this->config = $config;
        $this->startTime = microtime(true);
        
        // Create output directory if it doesn't exist
        if (!is_dir($config['test_results']['store_path'])) {
            mkdir($config['test_results']['store_path'], 0755, true);
        }
        
        // Initialize output file
        $this->outputFile = $config['test_results']['store_path'] . '/report_metrics_' . date('Y-m-d_H-i-s') . '.csv';
        
        // Write CSV header
        file_put_contents($this->outputFile, 
            "timestamp,report_type,response_time_ms,memory_usage_mb,cpu_usage_percent,db_query_time_ms,concurrent_reports\n");
            
        // Log start of monitoring
        $this->log("Performance monitoring started");
    }
    
    public function recordMetric($reportType, $responseTime, $memoryUsage, $cpuUsage, $dbQueryTime, $concurrentReports) {
        $timestamp = date('Y-m-d H:i:s');
        
        $metrics = [
            'timestamp' => $timestamp,
            'report_type' => $reportType,
            'response_time_ms' => $responseTime,
            'memory_usage_mb' => $memoryUsage,
            'cpu_usage_percent' => $cpuUsage,
            'db_query_time_ms' => $dbQueryTime,
            'concurrent_reports' => $concurrentReports
        ];
        
        $this->metrics[] = $metrics;
        
        // Write to CSV
        $line = implode(',', $metrics) . "\n";
        file_put_contents($this->outputFile, $line, FILE_APPEND);
        
        // Check for threshold violations
        $this->checkThresholds($reportType, $metrics);
    }
    
    private function checkThresholds($reportType, $metrics) {
        // Check response time threshold
        $responseTimeThreshold = $this->config['report_types'][$reportType]['response_time'] ?? 
                                $this->config['report_generation']['response_time']['warning_threshold'];
        
        if ($metrics['response_time_ms'] > $responseTimeThreshold) {
            $this->log("WARNING: Response time for {$reportType} report exceeded threshold: {$metrics['response_time_ms']}ms > {$responseTimeThreshold}ms");
        }
        
        // Check memory usage threshold
        $memoryThreshold = $this->config['report_types'][$reportType]['memory_usage'] ?? 
                          $this->config['report_generation']['memory_usage']['warning_threshold'];
        
        if ($metrics['memory_usage_mb'] > $memoryThreshold) {
            $this->log("WARNING: Memory usage for {$reportType} report exceeded threshold: {$metrics['memory_usage_mb']}MB > {$memoryThreshold}MB");
        }
        
        // Check CPU usage threshold
        if ($metrics['cpu_usage_percent'] > $this->config['system']['cpu_usage']['warning_threshold']) {
            $this->log("WARNING: CPU usage exceeded threshold: {$metrics['cpu_usage_percent']}% > {$this->config['system']['cpu_usage']['warning_threshold']}%");
        }
        
        // Check concurrent reports threshold
        if ($metrics['concurrent_reports'] > $this->config['report_generation']['concurrent_reports']['warning_threshold']) {
            $this->log("WARNING: Concurrent reports exceeded threshold: {$metrics['concurrent_reports']} > {$this->config['report_generation']['concurrent_reports']['warning_threshold']}");
        }
    }
    
    public function generateSummary() {
        if (empty($this->metrics)) {
            return "No metrics recorded";
        }
        
        // Calculate averages
        $totalResponseTime = 0;
        $totalMemoryUsage = 0;
        $totalCpuUsage = 0;
        $totalDbQueryTime = 0;
        $maxConcurrentReports = 0;
        $reportTypeStats = [];
        
        foreach ($this->metrics as $metric) {
            $totalResponseTime += $metric['response_time_ms'];
            $totalMemoryUsage += $metric['memory_usage_mb'];
            $totalCpuUsage += $metric['cpu_usage_percent'];
            $totalDbQueryTime += $metric['db_query_time_ms'];
            $maxConcurrentReports = max($maxConcurrentReports, $metric['concurrent_reports']);
            
            // Track stats by report type
            $reportType = $metric['report_type'];
            if (!isset($reportTypeStats[$reportType])) {
                $reportTypeStats[$reportType] = [
                    'count' => 0,
                    'total_response_time' => 0,
                    'total_memory_usage' => 0
                ];
            }
            
            $reportTypeStats[$reportType]['count']++;
            $reportTypeStats[$reportType]['total_response_time'] += $metric['response_time_ms'];
            $reportTypeStats[$reportType]['total_memory_usage'] += $metric['memory_usage_mb'];
        }
        
        $count = count($this->metrics);
        $avgResponseTime = $totalResponseTime / $count;
        $avgMemoryUsage = $totalMemoryUsage / $count;
        $avgCpuUsage = $totalCpuUsage / $count;
        $avgDbQueryTime = $totalDbQueryTime / $count;
        
        // Generate summary
        $summary = "Performance Test Summary\n";
        $summary .= "=======================\n";
        $summary .= "Total reports generated: {$count}\n";
        $summary .= "Average response time: " . number_format($avgResponseTime, 2) . " ms\n";
        $summary .= "Average memory usage: " . number_format($avgMemoryUsage, 2) . " MB\n";
        $summary .= "Average CPU usage: " . number_format($avgCpuUsage, 2) . "%\n";
        $summary .= "Average DB query time: " . number_format($avgDbQueryTime, 2) . " ms\n";
        $summary .= "Max concurrent reports: {$maxConcurrentReports}\n\n";
        
        // Report type breakdown
        $summary .= "Report Type Breakdown\n";
        $summary .= "--------------------\n";
        
        foreach ($reportTypeStats as $reportType => $stats) {
            $typeAvgResponseTime = $stats['total_response_time'] / $stats['count'];
            $typeAvgMemoryUsage = $stats['total_memory_usage'] / $stats['count'];
            
            $summary .= "{$reportType} Reports ({$stats['count']}):\n";
            $summary .= "  Average response time: " . number_format($typeAvgResponseTime, 2) . " ms\n";
            $summary .= "  Average memory usage: " . number_format($typeAvgMemoryUsage, 2) . " MB\n";
            
            // Compare with thresholds
            $responseTimeThreshold = $this->config['report_types'][$reportType]['response_time'] ?? 
                                    $this->config['report_generation']['response_time']['warning_threshold'];
            
            $memoryThreshold = $this->config['report_types'][$reportType]['memory_usage'] ?? 
                              $this->config['report_generation']['memory_usage']['warning_threshold'];
            
            $summary .= "  Response time threshold: {$responseTimeThreshold} ms (" . 
                        ($typeAvgResponseTime <= $responseTimeThreshold ? "PASSED" : "FAILED") . ")\n";
            
            $summary .= "  Memory usage threshold: {$memoryThreshold} MB (" . 
                        ($typeAvgMemoryUsage <= $memoryThreshold ? "PASSED" : "FAILED") . ")\n\n";
        }
        
        // Daily capacity assessment
        $estimatedDailyCapacity = (86400 / $avgResponseTime) * $maxConcurrentReports;
        $summary .= "Capacity Assessment\n";
        $summary .= "------------------\n";
        $summary .= "Required daily capacity: {$this->config['report_generation']['daily_capacity']} reports\n";
        $summary .= "Estimated daily capacity: " . number_format($estimatedDailyCapacity, 0) . " reports\n";
        $summary .= "Capacity requirement: " . 
                    ($estimatedDailyCapacity >= $this->config['report_generation']['daily_capacity'] ? "PASSED" : "FAILED") . "\n";
        
        // Save summary to file
        $summaryFile = $this->config['test_results']['store_path'] . '/report_summary_' . date('Y-m-d_H-i-s') . '.txt';
        file_put_contents($summaryFile, $summary);
        
        return $summary;
    }
    
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}\n";
        
        // Log to file
        $logFile = $this->config['test_results']['store_path'] . '/monitor.log';
        file_put_contents($logFile, $logMessage, FILE_APPEND);
        
        // Output to console
        echo $logMessage;
    }
}

// Usage example (when called directly)
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    $monitor = new ReportPerformanceMonitor($config);
    
    // Simulate monitoring for demonstration purposes
    $reportTypes = ['attendance', 'academic', 'financial', 'exam', 'behavior'];
    
    for ($i = 0; $i < 50; $i++) {
        $reportType = $reportTypes[array_rand($reportTypes)];
        $responseTime = rand(1000, 6000);
        $memoryUsage = rand(32, 256);
        $cpuUsage = rand(20, 95);
        $dbQueryTime = rand(100, 3000);
        $concurrentReports = rand(1, 40);
        
        $monitor->recordMetric($reportType, $responseTime, $memoryUsage, $cpuUsage, $dbQueryTime, $concurrentReports);
        
        // Simulate time passing
        usleep(100000); // 100ms
    }
    
    // Generate and display summary
    echo $monitor->generateSummary();
}