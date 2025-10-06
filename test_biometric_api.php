<?php

echo "=== Biometric API Test ===\n";
echo "Testing biometric API endpoint with authentication...\n\n";

// Test 1: Test endpoint without authentication
echo "1. Testing endpoint without authentication:\n";
$start = microtime(true);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1/api/external/biometric/devices');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_VERBOSE, false);
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

$end = microtime(true);
$duration = ($end - $start) * 1000;

echo "HTTP Code: $httpCode\n";
echo "Duration: " . number_format($duration, 2) . " ms\n";
if ($error) {
    echo "cURL Error: $error\n";
}
echo "Response:\n$response\n\n";

// Test 2: Try to login first and get a token
echo "2. Testing login to get authentication token:\n";
$start = microtime(true);

$loginData = json_encode([
    'email' => 'admin@pnsdhampur.local',
    'password' => 'Password123'
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1/api/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $loginData);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$loginResponse = curl_exec($ch);
$loginHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$loginError = curl_error($ch);
curl_close($ch);

$end = microtime(true);
$loginDuration = ($end - $start) * 1000;

echo "Login HTTP Code: $loginHttpCode\n";
echo "Login Duration: " . number_format($loginDuration, 2) . " ms\n";
if ($loginError) {
    echo "Login cURL Error: $loginError\n";
}
echo "Login Response: $loginResponse\n\n";

// Test 3: If login successful, try biometric endpoint with token
$loginData = json_decode($loginResponse, true);
if ($loginHttpCode == 200 && isset($loginData['token'])) {
    echo "3. Testing biometric endpoint with authentication token:\n";
    $token = $loginData['token'];
    
    $start = microtime(true);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1/api/external/biometric/devices');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Accept: application/json',
        'Content-Type: application/json'
    ]);
    
    $authResponse = curl_exec($ch);
    $authHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $authError = curl_error($ch);
    curl_close($ch);
    
    $end = microtime(true);
    $authDuration = ($end - $start) * 1000;
    
    echo "Authenticated HTTP Code: $authHttpCode\n";
    echo "Authenticated Duration: " . number_format($authDuration, 2) . " ms\n";
    if ($authError) {
        echo "Authenticated cURL Error: $authError\n";
    }
    echo "Authenticated Response: $authResponse\n\n";
} else {
    echo "3. Login failed, cannot test authenticated endpoint\n\n";
}

// Test 4: Test the simple endpoint for comparison
echo "4. Testing simple endpoint for performance comparison:\n";
$start = microtime(true);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1/test_simple_endpoint.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

$simpleResponse = curl_exec($ch);
$simpleHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$simpleError = curl_error($ch);
curl_close($ch);

$end = microtime(true);
$simpleDuration = ($end - $start) * 1000;

echo "Simple HTTP Code: $simpleHttpCode\n";
echo "Simple Duration: " . number_format($simpleDuration, 2) . " ms\n";
if ($simpleError) {
    echo "Simple cURL Error: $simpleError\n";
}
echo "Simple Response: $simpleResponse\n\n";

echo "=== Test Complete ===\n";
