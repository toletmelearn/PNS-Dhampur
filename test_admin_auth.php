<?php

// Test script to authenticate as admin and test external routes
$baseUrl = 'http://127.0.0.1:8080';
$outputFile = 'admin_auth_test_results.txt';

// Function to make HTTP requests with cURL
function makeRequest($url, $method = 'GET', $data = null, $headers = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
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
    curl_close($ch);
    
    return [
        'response' => $response,
        'http_code' => $httpCode,
        'error' => $error
    ];
}

// Start output buffering
ob_start();

echo "=== Admin Authentication and External Routes Test ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

// Test 1: Basic API endpoint
echo "1. Testing basic API endpoint...\n";
$result = makeRequest($baseUrl . '/api/test');
echo "Status: " . $result['http_code'] . "\n";
if ($result['error']) {
    echo "Error: " . $result['error'] . "\n";
} else {
    $headerSize = curl_getinfo_header_size($result['response']);
    $body = substr($result['response'], $headerSize);
    echo "Response: " . $body . "\n";
}
echo "\n";

// Test 2: Login as admin
echo "2. Attempting admin login...\n";
$loginData = json_encode([
    'email' => 'admin@pnsdhampur.local',
    'password' => 'Password123'
]);

$headers = [
    'Content-Type: application/json',
    'Accept: application/json'
];

$result = makeRequest($baseUrl . '/api/login', 'POST', $loginData, $headers);
echo "Status: " . $result['http_code'] . "\n";

$token = null;
if ($result['error']) {
    echo "Error: " . $result['error'] . "\n";
} else {
    // Parse response to extract token
    $headerSize = strpos($result['response'], "\r\n\r\n");
    if ($headerSize !== false) {
        $body = substr($result['response'], $headerSize + 4);
        $responseData = json_decode($body, true);
        
        if (isset($responseData['token'])) {
            $token = $responseData['token'];
            echo "Login successful! Token obtained.\n";
            echo "Token: " . substr($token, 0, 20) . "...\n";
        } else {
            echo "Login response: " . $body . "\n";
        }
    }
}
echo "\n";

// Test 3: Test external biometric devices route with authentication
if ($token) {
    echo "3. Testing external biometric devices route with authentication...\n";
    
    $authHeaders = [
        'Authorization: Bearer ' . $token,
        'Accept: application/json'
    ];
    
    $result = makeRequest($baseUrl . '/api/external/biometric/devices', 'GET', null, $authHeaders);
    echo "Status: " . $result['http_code'] . "\n";
    
    if ($result['error']) {
        echo "Error: " . $result['error'] . "\n";
    } else {
        $headerSize = strpos($result['response'], "\r\n\r\n");
        if ($headerSize !== false) {
            $body = substr($result['response'], $headerSize + 4);
            echo "Response: " . $body . "\n";
        }
    }
} else {
    echo "3. Skipping external route test - no authentication token available\n";
}
echo "\n";

// Test 4: Test external test-connection route
if ($token) {
    echo "4. Testing external test-connection route with authentication...\n";
    
    $authHeaders = [
        'Authorization: Bearer ' . $token,
        'Accept: application/json'
    ];
    
    $result = makeRequest($baseUrl . '/api/external/test-connection', 'GET', null, $authHeaders);
    echo "Status: " . $result['http_code'] . "\n";
    
    if ($result['error']) {
        echo "Error: " . $result['error'] . "\n";
    } else {
        $headerSize = strpos($result['response'], "\r\n\r\n");
        if ($headerSize !== false) {
            $body = substr($result['response'], $headerSize + 4);
            echo "Response: " . $body . "\n";
        }
    }
} else {
    echo "4. Skipping external test-connection - no authentication token available\n";
}

echo "\n=== Test Complete ===\n";

// Helper function to get header size (since curl_getinfo is not available in this context)
function curl_getinfo_header_size($response) {
    return strpos($response, "\r\n\r\n") + 4;
}

// Save output to file
$output = ob_get_clean();
file_put_contents($outputFile, $output);

echo $output;
echo "\nResults saved to: " . $outputFile . "\n";