<?php

echo "=== Testing BiometricController Directly ===\n\n";

// Bootstrap Laravel
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\BiometricController;
use App\Models\User;

try {
    // Find and authenticate as admin user
    $adminUser = User::where('email', 'admin@pns-dhampur.edu')->first();
    if (!$adminUser) {
        $adminUser = User::where('role', 'admin')->first();
    }
    
    if ($adminUser) {
        Auth::login($adminUser);
        echo "✅ Authenticated as: " . Auth::user()->email . " (Role: " . Auth::user()->role . ")\n\n";
    } else {
        echo "❌ No admin user found!\n";
        exit(1);
    }
    
    // Test 1: Direct controller instantiation
    echo "1. Testing BiometricController instantiation:\n";
    
    $startTime = microtime(true);
    // Create BiometricController instance with dependencies
    $deviceService = app(\App\Services\BiometricDeviceService::class);
    $realTimeProcessor = app(\App\Services\BiometricRealTimeProcessor::class);
    $controller = new BiometricController($deviceService, $realTimeProcessor);
    $endTime = microtime(true);
    
    echo "  ✅ Controller instantiated successfully\n";
    echo "  - Instantiation time: " . round(($endTime - $startTime) * 1000, 2) . " ms\n\n";
    
    // Test 2: Test importData method with minimal data
    echo "2. Testing importData method with minimal real_time data:\n";
    
    $request = Request::create('/api/external/biometric/import-data', 'POST');
    $request->headers->set('Accept', 'application/json');
    $request->headers->set('Content-Type', 'application/json');
    
    // Minimal real_time data
    $request->merge([
        'type' => 'real_time',
        'data' => [
            [
                'employee_id' => 'TEST001',
                'timestamp' => now()->toISOString(),
                'event_type' => 'check_in',
                'device_id' => 'TEST_DEVICE'
            ]
        ]
    ]);
    
    echo "  - Request data: " . json_encode($request->all()) . "\n";
    
    $startTime = microtime(true);
    
    try {
        $response = $controller->importData($request);
        $endTime = microtime(true);
        
        echo "  ✅ importData method executed successfully\n";
        echo "  - Execution time: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
        echo "  - Response status: " . $response->getStatusCode() . "\n";
        echo "  - Response content: " . $response->getContent() . "\n\n";
        
    } catch (\Exception $e) {
        $endTime = microtime(true);
        echo "  ❌ Exception in importData: " . $e->getMessage() . "\n";
        echo "  - Execution time: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
        echo "  - File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        echo "  - Stack trace:\n" . $e->getTraceAsString() . "\n\n";
    }
    
    // Test 3: Test with invalid type
    echo "3. Testing importData method with invalid type:\n";
    
    $invalidRequest = Request::create('/api/external/biometric/import-data', 'POST');
    $invalidRequest->headers->set('Accept', 'application/json');
    $invalidRequest->headers->set('Content-Type', 'application/json');
    $invalidRequest->merge([
        'type' => 'invalid_type',
        'data' => []
    ]);
    
    $startTime = microtime(true);
    
    try {
        $response = $controller->importData($invalidRequest);
        $endTime = microtime(true);
        
        echo "  ✅ importData method handled invalid type\n";
        echo "  - Execution time: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
        echo "  - Response status: " . $response->getStatusCode() . "\n";
        echo "  - Response content: " . $response->getContent() . "\n\n";
        
    } catch (\Exception $e) {
        $endTime = microtime(true);
        echo "  ❌ Exception with invalid type: " . $e->getMessage() . "\n";
        echo "  - Execution time: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
        echo "  - File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    }
    
    // Test 4: Test empty data
    echo "4. Testing importData method with empty data:\n";
    
    $emptyRequest = Request::create('/api/external/biometric/import-data', 'POST');
    $emptyRequest->headers->set('Accept', 'application/json');
    $emptyRequest->headers->set('Content-Type', 'application/json');
    $emptyRequest->merge([
        'type' => 'real_time',
        'data' => []
    ]);
    
    $startTime = microtime(true);
    
    try {
        $response = $controller->importData($emptyRequest);
        $endTime = microtime(true);
        
        echo "  ✅ importData method handled empty data\n";
        echo "  - Execution time: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
        echo "  - Response status: " . $response->getStatusCode() . "\n";
        echo "  - Response content: " . $response->getContent() . "\n\n";
        
    } catch (\Exception $e) {
        $endTime = microtime(true);
        echo "  ❌ Exception with empty data: " . $e->getMessage() . "\n";
        echo "  - Execution time: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
        echo "  - File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    }
    
    // Test 5: Check database connection within controller context
    echo "5. Testing database operations:\n";
    
    try {
        $deviceCount = \App\Models\BiometricDevice::count();
        echo "  ✅ Database connection working\n";
        echo "  - Biometric devices count: $deviceCount\n";
        
        $teacherCount = \App\Models\Teacher::count();
        echo "  - Teachers count: $teacherCount\n";
        
    } catch (\Exception $e) {
        echo "  ❌ Database error: " . $e->getMessage() . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Fatal exception: " . $e->getMessage() . "\n";
    echo "- File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "- Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (\Error $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
    echo "- File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== Test Complete ===\n";