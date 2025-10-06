<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use App\Http\Controllers\BiometricController;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Direct BiometricController Test ===\n";
echo "Testing BiometricController::importData method directly...\n\n";

try {
    // Authenticate as admin user
    $adminUser = User::where('email', 'admin@pnsdhampur.local')->first();
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
    
    echo "1. Testing database query directly...\n";
    $startTime = microtime(true);
    
    // Test the exact query from the controller
    $query = BiometricDevice::with('registeredBy:id,name');
    $devices = $query->orderBy('device_name')->get();
    
    $queryTime = microtime(true) - $startTime;
    echo "✅ Database query completed in " . round($queryTime * 1000, 2) . " ms\n";
    echo "- Found " . $devices->count() . " devices\n\n";
    
    echo "2. Testing device method calls...\n";
    $methodStartTime = microtime(true);
    
    // Test the methods that are called on each device
    $devices->each(function ($device) {
        $device->is_online = $device->isOnline();
        $device->needs_maintenance = $device->needsMaintenance();
        $device->todays_scans = $device->getTodaysScanCount();
        $device->uptime_percentage = $device->getUptimePercentage();
    });
    
    $methodTime = microtime(true) - $methodStartTime;
    echo "✅ Device methods completed in " . round($methodTime * 1000, 2) . " ms\n\n";
    
    echo "3. Testing controller instantiation...\n";
    $controllerStartTime = microtime(true);
    
    // Check if services exist
    try {
        $deviceService = app(\App\Services\BiometricDeviceService::class);
        echo "✅ BiometricDeviceService loaded\n";
    } catch (\Exception $e) {
        echo "❌ BiometricDeviceService error: " . $e->getMessage() . "\n";
        $deviceService = null;
    }
    
    try {
        $realTimeProcessor = app(\App\Services\BiometricRealTimeProcessor::class);
        echo "✅ BiometricRealTimeProcessor loaded\n";
    } catch (\Exception $e) {
        echo "❌ BiometricRealTimeProcessor error: " . $e->getMessage() . "\n";
        $realTimeProcessor = null;
    }
    
    if ($deviceService && $realTimeProcessor) {
        $controller = new BiometricController($deviceService, $realTimeProcessor);
        echo "✅ BiometricController instantiated\n";
    } else {
        echo "❌ Cannot instantiate BiometricController due to missing services\n";
        exit(1);
    }
    
    $controllerTime = microtime(true) - $controllerStartTime;
    echo "Controller setup time: " . round($controllerTime * 1000, 2) . " ms\n\n";
    
    echo "4. Testing controller method directly...\n";
    $directStartTime = microtime(true);
    
    // Create a mock request
    $request = Request::create('/api/external/biometric/devices', 'GET');
    
    // Call the controller method directly
    $response = $controller->getRegisteredDevices($request);
    
    $directTime = microtime(true) - $directStartTime;
    echo "✅ Controller method completed in " . round($directTime * 1000, 2) . " ms\n";
    echo "- Response status: " . $response->getStatusCode() . "\n";
    echo "- Response size: " . strlen($response->getContent()) . " bytes\n\n";
    
    $totalTime = microtime(true) - $startTime;
    echo "=== Summary ===\n";
    echo "Total execution time: " . round($totalTime * 1000, 2) . " ms\n";
    echo "- Database query: " . round($queryTime * 1000, 2) . " ms (" . round(($queryTime / $totalTime) * 100, 1) . "%)\n";
    echo "- Device methods: " . round($methodTime * 1000, 2) . " ms (" . round(($methodTime / $totalTime) * 100, 1) . "%)\n";
    echo "- Controller setup: " . round($controllerTime * 1000, 2) . " ms (" . round(($controllerTime / $totalTime) * 100, 1) . "%)\n";
    echo "- Controller method: " . round($directTime * 1000, 2) . " ms (" . round(($directTime / $totalTime) * 100, 1) . "%)\n";
    
    // Parse response to show data
    $responseData = json_decode($response->getContent(), true);
    if ($responseData && isset($responseData['data'])) {
        echo "\n=== Response Data ===\n";
        echo "Success: " . ($responseData['success'] ? 'true' : 'false') . "\n";
        echo "Device count: " . count($responseData['data']) . "\n";
        if (isset($responseData['summary'])) {
            echo "Summary: " . json_encode($responseData['summary'], JSON_PRETTY_PRINT) . "\n";
        }
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}