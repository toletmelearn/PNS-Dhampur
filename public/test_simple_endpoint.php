<?php

// Simple test endpoint without Laravel middleware
$startTime = microtime(true);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

echo json_encode([
    'status' => 'success',
    'message' => 'Simple endpoint test',
    'timestamp' => date('Y-m-d H:i:s'),
    'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
]);