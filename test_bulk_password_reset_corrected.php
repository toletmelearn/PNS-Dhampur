<?php

// Test bulk password reset functionality with corrected field names
$baseUrl = 'http://127.0.0.1:8000';

echo "Testing Bulk Password Reset Functionality (Corrected)\n";
echo "====================================================\n\n";

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

// Step 3: Get bulk password reset form
echo "3. Getting bulk password reset form...\n";
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/users/bulk-password-reset');
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, []);

$resetFormPage = curl_exec($ch);
$resetFormHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($resetFormHttpCode !== 200) {
    echo "❌ Failed to get bulk password reset form (Status: $resetFormHttpCode)\n";
    echo "Response: " . substr($resetFormPage, 0, 300) . "\n";
    exit(1);
}

// Extract fresh CSRF token from the form
preg_match('/<meta name="csrf-token" content="([^"]+)"/', $resetFormPage, $matches);
if (empty($matches[1])) {
    preg_match('/<input[^>]*name="_token"[^>]*value="([^"]+)"/', $resetFormPage, $matches);
}

if (empty($matches[1])) {
    echo "❌ CSRF token not found in reset form\n";
    exit(1);
}

$resetCsrfToken = $matches[1];
echo "✅ Bulk password reset form loaded\n";
echo "✅ Fresh CSRF token found: " . substr($resetCsrfToken, 0, 10) . "...\n\n";

// Step 4: Create test users for password reset
echo "4. Creating test users for password reset...\n";

$createUsersScript = '<?php
require_once __DIR__ . "/vendor/autoload.php";

$app = require_once __DIR__ . "/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$testUsers = [
    ["name" => "Reset Test User 1", "email" => "resettest1@test.com", "role" => "teacher"],
    ["name" => "Reset Test User 2", "email" => "resettest2@test.com", "role" => "teacher"],
];

$userIds = [];
foreach ($testUsers as $userData) {
    $user = User::firstOrCreate(
        ["email" => $userData["email"]],
        [
            "name" => $userData["name"],
            "role" => $userData["role"],
            "password" => Hash::make("oldpassword123"),
            "email_verified_at" => now(),
        ]
    );
    $userIds[] = $user->id;
    echo "Created/Found user: {$user->email} (ID: {$user->id})\n";
}

echo "User IDs for reset: " . implode(",", $userIds) . "\n";
';

file_put_contents('create_reset_test_users.php', $createUsersScript);
$createUsersOutput = shell_exec('php create_reset_test_users.php 2>&1');
echo $createUsersOutput;

// Extract user IDs from create output
preg_match('/User IDs for reset: (.+)/', $createUsersOutput, $matches);
$userIds = isset($matches[1]) ? trim($matches[1]) : '';

if (empty($userIds)) {
    echo "❌ Could not get user IDs for password reset\n";
    exit(1);
}

echo "✅ User IDs for password reset: $userIds\n\n";

// Step 5: Test bulk password reset with custom password
echo "5. Testing bulk password reset with custom password...\n";

// Prepare the reset data with correct field names based on the form
$resetData = [
    '_token' => $resetCsrfToken,
    'password_type' => 'custom',
    'custom_password' => 'NewPassword123',  // This matches the validation rule
    'send_email' => '0',  // Boolean field
    'force_reset' => '1'  // Boolean field
];

// Add user IDs as array
$userIdArray = explode(',', $userIds);
foreach ($userIdArray as $index => $userId) {
    $resetData["user_ids[$index]"] = trim($userId);
}

curl_setopt($ch, CURLOPT_URL, $baseUrl . '/users/reset-passwords');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($resetData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded',
    'X-CSRF-TOKEN: ' . $resetCsrfToken,
    'Referer: ' . $baseUrl . '/users/bulk-password-reset',
    'Accept: application/json',  // Request JSON response
]);

$resetResponse = curl_exec($ch);
$resetHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$resetContentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

echo "   Reset Status: $resetHttpCode\n";
echo "   Content-Type: $resetContentType\n";

if ($resetHttpCode === 200) {
    echo "✅ Password reset processed successfully\n";
    
    // Try to decode JSON response
    $jsonResponse = json_decode($resetResponse, true);
    
    if ($jsonResponse !== null) {
        echo "✅ JSON response received\n";
        echo "📊 Reset Results:\n";
        
        if (isset($jsonResponse['message'])) {
            echo "   Message: " . $jsonResponse['message'] . "\n";
        }
        
        if (isset($jsonResponse['reset_users'])) {
            echo "   Users Reset: " . count($jsonResponse['reset_users']) . "\n";
            foreach ($jsonResponse['reset_users'] as $resetUser) {
                echo "     - " . $resetUser['email'] . " (ID: " . $resetUser['id'] . ")\n";
            }
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
        if (strpos($resetResponse, 'alert-success') !== false) {
            echo "✅ Success alert found in HTML\n";
        }
        if (strpos($resetResponse, 'alert-danger') !== false) {
            echo "❌ Error alert found in HTML\n";
        }
        echo "📄 Response snippet: " . substr(strip_tags($resetResponse), 0, 200) . "\n";
    }
    
} elseif ($resetHttpCode === 302) {
    echo "🔄 Password reset redirected (likely successful)\n";
    
    // Get the redirect location
    $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    if ($redirectUrl) {
        echo "   Redirect URL: $redirectUrl\n";
    }
    
} elseif ($resetHttpCode === 422) {
    echo "❌ Validation error\n";
    $jsonResponse = json_decode($resetResponse, true);
    if ($jsonResponse && isset($jsonResponse['errors'])) {
        foreach ($jsonResponse['errors'] as $field => $errors) {
            echo "   $field: " . implode(', ', $errors) . "\n";
        }
    } else {
        echo "📄 Response: " . substr($resetResponse, 0, 300) . "\n";
    }
} else {
    echo "❌ Password reset failed\n";
    echo "📄 Response: " . substr($resetResponse, 0, 500) . "\n";
}

echo "\n";

// Step 6: Verify password reset by checking database
echo "6. Verifying password reset results...\n";

$verifyResetScript = '<?php
require_once __DIR__ . "/vendor/autoload.php";

$app = require_once __DIR__ . "/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$userIds = [' . $userIds . '];
$newPassword = "NewPassword123";

echo "Verifying password reset for users:\n";
$successCount = 0;

foreach ($userIds as $userId) {
    $user = User::find($userId);
    if ($user) {
        $passwordMatches = Hash::check($newPassword, $user->password);
        if ($passwordMatches) {
            echo "✅ User ID $userId ({$user->email}): Password successfully reset\n";
            $successCount++;
        } else {
            echo "❌ User ID $userId ({$user->email}): Password NOT reset\n";
        }
    } else {
        echo "❌ User ID $userId: User not found\n";
    }
}

echo "\nPassword reset verification: $successCount out of " . count($userIds) . " users\n";

if ($successCount > 0) {
    echo "🎉 Bulk password reset functionality is working!\n";
} else {
    echo "❌ Bulk password reset functionality failed\n";
}
';

file_put_contents('verify_password_reset.php', $verifyResetScript);

// Execute the verification
$verifyOutput = shell_exec('php verify_password_reset.php 2>&1');
echo $verifyOutput;

// Cleanup
curl_close($ch);
if (file_exists($cookieJar)) {
    unlink($cookieJar);
}
if (file_exists('create_reset_test_users.php')) {
    unlink('create_reset_test_users.php');
}
if (file_exists('verify_password_reset.php')) {
    unlink('verify_password_reset.php');
}

echo "\n🎉 Bulk password reset testing completed!\n";