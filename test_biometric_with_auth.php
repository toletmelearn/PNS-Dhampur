<?php

echo "=== Biometric API Test with Authentication ===\n";

// Test credentials from the codebase
$credentials = [
    'email' => 'admin@example.com',
    'password' => 'password'
];

$baseUrl = 'http://127.0.0.1:8001';

function makeRequest($url, $method = 'GET', $data = null, $headers = []) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER => array_merge([
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: BiometricTest/1.0'
        ], $headers),
        CURLOPT_VERBOSE => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_COOKIEJAR => 'cookies.txt',
        CURLOPT_COOKIEFILE => 'cookies.txt'
    ]);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
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

// Step 1: Test basic connectivity
echo "1. Testing basic server connectivity...\n";
$result = makeRequest("$baseUrl/api/test");
echo "- HTTP Code: {$result['http_code']}\n";
echo "- Error: " . ($result['error'] ?: 'None') . "\n";
echo "- Response: " . substr($result['response'], 0, 100) . "...\n\n";

// Step 2: Get CSRF token first
echo "2. Getting CSRF token...\n";
$result = makeRequest("$baseUrl/sanctum/csrf-cookie");
echo "- HTTP Code: {$result['http_code']}\n";
echo "- Error: " . ($result['error'] ?: 'None') . "\n\n";

// Step 3: Attempt login
echo "3. Testing login endpoint...\n";
$result = makeRequest("$baseUrl/api/login", 'POST', $credentials);
echo "- HTTP Code: {$result['http_code']}\n";
echo "- Error: " . ($result['error'] ?: 'None') . "\n";

if ($result['http_code'] == 200) {
    echo "- Login successful!\n";
    $loginData = json_decode($result['response'], true);
    $token = $loginData['token'] ?? null;
    echo "- Token: " . ($token ? substr($token, 0, 20) . '...' : 'Not found') . "\n";
    
    // Step 4: Test biometric endpoint with authentication
    echo "\n4. Testing biometric endpoint with authentication...\n";
    $biometricData = [
        'device_id' => 'TEST_DEVICE_001',
        'employee_id' => 'EMP001',
        'timestamp' => date('Y-m-d H:i:s'),
        'event_type' => 'check_in',
        'data' => [
            'fingerprint_template' => 'test_template_data',
            'confidence_score' => 95.5
        ]
    ];
    
    $headers = $token ? ["Authorization: Bearer $token"] : [];
    $result = makeRequest("$baseUrl/api/external/biometric/import-data", 'POST', $biometricData, $headers);
    echo "- HTTP Code: {$result['http_code']}\n";
    echo "- Error: " . ($result['error'] ?: 'None') . "\n";
    echo "- Response: " . substr($result['response'], 0, 200) . "...\n";
    
    // Step 5: Test with larger payload
    echo "\n5. Testing with larger biometric payload...\n";
    $largeBiometricData = [
        'device_id' => 'TEST_DEVICE_002',
        'employee_id' => 'EMP002',
        'timestamp' => date('Y-m-d H:i:s'),
        'event_type' => 'check_out',
        'data' => [
            'fingerprint_template' => str_repeat('A', 1000), // 1KB of data
            'confidence_score' => 88.2,
            'additional_data' => str_repeat('B', 2000) // 2KB more
        ]
    ];
    
    $result = makeRequest("$baseUrl/api/external/biometric/import-data", 'POST', $largeBiometricData, $headers);
    echo "- HTTP Code: {$result['http_code']}\n";
    echo "- Error: " . ($result['error'] ?: 'None') . "\n";
    echo "- Response: " . substr($result['response'], 0, 200) . "...\n";
    
} else {
    echo "- Login failed with HTTP code: {$result['http_code']}\n";
    echo "- Response: " . substr($result['response'], 0, 200) . "...\n";
}

// Clean up
if (file_exists('cookies.txt')) {
    unlink('cookies.txt');
}

echo "\n=== Test Complete ===\n";