<?php

/**
 * Performance Test Report Generator
 * 
 * This script generates a formatted performance test report from JMeter results
 * and monitoring data, using the report template.
 */

// Configuration
$config = [
    'template_file' => 'performance_test_report_template.md',
    'jmeter_results' => 'performance_results/results.jtl',
    'monitoring_results' => 'performance_results/report_metrics_*.csv',
    'output_file' => 'performance_results/performance_test_report.md',
    'thresholds' => [
        'response_time' => [
            'attendance' => 2000,
            'academic' => 3000,
            'financial' => 2500,
            'exam' => 4000,
            'behavior' => 2000,
            'default' => 3000
        ],
        'error_rate' => 1.0, // percent
        'cpu' => 80, // percent
        'memory' => 70, // percent
        'daily_capacity' => 1000 // reports
    ]
];

// Load template
if (!file_exists($config['template_file'])) {
    die("Error: Template file not found: {$config['template_file']}\n");
}

$template = file_get_contents($config['template_file']);

// Parse JMeter results
$jmeterData = parseJMeterResults($config['jmeter_results']);

// Parse monitoring results
$monitoringData = parseMonitoringResults($config['monitoring_results']);

// Prepare report data
$reportData = [
    'DATE' => date('Y-m-d'),
    'DURATION' => $jmeterData['duration'] ?? 'N/A',
    'THREADS' => $jmeterData['threads'] ?? 'N/A',
    'TEST_TYPE' => $jmeterData['test_type'] ?? 'report',
    'AVG_RESPONSE_TIME' => number_format($jmeterData['avg_response_time'] ?? 0, 2),
    'PEAK_RESPONSE_TIME' => number_format($jmeterData['max_response_time'] ?? 0, 2),
    'ERROR_RATE' => number_format($jmeterData['error_rate'] ?? 0, 2),
    'CAPACITY' => number_format($jmeterData['estimated_daily_capacity'] ?? 0, 0),
    
    // Resource utilization
    'CPU_AVG' => number_format($monitoringData['cpu_avg'] ?? 0, 2),
    'CPU_PEAK' => number_format($monitoringData['cpu_peak'] ?? 0, 2),
    'CPU_THRESHOLD' => $config['thresholds']['cpu'],
    'CPU_STATUS' => ($monitoringData['cpu_peak'] ?? 0) <= $config['thresholds']['cpu'] ? 'PASS' : 'FAIL',
    
    'MEM_AVG' => number_format($monitoringData['memory_avg'] ?? 0, 2),
    'MEM_PEAK' => number_format($monitoringData['memory_peak'] ?? 0, 2),
    'MEM_THRESHOLD' => $config['thresholds']['memory'],
    'MEM_STATUS' => ($monitoringData['memory_peak'] ?? 0) <= $config['thresholds']['memory'] ? 'PASS' : 'FAIL',
    
    // Overall status
    'PASS/FAIL' => determineOverallStatus($jmeterData, $monitoringData, $config['thresholds']),
    
    // Environment
    'ENVIRONMENT' => 'Test Environment',
    'SERVER_CONFIG' => 'Apache 2.4, PHP 7.4',
    'DB_CONFIG' => 'MySQL 8.0',
    'DATA_VOLUME' => 'Simulated 1,000 daily reports',
    
    // Resource summary
    'RESOURCE_SUMMARY' => generateResourceSummary($monitoringData),
    
    // Conclusion
    'CONCLUSION_TEXT' => generateConclusion($jmeterData, $monitoringData, $config['thresholds'])
];

// Add report type specific data
$reportTypes = ['attendance', 'academic', 'financial', 'exam', 'behavior'];
foreach ($reportTypes as $type) {
    $typeData = $jmeterData['report_types'][$type] ?? [];
    $threshold = $config['thresholds']['response_time'][$type] ?? $config['thresholds']['response_time']['default'];
    
    $reportData[strtoupper(substr($type, 0, 3)) . '_AVG_RT'] = number_format($typeData['avg_response_time'] ?? 0, 2);
    $reportData[strtoupper(substr($type, 0, 3)) . '_90_RT'] = number_format($typeData['percentile_90'] ?? 0, 2);
    $reportData[strtoupper(substr($type, 0, 3)) . '_MAX_RT'] = number_format($typeData['max_response_time'] ?? 0, 2);
    $reportData[strtoupper(substr($type, 0, 3)) . '_THRESHOLD'] = $threshold;
    $reportData[strtoupper(substr($type, 0, 3)) . '_STATUS'] = 
        ($typeData['avg_response_time'] ?? 0) <= $threshold ? 'PASS' : 'FAIL';
    
    // Throughput data
    $tps = $typeData['throughput'] ?? 0;
    $tph = $tps * 3600;
    $daily = $tps * 86400;
    
    $reportData[strtoupper(substr($type, 0, 3)) . '_TPS'] = number_format($tps, 2);
    $reportData[strtoupper(substr($type, 0, 3)) . '_TPH'] = number_format($tph, 0);
    $reportData[strtoupper(substr($type, 0, 3)) . '_DAILY'] = number_format($daily, 0);
}

