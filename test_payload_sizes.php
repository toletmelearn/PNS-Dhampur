<?php
// Test different payload sizes to identify connection reset threshold
echo "=== Biometric Payload Size Test ===\n";
echo "Testing different payload sizes to identify connection reset threshold...\n\n";

$baseUrl = 'http://127.0.0.1:8001';
$credentials = [
    'email' => 'admin@example.com',
    'password' => 'password'
];

function makeRequest($url, $method = 'GET', $data = null, $token = null, $timeout = 30) {
    $ch = curl_init();
    
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];
    
    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_VERBOSE => false,
        CURLOPT_HEADER => false
    ]);
    
    if ($method === 'POST' && $data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $start = microtime(true);
    $response = curl_exec($ch);
    $end = microtime(true);
    
    $result = [
        'http_code' => curl_getinfo($ch, CURLINFO_HTTP_CODE),
        'error' => curl_error($ch),
        'response' => $response,
        'time_ms' => ($end - $start) * 1000
    ];
    
    curl_close($ch);
    return $result;
}

// Step 1: Login to get token
echo "1. Getting authentication token...\n";
$result = makeRequest("$baseUrl/api/login", 'POST', $credentials);

if ($result['http_code'] !== 200) {
    echo "❌ Login failed: HTTP {$result['http_code']}\n";
    echo "Error: {$result['error']}\n";
    exit(1);
}

$loginData = json_decode($result['response'], true);
$token = $loginData['token'] ?? null;

if (!$token) {
    echo "❌ No token received\n";
    exit(1);
}

echo "✅ Token obtained\n\n";

// Step 2: Test different payload sizes
$payloadSizes = [
    'tiny' => 10,      // 10 bytes of data
    'small' => 100,    // 100 bytes
    'medium' => 1000,  // 1KB
    'large' => 10000,  // 10KB
    'xlarge' => 50000, // 50KB
    'xxlarge' => 100000, // 100KB
    'huge' => 500000,  // 500KB
    'massive' => 1000000 // 1MB
];

foreach ($payloadSizes as $sizeName => $dataSize) {
    echo "2. Testing $sizeName payload ($dataSize bytes)...\n";
    
    $biometricData = [
        'type' => 'real_time',
        'device_id' => 'TEST_DEVICE_' . strtoupper($sizeName),
        'employee_id' => 'EMP001',
        'timestamp' => date('Y-m-d H:i:s'),
        'event_type' => 'check_in',
        'data' => [
            'fingerprint_template' => str_repeat('X', $dataSize),
            'confidence_score' => 95.5
        ]
    ];
    
    $start = microtime(true);
    $result = makeRequest("$baseUrl/api/external/biometric/import-data", 'POST', $biometricData, $token, 60);
    $end = microtime(true);
    
    $duration = ($end - $start) * 1000;
    
    echo "   - HTTP Code: {$result['http_code']}\n";
    echo "   - Duration: " . number_format($duration, 2) . " ms\n";
    echo "   - Error: " . ($result['error'] ?: 'None') . "\n";
    
    if ($result['error']) {
        echo "   - ❌ Connection issue detected at $sizeName payload ($dataSize bytes)\n";
        if (strpos($result['error'], 'reset') !== false) {
            echo "   - 🔍 CONNECTION RESET THRESHOLD FOUND: $dataSize bytes\n";
        }
    } else {
        echo "   - ✅ Success\n";
        if ($result['response']) {
            $responseData = json_decode($result['response'], true);
            if (isset($responseData['success'])) {
                echo "   - Response: " . ($responseData['success'] ? 'Success' : 'Failed') . "\n";
            }
        }
    }
    
    echo "\n";
    
    // If we hit a connection reset, no point testing larger payloads
    if ($result['error'] && strpos($result['error'], 'reset') !== false) {
        echo "🛑 Connection reset detected. Stopping further tests.\n";
        break;
    }
    
    // Small delay between tests
    usleep(500000); // 0.5 seconds
}

echo "=== Payload Size Test Complete ===\n";