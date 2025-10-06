<?php

// Simple test to login and access protected routes
$baseUrl = 'http://127.0.0.1:8000';

echo "Testing Login and Session Management\n";
echo "====================================\n\n";

// Initialize cURL session
$ch = curl_init();

// Set common cURL options
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_COOKIEJAR => 'cookies.txt',
    CURLOPT_COOKIEFILE => 'cookies.txt',
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    CURLOPT_TIMEOUT => 30,
]);

// Step 1: Get login page and CSRF token
echo "1. Getting login page...\n";
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/login');
curl_setopt($ch, CURLOPT_HTTPGET, true);
$loginPage = curl_exec($ch);

if (curl_error($ch)) {
    echo "❌ Error getting login page: " . curl_error($ch) . "\n";
    exit(1);
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "   Status: $httpCode\n";

if ($httpCode !== 200) {
    echo "❌ Login page not accessible\n";
    exit(1);
}

// Extract CSRF token
preg_match('/<meta name="csrf-token" content="([^"]+)"/', $loginPage, $matches);
if (empty($matches[1])) {
    preg_match('/<input[^>]*name="_token"[^>]*value="([^"]+)"/', $loginPage, $matches);
}

if (empty($matches[1])) {
    echo "❌ CSRF token not found\n";
    exit(1);
}

$csrfToken = $matches[1];
echo "✅ CSRF token found: " . substr($csrfToken, 0, 10) . "...\n\n";

// Step 2: Login with admin credentials
echo "2. Attempting login...\n";
$loginData = [
    '_token' => $csrfToken,
    'email' => 'admin@pnsdhampur.local',
    'password' => 'Password123',
];

curl_setopt($ch, CURLOPT_URL, $baseUrl . '/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded',
    'X-CSRF-TOKEN: ' . $csrfToken,
]);

$loginResponse = curl_exec($ch);
$loginHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);

echo "   Login Status: $loginHttpCode\n";
echo "   Redirect URL: " . ($redirectUrl ?: 'None') . "\n";

if ($loginHttpCode === 302 || $loginHttpCode === 200) {
    echo "✅ Login successful\n\n";
} else {
    echo "❌ Login failed\n";
    echo "Response: " . substr($loginResponse, 0, 500) . "\n";
    exit(1);
}

// Step 3: Test protected routes
$routesToTest = [
    '/users' => 'User Management Index',
    '/users/bulk-import' => 'Bulk Import Form',
    '/users/bulk-password-reset' => 'Bulk Password Reset Form',
    '/users/import-template' => 'Download Import Template',
    '/users/permission-templates' => 'Permission Templates',
];

echo "3. Testing protected routes...\n";
foreach ($routesToTest as $route => $description) {
    echo "   Testing: $description\n";
    echo "   Route: $route\n";
    
    curl_setopt($ch, CURLOPT_URL, $baseUrl . $route);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_POST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, []);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    
    echo "   Status: $httpCode\n";
    
    if ($httpCode === 200) {
        echo "   ✅ Success\n";
        if (strpos($contentType, 'text/csv') !== false || strpos($contentType, 'application/') !== false) {
            echo "   📄 Download response detected\n";
        } else {
            echo "   📄 HTML page response\n";
        }
    } elseif ($httpCode === 302) {
        $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        echo "   🔄 Redirect to: $redirectUrl\n";
    } else {
        echo "   ❌ Failed\n";
        echo "   Response snippet: " . substr($response, 0, 200) . "\n";
    }
    
    echo "\n";
}

// Cleanup
curl_close($ch);
if (file_exists('cookies.txt')) {
    unlink('cookies.txt');
}

echo "🎉 Testing completed!\n";