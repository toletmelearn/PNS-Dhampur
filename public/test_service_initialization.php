<?php

echo "=== Testing Service Initialization Times ===\n\n";

// Bootstrap Laravel
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\BiometricDeviceService;
use App\Services\BiometricRealTimeProcessor;

try {
    echo "1. Testing BiometricDeviceService initialization...\n";
    
    $startTime = microtime(true);
    $deviceService = app(BiometricDeviceService::class);
    $endTime = microtime(true);
    
    echo "✅ BiometricDeviceService initialized\n";
    echo "- Initialization time: " . round(($endTime - $startTime) * 1000, 2) . " ms\n\n";
    
    echo "2. Testing BiometricRealTimeProcessor initialization...\n";
    
    $startTime = microtime(true);
    $realTimeProcessor = app(BiometricRealTimeProcessor::class);
    $endTime = microtime(true);
    
    echo "✅ BiometricRealTimeProcessor initialized\n";
    echo "- Initialization time: " . round(($endTime - $startTime) * 1000, 2) . " ms\n\n";
    
    echo "3. Testing multiple service initializations...\n";
    
    for ($i = 1; $i <= 3; $i++) {
        $startTime = microtime(true);
        $service1 = app(BiometricDeviceService::class);
        $service2 = app(BiometricRealTimeProcessor::class);
        $endTime = microtime(true);
        
        echo "  Iteration $i: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
    }
    
    echo "\n4. Testing config loading performance...\n";
    
    $configKeys = [
        'attendance.school_start_time',
        'attendance.school_end_time', 
        'attendance.minimum_working_hours',
        'attendance.grace_minutes',
        'attendance.overtime_threshold'
    ];
    
    foreach ($configKeys as $key) {
        $startTime = microtime(true);
        $value = config($key, 'default');
        $endTime = microtime(true);
        
        echo "  config('$key'): $value (" . round(($endTime - $startTime) * 1000, 2) . " ms)\n";
    }
    
    echo "\n5. Testing Laravel service container performance...\n";
    
    $startTime = microtime(true);
    for ($i = 0; $i < 10; $i++) {
        $service = app(BiometricDeviceService::class);
    }
    $endTime = microtime(true);
    
    echo "10 service resolutions: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
    echo "Average per resolution: " . round(($endTime - $startTime) * 1000 / 10, 2) . " ms\n\n";
    
    echo "6. Testing database connection performance...\n";
    
    $startTime = microtime(true);
    $connection = \Illuminate\Support\Facades\DB::connection();
    $endTime = microtime(true);
    
    echo "Database connection: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
    
    $startTime = microtime(true);
    $result = \Illuminate\Support\Facades\DB::select('SELECT 1 as test');
    $endTime = microtime(true);
    
    echo "Simple query: " . round(($endTime - $startTime) * 1000, 2) . " ms\n\n";
    
    echo "7. Testing cache performance...\n";
    
    $startTime = microtime(true);
    \Illuminate\Support\Facades\Cache::put('test_key', 'test_value', 60);
    $endTime = microtime(true);
    
    echo "Cache put: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
    
    $startTime = microtime(true);
    $value = \Illuminate\Support\Facades\Cache::get('test_key');
    $endTime = microtime(true);
    
    echo "Cache get: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
    
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