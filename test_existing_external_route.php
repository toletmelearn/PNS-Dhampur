<?php
// Test existing external routes to isolate the connection reset issue

echo "=== Testing Existing External Routes ===\n";

// Test credentials from seeders
$credentials = [
    ['email' => 'admin@pnsdhampur.local', 'password' => 'Password123'],
    ['email' => 'admin@pns-dhampur.edu', 'password' => 'password'],
    ['email' => 'admin@pnsdhampur.edu.in', 'password' => 'admin123']
];

// Function to make HTTP requests
function makeRequest($url, $method = 'GET', $data = null, $headers = []) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
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

$token = null;

// Try each credential set
foreach ($credentials as $index => $cred) {
    echo "\n" . ($index + 1) . ". Testing login with {$cred['email']}...\n";
    
    $loginData = json_encode([
        'email' => $cred['email'],
        'password' => $cred['password']
    ]);

    $loginResponse = makeRequest(
        'http://127.0.0.1/api/login',
        'POST',
        $loginData,
        ['Content-Type: application/json']
    );

    echo "Login Response Code: " . $loginResponse['http_code'] . "\n";
    
    if ($loginResponse['error']) {
        echo "Login Error: " . $loginResponse['error'] . "\n";
        continue;
    }

    // Try to extract token from response
    $loginData = json_decode($loginResponse['body'], true);
    
    if (isset($loginData['token'])) {
        $token = $loginData['token'];
        echo "✓ Token obtained: " . substr($token, 0, 20) . "...\n";
        break;
    } else {
        echo "✗ Failed to get token. Response: " . substr($loginResponse['body'], 0, 200) . "...\n";
    }
}

echo "\n=== Testing External Routes ===\n";

echo "\n1. Testing external route WITHOUT authentication...\n";
$response1 = makeRequest('http://127.0.0.1/api/external/biometric/devices');
echo "Response Code: " . $response1['http_code'] . "\n";
if ($response1['error']) {
    echo "Error: " . $response1['error'] . "\n";
} else {
    echo "Response: " . substr($response1['body'], 0, 200) . "...\n";
}

echo "\n2. Testing external route WITH authentication...\n";
if ($token) {
    $response2 = makeRequest(
        'http://127.0.0.1/api/external/biometric/devices',
        'GET',
        null,
        ['Authorization: Bearer ' . $token]
    );
    echo "Response Code: " . $response2['http_code'] . "\n";
    if ($response2['error']) {
        echo "Error: " . $response2['error'] . "\n";
    } else {
        echo "Response: " . substr($response2['body'], 0, 200) . "...\n";
    }
} else {
    echo "Skipping authenticated test - no token available\n";
}

echo "\n3. Testing different external route...\n";
$response3 = makeRequest('http://127.0.0.1/api/external/biometric/test-connection');
echo "Response Code: " . $response3['http_code'] . "\n";
if ($response3['error']) {
    echo "Error: " . $response3['error'] . "\n";
} else {
    echo "Response: " . substr($response3['body'], 0, 200) . "...\n";
}

echo "\n=== Testing Regular API Routes for Comparison ===\n";

echo "\n4. Testing regular API route...\n";
if ($token) {
    $response4 = makeRequest(
        'http://127.0.0.1/api/user',
        'GET',
        null,
        ['Authorization: Bearer ' . $token]
    );
    echo "Response Code: " . $response4['http_code'] . "\n";
    if ($response4['error']) {
        echo "Error: " . $response4['error'] . "\n";
    } else {
        echo "Response: " . substr($response4['body'], 0, 200) . "...\n";
    }
} else {
    echo "Skipping regular API test - no token available\n";
}

echo "\n5. Testing another regular API route...\n";
$response5 = makeRequest('http://127.0.0.1/api/test');
echo "Response Code: " . $response5['http_code'] . "\n";
if ($response5['error']) {
    echo "Error: " . $response5['error'] . "\n";
} else {
    echo "Response: " . substr($response5['body'], 0, 200) . "...\n";
}

echo "\nTest completed.\n";
?>