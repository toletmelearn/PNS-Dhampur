<?php

echo "Testing Laravel routes through PHP built-in server...\n";

// Function to make HTTP requests
function makeRequest($url, $method = 'GET', $data = null, $headers = []) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    
    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    return [
        'body' => $response,
        'http_code' => $httpCode,
        'error' => $error
    ];
}

echo "\n1. Testing regular API route...\n";
$response1 = makeRequest('http://127.0.0.1:8080/api/test');
echo "Response Code: " . $response1['http_code'] . "\n";
if ($response1['error']) {
    echo "Error: " . $response1['error'] . "\n";
} else {
    echo "Response: " . substr($response1['body'], 0, 100) . "...\n";
}

echo "\n2. Testing external route (without auth)...\n";
$response2 = makeRequest('http://127.0.0.1:8080/api/external/biometric/devices');
echo "Response Code: " . $response2['http_code'] . "\n";
if ($response2['error']) {
    echo "Error: " . $response2['error'] . "\n";
} else {
    echo "Response: " . substr($response2['body'], 0, 100) . "...\n";
}

echo "\n3. Testing external route (test-connection)...\n";
$response3 = makeRequest('http://127.0.0.1:8080/api/external/biometric/test-connection');
echo "Response Code: " . $response3['http_code'] . "\n";
if ($response3['error']) {
    echo "Error: " . $response3['error'] . "\n";
} else {
    echo "Response: " . substr($response3['body'], 0, 100) . "...\n";
}

echo "\n4. Testing Laravel home page...\n";
$response4 = makeRequest('http://127.0.0.1:8080/');
echo "Response Code: " . $response4['http_code'] . "\n";
if ($response4['error']) {
    echo "Error: " . $response4['error'] . "\n";
} else {
    echo "Response: " . substr($response4['body'], 0, 100) . "...\n";
}

echo "\nTest completed.\n";