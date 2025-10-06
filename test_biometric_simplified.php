<?php

echo "=== Simplified Biometric Endpoint Test ===\n";

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Controllers\BiometricController;
use Illuminate\Http\Request;

try {
    echo "1. Setting up controller and services...\n";
    $deviceService = app(\App\Services\BiometricDeviceService::class);
    $processor = app(\App\Services\BiometricRealTimeProcessor::class);
    $controller = new BiometricController($deviceService, $processor);
    echo "✅ Controller setup complete\n\n";
    
    echo "2. Testing import-data endpoint with minimal real_time data...\n";
    
    // Create a minimal request for real_time import
    $requestData = [
        'type' => 'real_time',
        'device_id' => 'TEST_DEVICE_001',
        'employee_id' => '12345',
        'timestamp' => date('Y-m-d H:i:s'),
        'event_type' => 'check_in',
        'verification_method' => 'fingerprint',
        'confidence_score' => 95.5,
        'location' => 'Main Entrance'
    ];
    
    $request = new Request($requestData);
    
    echo "Request data:\n";
    foreach ($requestData as $key => $value) {
        echo "  $key: $value\n";
    }
    echo "\n";
    
    echo "3. Calling importData method directly...\n";
    $startTime = microtime(true);
    
    try {
        $response = $controller->importData($request);
        $endTime = microtime(true);
        
        echo "✅ Method executed successfully\n";
        echo "Execution time: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
        echo "Response status: " . $response->getStatusCode() . "\n";
        
        $responseData = json_decode($response->getContent(), true);
        echo "Response data:\n";
        echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
        
    } catch (\Exception $e) {
        $endTime = microtime(true);
        echo "❌ Method execution failed\n";
        echo "Execution time: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
        echo "Error: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        
        // Show more detailed error info
        if ($e->getPrevious()) {
            echo "Previous error: " . $e->getPrevious()->getMessage() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Setup error:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}