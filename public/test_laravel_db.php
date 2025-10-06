<?php

// Laravel test with database queries
require_once __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Bootstrap Laravel
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    // Test database connection
    $dbTest = \DB::select('SELECT 1 as test');
    
    // Test BiometricDevice model basic query
    $deviceCount = \App\Models\BiometricDevice::count();
    
    // Test with relationship
    $devices = \App\Models\BiometricDevice::with('registeredBy:id,name')->limit(2)->get();
    
    $response = [
        'success' => true,
        'message' => 'Laravel database endpoint working',
        'timestamp' => date('Y-m-d H:i:s'),
        'database_test' => $dbTest[0]->test ?? 'failed',
        'device_count' => $deviceCount,
        'devices_with_relations' => $devices->count(),
        'server_info' => [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
        ]
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Laravel database error: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s'),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
?>