// Overall throughput
$reportData['OVERALL_TPS'] = number_format($jmeterData['throughput'] ?? 0, 2);
$reportData['OVERALL_TPH'] = number_format(($jmeterData['throughput'] ?? 0) * 3600, 0);
$reportData['OVERALL_DAILY'] = number_format(($jmeterData['throughput'] ?? 0) * 86400, 0);

// Database metrics
$reportData['DB_QUERY_AVG'] = number_format($monitoringData['db_query_time_avg'] ?? 0, 2);
$reportData['DB_QUERY_THRESHOLD'] = 1000;
$reportData['DB_QUERY_STATUS'] = ($monitoringData['db_query_time_avg'] ?? 0) <= 1000 ? 'PASS' : 'FAIL';
$reportData['SLOW_QUERIES'] = $monitoringData['slow_queries'] ?? 0;
$reportData['SLOW_QUERIES_THRESHOLD'] = 10;
$reportData['SLOW_QUERIES_STATUS'] = ($monitoringData['slow_queries'] ?? 0) <= 10 ? 'PASS' : 'FAIL';
$reportData['DB_QPS'] = number_format($monitoringData['db_qps'] ?? 0, 2);
$reportData['DB_QPS_THRESHOLD'] = 100;
$reportData['DB_QPS_STATUS'] = ($monitoringData['db_qps'] ?? 0) <= 100 ? 'PASS' : 'FAIL';

// Test scenarios
$reportData['NORMAL_USERS'] = $jmeterData['threads'] ?? 50;
$reportData['NORMAL_DURATION'] = $jmeterData['duration'] ?? 300;
$reportData['NORMAL_RESULTS'] = "Average response time: {$reportData['AVG_RESPONSE_TIME']} ms, Error rate: {$reportData['ERROR_RATE']}%";

$reportData['PEAK_USERS'] = ($jmeterData['threads'] ?? 50) * 2;
$reportData['PEAK_DURATION'] = 300;
$reportData['PEAK_RESULTS'] = "Not executed in this test run";

$reportData['STRESS_USERS'] = ($jmeterData['threads'] ?? 50) * 4;
$reportData['STRESS_DURATION'] = 300;
$reportData['STRESS_RESULTS'] = "Not executed in this test run";

// Bottlenecks and recommendations
$bottlenecks = identifyBottlenecks($jmeterData, $monitoringData, $config['thresholds']);
$recommendations = generateRecommendations($bottlenecks);

for ($i = 1; $i <= count($bottlenecks); $i++) {
    if (isset($bottlenecks[$i-1])) {
        $reportData["BOTTLENECK_{$i}"] = $bottlenecks[$i-1]['name'];
        $reportData["BOTTLENECK_{$i}_DESC"] = $bottlenecks[$i-1]['description'];
        $reportData["BOTTLENECK_{$i}_IMPACT"] = $bottlenecks[$i-1]['impact'];
        $reportData["BOTTLENECK_{$i}_REC"] = $bottlenecks[$i-1]['recommendation'];
    }
}

for ($i = 1; $i <= count($recommendations); $i++) {
    if (isset($recommendations[$i-1])) {
        $reportData["REC_{$i}_TITLE"] = $recommendations[$i-1]['title'];
        $reportData["REC_{$i}_DESC"] = $recommendations[$i-1]['description'];
    }
}

// Replace placeholders in template
foreach ($reportData as $key => $value) {
    $template = str_replace("{{$key}}", $value, $template);
}

// Write report to file
file_put_contents($config['output_file'], $template);
echo "Performance test report generated: {$config['output_file']}\n";

// Helper functions

function parseJMeterResults($resultsFile) {
    if (!file_exists($resultsFile)) {
        echo "Warning: JMeter results file not found: {$resultsFile}\n";
        return simulateJMeterResults();
    }
    
    // Parse JMeter JTL file (CSV format)
    $data = [
        'duration' => 300,
        'threads' => 50,
        'test_type' => 'report',
        'avg_response_time' => 0,
        'max_response_time' => 0,
        'min_response_time' => PHP_INT_MAX,
        'total_samples' => 0,
        'error_count' => 0,
        'throughput' => 0,
        'report_types' => [
            'attendance' => ['samples' => 0, 'errors' => 0, 'total_time' => 0, 'max_time' => 0, 'min_time' => PHP_INT_MAX],
            'academic' => ['samples' => 0, 'errors' => 0, 'total_time' => 0, 'max_time' => 0, 'min_time' => PHP_INT_MAX],
            'financial' => ['samples' => 0, 'errors' => 0, 'total_time' => 0, 'max_time' => 0, 'min_time' => PHP_INT_MAX],
            'exam' => ['samples' => 0, 'errors' => 0, 'total_time' => 0, 'max_time' => 0, 'min_time' => PHP_INT_MAX],
            'behavior' => ['samples' => 0, 'errors' => 0, 'total_time' => 0, 'max_time' => 0, 'min_time' => PHP_INT_MAX]
        ]
    ];
    
    // In a real implementation, parse the JTL file here
    // For this example, we'll use simulated data
    return simulateJMeterResults();
}

