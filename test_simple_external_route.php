<?php
// Simple test to check if /api/external/ routes work at all
// This bypasses Laravel and creates a direct endpoint

echo "=== Testing Simple External Route ===\n";

// Test 1: Direct HTTP request to a simple endpoint
$testUrl = 'http://127.0.0.1/api/external/test-simple';

echo "Testing URL: $testUrl\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_VERBOSE, true);
curl_setopt($ch, CURLOPT_STDERR, fopen('php://temp', 'w+'));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: " . substr($response, 0, 200) . "\n";
if ($error) {
    echo "cURL Error: $error\n";
}

curl_close($ch);

echo "\n=== Test Complete ===\n";
?>