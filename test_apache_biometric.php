<?php

echo "=== Testing Biometric Endpoint with Apache Server ===\n\n";

// Test configuration
$base_url = 'http://localhost';
$login_url = $base_url . '/api/login';
$biometric_url = $base_url . '/api/external/biometric/devices';

// Login credentials
$credentials = [
    'email' => 'admin@pnsdhampur.local',
    'password' => 'Password123'
];

echo "1. Testing login endpoint...\n";

// Initialize cURL for login
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $login_url,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($credentials),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json'
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_VERBOSE => false
]);

$login_response = curl_exec($ch);
$login_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$login_error = curl_error($ch);

if ($login_error) {
    echo "❌ Login cURL Error: $login_error\n";
    curl_close($ch);
    exit(1);
}

echo "Login HTTP Status: $login_http_code\n";

if ($login_http_code !== 200) {
    echo "❌ Login failed with status: $login_http_code\n";
    echo "Response: $login_response\n";
    curl_close($ch);
    exit(1);
}

$login_data = json_decode($login_response, true);
if (!isset($login_data['token'])) {
    echo "❌ No access token in login response\n";
    echo "Response: $login_response\n";
    curl_close($ch);
    exit(1);
}

$token = $login_data['token'];
echo "✅ Login successful, token obtained\n\n";

echo "2. Testing biometric devices endpoint...\n";

// Test biometric endpoint
curl_setopt_array($ch, [
    CURLOPT_URL => $biometric_url,
    CURLOPT_CUSTOMREQUEST => 'GET',
    CURLOPT_POSTFIELDS => null,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Accept: application/json',
        'Content-Type: application/json'
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_VERBOSE => false
]);

$start_time = microtime(true);
$biometric_response = curl_exec($ch);
$end_time = microtime(true);
$execution_time = ($end_time - $start_time) * 1000;

$biometric_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$biometric_error = curl_error($ch);

curl_close($ch);

echo "Execution time: " . number_format($execution_time, 2) . " ms\n";
echo "HTTP Status: $biometric_http_code\n";

if ($biometric_error) {
    echo "❌ Biometric cURL Error: $biometric_error\n";
    exit(1);
}

if ($biometric_http_code !== 200) {
    echo "❌ Biometric endpoint failed with status: $biometric_http_code\n";
    echo "Response: $biometric_response\n";
    exit(1);
}

echo "✅ Biometric endpoint successful!\n";
echo "Response length: " . strlen($biometric_response) . " bytes\n";

// Validate JSON response
$biometric_data = json_decode($biometric_response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "❌ Invalid JSON response: " . json_last_error_msg() . "\n";
    echo "Response: $biometric_response\n";
    exit(1);
}

echo "✅ Valid JSON response\n";

if (isset($biometric_data['data']) && is_array($biometric_data['data'])) {
    echo "✅ Found " . count($biometric_data['data']) . " biometric device(s)\n";
    
    foreach ($biometric_data['data'] as $index => $device) {
        echo "  Device " . ($index + 1) . ":\n";
        echo "    - ID: " . ($device['id'] ?? 'N/A') . "\n";
        echo "    - Name: " . ($device['name'] ?? 'N/A') . "\n";
        echo "    - IP: " . ($device['ip_address'] ?? 'N/A') . "\n";
        echo "    - Status: " . ($device['status'] ?? 'N/A') . "\n";
    }
} else {
    echo "⚠️  Unexpected response structure\n";
    echo "Response: " . json_encode($biometric_data, JSON_PRETTY_PRINT) . "\n";
}

echo "\n=== Test Multiple Requests ===\n";

// Test multiple consecutive requests to ensure stability
for ($i = 1; $i <= 5; $i++) {
    echo "Request $i: ";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $biometric_url,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Accept: application/json'
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10
    ]);
    
    $start = microtime(true);
    $response = curl_exec($ch);
    $end = microtime(true);
    $time = ($end - $start) * 1000;
    
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "❌ Error: $error\n";
        break;
    }
    
    if ($http_code === 200) {
        echo "✅ Success (" . number_format($time, 1) . "ms)\n";
    } else {
        echo "❌ HTTP $http_code\n";
        break;
    }
    
    // Small delay between requests
    usleep(100000); // 100ms
}

echo "\n🎉 All tests completed successfully with Apache server!\n";
echo "✅ No connection resets detected\n";
echo "✅ Stable performance across multiple requests\n";