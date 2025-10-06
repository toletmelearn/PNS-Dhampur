<?php

echo "=== Testing Biometric Endpoint Without Middleware ===\n\n";

// Bootstrap Laravel without middleware
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Create a mock request
$request = Illuminate\Http\Request::create('/api/external/biometric/devices', 'GET');

// Add authorization header
$token = '17|cBcVr2nHx877N53M4Ej8Qs1234567890'; // Mock token for testing
$request->headers->set('Authorization', 'Bearer ' . $token);
$request->headers->set('Accept', 'application/json');

echo "1. Testing BiometricController directly (bypassing middleware)...\n";

try {
    // Create controller with dependencies
    $biometricDeviceService = app(\App\Services\BiometricDeviceService::class);
    $biometricRealTimeProcessor = app(\App\Services\BiometricRealTimeProcessor::class);
    
    $controller = new \App\Http\Controllers\BiometricController(
        $biometricDeviceService,
        $biometricRealTimeProcessor
    );
    
    $startTime = microtime(true);
    $response = $controller->getRegisteredDevices($request);
    $endTime = microtime(true);
    
    echo "  - Status Code: " . $response->getStatusCode() . "\n";
    echo "  - Time: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
    echo "  - Memory Used: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB\n";
    echo "  - Response Headers:\n";
    
    foreach ($response->headers->all() as $name => $values) {
        echo "    * $name: " . implode(', ', $values) . "\n";
    }
    
    $content = $response->getContent();
    echo "  - Response Length: " . strlen($content) . " bytes\n";
    echo "  - Response Preview: " . substr($content, 0, 200) . "...\n";
    
} catch (Exception $e) {
    echo "  - Error: " . $e->getMessage() . "\n";
    echo "  - File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n2. Testing with SecurityHeadersMiddleware applied...\n";

try {
    // Apply SecurityHeadersMiddleware
    $middleware = new \App\Http\Middleware\SecurityHeadersMiddleware();
    
    $startTime = microtime(true);
    
    $response = $middleware->handle($request, function($req) use ($controller) {
        return $controller->getRegisteredDevices($req);
    });
    
    $endTime = microtime(true);
    
    echo "  - Status Code: " . $response->getStatusCode() . "\n";
    echo "  - Time: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
    echo "  - Memory Used: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB\n";
    echo "  - Response Headers:\n";
    
    foreach ($response->headers->all() as $name => $values) {
        echo "    * $name: " . implode(', ', $values) . "\n";
    }
    
    $content = $response->getContent();
    echo "  - Response Length: " . strlen($content) . " bytes\n";
    echo "  - Response Preview: " . substr($content, 0, 200) . "...\n";
    
} catch (Exception $e) {
    echo "  - Error: " . $e->getMessage() . "\n";
    echo "  - File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n3. Testing response serialization...\n";

try {
    // Test if the response can be properly serialized
    $controller = new \App\Http\Controllers\BiometricController(
        app(\App\Services\BiometricDeviceService::class),
        app(\App\Services\BiometricRealTimeProcessor::class)
    );
    
    $response = $controller->getRegisteredDevices($request);
    $content = $response->getContent();
    
    // Try to parse JSON
    $data = json_decode($content, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "  - JSON Valid: Yes\n";
        echo "  - Data Structure: " . print_r(array_keys($data), true);
    } else {
        echo "  - JSON Valid: No\n";
        echo "  - JSON Error: " . json_last_error_msg() . "\n";
    }
    
    // Test response streaming
    echo "  - Testing response streaming...\n";
    ob_start();
    echo $content;
    $output = ob_get_clean();
    echo "  - Streamed Length: " . strlen($output) . " bytes\n";
    
} catch (Exception $e) {
    echo "  - Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";