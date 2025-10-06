<?php

// Test bulk import functionality using web interface (non-AJAX)
$baseUrl = 'http://127.0.0.1:8000';
$csvFile = __DIR__ . '/test_bulk_import.csv';

echo "Testing Bulk Import via Web Interface\n";
echo "====================================\n\n";

// Check if CSV file exists
if (!file_exists($csvFile)) {
    echo "❌ Test CSV file not found: $csvFile\n";
    exit(1);
}

echo "📄 Test CSV file found: " . basename($csvFile) . "\n";
echo "📊 File size: " . filesize($csvFile) . " bytes\n\n";

// Initialize cURL session with proper session handling
$cookieJar = tempnam(sys_get_temp_dir(), 'laravel_cookies');
$ch = curl_init();

// Set common cURL options for session persistence
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_COOKIEJAR => $cookieJar,
    CURLOPT_COOKIEFILE => $cookieJar,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    CURLOPT_TIMEOUT => 60,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_HEADER => false,
]);

// Step 1: Get login page and CSRF token
echo "1. Getting login page...\n";
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/login');
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, []);

$loginPage = curl_exec($ch);
$loginHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_error($ch) || $loginHttpCode !== 200) {
    echo "❌ Error getting login page: " . curl_error($ch) . " (Status: $loginHttpCode)\n";
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
    'Referer: ' . $baseUrl . '/login',
]);

$loginResponse = curl_exec($ch);
$loginHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($loginHttpCode === 302) {
    echo "✅ Login successful (redirected)\n\n";
} elseif ($loginHttpCode === 200) {
    // Check if we're still on login page (login failed)
    if (strpos($loginResponse, 'login') !== false && strpos($loginResponse, 'password') !== false) {
        echo "❌ Login failed - still on login page\n";
        exit(1);
    }
    echo "✅ Login successful\n\n";
} else {
    echo "❌ Login failed (Status: $loginHttpCode)\n";
    exit(1);
}

// Step 3: Get bulk import form and extract fresh CSRF token
echo "3. Getting bulk import form...\n";
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/users/bulk-import');
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, []);

$importFormPage = curl_exec($ch);
$importFormHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($importFormHttpCode !== 200) {
    echo "❌ Failed to get bulk import form (Status: $importFormHttpCode)\n";
    echo "Response: " . substr($importFormPage, 0, 300) . "\n";
    exit(1);
}

// Extract fresh CSRF token from the form
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
echo "✅ Fresh CSRF token found: " . substr($importCsrfToken, 0, 10) . "...\n\n";

// Step 4: Upload CSV file for bulk import (regular form submission, not AJAX)
echo "4. Uploading CSV file for bulk import...\n";

// Create CURLFile for file upload
$csvFileUpload = new CURLFile($csvFile, 'text/csv', 'test_bulk_import.csv');

$uploadData = [
    '_token' => $importCsrfToken,
    'csv_file' => $csvFileUpload,
    'update_existing' => '0',
    'send_welcome_email' => '0'
];

curl_setopt($ch, CURLOPT_URL, $baseUrl . '/users/bulk-import');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $uploadData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-CSRF-TOKEN: ' . $importCsrfToken,
    'Referer: ' . $baseUrl . '/users/bulk-import',
    // Remove AJAX headers to simulate regular form submission
]);

$uploadResponse = curl_exec($ch);
$uploadHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$uploadContentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

echo "   Upload Status: $uploadHttpCode\n";
echo "   Content-Type: $uploadContentType\n";