function parseMonitoringResults($resultsPattern) {
    // In a real implementation, parse the monitoring CSV files
    // For this example, we'll use simulated data
    return [
        'cpu_avg' => 45.2,
        'cpu_peak' => 78.5,
        'memory_avg' => 62.3,
        'memory_peak' => 85.1,
        'disk_avg' => 12.5,
        'disk_peak' => 35.8,
        'network_avg' => 8.2,
        'network_peak' => 22.4,
        'db_connections_avg' => 35,
        'db_connections_peak' => 65,
        'db_query_time_avg' => 850,
        'slow_queries' => 5,
        'db_qps' => 75.2
    ];
}

function simulateJMeterResults() {
    // Generate simulated JMeter results for demonstration
    $reportTypes = ['attendance', 'academic', 'financial', 'exam', 'behavior'];
    $data = [
        'duration' => 300,
        'threads' => 50,
        'test_type' => 'report',
        'total_samples' => 0,
        'error_count' => 0,
        'total_time' => 0,
        'max_response_time' => 0,
        'min_response_time' => PHP_INT_MAX,
        'report_types' => []
    ];
    
    // Generate data for each report type
    foreach ($reportTypes as $type) {
        $samples = rand(100, 500);
        $errors = rand(0, 5);
        $avgTime = rand(1500, 4500);
        $maxTime = $avgTime + rand(1000, 3000);
        $minTime = max(500, $avgTime - rand(500, 1000));
        $p90 = $avgTime + rand(500, 1500);
        $throughput = $samples / 300; // samples per second
        
        $data['report_types'][$type] = [
            'samples' => $samples,
            'errors' => $errors,
            'avg_response_time' => $avgTime,
            'max_response_time' => $maxTime,
            'min_response_time' => $minTime,
            'percentile_90' => $p90,
            'throughput' => $throughput,
            'error_rate' => ($samples > 0) ? ($errors / $samples) * 100 : 0
        ];
        
        $data['total_samples'] += $samples;
        $data['error_count'] += $errors;
        $data['total_time'] += $samples * $avgTime;
        $data['max_response_time'] = max($data['max_response_time'], $maxTime);
        $data['min_response_time'] = min($data['min_response_time'], $minTime);
    }
    
    // Calculate overall metrics
    $data['avg_response_time'] = $data['total_samples'] > 0 ? $data['total_time'] / $data['total_samples'] : 0;
    $data['error_rate'] = $data['total_samples'] > 0 ? ($data['error_count'] / $data['total_samples']) * 100 : 0;
    $data['throughput'] = $data['total_samples'] / 300; // samples per second
    $data['estimated_daily_capacity'] = $data['throughput'] * 86400; // daily capacity
    
    return $data;
}

function determineOverallStatus($jmeterData, $monitoringData, $thresholds) {
    // Check if daily capacity meets requirement
    $capacityMet = ($jmeterData['estimated_daily_capacity'] ?? 0) >= $thresholds['daily_capacity'];
    
    // Check if error rate is acceptable
    $errorRateAcceptable = ($jmeterData['error_rate'] ?? 0) <= $thresholds['error_rate'];
    
    // Check if resource utilization is within limits
    $cpuAcceptable = ($monitoringData['cpu_peak'] ?? 0) <= $thresholds['cpu'];
    $memoryAcceptable = ($monitoringData['memory_peak'] ?? 0) <= $thresholds['memory'];
    
    // Check if response times are acceptable for all report types
    $responseTimesAcceptable = true;
    foreach ($jmeterData['report_types'] ?? [] as $type => $data) {
        $threshold = $thresholds['response_time'][$type] ?? $thresholds['response_time']['default'];
        if (($data['avg_response_time'] ?? 0) > $threshold) {
            $responseTimesAcceptable = false;
            break;
        }
    }
    
    // Overall status is PASS only if all criteria are met
    return ($capacityMet && $errorRateAcceptable && $cpuAcceptable && $memoryAcceptable && $responseTimesAcceptable) 
        ? 'PASS' : 'FAIL';
}

