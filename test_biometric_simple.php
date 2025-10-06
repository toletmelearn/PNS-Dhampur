<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Http\Request;
use App\Http\Controllers\BiometricController;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Simple BiometricController Test ===\n";
echo "Testing BiometricController::importData method directly...\n\n";

try {
    // Create controller instance
    $controller = app(BiometricController::class);
    
    // Test 1: Test with invalid type (should return 400)
    echo "1. Testing with invalid type:\n";
    $request = Request::create('/test', 'POST');
    $request->merge([
        'type' => 'invalid_type',
        'data' => []
    ]);
    
    $startTime = microtime(true);
    $response = $controller->importData($request);
    $endTime = microtime(true);
    
    echo "  - Duration: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
    echo "  - Status Code: " . $response->getStatusCode() . "\n";
    echo "  - Response: " . $response->getContent() . "\n\n";
    
    // Test 2: Test with real_time type but missing required fields (should return 422)
    echo "2. Testing real_time type with missing fields:\n";
    $request2 = Request::create('/test', 'POST');
    $request2->merge([
        'type' => 'real_time',
        'device_id' => 'TEST_DEVICE',
        'employee_id' => 'TEST001'
        // Missing timestamp and event_type
    ]);
    
    $startTime = microtime(true);
    $response2 = $controller->importData($request2);
    $endTime = microtime(true);
    
    echo "  - Duration: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
    echo "  - Status Code: " . $response2->getStatusCode() . "\n";
    echo "  - Response: " . $response2->getContent() . "\n\n";
    
    // Test 3: Test with minimal valid real_time data
    echo "3. Testing with minimal valid real_time data:\n";
    $request3 = Request::create('/test', 'POST');
    $request3->merge([
        'type' => 'real_time',
        'device_id' => 'TEST_DEVICE',
        'employee_id' => 'TEST001',
        'timestamp' => now()->toISOString(),
        'event_type' => 'check_in'
    ]);
    
    $startTime = microtime(true);
    $response3 = $controller->importData($request3);
    $endTime = microtime(true);
    
    echo "  - Duration: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
    echo "  - Status Code: " . $response3->getStatusCode() . "\n";
    echo "  - Response: " . $response3->getContent() . "\n\n";
    
    echo "✅ Direct controller test completed successfully\n";
    
} catch (\Exception $e) {
    echo "❌ Exception occurred: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";