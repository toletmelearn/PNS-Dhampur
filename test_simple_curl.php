<?php
echo "=== Simple cURL Test for Biometric Endpoint ===\n\n";

$base_url = 'http://127.0.0.1:8001';

// Test 1: Simple GET to test endpoint
echo "1. Testing basic server connectivity...\n";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $base_url . '/api/test',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_CONNECTTIMEOUT => 5,
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "- HTTP Code: $http_code\n";
echo "- Error: " . ($error ?: 'None') . "\n";
echo "- Response: " . substr($response, 0, 100) . "...\n\n";

// Test 2: Login
echo "2. Testing login endpoint...\n";
$login_data = json_encode([
    'email' => 'admin@pns-dhampur.edu',
    'password' => 'admin123'
]);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $base_url . '/api/login',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $login_data,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT => 10,
    CURLOPT_CONNECTTIMEOUT => 5,
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "- HTTP Code: $http_code\n";
echo "- Error: " . ($error ?: 'None') . "\n";

if ($http_code == 200) {
    $login_response = json_decode($response, true);
    if (isset($login_response['token'])) {
        $token = $login_response['token'];
        echo "- Login successful, token: " . substr($token, 0, 20) . "...\n\n";
        
        // Test 3: Biometric endpoint
        echo "3. Testing biometric endpoint...\n";
        $biometric_data = json_encode([
            'type' => 'real_time',
            'device_id' => 'TEST_DEVICE',
            'employee_id' => 'TEST001',
            'timestamp' => date('c'),
            'event_type' => 'check_in'
        ]);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $base_url . '/api/external/biometric/import-data',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $biometric_data,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        
        $start_time = microtime(true);
        $response = curl_exec($ch);
        $end_time = microtime(true);
        
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        
        $duration = round(($end_time - $start_time) * 1000, 2);
        
        echo "- HTTP Code: $http_code\n";
        echo "- Duration: {$duration} ms\n";
        echo "- Error: " . ($error ?: 'None') . "\n";
        echo "- Total Time: " . $info['total_time'] . "s\n";
        echo "- Connect Time: " . $info['connect_time'] . "s\n";
        echo "- Response: " . substr($response, 0, 200) . "...\n";
        
        if ($error) {
            echo "\n--- Connection Details ---\n";
            echo "- Namelookup Time: " . $info['namelookup_time'] . "s\n";
            echo "- Connect Time: " . $info['connect_time'] . "s\n";
            echo "- Pretransfer Time: " . $info['pretransfer_time'] . "s\n";
            echo "- Starttransfer Time: " . $info['starttransfer_time'] . "s\n";
            echo "- Redirect Time: " . $info['redirect_time'] . "s\n";
        }
        
    } else {
        echo "- Login failed: No token in response\n";
        echo "- Response: " . substr($response, 0, 200) . "...\n";
    }
} else {
    echo "- Login failed with HTTP code: $http_code\n";
    echo "- Response: " . substr($response, 0, 200) . "...\n";
}

echo "\n=== Simple Test Complete ===\n";