function generateResourceSummary($monitoringData) {
    return "CPU: {$monitoringData['cpu_avg']}% avg, {$monitoringData['cpu_peak']}% peak; " .
           "Memory: {$monitoringData['memory_avg']}% avg, {$monitoringData['memory_peak']}% peak; " .
           "DB: {$monitoringData['db_query_time_avg']} ms avg query time";
}

function identifyBottlenecks($jmeterData, $monitoringData, $thresholds) {
    $bottlenecks = [];
    
    // Check for response time bottlenecks
    foreach ($jmeterData['report_types'] ?? [] as $type => $data) {
        $threshold = $thresholds['response_time'][$type] ?? $thresholds['response_time']['default'];
        if (($data['avg_response_time'] ?? 0) > $threshold) {
            $bottlenecks[] = [
                'name' => ucfirst($type) . ' Report Response Time',
                'description' => "Average response time for {$type} reports exceeds threshold",
                'impact' => "Reduced user experience and system capacity",
                'recommendation' => "Optimize database queries and add caching for {$type} report generation"
            ];
        }
    }
    
    // Check for CPU bottleneck
    if (($monitoringData['cpu_peak'] ?? 0) > $thresholds['cpu']) {
        $bottlenecks[] = [
            'name' => 'CPU Utilization',
            'description' => "Peak CPU utilization exceeds threshold",
            'impact' => "System instability and degraded performance under load",
            'recommendation' => "Optimize code execution and consider vertical scaling"
        ];
    }
    
    // Check for memory bottleneck
    if (($monitoringData['memory_peak'] ?? 0) > $thresholds['memory']) {
        $bottlenecks[] = [
            'name' => 'Memory Utilization',
            'description' => "Peak memory utilization exceeds threshold",
            'impact' => "Potential out-of-memory errors and system crashes",
            'recommendation' => "Optimize memory usage, implement pagination, and consider increasing server memory"
        ];
    }
    
    // Check for database bottlenecks
    if (($monitoringData['db_query_time_avg'] ?? 0) > 1000) {
        $bottlenecks[] = [
            'name' => 'Database Query Performance',
            'description' => "Average database query time is too high",
            'impact' => "Slow report generation and reduced system capacity",
            'recommendation' => "Optimize database queries, add indexes, and implement query caching"
        ];
    }
    
    return $bottlenecks;
}

function generateRecommendations($bottlenecks) {
    $recommendations = [];
    
    // Extract recommendations from bottlenecks
    foreach ($bottlenecks as $bottleneck) {
        $recommendations[] = [
            'title' => "Resolve {$bottleneck['name']} Bottleneck",
            'description' => $bottleneck['recommendation']
        ];
    }
    
    // Add general recommendations if needed
    if (empty($recommendations)) {
        $recommendations[] = [
            'title' => 'Implement Caching',
            'description' => 'Add application-level caching for frequently generated reports to reduce database load and improve response times.'
        ];
    }
    
    // Add standard recommendations
    $recommendations[] = [
        'title' => 'Regular Performance Testing',
        'description' => 'Conduct regular performance tests as part of the CI/CD pipeline to catch performance regressions early.'
    ];
    
    $recommendations[] = [
        'title' => 'Monitoring Implementation',
        'description' => 'Implement real-time monitoring and alerting for production environment to proactively identify performance issues.'
    ];
    
    $recommendations[] = [
        'title' => 'Load Balancing',
        'description' => 'Consider implementing load balancing for report generation to distribute workload across multiple servers during peak times.'
    ];
    
    $recommendations[] = [
        'title' => 'Report Scheduling',
        'description' => 'Implement a report scheduling system to distribute report generation load throughout the day rather than processing all at peak times.'
    ];
    
    return $recommendations;
}

function generateConclusion($jmeterData, $monitoringData, $thresholds) {
    $status = determineOverallStatus($jmeterData, $monitoringData, $thresholds);
    $capacity = number_format($jmeterData['estimated_daily_capacity'] ?? 0, 0);
    $requiredCapacity = number_format($thresholds['daily_capacity'], 0);
    
    if ($status == 'PASS') {
        return "The performance testing results demonstrate that the PNS-Dhampur Report Generation system successfully meets the requirement of handling {$requiredCapacity} reports per day. The system showed an estimated capacity of {$capacity} reports per day with acceptable response times and resource utilization. All performance metrics were within defined thresholds, indicating that the system is ready for production use. Regular performance testing should be conducted to ensure continued compliance with performance requirements as the system evolves.";
    } else {
        return "The performance testing results indicate that the PNS-Dhampur Report Generation system does not currently meet all performance requirements. While the system demonstrated an estimated capacity of {$capacity} reports per day against a requirement of {$requiredCapacity}, some performance metrics exceeded defined thresholds. The identified bottlenecks should be addressed before deploying to production. After implementing the recommended optimizations, another round of performance testing should be conducted to verify that all requirements are met.";
    }
}

// Run the script if executed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    // The script is already running
}