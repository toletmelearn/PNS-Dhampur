<?php

echo "=== Testing Different HTTP Clients ===\n\n";

// First, get the auth token
function getAuthToken() {
    $loginData = json_encode([
        'email' => 'admin@pns-dhampur.edu',
        'password' => 'password'
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8001/api/login');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $loginData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        return $data['token'] ?? null;
    }
    
    return null;
}

$token = getAuthToken();
if (!$token) {
    echo "❌ Failed to get auth token\n";
    exit(1);
}

echo "✅ Auth token obtained: " . substr($token, 0, 20) . "...\n\n";

// Test 1: Standard cURL
echo "1. Testing with standard cURL...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8001/api/external/biometric/devices');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

$startTime = microtime(true);
$response = curl_exec($ch);
$endTime = microtime(true);

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "  - HTTP Code: $httpCode\n";
echo "  - Time: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
echo "  - Error: " . ($error ?: 'None') . "\n";
echo "  - Response length: " . strlen($response) . " bytes\n\n";

// Test 2: cURL with different options
echo "2. Testing with cURL (HTTP/1.0, no keep-alive)...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8001/api/external/biometric/devices');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json',
    'Accept: application/json',
    'Connection: close'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
curl_setopt($ch, CURLOPT_FORBID_REUSE, true);

$startTime = microtime(true);
$response = curl_exec($ch);
$endTime = microtime(true);

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "  - HTTP Code: $httpCode\n";
echo "  - Time: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
echo "  - Error: " . ($error ?: 'None') . "\n";
echo "  - Response length: " . strlen($response) . " bytes\n\n";

// Test 3: Using file_get_contents
echo "3. Testing with file_get_contents...\n";
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        'timeout' => 10
    ]
]);

$startTime = microtime(true);
$response = @file_get_contents('http://127.0.0.1:8001/api/external/biometric/devices', false, $context);
$endTime = microtime(true);

$error = error_get_last();
echo "  - Time: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
echo "  - Success: " . ($response !== false ? 'Yes' : 'No') . "\n";
echo "  - Response length: " . strlen($response ?: '') . " bytes\n";
if ($error && strpos($error['message'], 'file_get_contents') !== false) {
    echo "  - Error: " . $error['message'] . "\n";
}
echo "\n";

// Test 4: Using Guzzle HTTP (if available)
if (class_exists('GuzzleHttp\Client')) {
    echo "4. Testing with Guzzle HTTP...\n";
    try {
        $client = new \GuzzleHttp\Client(['timeout' => 10]);
        
        $startTime = microtime(true);
        $response = $client->get('http://127.0.0.1:8001/api/external/biometric/devices', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]
        ]);
        $endTime = microtime(true);
        
        echo "  - HTTP Code: " . $response->getStatusCode() . "\n";
        echo "  - Time: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
        echo "  - Response length: " . strlen($response->getBody()) . " bytes\n";
        
    } catch (Exception $e) {
        echo "  - Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
} else {
    echo "4. Guzzle HTTP not available\n\n";
}

echo "=== Test Complete ===\n";