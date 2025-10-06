<?php

echo "Testing external routes directly through PHP built-in server...\n";

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

echo "\n1. Testing regular API route (/api/test)...\n";
$result1 = makeSimpleRequest('http://127.0.0.1:8080/api/test');
echo "Headers: " . implode(', ', array_slice($result1['headers'], 0, 3)) . "\n";
echo "Response: " . substr($result1['response'] ?: 'No response', 0, 100) . "\n";

echo "\n2. Testing external biometric devices route...\n";
$result2 = makeSimpleRequest('http://127.0.0.1:8080/api/external/biometric/devices');
echo "Headers: " . implode(', ', array_slice($result2['headers'], 0, 3)) . "\n";
echo "Response: " . substr($result2['response'] ?: 'No response', 0, 100) . "\n";

echo "\n3. Testing external test-connection route...\n";
$result3 = makeSimpleRequest('http://127.0.0.1:8080/api/external/biometric/test-connection');
echo "Headers: " . implode(', ', array_slice($result3['headers'], 0, 3)) . "\n";
echo "Response: " . substr($result3['response'] ?: 'No response', 0, 100) . "\n";

echo "\n4. Testing Laravel home page...\n";
$result4 = makeSimpleRequest('http://127.0.0.1:8080/');
echo "Headers: " . implode(', ', array_slice($result4['headers'], 0, 3)) . "\n";
echo "Response: " . substr($result4['response'] ?: 'No response', 0, 100) . "\n";

echo "\nTest completed.\n";