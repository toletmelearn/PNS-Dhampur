<?php

echo "=== Testing BiometricController getRegisteredDevices Method ===\n\n";

// Bootstrap Laravel
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\BiometricController;
use App\Models\User;
use App\Models\BiometricDevice;

try {
    // Authenticate as admin user
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
    
    // Create BiometricController instance with dependencies
    $deviceService = app(\App\Services\BiometricDeviceService::class);
    $realTimeProcessor = app(\App\Services\BiometricRealTimeProcessor::class);
    $controller = new BiometricController($deviceService, $realTimeProcessor);
    
    // Create a mock request
    $request = Request::create('/api/external/biometric/devices', 'GET');
    $request->headers->set('Accept', 'application/json');
    $request->headers->set('User-Agent', 'Test-Client/1.0');
    
    echo "1. Testing BiometricController->getRegisteredDevices()...\n";
    
    $startTime = microtime(true);
    $startMemory = memory_get_usage(true);
    
    // Call the actual controller method
    $response = $controller->getRegisteredDevices($request);
    
    $endTime = microtime(true);
    $endMemory = memory_get_usage(true);
    
    echo "✅ Controller method executed successfully\n";
    echo "- Execution time: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
    echo "- Memory used: " . round(($endMemory - $startMemory) / 1024 / 1024, 2) . " MB\n";
    echo "- Response status: " . $response->getStatusCode() . "\n";
    
    // Get response content
    $responseContent = $response->getContent();
    $responseData = json_decode($responseContent, true);
    
    echo "- Response size: " . strlen($responseContent) . " bytes\n";
    
    if ($responseData) {
        echo "- Response structure:\n";
        if (isset($responseData['success'])) {
            echo "  * success: " . ($responseData['success'] ? 'true' : 'false') . "\n";
        }
        if (isset($responseData['message'])) {
            echo "  * message: " . $responseData['message'] . "\n";
        }
        if (isset($responseData['data'])) {
            echo "  * data count: " . (is_array($responseData['data']) ? count($responseData['data']) : 'not array') . "\n";
            
            // Show first device details if available
            if (is_array($responseData['data']) && count($responseData['data']) > 0) {
                $firstDevice = $responseData['data'][0];
                echo "  * first device keys: " . implode(', ', array_keys($firstDevice)) . "\n";
            }
        }
    } else {
        echo "- Response content (first 500 chars): " . substr($responseContent, 0, 500) . "\n";
    }
    
    echo "\n2. Testing multiple rapid calls to controller method...\n";
    
    for ($i = 1; $i <= 3; $i++) {
        $rapidRequest = Request::create('/api/external/biometric/devices', 'GET');
        $rapidRequest->headers->set('Accept', 'application/json');
        
        $rapidStartTime = microtime(true);
        
        try {
            $rapidResponse = $controller->getRegisteredDevices($rapidRequest);
            $rapidEndTime = microtime(true);
            
            echo "  Call $i: Status " . $rapidResponse->getStatusCode() . 
                 " (" . round(($rapidEndTime - $rapidStartTime) * 1000, 2) . " ms)\n";
        } catch (Exception $e) {
            $rapidEndTime = microtime(true);
            echo "  Call $i: Exception - " . $e->getMessage() . 
                 " (" . round(($rapidEndTime - $rapidStartTime) * 1000, 2) . " ms)\n";
        }
    }
    
    echo "\n3. Testing database queries directly...\n";
    
    // Test the database query that the controller uses
    $dbStartTime = microtime(true);
    
    $devices = BiometricDevice::all();
    
    $dbEndTime = microtime(true);
    
    echo "✅ Direct database query completed\n";
    echo "- Query time: " . round(($dbEndTime - $dbStartTime) * 1000, 2) . " ms\n";
    echo "- Devices found: " . $devices->count() . "\n";
    
    // Test individual device methods that might be slow
    if ($devices->count() > 0) {
        echo "\n4. Testing individual device methods...\n";
        
        $testDevice = $devices->first();
        echo "Testing device: " . $testDevice->device_name . " (ID: " . $testDevice->id . ")\n";
        
        // Test each method individually
        $methods = ['isOnline', 'needsMaintenance', 'getTodaysScanCount', 'getUptimePercentage'];
        
        foreach ($methods as $method) {
            $methodStartTime = microtime(true);
            
            try {
                $result = $testDevice->$method();
                $methodEndTime = microtime(true);
                
                echo "  * $method(): " . json_encode($result) . 
                     " (" . round(($methodEndTime - $methodStartTime) * 1000, 2) . " ms)\n";
            } catch (Exception $e) {
                $methodEndTime = microtime(true);
                echo "  * $method(): Exception - " . $e->getMessage() . 
                     " (" . round(($methodEndTime - $methodStartTime) * 1000, 2) . " ms)\n";
            }
        }
    }
    
    echo "\n5. Testing response serialization...\n";
    
    // Test if the response serialization is causing issues
    $serializationStartTime = microtime(true);
    
    $testResponse = response()->json([
        'success' => true,
        'message' => 'Devices retrieved successfully',
        'data' => $devices->map(function ($device) {
            return [
                'id' => $device->id,
                'device_name' => $device->device_name,
                'ip_address' => $device->ip_address,
                'port' => $device->port,
                'location' => $device->location,
                'is_active' => $device->is_active,
                'last_sync' => $device->last_sync,
                'status' => [
                    'online' => $device->isOnline(),
                    'needs_maintenance' => $device->needsMaintenance(),
                    'todays_scans' => $device->getTodaysScanCount(),
                    'uptime_percentage' => $device->getUptimePercentage()
                ]
            ];
        })->toArray()
    ]);
    
    $serializationEndTime = microtime(true);
    
    echo "✅ Response serialization completed\n";
    echo "- Serialization time: " . round(($serializationEndTime - $serializationStartTime) * 1000, 2) . " ms\n";
    echo "- Response status: " . $testResponse->getStatusCode() . "\n";
    echo "- Response size: " . strlen($testResponse->getContent()) . " bytes\n";
    
} catch (Exception $e) {
    echo "❌ Exception caught: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "❌ Fatal error caught: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";