if ($uploadHttpCode === 200) {
    echo "✅ CSV upload processed successfully\n";
    
    // Check if response contains success indicators
    if (strpos($uploadResponse, 'success') !== false || strpos($uploadResponse, 'imported') !== false) {
        echo "✅ Success message found in response\n";
    }
    
    // Try to decode JSON response if it's JSON
    $jsonResponse = json_decode($uploadResponse, true);
    
    if ($jsonResponse !== null) {
        echo "✅ JSON response received\n";
        echo "📊 Import Results:\n";
        
        if (isset($jsonResponse['message'])) {
            echo "   Message: " . $jsonResponse['message'] . "\n";
        }
        
        if (isset($jsonResponse['success']) && $jsonResponse['success']) {
            echo "   Status: Success\n";
        }
        
        if (isset($jsonResponse['imported'])) {
            echo "   Imported: " . $jsonResponse['imported'] . " users\n";
        }
        
        if (isset($jsonResponse['updated'])) {
            echo "   Updated: " . $jsonResponse['updated'] . " users\n";
        }
        
        if (isset($jsonResponse['errors']) && !empty($jsonResponse['errors'])) {
            echo "   Errors: " . count($jsonResponse['errors']) . "\n";
            foreach ($jsonResponse['errors'] as $error) {
                echo "     - $error\n";
            }
        }
        
        if (isset($jsonResponse['total_processed'])) {
            echo "   Total Processed: " . $jsonResponse['total_processed'] . "\n";
        }
        
    } else {
        echo "⚠️ HTML response received (not JSON)\n";
        // Look for success indicators in HTML
        if (strpos($uploadResponse, 'alert-success') !== false) {
            echo "✅ Success alert found in HTML\n";
        }
        if (strpos($uploadResponse, 'alert-danger') !== false) {
            echo "❌ Error alert found in HTML\n";
        }
        echo "📄 Response snippet: " . substr(strip_tags($uploadResponse), 0, 200) . "\n";
    }
    
} elseif ($uploadHttpCode === 302) {
    echo "🔄 Upload redirected (likely successful)\n";
    
    // Get the redirect location
    $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    if ($redirectUrl) {
        echo "   Redirect URL: $redirectUrl\n";
    }
    
} elseif ($uploadHttpCode === 422) {
    echo "❌ Validation error\n";
    $jsonResponse = json_decode($uploadResponse, true);
    if ($jsonResponse && isset($jsonResponse['errors'])) {
        foreach ($jsonResponse['errors'] as $field => $errors) {
            echo "   $field: " . implode(', ', $errors) . "\n";
        }
    } else {
        echo "📄 Response: " . substr($uploadResponse, 0, 300) . "\n";
    }
} else {
    echo "❌ CSV upload failed\n";
    echo "📄 Response: " . substr($uploadResponse, 0, 500) . "\n";
}

echo "\n";

// Step 5: Verify users were created by checking database directly
echo "5. Verifying import results in database...\n";

// Create a simple PHP script to check database
$dbCheckScript = '<?php
require_once __DIR__ . "/vendor/autoload.php";

$app = require_once __DIR__ . "/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

$testEmails = ["john.doe@test.com", "jane.smith@test.com", "bob.johnson@test.com", "alice.brown@test.com", "mike.wilson@test.com", "sarah.davis@test.com"];

echo "Checking database for imported users:\n";
$foundCount = 0;

foreach ($testEmails as $email) {
    $user = User::where("email", $email)->first();
    if ($user) {
        echo "✅ Found: $email (ID: {$user->id}, Role: {$user->role})\n";
        $foundCount++;
    } else {
        echo "❌ Not found: $email\n";
    }
}

echo "\nTotal found: $foundCount out of " . count($testEmails) . " test users\n";

// Also check total user count
$totalUsers = User::count();
echo "Total users in database: $totalUsers\n";

// Check for recent users (created in last 5 minutes)
$recentUsers = User::where("created_at", ">=", now()->subMinutes(5))->get();
echo "Recent users (last 5 minutes): " . $recentUsers->count() . "\n";
foreach ($recentUsers as $user) {
    echo "  - {$user->email} (Role: {$user->role}, Created: {$user->created_at})\n";
}
';

file_put_contents('check_imported_users.php', $dbCheckScript);

// Execute the database check
$dbCheckOutput = shell_exec('php check_imported_users.php 2>&1');
echo $dbCheckOutput;

// Cleanup
curl_close($ch);
if (file_exists($cookieJar)) {
    unlink($cookieJar);
}
if (file_exists('check_imported_users.php')) {
    unlink('check_imported_users.php');
}

echo "\n🎉 Bulk import testing completed!\n";