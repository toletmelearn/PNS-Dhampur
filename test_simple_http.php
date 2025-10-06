<?php

echo "=== Simple HTTP Connection Test ===\n";

// Test different request sizes and types to isolate the connection reset issue

$baseUrl = 'http://127.0.0.1';
$endpoints = [
    '/api/test' => 'GET',
    '/api/external/biometric/devices' => 'GET',
    '/api/external/biometric/import-data' => 'POST'
];

// Get auth token first
echo "Getting authentication token...\n";
$loginData = json_encode([
    'email' => 'admin@pnsdhampur.local',
    'password' => 'Password123'
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/api/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $loginData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

$loginResponse = curl_exec($ch);
$loginHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$loginError = curl_error($ch);
curl_close($ch);

$token = null;
if (!$loginError && $loginHttpCode == 200) {
    $loginData = json_decode($loginResponse, true);
    if (isset($loginData['token'])) {
        $token = $loginData['token'];
        echo "✅ Login successful\n\n";
    }
} else {
    echo "❌ Login failed: HTTP $loginHttpCode, Error: $loginError\n";
    exit(1);
}

// Test 1: Simple GET request
echo "1. Testing simple GET /api/test...\n";
testEndpoint($baseUrl . '/api/test', 'GET', [], null);

// Test 2: Authenticated GET request
echo "\n2. Testing authenticated GET /api/external/biometric/devices...\n";
testEndpoint($baseUrl . '/api/external/biometric/devices', 'GET', [
    'Authorization: Bearer ' . $token,
    'Accept: application/json'
], null);

// Test 3: Small POST request
echo "\n3. Testing small POST request...\n";
$smallData = json_encode(['test' => 'data']);
testEndpoint($baseUrl . '/api/external/biometric/import-data', 'POST', [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json',
    'Accept: application/json'
], $smallData);

// Test 4: Medium POST request
echo "\n4. Testing medium POST request...\n";
$mediumData = json_encode([
    'real_time' => [
        [
            'employee_id' => '12345',
            'timestamp' => '2024-01-15 09:00:00',
            'event_type' => 'check_in',
            'device_id' => 'BIO001'
        ]
    ]
]);
testEndpoint($baseUrl . '/api/external/biometric/import-data', 'POST', [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json',
    'Accept: application/json'
], $mediumData);

// Test 5: Large POST request
echo "\n5. Testing large POST request...\n";
$largeRealTimeData = [];
for ($i = 0; $i < 100; $i++) {
    $largeRealTimeData[] = [
        'employee_id' => '12345',
        'timestamp' => '2024-01-15 09:' . sprintf('%02d', $i % 60) . ':00',
        'event_type' => ($i % 2 == 0) ? 'check_in' : 'check_out',
        'device_id' => 'BIO001'
    ];
}
$largeData = json_encode(['real_time' => $largeRealTimeData]);
testEndpoint($baseUrl . '/api/external/biometric/import-data', 'POST', [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json',
    'Accept: application/json'
], $largeData);

function testEndpoint($url, $method, $headers, $data) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
    }
    
    $startTime = microtime(true);
    $response = curl_exec($ch);
    $endTime = microtime(true);
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    $executionTime = round(($endTime - $startTime) * 1000, 2);
    
    echo "  URL: $url\n";
    echo "  Method: $method\n";
    echo "  Data size: " . (is_null($data) ? '0' : strlen($data)) . " bytes\n";
    echo "  Time: {$executionTime} ms\n";
    echo "  HTTP Code: $httpCode\n";
    echo "  Error: " . ($error ?: 'None') . "\n";
    echo "  Response size: " . strlen($response) . " bytes\n";
    
    if ($error) {
        echo "  ❌ Connection failed: $error\n";
    } elseif ($httpCode >= 200 && $httpCode < 300) {
        echo "  ✅ Success\n";
    } else {
        echo "  ⚠️  HTTP Error: $httpCode\n";
        echo "  Response: " . substr($response, 0, 200) . "\n";
    }
}

echo "\n=== Test completed ===\n";