<?php

// Test bulk import functionality with file upload
$baseUrl = 'http://127.0.0.1:8000';
$csvFile = __DIR__ . '/test_bulk_import.csv';

echo "Testing Bulk Import Functionality\n";
echo "=================================\n\n";

// Check if CSV file exists
if (!file_exists($csvFile)) {
    echo "❌ Test CSV file not found: $csvFile\n";
    exit(1);
}

echo "📄 Test CSV file found: " . basename($csvFile) . "\n";
echo "📊 File size: " . filesize($csvFile) . " bytes\n\n";

// Initialize cURL session
$ch = curl_init();

// Set common cURL options
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_COOKIEJAR => 'cookies.txt',
    CURLOPT_COOKIEFILE => 'cookies.txt',
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    CURLOPT_TIMEOUT => 60,
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
echo "✅ CSRF token found\n\n";

// Step 2: Login with admin credentials
echo "2. Logging in as admin...\n";
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

if ($loginHttpCode === 302 || $loginHttpCode === 200) {
    echo "✅ Login successful\n\n";
} else {
    echo "❌ Login failed (Status: $loginHttpCode)\n";
    exit(1);
}

// Step 3: Get bulk import form and extract CSRF token
echo "3. Getting bulk import form...\n";
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/users/bulk-import');
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, []);

$importFormPage = curl_exec($ch);
$importFormHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($importFormHttpCode !== 200) {
    echo "❌ Failed to get bulk import form (Status: $importFormHttpCode)\n";
    exit(1);
}

// Extract CSRF token from the form
preg_match('/<meta name="csrf-token" content="([^"]+)"/', $importFormPage, $matches);
if (empty($matches[1])) {
    preg_match('/<input[^>]*name="_token"[^>]*value="([^"]+)"/', $importFormPage, $matches);
}

if (empty($matches[1])) {
    echo "❌ CSRF token not found in import form\n";
    exit(1);
}

$importCsrfToken = $matches[1];
echo "✅ Bulk import form loaded\n";
echo "✅ Import form CSRF token found\n\n";

// Step 4: Test import template download
echo "4. Testing import template download...\n";
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/users/import-template');
curl_setopt($ch, CURLOPT_HTTPGET, true);

$templateResponse = curl_exec($ch);
$templateHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$templateContentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

echo "   Status: $templateHttpCode\n";
echo "   Content-Type: $templateContentType\n";

if ($templateHttpCode === 200) {
    echo "✅ Import template download successful\n";
    if (strpos($templateContentType, 'csv') !== false || strpos($templateContentType, 'text') !== false) {
        echo "📄 CSV template detected\n";
        echo "📊 Template size: " . strlen($templateResponse) . " bytes\n";
    }
} else {
    echo "❌ Import template download failed\n";
}
echo "\n";

// Step 5: Upload CSV file for bulk import
echo "5. Testing CSV file upload for bulk import...\n";

// Create CURLFile for file upload
$csvFileUpload = new CURLFile($csvFile, 'text/csv', 'test_bulk_import.csv');

$uploadData = [
    '_token' => $importCsrfToken,
    'csv_file' => $csvFileUpload,
];

curl_setopt($ch, CURLOPT_URL, $baseUrl . '/users/bulk-import');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $uploadData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-CSRF-TOKEN: ' . $importCsrfToken,
]);

$uploadResponse = curl_exec($ch);
$uploadHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$uploadRedirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);

echo "   Upload Status: $uploadHttpCode\n";
if ($uploadRedirectUrl) {
    echo "   Redirect URL: $uploadRedirectUrl\n";
}

if ($uploadHttpCode === 200) {
    echo "✅ CSV upload processed successfully\n";
    
    // Check for success/error messages in response
    if (strpos($uploadResponse, 'success') !== false || strpos($uploadResponse, 'imported') !== false) {
        echo "✅ Import appears successful\n";
    } elseif (strpos($uploadResponse, 'error') !== false || strpos($uploadResponse, 'failed') !== false) {
        echo "⚠️ Import may have encountered errors\n";
    }
    
    // Show response snippet
    $responseSnippet = substr(strip_tags($uploadResponse), 0, 200);
    echo "📄 Response snippet: " . trim($responseSnippet) . "\n";
    
} elseif ($uploadHttpCode === 302) {
    echo "🔄 Upload redirected (likely successful)\n";
} else {
    echo "❌ CSV upload failed\n";
    echo "📄 Response snippet: " . substr($uploadResponse, 0, 300) . "\n";
}

echo "\n";

// Step 6: Verify users were created (check users list)
echo "6. Verifying import results...\n";
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/users');
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_POSTFIELDS, '');
curl_setopt($ch, CURLOPT_HTTPHEADER, []);

$usersListResponse = curl_exec($ch);
$usersListHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($usersListHttpCode === 200) {
    echo "✅ Users list accessible\n";
    
    // Check for test users in the response
    $testEmails = ['john.doe@test.com', 'jane.smith@test.com', 'bob.johnson@test.com'];
    $foundUsers = 0;
    
    foreach ($testEmails as $email) {
        if (strpos($usersListResponse, $email) !== false) {
            $foundUsers++;
            echo "✅ Found imported user: $email\n";
        }
    }
    
    if ($foundUsers > 0) {
        echo "🎉 Import verification successful! Found $foundUsers test users\n";
    } else {
        echo "⚠️ No test users found in users list\n";
    }
} else {
    echo "❌ Failed to access users list for verification\n";
}

// Cleanup
curl_close($ch);
if (file_exists('cookies.txt')) {
    unlink('cookies.txt');
}

echo "\n🎉 Bulk import testing completed!\n";