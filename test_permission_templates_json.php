<?php

// Test permission template JSON API functionality
$baseUrl = 'http://127.0.0.1:8000';

echo "Testing Permission Template JSON API Functionality\n";
echo "=================================================\n\n";

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

// Step 3: Test permission templates JSON API - Get all templates
echo "3. Testing permission templates JSON API - All templates...\n";
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/users/permission-templates');
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json',
    'X-CSRF-TOKEN: ' . $csrfToken,
]);

$templatesResponse = curl_exec($ch);
$templatesHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$templatesContentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

echo "   Status: $templatesHttpCode\n";
echo "   Content-Type: $templatesContentType\n";

if ($templatesHttpCode === 200) {
    echo "✅ Permission templates API accessible\n";
    
    // Try to decode JSON response
    $templatesData = json_decode($templatesResponse, true);
    
    if ($templatesData !== null) {
        echo "✅ Valid JSON response received\n";
        echo "📊 Response size: " . strlen($templatesResponse) . " bytes\n";
        
        // Check if we have templates
        if (isset($templatesData['templates'])) {
            echo "✅ Templates found in response\n";
            echo "📋 Available templates:\n";
            
            foreach ($templatesData['templates'] as $role => $template) {
                echo "   - $role: {$template['name']}\n";
                echo "     Description: {$template['description']}\n";
                echo "     Permissions: " . count($template['permissions']) . " categories\n";
            }
            
            // Save the templates data to a file for inspection
            file_put_contents('permission_templates_data.json', json_encode($templatesData, JSON_PRETTY_PRINT));
            echo "💾 Templates data saved to: permission_templates_data.json\n";
            
        } else {
            echo "⚠️ No templates found in response\n";
            echo "📄 Response content: " . substr($templatesResponse, 0, 500) . "\n";
        }
        
    } else {
        echo "❌ Invalid JSON response\n";
        echo "📄 Response content: " . substr($templatesResponse, 0, 500) . "\n";
    }
    
} elseif ($templatesHttpCode === 401 || $templatesHttpCode === 403) {
    echo "❌ Access denied to permission templates API\n";
} else {
    echo "❌ Permission templates API request failed\n";
    echo "📄 Response: " . substr($templatesResponse, 0, 300) . "\n";
}

echo "\n";

// Step 4: Test specific role template requests
echo "4. Testing specific role template requests...\n";

$rolesToTest = ['admin', 'teacher', 'principal', 'student', 'accountant'];

foreach ($rolesToTest as $role) {
    echo "   Testing $role template...\n";
    
    curl_setopt($ch, CURLOPT_URL, $baseUrl . '/users/permission-templates?role=' . $role);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_POST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/json',
        'X-CSRF-TOKEN: ' . $csrfToken,
    ]);
    
    $roleResponse = curl_exec($ch);
    $roleHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    echo "     Status: $roleHttpCode\n";
    
    if ($roleHttpCode === 200) {
        $roleData = json_decode($roleResponse, true);
        
        if ($roleData !== null && isset($roleData['template'])) {
            echo "     ✅ $role template retrieved successfully\n";
            echo "     📋 Template: {$roleData['template']['name']}\n";
            echo "     📝 Description: {$roleData['template']['description']}\n";
            echo "     🔑 Permission categories: " . count($roleData['template']['permissions']) . "\n";
            
            // Count total permissions
            $totalPermissions = 0;
            foreach ($roleData['template']['permissions'] as $category => $permissions) {
                $totalPermissions += count($permissions);
            }
            echo "     📊 Total permissions: $totalPermissions\n";
            
        } else {
            echo "     ❌ Invalid response for $role template\n";
        }
    } else {
        echo "     ❌ Failed to get $role template (Status: $roleHttpCode)\n";
    }
    
    echo "\n";
}

// Step 5: Test apply permission template functionality
echo "5. Testing apply permission template functionality...\n";

// First, let's check if we have any users to test with
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/users');
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: text/html',
]);

$usersPageResponse = curl_exec($ch);
$usersPageHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($usersPageHttpCode === 200) {
    echo "✅ Users page accessible\n";
    
    // Look for user IDs in the page (this is a simple approach)
    preg_match_all('/data-user-id="(\d+)"/', $usersPageResponse, $userIdMatches);
    if (empty($userIdMatches[1])) {
        // Try alternative pattern
        preg_match_all('/\/users\/(\d+)/', $usersPageResponse, $userIdMatches);
    }
    
    if (!empty($userIdMatches[1])) {
        $testUserId = $userIdMatches[1][0]; // Use first user ID found
        echo "✅ Found test user ID: $testUserId\n";
        
        // Test applying a template to this user
        echo "   Testing apply template to user $testUserId...\n";
        
        $applyData = [
            'user_ids' => [$testUserId],
            'template_role' => 'teacher'
        ];
        
        curl_setopt($ch, CURLOPT_URL, $baseUrl . '/users/apply-template');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($applyData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/json',
            'X-CSRF-TOKEN: ' . $csrfToken,
        ]);
        
        $applyResponse = curl_exec($ch);
        $applyHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        echo "     Apply template status: $applyHttpCode\n";
        
        if ($applyHttpCode === 200) {
            $applyData = json_decode($applyResponse, true);
            if ($applyData !== null) {
                echo "     ✅ Template applied successfully\n";
                echo "     📄 Response: " . substr($applyResponse, 0, 200) . "\n";
            } else {
                echo "     ⚠️ Template applied but invalid JSON response\n";
            }
        } else {
            echo "     ❌ Failed to apply template (Status: $applyHttpCode)\n";
            echo "     📄 Response: " . substr($applyResponse, 0, 200) . "\n";
        }
        
    } else {
        echo "⚠️ No user IDs found for testing apply template functionality\n";
    }
    
} else {
    echo "❌ Users page not accessible for testing apply template\n";
}

// Cleanup
curl_close($ch);
if (file_exists($cookieJar)) {
    unlink($cookieJar);
}

echo "\n🎉 Permission template JSON API testing completed!\n";

// Summary
echo "\n📋 Test Summary:\n";
echo "================\n";
echo "✅ Login functionality: Working\n";
echo "✅ Authentication: Working\n";

if (file_exists('permission_templates_data.json')) {
    echo "✅ Permission templates JSON API: Working\n";
    echo "✅ Template data retrieval: Working\n";
} else {
    echo "❌ Permission templates JSON API: Failed\n";
}

echo "\n";