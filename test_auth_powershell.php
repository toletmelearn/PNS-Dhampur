<?php

echo "Testing Laravel API Authentication with PowerShell Commands\n";
echo "=========================================================\n\n";

// Configuration
$baseUrl = 'http://127.0.0.1:8080';

// Function to execute PowerShell Invoke-WebRequest commands
function executePowerShellRequest($url, $method = 'GET', $body = null, $headers = []) {
    $cmd = "Invoke-WebRequest -Uri \"$url\" -Method $method";
    
    if ($body) {
        $bodyJson = json_encode($body);
        $cmd .= " -Body '$bodyJson'";
    }
    
    if (!empty($headers)) {
        $headerString = '';
        foreach ($headers as $key => $value) {
            $headerString .= "'$key'='$value';";
        }
        $cmd .= " -Headers @{$headerString}";
    }
    
    $cmd .= " -ContentType 'application/json' 2>&1";
    
    echo "Executing: $cmd\n";
    $output = shell_exec($cmd);
    return $output;
}

// Test 1: Basic API test
echo "1. Testing basic API endpoint...\n";
$result = executePowerShellRequest("$baseUrl/api/test");
echo "Result: " . ($result ?: 'No output') . "\n\n";

// Test 2: Try to login
echo "2. Attempting to login...\n";
$loginData = [
    'email' => 'admin@example.com',
    'password' => 'password'
];
$result = executePowerShellRequest("$baseUrl/api/login", 'POST', $loginData);
echo "Result: " . ($result ?: 'No output') . "\n\n";

// Test 3: Try registration
echo "3. Testing user registration...\n";
$registerData = [
    'name' => 'Test Admin',
    'email' => 'testadmin@example.com',
    'password' => 'password123',
    'password_confirmation' => 'password123'
];
$result = executePowerShellRequest("$baseUrl/api/register", 'POST', $registerData);
echo "Result: " . ($result ?: 'No output') . "\n\n";

echo "PowerShell authentication test completed.\n";