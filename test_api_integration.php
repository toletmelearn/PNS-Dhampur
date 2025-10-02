<?php

// Test API Integration Script
echo "=== PNS Dhampur API Integration Test ===\n\n";

$baseUrl = 'http://127.0.0.1:8000/api';

// Function to make HTTP requests
function makeRequest($url, $method = 'GET', $data = null, $headers = []) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
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

// Test 1: API Test Endpoint
echo "1. Testing API Test Endpoint...\n";
$result = makeRequest($baseUrl . '/test');
echo "Status Code: " . $result['http_code'] . "\n";
echo "Response: " . $result['response'] . "\n";
echo "Error: " . ($result['error'] ?: 'None') . "\n\n";

// Test 2: Login Endpoint
echo "2. Testing Login Endpoint...\n";
$loginData = json_encode([
    'email' => 'admin@pns-dhampur.edu',
    'password' => 'password'
]);

$result = makeRequest(
    $baseUrl . '/login',
    'POST',
    $loginData,
    ['Content-Type: application/json']
);

echo "Status Code: " . $result['http_code'] . "\n";
echo "Response: " . $result['response'] . "\n";
echo "Error: " . ($result['error'] ?: 'None') . "\n\n";

// Parse login response to get token
$loginResponse = json_decode($result['response'], true);
$token = null;

if ($result['http_code'] === 200 && isset($loginResponse['token'])) {
    $token = $loginResponse['token'];
    echo "✓ Login successful! Token obtained.\n\n";
} else {
    echo "✗ Login failed or no token received.\n\n";
}

// Test 3: Protected Endpoint (Bell Timings)
echo "3. Testing Protected Endpoint (Bell Timings)...\n";
if ($token) {
    $result = makeRequest(
        $baseUrl . '/bell-timings',
        'GET',
        null,
        [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ]
    );
    
    echo "Status Code: " . $result['http_code'] . "\n";
    echo "Response: " . substr($result['response'], 0, 200) . "...\n";
    echo "Error: " . ($result['error'] ?: 'None') . "\n\n";
} else {
    echo "Skipping - No token available\n\n";
}

// Test 4: Current Bell Schedule (used by Flutter app)
echo "4. Testing Current Bell Schedule Endpoint...\n";
if ($token) {
    $result = makeRequest(
        $baseUrl . '/bell-timings/schedule/current',
        'GET',
        null,
        [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ]
    );
    
    echo "Status Code: " . $result['http_code'] . "\n";
    echo "Response: " . $result['response'] . "\n";
    echo "Error: " . ($result['error'] ?: 'None') . "\n\n";
} else {
    echo "Skipping - No token available\n\n";
}

// Test 5: Bell Notification Check (used by Flutter app)
echo "5. Testing Bell Notification Check Endpoint...\n";
if ($token) {
    $result = makeRequest(
        $baseUrl . '/bell-timings/notification/check',
        'GET',
        null,
        [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ]
    );
    
    echo "Status Code: " . $result['http_code'] . "\n";
    echo "Response: " . $result['response'] . "\n";
    echo "Error: " . ($result['error'] ?: 'None') . "\n\n";
} else {
    echo "Skipping - No token available\n\n";
}

echo "=== API Integration Test Complete ===\n";