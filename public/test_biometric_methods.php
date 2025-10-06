<?php

// Test BiometricDevice methods that are called in getRegisteredDevices
require_once __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Bootstrap Laravel
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    // Get devices with relationships (same as controller)
    $query = \App\Models\BiometricDevice::with('registeredBy:id,name');
    $devices = $query->orderBy('device_name')->get();
    
    $results = [];
    $methodTimes = [];
    
    // Test each method that's called in the controller
    foreach ($devices as $index => $device) {
        $deviceResult = [
            'device_id' => $device->device_id,
            'device_name' => $device->device_name,
            'methods' => []
        ];
        
        // Test isOnline method
        $startTime = microtime(true);
        try {
            $isOnline = $device->isOnline();
            $methodTime = microtime(true) - $startTime;
            $deviceResult['methods']['isOnline'] = [
                'result' => $isOnline,
                'time_ms' => round($methodTime * 1000, 2),
                'success' => true
            ];
            $methodTimes['isOnline'][] = $methodTime;
        } catch (Exception $e) {
            $deviceResult['methods']['isOnline'] = [
                'error' => $e->getMessage(),
                'success' => false
            ];
        }
        
        // Test needsMaintenance method
        $startTime = microtime(true);
        try {
            $needsMaintenance = $device->needsMaintenance();
            $methodTime = microtime(true) - $startTime;
            $deviceResult['methods']['needsMaintenance'] = [
                'result' => $needsMaintenance,
                'time_ms' => round($methodTime * 1000, 2),
                'success' => true
            ];
            $methodTimes['needsMaintenance'][] = $methodTime;
        } catch (Exception $e) {
            $deviceResult['methods']['needsMaintenance'] = [
                'error' => $e->getMessage(),
                'success' => false
            ];
        }
        
        // Test getTodaysScanCount method (this is likely the problematic one)
        $startTime = microtime(true);
        try {
            $scanCount = $device->getTodaysScanCount();
            $methodTime = microtime(true) - $startTime;
            $deviceResult['methods']['getTodaysScanCount'] = [
                'result' => $scanCount,
                'time_ms' => round($methodTime * 1000, 2),
                'success' => true
            ];
            $methodTimes['getTodaysScanCount'][] = $methodTime;
        } catch (Exception $e) {
            $deviceResult['methods']['getTodaysScanCount'] = [
                'error' => $e->getMessage(),
                'success' => false
            ];
        }
        
        // Test getUptimePercentage method
        $startTime = microtime(true);
        try {
            $uptime = $device->getUptimePercentage();
            $methodTime = microtime(true) - $startTime;
            $deviceResult['methods']['getUptimePercentage'] = [
                'result' => $uptime,
                'time_ms' => round($methodTime * 1000, 2),
                'success' => true
            ];
            $methodTimes['getUptimePercentage'][] = $methodTime;
        } catch (Exception $e) {
            $deviceResult['methods']['getUptimePercentage'] = [
                'error' => $e->getMessage(),
                'success' => false
            ];
        }
        
        $results[] = $deviceResult;
    }
    
    // Calculate average times
    $averageTimes = [];
    foreach ($methodTimes as $method => $times) {
        $averageTimes[$method] = [
            'average_ms' => round(array_sum($times) / count($times) * 1000, 2),
            'max_ms' => round(max($times) * 1000, 2),
            'min_ms' => round(min($times) * 1000, 2),
            'count' => count($times)
        ];
    }
    
    $response = [
        'success' => true,
        'message' => 'BiometricDevice methods test completed',
        'timestamp' => date('Y-m-d H:i:s'),
        'device_count' => $devices->count(),
        'results' => $results,
        'performance_summary' => $averageTimes,
        'server_info' => [
            'php_version' => PHP_VERSION,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ]
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'BiometricDevice methods error: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s'),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
?>