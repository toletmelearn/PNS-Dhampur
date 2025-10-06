<?php

// Test biometric API endpoint through Apache
echo "Testing Biometric API through Apache...\n";

// Test 1: Simple API test endpoint
echo "\n1. Testing simple API endpoint...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/api/test');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($error) {
    echo "cURL Error: $error\n";
} else {
    echo "Response: $response\n";
}

// Test 2: Login to get authentication token
echo "\n2. Testing login endpoint...\n";
$loginData = json_encode([
    'email' => 'admin@example.com',
    'password' => 'password'
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/api/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $loginData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json'
]);

$loginResponse = curl_exec($ch);
$loginHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$loginError = curl_error($ch);
curl_close($ch);

echo "Login HTTP Code: $loginHttpCode\n";
if ($loginError) {
    echo "Login cURL Error: $loginError\n";
} else {
    echo "Login Response: $loginResponse\n";
    
    // Extract token if login successful
    $loginData = json_decode($loginResponse, true);
    $token = isset($loginData['token']) ? $loginData['token'] : null;
    
    if ($token) {
        // Test 3: Test biometric endpoint with authentication
        echo "\n3. Testing biometric endpoint with authentication...\n";
        
        $biometricData = json_encode([
            'type' => 'real_time',
            'employee_id' => 'TEST001',
            'timestamp' => date('Y-m-d H:i:s'),
            'device_id' => 'TEST_DEVICE'
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost/api/external/biometric/import-data');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $biometricData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ]);
        
        $biometricResponse = curl_exec($ch);
        $biometricHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $biometricError = curl_error($ch);
        curl_close($ch);
        
        echo "Biometric HTTP Code: $biometricHttpCode\n";
        if ($biometricError) {
            echo "Biometric cURL Error: $biometricError\n";
        } else {
            echo "Biometric Response: $biometricResponse\n";
        }
    }
}

echo "\nTest completed!\n";