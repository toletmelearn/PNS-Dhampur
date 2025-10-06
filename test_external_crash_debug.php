<?php

// Debug script to test external routes and identify crash causes
$baseUrl = 'http://127.0.0.1:8080';
$outputFile = 'external_crash_debug_results.txt';

// Function to make HTTP requests with detailed error reporting
function makeRequest($url, $method = 'GET', $data = null, $headers = [], $timeout = 10) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    
    // Capture verbose output
    $verboseHandle = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verboseHandle);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
    }
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $info = curl_getinfo($ch);
    
    // Get verbose output
    rewind($verboseHandle);
    $verboseLog = stream_get_contents($verboseHandle);
    fclose($verboseHandle);
    
    curl_close($ch);
    
    return [
        'response' => $response,
        'http_code' => $httpCode,
        'error' => $error,
        'info' => $info,
        'verbose' => $verboseLog
    ];
}

// Start output buffering
ob_start();

echo "=== External Routes Crash Debug Test ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

// First, get authentication token
echo "Step 1: Getting authentication token...\n";
$loginData = json_encode([
    'email' => 'admin@pnsdhampur.local',
    'password' => 'Password123'
]);

$headers = [
    'Content-Type: application/json',
    'Accept: application/json'
];

$result = makeRequest($baseUrl . '/api/login', 'POST', $loginData, $headers);
echo "Login Status: " . $result['http_code'] . "\n";

$token = null;
if ($result['http_code'] == 200 && !$result['error']) {
    $headerSize = strpos($result['response'], "\r\n\r\n");
    if ($headerSize !== false) {
        $body = substr($result['response'], $headerSize + 4);
        $responseData = json_decode($body, true);
        
        if (isset($responseData['token'])) {
            $token = $responseData['token'];
            echo "Token obtained successfully.\n";
        }
    }
}

if (!$token) {
    echo "Failed to get authentication token. Stopping test.\n";
    echo "Login error: " . $result['error'] . "\n";
    echo "Login response: " . $result['response'] . "\n";
    exit(1);
}

echo "\n";

// Test external routes one by one with detailed debugging
$externalRoutes = [
    '/api/external/test-connection',
    '/api/external/biometric/devices',
    '/api/external/biometric/import',
    '/api/external/aadhaar/verify'
];

$authHeaders = [
    'Authorization: Bearer ' . $token,
    'Accept: application/json'
];

foreach ($externalRoutes as $index => $route) {
    echo "Step " . ($index + 2) . ": Testing route: $route\n";
    echo "Making request with 5-second timeout...\n";
    
    $startTime = microtime(true);
    $result = makeRequest($baseUrl . $route, 'GET', null, $authHeaders, 5);
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);
    
    echo "Request duration: {$duration}ms\n";
    echo "HTTP Status: " . $result['http_code'] . "\n";
    
    if ($result['error']) {
        echo "cURL Error: " . $result['error'] . "\n";
    }
    
    if ($result['http_code'] > 0) {
        $headerSize = strpos($result['response'], "\r\n\r\n");
        if ($headerSize !== false) {
            $body = substr($result['response'], $headerSize + 4);
            echo "Response Body: " . substr($body, 0, 200) . "\n";
        }
    }
    
    // Show connection info
    echo "Connection Info:\n";
    echo "  - Total Time: " . $result['info']['total_time'] . "s\n";
    echo "  - Connect Time: " . $result['info']['connect_time'] . "s\n";
    echo "  - Namelookup Time: " . $result['info']['namelookup_time'] . "s\n";
    
    if (!empty($result['verbose'])) {
        echo "Verbose Log (first 300 chars):\n";
        echo substr($result['verbose'], 0, 300) . "\n";
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
    
    // Add delay between requests to avoid overwhelming the server
    sleep(1);
}

echo "=== Debug Test Complete ===\n";

// Save output to file
$output = ob_get_clean();
file_put_contents($outputFile, $output);

echo $output;
echo "\nResults saved to: " . $outputFile . "\n";