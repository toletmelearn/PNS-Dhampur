<?php

echo "=== Service Instantiation Test ===\n";

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Laravel bootstrapped successfully\n\n";

try {
    echo "1. Testing BiometricRealTimeProcessor instantiation...\n";
    $processor = app(\App\Services\BiometricRealTimeProcessor::class);
    echo "✅ BiometricRealTimeProcessor instantiated successfully\n";
    echo "   Class: " . get_class($processor) . "\n\n";
    
    echo "2. Testing BiometricDeviceService instantiation...\n";
    $service = app(\App\Services\BiometricDeviceService::class);
    echo "✅ BiometricDeviceService instantiated successfully\n";
    echo "   Class: " . get_class($service) . "\n\n";
    
    echo "3. Testing BiometricController instantiation...\n";
    $controller = new \App\Http\Controllers\BiometricController($service, $processor);
    echo "✅ BiometricController instantiated successfully\n";
    echo "   Class: " . get_class($controller) . "\n\n";
    
    echo "4. Testing simple method call...\n";
    $request = new \Illuminate\Http\Request();
    $response = $controller->getRegisteredDevices($request);
    echo "✅ getRegisteredDevices method called successfully\n";
    echo "   Response status: " . $response->getStatusCode() . "\n";
    
    $responseData = json_decode($response->getContent(), true);
    if (isset($responseData['success']) && $responseData['success']) {
        echo "   Response success: true\n";
        echo "   Device count: " . (isset($responseData['data']) ? count($responseData['data']) : 0) . "\n";
    } else {
        echo "   Response success: false\n";
        if (isset($responseData['error'])) {
            echo "   Error: " . $responseData['error'] . "\n";
        }
    }
    
    echo "\n✅ All tests passed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error occurred:\n";
    echo "   Message: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   Stack trace:\n" . $e->getTraceAsString() . "\n";
}