<?php

echo "Testing bypass routes...\n";

// Function to make HTTP requests using file_get_contents
function makeSimpleRequest($url) {
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    $headers = $http_response_header ?? [];
    
    return [
        'response' => $response,
        'headers' => $headers
    ];
}

echo "\n1. Testing route without middleware...\n";
$result1 = makeSimpleRequest('http://127.0.0.1:8080/api/test-no-middleware');
echo "Headers: " . implode(', ', array_slice($result1['headers'], 0, 3)) . "\n";
echo "Response: " . ($result1['response'] ?: 'No response') . "\n";

echo "\n2. Testing route with basic middleware...\n";
$result2 = makeSimpleRequest('http://127.0.0.1:8080/api/test-basic-middleware');
echo "Headers: " . implode(', ', array_slice($result2['headers'], 0, 3)) . "\n";
echo "Response: " . ($result2['response'] ?: 'No response') . "\n";

echo "\n3. Testing original API test route...\n";
$result3 = makeSimpleRequest('http://127.0.0.1:8080/api/test');
echo "Headers: " . implode(', ', array_slice($result3['headers'], 0, 3)) . "\n";
echo "Response: " . ($result3['response'] ?: 'No response') . "\n";

echo "\n4. Testing external route (should still crash if middleware is the issue)...\n";
$result4 = makeSimpleRequest('http://127.0.0.1:8080/api/external/biometric/devices');
echo "Headers: " . implode(', ', array_slice($result4['headers'], 0, 3)) . "\n";
echo "Response: " . ($result4['response'] ?: 'No response') . "\n";

echo "\nTest completed.\n";