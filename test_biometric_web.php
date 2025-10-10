<?php
// Test the biometric endpoint via web server to isolate connection reset issue
echo "=== Testing Biometric Endpoint via Web Server ===\n\n";

// Test configuration
$base_url = 'http://127.0.0.1:8001';
$login_url = $base_url . '/api/login';
$biometric_url = $base_url . '/api/external/biometric/import-data';

// Test credentials
$credentials = [
    'email' => 'admin@pns-dhampur.edu',
    'password' => 'admin123'
];

// Function to make HTTP request with detailed logging
function makeRequest($url, $data = null, $headers = [], $timeout = 30) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_VERBOSE => true,
        CURLOPT_STDERR => fopen('php://temp', 'w+'),
    ]);
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $headers[] = 'Content-Type: application/json';
    }
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $start_time = microtime(true);
    $response = curl_exec($ch);
    $end_time = microtime(true);
    
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $info = curl_getinfo($ch);
    
    // Get verbose output
    $verbose_log = 'Verbose logging not available';
    // Note: CURLOPT_STDERR is not retrieved via curl_getinfo
    
    curl_close($ch);
    
    return [
        'response' => $response,
        'http_code' => $http_code,
        'error' => $error,
        'time' => round(($end_time - $start_time) * 1000, 2),
        'info' => $info,
        'verbose' => $verbose_log
    ];
}

// Step 1: Login to get token
echo "1. Logging in to get authentication token...\n";
$login_result = makeRequest($login_url, $credentials);

if ($login_result['http_code'] == 200) {
    $login_data = json_decode($login_result['response'], true);
    if (isset($login_data['token'])) {
        $token = $login_data['token'];
        echo "✅ Login successful (Time: {$login_result['time']} ms)\n";
        echo "- Token: " . substr($token, 0, 20) . "...\n\n";
    } else {
        echo "❌ Login failed - no token in response\n";
        echo "Response: " . $login_result['response'] . "\n";
        exit(1);
    }
} else {
    echo "❌ Login failed (HTTP Code: {$login_result['http_code']})\n";
    echo "Error: " . $login_result['error'] . "\n";
    echo "Response: " . $login_result['response'] . "\n";
    exit(1);
}

// Step 2: Test minimal biometric data
echo "2. Testing minimal biometric data...\n";
$minimal_data = [
    'type' => 'real_time',
    'device_id' => 'TEST_DEVICE',
    'employee_id' => 'TEST001',
    'timestamp' => date('c'),
    'event_type' => 'check_in'
];

$headers = ['Authorization: Bearer ' . $token];
$biometric_result = makeRequest($biometric_url, $minimal_data, $headers, 30);

echo "- HTTP Code: {$biometric_result['http_code']}\n";
echo "- Time: {$biometric_result['time']} ms\n";
echo "- Error: " . ($biometric_result['error'] ?: 'None') . "\n";
echo "- Response: " . substr($biometric_result['response'], 0, 200) . "...\n";

if ($biometric_result['error']) {
    echo "\n--- Detailed Error Information ---\n";
    echo "cURL Error: " . $biometric_result['error'] . "\n";
    echo "Connection Info:\n";
    echo "- Total Time: " . $biometric_result['info']['total_time'] . "s\n";
    echo "- Connect Time: " . $biometric_result['info']['connect_time'] . "s\n";
    echo "- Pretransfer Time: " . $biometric_result['info']['pretransfer_time'] . "s\n";
    echo "- Starttransfer Time: " . $biometric_result['info']['starttransfer_time'] . "s\n";
    echo "- Redirect Time: " . $biometric_result['info']['redirect_time'] . "s\n";
    
    echo "\n--- Verbose Log ---\n";
    echo $biometric_result['verbose'] . "\n";
}

// Step 3: Test with different timeout values
echo "\n3. Testing with different timeout values...\n";

$timeouts = [5, 10, 15, 30, 60];
foreach ($timeouts as $timeout) {
    echo "- Testing with {$timeout}s timeout...\n";
    $result = makeRequest($biometric_url, $minimal_data, $headers, $timeout);
    echo "  HTTP Code: {$result['http_code']}, Time: {$result['time']} ms, Error: " . ($result['error'] ?: 'None') . "\n";
    
    if ($result['http_code'] == 200 || $result['http_code'] == 422) {
        echo "  ✅ Request completed successfully\n";
        break;
    }
}

// Step 4: Test server availability
echo "\n4. Testing server availability...\n";
$test_result = makeRequest($base_url . '/api/test', null, [], 10);
echo "- Test endpoint HTTP Code: {$test_result['http_code']}\n";
echo "- Test endpoint Time: {$test_result['time']} ms\n";
echo "- Test endpoint Error: " . ($test_result['error'] ?: 'None') . "\n";

echo "\n=== Web Test Complete ===\n";