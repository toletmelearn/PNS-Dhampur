<?php

// Minimal Laravel test endpoint
$startTime = microtime(true);

// Bootstrap Laravel with minimal setup
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // Minimal response without full Laravel stack
    $response = [
        'status' => 'success',
        'message' => 'Minimal Laravel endpoint',
        'timestamp' => date('Y-m-d H:i:s'),
        'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
        'php_version' => PHP_VERSION,
        'laravel_loaded' => class_exists('Illuminate\Foundation\Application')
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
    ]);
}