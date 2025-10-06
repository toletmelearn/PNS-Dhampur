<?php

echo "=== Verbose cURL Test for Biometric Endpoint ===\n\n";

// First, let's test the login endpoint
echo "1. Testing Login Endpoint...\n";
$loginData = json_encode([
            'email' => 'admin@pns-dhampur.edu',
            'password' => 'password'
        ]);

$loginCh = curl_init();
$loginStderr = fopen('php://temp', 'w+');
curl_setopt_array($loginCh, [
            CURLOPT_URL => 'http://127.0.0.1:8001/api/login',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $loginData,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json'
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_VERBOSE => true,
    CURLOPT_STDERR => $loginStderr,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 3
]);

$loginResponse = curl_exec($loginCh);
$loginHttpCode = curl_getinfo($loginCh, CURLINFO_HTTP_CODE);
$loginError = curl_error($loginCh);

// Get verbose output
rewind($loginStderr);
$loginVerbose = stream_get_contents($loginStderr);
fclose($loginStderr);

echo "Login Response Code: $loginHttpCode\n";
echo "Login cURL Error: " . ($loginError ?: 'None') . "\n";
echo "Login Response: " . substr($loginResponse, 0, 200) . "...\n";
echo "Login Verbose Output:\n$loginVerbose\n";

curl_close($loginCh);

if ($loginHttpCode === 200 && $loginResponse) {
    $loginData = json_decode($loginResponse, true);
    if (isset($loginData['token'])) {
        $token = $loginData['token'];
        echo "\n✅ Login successful! Token obtained.\n\n";
        
        // Now test the biometric endpoint
        echo "2. Testing Biometric Devices Endpoint...\n";
        
        $biometricCh = curl_init();
        $biometricStderr = fopen('php://temp', 'w+');
        curl_setopt_array($biometricCh, [
            CURLOPT_URL => 'http://127.0.0.1:8001/api/external/biometric/devices',
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Accept: application/json',
                'Content-Type: application/json'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_VERBOSE => true,
            CURLOPT_STDERR => $biometricStderr,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TCP_KEEPALIVE => 1,
            CURLOPT_TCP_KEEPIDLE => 2,
            CURLOPT_TCP_KEEPINTVL => 2
        ]);
        
        $biometricResponse = curl_exec($biometricCh);
        $biometricHttpCode = curl_getinfo($biometricCh, CURLINFO_HTTP_CODE);
        $biometricError = curl_error($biometricCh);
        
        // Get connection info
        $connectTime = curl_getinfo($biometricCh, CURLINFO_CONNECT_TIME);
        $totalTime = curl_getinfo($biometricCh, CURLINFO_TOTAL_TIME);
        $namelookupTime = curl_getinfo($biometricCh, CURLINFO_NAMELOOKUP_TIME);
        
        // Get verbose output
        rewind($biometricStderr);
        $biometricVerbose = stream_get_contents($biometricStderr);
        fclose($biometricStderr);
        
        echo "Biometric Response Code: $biometricHttpCode\n";
        echo "Biometric cURL Error: " . ($biometricError ?: 'None') . "\n";
        echo "Connect Time: {$connectTime}s\n";
        echo "Total Time: {$totalTime}s\n";
        echo "Name Lookup Time: {$namelookupTime}s\n";
        echo "Biometric Response: " . substr($biometricResponse, 0, 500) . "\n";
        echo "\nBiometric Verbose Output:\n$biometricVerbose\n";
        
        curl_close($biometricCh);
        
        if ($biometricHttpCode === 200) {
            echo "\n✅ Biometric endpoint test successful!\n";
        } else {
            echo "\n❌ Biometric endpoint test failed!\n";
        }
    } else {
        echo "\n❌ Login response doesn't contain token!\n";
    }
} else {
    echo "\n❌ Login failed!\n";
}

echo "\n=== Test Complete ===\n";