<?php

echo "=== Simple API Test ===\n";

// Test 1: Basic connectivity
echo "1. Testing basic connectivity...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:8080/api/test");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'User-Agent: PNS-Dhampur-Test-Client/1.0'
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "Status: $httpCode\n";
echo "Response: $response\n";
echo "Error: $error\n\n";

// Test 2: Login
echo "2. Testing login...\n";
$loginData = json_encode([
    'email' => 'admin@example.com',
    'password' => 'password'
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:8080/api/v1/login");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $loginData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'User-Agent: PNS-Dhampur-Test-Client/1.0'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$loginResponse = curl_exec($ch);
$loginHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$loginError = curl_error($ch);
curl_close($ch);

echo "Login Status: $loginHttpCode\n";
echo "Login Response: $loginResponse\n";
echo "Login Error: $loginError\n\n";

if ($loginHttpCode == 200) {
    $loginData = json_decode($loginResponse, true);
    if (isset($loginData['token'])) {
        $token = $loginData['token'];
        echo "✓ Token obtained: $token\n\n";
        
        // Test 3: Protected endpoint with proper headers
        echo "3. Testing protected endpoint...\n";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:8080/api/user");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
            'User-Agent: PNS-Dhampur-Test-Client/1.0',
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        
        $protectedResponse = curl_exec($ch);
        $protectedHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $protectedError = curl_error($ch);
        curl_close($ch);
        
        echo "Protected Status: $protectedHttpCode\n";
        echo "Protected Response: $protectedResponse\n";
        echo "Protected Error: $protectedError\n";
    }
}

echo "\n=== Test Complete ===\n";