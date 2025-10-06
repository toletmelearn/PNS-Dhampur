<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Controllers\BiometricController;
use Illuminate\Http\Request;

echo "=== Direct BiometricController Test ===\n\n";

try {
    // Create a mock request
    $request = new Request();
    
    // Create controller instance with dependencies
    $deviceService = app(\App\Services\BiometricDeviceService::class);
    $realTimeProcessor = app(\App\Services\BiometricRealTimeProcessor::class);
    $controller = new BiometricController($deviceService, $realTimeProcessor);
    
    echo "1. Testing getRegisteredDevices method directly...\n";
    
    // Enable error reporting
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    // Set memory limit monitoring
    $startMemory = memory_get_usage(true);
    $startTime = microtime(true);
    
    echo "Starting memory: " . round($startMemory / 1024 / 1024, 2) . " MB\n";
    
    // Call the method that's causing issues
    $response = $controller->getRegisteredDevices($request);
    
    $endTime = microtime(true);
    $endMemory = memory_get_usage(true);
    
    echo "✅ Method executed successfully!\n";
    echo "Execution time: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
    echo "Memory used: " . round(($endMemory - $startMemory) / 1024 / 1024, 2) . " MB\n";
    echo "Final memory: " . round($endMemory / 1024 / 1024, 2) . " MB\n";
    
    // Get response content
    $content = $response->getContent();
    $data = json_decode($content, true);
    
    if ($data) {
        echo "Response status: " . $response->getStatusCode() . "\n";
        echo "Number of devices returned: " . (isset($data['data']) ? count($data['data']) : 'N/A') . "\n";
        
        if (isset($data['data']) && count($data['data']) > 0) {
            echo "First device sample:\n";
            $firstDevice = $data['data'][0];
            foreach ($firstDevice as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    echo "  $key: " . json_encode($value) . "\n";
                } else {
                    echo "  $key: $value\n";
                }
            }
        }
    } else {
        echo "Response content: " . substr($content, 0, 500) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception caught: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "❌ Fatal error caught: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";