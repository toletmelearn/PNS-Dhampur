<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Test the BiometricController directly
try {
    echo "=== TESTING BIOMETRIC CONTROLLER DIRECTLY ===\n";
    
    // Create a mock request
    $request = new Illuminate\Http\Request();
    
    // Create controller instance with dependencies
    $deviceService = app(App\Services\BiometricDeviceService::class);
    $realTimeProcessor = app(App\Services\BiometricRealTimeProcessor::class);
    $controller = new App\Http\Controllers\BiometricController($deviceService, $realTimeProcessor);
    
    // Call the method directly with request parameter
    $response = $controller->getRegisteredDevices($request);
    
    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Response Content:\n";
    echo json_encode($response->getData(), JSON_PRETTY_PRINT) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}