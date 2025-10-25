<?php
/**
 * Performance Monitoring Script
 * 
 * This script collects and records performance metrics during test execution.
 * It can be run alongside JMeter tests to gather system-level metrics.
 */

// Configuration
$interval = 5; // seconds between measurements
$outputFile = 'performance_metrics.csv';
$duration = isset($argv[1]) ? (int)$argv[1] : 300; // default 5 minutes
$testName = isset($argv[2]) ? $argv[2] : 'performance_test_' . date('Y-m-d_H-i-s');

// Initialize output file
$fh = fopen($outputFile, 'w');
fputcsv($fh, [
    'timestamp', 
    'test_name',
    'cpu_usage_percent', 
    'memory_usage_percent', 
    'disk_usage_percent',
    'network_rx_bytes',
    'network_tx_bytes',
    'mysql_connections',
    'mysql_queries_per_second',
    'php_memory_usage_mb'
]);

echo "Starting performance monitoring for $duration seconds...\n";
echo "Test name: $testName\n";
echo "Recording metrics every $interval seconds\n";
echo "Output file: $outputFile\n\n";

$startTime = time();
$endTime = $startTime + $duration;

$lastNetworkRx = 0;
$lastNetworkTx = 0;

// Main monitoring loop
while (time() < $endTime) {
    $timestamp = date('Y-m-d H:i:s');
    
    // Get CPU usage
    $cpuUsage = getCpuUsage();
    
    // Get memory usage
    $memoryUsage = getMemoryUsage();
    
    // Get disk usage
    $diskUsage = getDiskUsage();
    
    // Get network usage
    list($networkRx, $networkTx) = getNetworkUsage();
    $networkRxDelta = $lastNetworkRx > 0 ? $networkRx - $lastNetworkRx : 0;
    $networkTxDelta = $lastNetworkTx > 0 ? $networkTx - $lastNetworkTx : 0;
    $lastNetworkRx = $networkRx;
    $lastNetworkTx = $networkTx;
    
    // Get MySQL metrics
    list($mysqlConnections, $mysqlQps) = getMySQLMetrics();
    
    // Get PHP memory usage
    $phpMemory = getPhpMemoryUsage();
    
    // Write metrics to file
    fputcsv($fh, [
        $timestamp,
        $testName,
        $cpuUsage,
        $memoryUsage,
        $diskUsage,
        $networkRxDelta,
        $networkTxDelta,
        $mysqlConnections,
        $mysqlQps,
        $phpMemory
    ]);
    
    // Print current metrics
    echo "[$timestamp] CPU: {$cpuUsage}%, Mem: {$memoryUsage}%, MySQL QPS: {$mysqlQps}\n";
    
    // Wait for next interval
    sleep($interval);
}

fclose($fh);
echo "\nPerformance monitoring completed. Results saved to $outputFile\n";

/**
 * Get CPU usage percentage
 */
function getCpuUsage() {
    if (PHP_OS_FAMILY === 'Windows') {
        $cmd = 'wmic cpu get LoadPercentage';
        $output = [];
        exec($cmd, $output);
        if (isset($output[1])) {
            return (int)trim($output[1]);
        }
        return 0;
    } else {
        $load = sys_getloadavg();
        $cores = trim(shell_exec("grep -c ^processor /proc/cpuinfo"));
        return round(($load[0] / $cores) * 100, 2);
    }
}

/**
 * Get memory usage percentage
 */
function getMemoryUsage() {
    if (PHP_OS_FAMILY === 'Windows') {
        $cmd = 'wmic OS get FreePhysicalMemory,TotalVisibleMemorySize /Value';
        $output = [];
        exec($cmd, $output);
        
        $total = 0;
        $free = 0;
        
        foreach ($output as $line) {
            if (strpos($line, 'TotalVisibleMemorySize') !== false) {
                $total = (int)trim(explode('=', $line)[1]);
            }
            if (strpos($line, 'FreePhysicalMemory') !== false) {
                $free = (int)trim(explode('=', $line)[1]);
            }
        }
        
        if ($total > 0) {
            return round(($total - $free) / $total * 100, 2);
        }
        return 0;
    } else {
        $memInfo = file_get_contents('/proc/meminfo');
        preg_match('/MemTotal:\s+(\d+)/', $memInfo, $totalMatches);
        preg_match('/MemAvailable:\s+(\d+)/', $memInfo, $availableMatches);
        
        if (isset($totalMatches[1]) && isset($availableMatches[1])) {
            $total = (int)$totalMatches[1];
            $available = (int)$availableMatches[1];
            $used = $total - $available;
            return round(($used / $total) * 100, 2);
        }
        return 0;
    }
}

/**
 * Get disk usage percentage
 */
function getDiskUsage() {
    if (PHP_OS_FAMILY === 'Windows') {
        $drive = 'C:';
        $total = disk_total_space($drive);
        $free = disk_free_space($drive);
        return round(($total - $free) / $total * 100, 2);
    } else {
        $output = [];
        exec('df / | tail -1', $output);
        $parts = preg_split('/\s+/', trim($output[0]));
        return isset($parts[4]) ? (int)$parts[4] : 0;
    }
}

/**
 * Get network usage in bytes
 */
function getNetworkUsage() {
    if (PHP_OS_FAMILY === 'Windows') {
        $cmd = 'netstat -e';
        $output = [];
        exec($cmd, $output);
        
        $rx = 0;
        $tx = 0;
        
        if (count($output) >= 4) {
            $parts = preg_split('/\s+/', trim($output[4]));
            if (count($parts) >= 3) {
                $rx = (int)$parts[1];
                $tx = (int)$parts[2];
            }
        }
        
        return [$rx, $tx];
    } else {
        $output = [];
        exec("cat /proc/net/dev | grep eth0", $output);
        
        if (!empty($output)) {
            $parts = preg_split('/\s+/', trim($output[0]));
            $rx = isset($parts[1]) ? (int)$parts[1] : 0;
            $tx = isset($parts[9]) ? (int)$parts[9] : 0;
            return [$rx, $tx];
        }
        
        return [0, 0];
    }
}

/**
 * Get MySQL metrics
 */
function getMySQLMetrics() {
    // This is a simplified version - in production, you would use proper credentials
    $connections = 0;
    $qps = 0;
    
    try {
        $db = new PDO('mysql:host=localhost;dbname=information_schema', 'root', '');
        
        // Get connections
        $stmt = $db->query("SHOW STATUS LIKE 'Threads_connected'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $connections = isset($result['Value']) ? (int)$result['Value'] : 0;
        
        // Get queries per second (approximation)
        $stmt = $db->query("SHOW STATUS LIKE 'Questions'");
        $result1 = $stmt->fetch(PDO::FETCH_ASSOC);
        $queries1 = isset($result1['Value']) ? (int)$result1['Value'] : 0;
        
        sleep(1);
        
        $stmt = $db->query("SHOW STATUS LIKE 'Questions'");
        $result2 = $stmt->fetch(PDO::FETCH_ASSOC);
        $queries2 = isset($result2['Value']) ? (int)$result2['Value'] : 0;
        
        $qps = $queries2 - $queries1;
    } catch (PDOException $e) {
        // Database connection failed
    }
    
    return [$connections, $qps];
}

/**
 * Get PHP memory usage in MB
 */
function getPhpMemoryUsage() {
    return round(memory_get_usage(true) / 1024 / 1024, 2);
}