<?php

// Direct test of login endpoint with minimal validation
echo "Testing direct login to API...\n";

$data = [
    'email' => 'admin@example.com',
    'password' => 'password'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:8080/api/v1/login");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'User-Agent: PNS-Dhampur-Test-Client/1.0'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Error: $error\n";
echo "Response: $response\n";

if ($httpCode == 200) {
    $data = json_decode($response, true);
    if (isset($data['token'])) {
        echo "✓ Login successful! Token: " . $data['token'] . "\n";
    }
} else {
    echo "✗ Login failed\n";
}