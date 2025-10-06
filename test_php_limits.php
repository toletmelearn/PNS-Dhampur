<?php

echo "=== PHP Configuration Check ===\n\n";

echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "Max Execution Time: " . ini_get('max_execution_time') . " seconds\n";
echo "Max Input Time: " . ini_get('max_input_time') . " seconds\n";
echo "Post Max Size: " . ini_get('post_max_size') . "\n";
echo "Upload Max Filesize: " . ini_get('upload_max_filesize') . "\n";
echo "Default Socket Timeout: " . ini_get('default_socket_timeout') . " seconds\n";

echo "\nPHP Version: " . phpversion() . "\n";
echo "Current Memory Usage: " . memory_get_usage(true) / 1024 / 1024 . " MB\n";
echo "Peak Memory Usage: " . memory_get_peak_usage(true) / 1024 / 1024 . " MB\n";

echo "\n=== Testing Memory Intensive Operation ===\n";

// Simulate what might happen in the BiometricController
try {
    $startMemory = memory_get_usage(true);
    $startTime = microtime(true);
    
    // Create a large array to simulate memory usage
    $data = [];
    for ($i = 0; $i < 10000; $i++) {
        $data[] = [
            'id' => $i,
            'device_id' => 'DEVICE_' . $i,
            'device_name' => 'Test Device ' . $i,
            'status' => 'online',
            'last_heartbeat_at' => date('Y-m-d H:i:s'),
            'configuration' => json_encode(['timeout' => 30, 'retry' => 3])
        ];
    }
    
    $endTime = microtime(true);
    $endMemory = memory_get_usage(true);
    
    echo "✅ Created 10,000 records successfully\n";
    echo "Time taken: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
    echo "Memory used: " . round(($endMemory - $startMemory) / 1024 / 1024, 2) . " MB\n";
    
    unset($data);
    
} catch (Exception $e) {
    echo "❌ Memory test failed: " . $e->getMessage() . "\n";
}

echo "\n=== Testing Database Connection Pool ===\n";

try {
    require_once __DIR__ . '/vendor/autoload.php';
    
    $app = require_once __DIR__ . '/bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    // Test multiple database connections
    for ($i = 0; $i < 5; $i++) {
        $result = \Illuminate\Support\Facades\DB::select('SELECT 1 as test');
        echo "Connection test " . ($i + 1) . ": ✅\n";
        usleep(100000); // 100ms delay
    }
    
} catch (Exception $e) {
    echo "❌ Database connection test failed: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";