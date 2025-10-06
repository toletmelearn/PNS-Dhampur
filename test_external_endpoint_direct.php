<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Http\Request;
use App\Http\Controllers\BiometricController;

// Bootstrap Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a mock request for biometric devices endpoint
$request = Request::create('/api/external/biometric/devices', 'GET');

try {
    echo "=== Testing BiometricController directly (bypassing middleware) ===\n";
    
    // Create controller instance with dependencies
    $deviceService = app(\App\Services\BiometricDeviceService::class);
    $realTimeProcessor = app(\App\Services\BiometricRealTimeProcessor::class);
    $controller = new BiometricController($deviceService, $realTimeProcessor);
    
    // Test getRegisteredDevices method directly
    echo "1. Testing getRegisteredDevices method...\n";
    $response = $controller->getRegisteredDevices();
    echo "Response status: " . $response->getStatusCode() . "\n";
    echo "Response content: " . $response->getContent() . "\n\n";
    
    // Test importData method with minimal data
    echo "2. Testing importData method...\n";
    $importRequest = Request::create('/api/external/biometric/import-data', 'POST', [], [], [], [], 
        json_encode(['real_time' => [['employee_id' => '12345', 'timestamp' => '2025-01-01 10:00:00']]])
    );
    $importRequest->headers->set('Content-Type', 'application/json');
    
    $importResponse = $controller->importData($importRequest);
    echo "Import response status: " . $importResponse->getStatusCode() . "\n";
    echo "Import response content: " . $importResponse->getContent() . "\n\n";
    
    echo "=== Direct controller test completed successfully ===\n";
    
} catch (Exception $e) {
    echo "Error during direct controller test: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}