<?php

echo "=== Laravel Route Performance Test ===\n\n";

function testEndpoint($url, $headers = [], $description = "") {
    echo "Testing: $description\n";
    echo "URL: $url\n";
    
    $startTime = microtime(true);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
    $error = curl_error($ch);
    curl_close($ch);
    
    $endTime = microtime(true);
    $actualTime = ($endTime - $startTime) * 1000;
    
    if ($error) {
        echo "❌ cURL Error: $error\n";
    } else {
        echo "✅ HTTP $httpCode - Response time: " . round($actualTime, 2) . " ms\n";
        if ($response) {
            $responseLength = strlen($response);
            echo "- Response size: $responseLength bytes\n";
            
            // Show first 100 chars of response for debugging
            $preview = substr($response, 0, 100);
            if (strlen($response) > 100) $preview .= "...";
            echo "- Preview: " . str_replace(["\n", "\r"], " ", $preview) . "\n";
        }
    }
    echo "\n";
    
    return [
        'success' => !$error && $httpCode == 200,
        'time' => $actualTime,
        'http_code' => $httpCode,
        'error' => $error
    ];
}

// Test different route types
$tests = [
    [
        'url' => 'http://127.0.0.1/api/test',
        'description' => 'Simple API route (no auth)',
        'headers' => []
    ],
    [
        'url' => 'http://127.0.0.1/api/login',
        'description' => 'Login route (POST converted to GET for test)',
        'headers' => []
    ],
    [
        'url' => 'http://127.0.0.1/',
        'description' => 'Laravel welcome page',
        'headers' => []
    ]
];

// Get auth token first
echo "=== Getting Authentication Token ===\n";
$loginData = json_encode([
    'email' => 'admin@pnsdhampur.local',
    'password' => 'Password123'
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1/api/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $loginData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$loginResponse = curl_exec($ch);
$loginHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$loginError = curl_error($ch);
curl_close($ch);

$token = null;
if (!$loginError && $loginHttpCode == 200) {
    $loginData = json_decode($loginResponse, true);
    if (isset($loginData['token'])) {
        $token = $loginData['token'];
        echo "✅ Login successful, token obtained\n\n";
    }
} else {
    echo "❌ Login failed: HTTP $loginHttpCode\n";
    if ($loginError) echo "cURL Error: $loginError\n";
    echo "\n";
}

// Add authenticated routes if we have a token
if ($token) {
    $tests[] = [
        'url' => 'http://127.0.0.1/api/external/biometric/devices',
        'description' => 'Biometric devices (authenticated)',
        'headers' => ['Authorization: Bearer ' . $token]
    ];
    
    $tests[] = [
        'url' => 'http://127.0.0.1/api/external/biometric/device-status/1',
        'description' => 'Device status (authenticated)',
        'headers' => ['Authorization: Bearer ' . $token]
    ];
}

// Run all tests
echo "=== Running Route Performance Tests ===\n";
$results = [];

foreach ($tests as $test) {
    $result = testEndpoint($test['url'], $test['headers'], $test['description']);
    $results[] = array_merge($result, ['description' => $test['description']]);
}

// Summary
echo "=== Performance Summary ===\n";
$totalTests = count($results);
$successfulTests = array_filter($results, function($r) { return $r['success']; });
$avgTime = array_sum(array_column($results, 'time')) / $totalTests;

echo "Total tests: $totalTests\n";
echo "Successful: " . count($successfulTests) . "\n";
echo "Average response time: " . round($avgTime, 2) . " ms\n\n";

echo "Individual results:\n";
foreach ($results as $result) {
    $status = $result['success'] ? '✅' : '❌';
    echo "$status {$result['description']}: " . round($result['time'], 2) . " ms\n";
}

// Performance analysis
$fastTests = array_filter($results, function($r) { return $r['time'] < 100; });
$slowTests = array_filter($results, function($r) { return $r['time'] > 1000; });

if (count($slowTests) > 0) {
    echo "\n⚠️  Slow routes (>1000ms):\n";
    foreach ($slowTests as $test) {
        echo "- {$test['description']}: " . round($test['time'], 2) . " ms\n";
    }
}

if (count($fastTests) > 0) {
    echo "\n✅ Fast routes (<100ms):\n";
    foreach ($fastTests as $test) {
        echo "- {$test['description']}: " . round($test['time'], 2) . " ms\n";
    }
}