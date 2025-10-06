<?php

echo "Testing Laravel API with Authentication\n";
echo "=====================================\n\n";

// Configuration
$baseUrl = 'http://127.0.0.1:8080';
$testCredentials = [
    'email' => 'admin@example.com',
    'password' => 'password'
];

// Function to make HTTP requests
function makeRequest($url, $method = 'GET', $data = null, $headers = []) {
    $context = [
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers),
            'ignore_errors' => true
        ]
    ];
    
    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        $context['http']['content'] = is_array($data) ? json_encode($data) : $data;
        if (!in_array('Content-Type: application/json', $headers)) {
            $context['http']['header'] .= "\r\nContent-Type: application/json";
        }
    }
    
    $response = @file_get_contents($url, false, stream_context_create($context));
    $headers = $http_response_header ?? [];
    
    return [
        'response' => $response,
        'headers' => $headers,
        'status_code' => isset($headers[0]) ? $headers[0] : 'No response'
    ];
}

// Test 1: Basic API test
echo "1. Testing basic API endpoint...\n";
$result = makeRequest("$baseUrl/api/test");
echo "Status: " . $result['status_code'] . "\n";
echo "Response: " . ($result['response'] ?: 'No response') . "\n\n";

// Test 2: Try to login
echo "2. Attempting to login...\n";
$loginData = $testCredentials;
$result = makeRequest("$baseUrl/api/login", 'POST', $loginData);
echo "Status: " . $result['status_code'] . "\n";
echo "Response: " . ($result['response'] ?: 'No response') . "\n";

$token = null;
if ($result['response']) {
    $loginResponse = json_decode($result['response'], true);
    if (isset($loginResponse['token'])) {
        $token = $loginResponse['token'];
        echo "✅ Login successful! Token obtained.\n";
    } else {
        echo "❌ Login failed or no token in response.\n";
    }
}
echo "\n";

// Test 3: Test external routes with authentication
if ($token) {
    $authHeaders = [
        'Authorization: Bearer ' . $token,
        'Accept: application/json',
        'Content-Type: application/json'
    ];
    
    echo "3. Testing external biometric devices route with authentication...\n";
    $result = makeRequest("$baseUrl/api/external/biometric/devices", 'GET', null, $authHeaders);
    echo "Status: " . $result['status_code'] . "\n";
    echo "Response: " . ($result['response'] ?: 'No response') . "\n\n";
    
    echo "4. Testing external biometric test-connection route...\n";
    $result = makeRequest("$baseUrl/api/external/biometric/test-connection/1", 'POST', [], $authHeaders);
    echo "Status: " . $result['status_code'] . "\n";
    echo "Response: " . ($result['response'] ?: 'No response') . "\n\n";
    
    echo "5. Testing external biometric stats route...\n";
    $result = makeRequest("$baseUrl/api/external/biometric/stats", 'GET', null, $authHeaders);
    echo "Status: " . $result['status_code'] . "\n";
    echo "Response: " . ($result['response'] ?: 'No response') . "\n\n";
} else {
    echo "3. Skipping authenticated tests - no token available.\n\n";
}

// Test 4: Check if we need to create a test user
echo "6. Testing user registration (if enabled)...\n";
$registerData = [
    'name' => 'Test Admin',
    'email' => 'testadmin@example.com',
    'password' => 'password123',
    'password_confirmation' => 'password123',
    'role' => 'admin'
];
$result = makeRequest("$baseUrl/api/register", 'POST', $registerData);
echo "Status: " . $result['status_code'] . "\n";
echo "Response: " . ($result['response'] ?: 'No response') . "\n\n";

echo "Authentication test completed.\n";