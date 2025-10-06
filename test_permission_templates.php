<?php

// Test permission template download functionality
$baseUrl = 'http://127.0.0.1:8000';

echo "Testing Permission Template Download Functionality\n";
echo "================================================\n\n";

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

// Step 3: Test permission templates page access
echo "3. Testing permission templates page access...\n";
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/users/permission-templates');
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, []);

$templatesPage = curl_exec($ch);
$templatesHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$templatesContentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

echo "   Status: $templatesHttpCode\n";
echo "   Content-Type: $templatesContentType\n";

if ($templatesHttpCode === 200) {
    echo "✅ Permission templates page accessible\n";
    
    // Check if it's a download response (file)
    if (strpos($templatesContentType, 'application/') !== false || 
        strpos($templatesContentType, 'text/csv') !== false ||
        strpos($templatesContentType, 'text/plain') !== false) {
        
        echo "✅ File download response detected\n";
        echo "📄 Content-Type: $templatesContentType\n";
        echo "📊 Content Length: " . strlen($templatesPage) . " bytes\n";
        
        // Save the downloaded content to a file for inspection
        $downloadFile = 'permission_templates_download.txt';
        file_put_contents($downloadFile, $templatesPage);
        echo "💾 Downloaded content saved to: $downloadFile\n";
        
        // Show first few lines of the content
        $lines = explode("\n", $templatesPage);
        echo "📋 First few lines of content:\n";
        for ($i = 0; $i < min(10, count($lines)); $i++) {
            echo "   " . ($i + 1) . ": " . trim($lines[$i]) . "\n";
        }
        
    } else {
        echo "⚠️ HTML page response (not a file download)\n";
        
        // Look for download links or buttons in the HTML
        if (strpos($templatesPage, 'download') !== false) {
            echo "✅ Download links found in HTML\n";
        }
        
        if (strpos($templatesPage, 'template') !== false) {
            echo "✅ Template references found in HTML\n";
        }
        
        // Extract any download URLs from the page
        preg_match_all('/href="([^"]*(?:template|download)[^"]*)"/', $templatesPage, $downloadMatches);
        if (!empty($downloadMatches[1])) {
            echo "🔗 Found download URLs:\n";
            foreach ($downloadMatches[1] as $url) {
                echo "   - $url\n";
            }
        }
        
        echo "📄 Page snippet: " . substr(strip_tags($templatesPage), 0, 200) . "\n";
    }
    
} elseif ($templatesHttpCode === 302) {
    echo "🔄 Permission templates redirected\n";
    
    // Get the redirect location
    $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    if ($redirectUrl) {
        echo "   Redirect URL: $redirectUrl\n";
        
        // Follow the redirect to see what happens
        echo "   Following redirect...\n";
        curl_setopt($ch, CURLOPT_URL, $redirectUrl);
        $redirectResponse = curl_exec($ch);
        $redirectHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $redirectContentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        
        echo "   Redirect Status: $redirectHttpCode\n";
        echo "   Redirect Content-Type: $redirectContentType\n";
        
        if ($redirectHttpCode === 200) {
            if (strpos($redirectContentType, 'application/') !== false) {
                echo "✅ File download after redirect\n";
                echo "📊 Content Length: " . strlen($redirectResponse) . " bytes\n";
            } else {
                echo "📄 HTML page after redirect\n";
            }
        }
    }
    
} elseif ($templatesHttpCode === 404) {
    echo "❌ Permission templates route not found\n";
} elseif ($templatesHttpCode === 401 || $templatesHttpCode === 403) {
    echo "❌ Access denied to permission templates\n";
} else {
    echo "❌ Permission templates request failed\n";
    echo "📄 Response: " . substr($templatesPage, 0, 300) . "\n";
}

echo "\n";

// Step 4: Test specific template download routes (if they exist)
echo "4. Testing specific template download routes...\n";

$templateRoutes = [
    '/users/import-template' => 'Import Template',
    '/users/permission-templates' => 'Permission Templates',
];

foreach ($templateRoutes as $route => $description) {
    echo "   Testing $description ($route)...\n";
    
    curl_setopt($ch, CURLOPT_URL, $baseUrl . $route);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_POST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, []);
    
    $routeResponse = curl_exec($ch);
    $routeHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $routeContentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    
    echo "     Status: $routeHttpCode\n";
    echo "     Content-Type: $routeContentType\n";
    
    if ($routeHttpCode === 200) {
        if (strpos($routeContentType, 'application/') !== false || 
            strpos($routeContentType, 'text/csv') !== false) {
            echo "     ✅ File download successful\n";
            echo "     📊 Size: " . strlen($routeResponse) . " bytes\n";
            
            // Save the file
            $filename = strtolower(str_replace(' ', '_', $description)) . '_download.csv';
            file_put_contents($filename, $routeResponse);
            echo "     💾 Saved as: $filename\n";
            
        } else {
            echo "     ⚠️ HTML response (not file download)\n";
        }
    } else {
        echo "     ❌ Failed (Status: $routeHttpCode)\n";
    }
    
    echo "\n";
}

// Step 5: Check for any permission-related functionality
echo "5. Checking for permission-related functionality...\n";

// Test users index page to see if it has permission template links
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/users');
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, []);

$usersPage = curl_exec($ch);
$usersHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($usersHttpCode === 200) {
    echo "✅ Users page accessible\n";
    
    // Look for permission-related links
    if (strpos($usersPage, 'permission') !== false) {
        echo "✅ Permission references found on users page\n";
        
        // Extract permission-related links
        preg_match_all('/href="([^"]*permission[^"]*)"/', $usersPage, $permissionMatches);
        if (!empty($permissionMatches[1])) {
            echo "🔗 Permission-related URLs found:\n";
            foreach ($permissionMatches[1] as $url) {
                echo "   - $url\n";
            }
        }
    }
    
    // Look for template download links
    if (strpos($usersPage, 'template') !== false) {
        echo "✅ Template references found on users page\n";
        
        preg_match_all('/href="([^"]*template[^"]*)"/', $usersPage, $templateMatches);
        if (!empty($templateMatches[1])) {
            echo "🔗 Template-related URLs found:\n";
            foreach ($templateMatches[1] as $url) {
                echo "   - $url\n";
            }
        }
    }
    
} else {
    echo "❌ Users page not accessible (Status: $usersHttpCode)\n";
}

// Cleanup
curl_close($ch);
if (file_exists($cookieJar)) {
    unlink($cookieJar);
}

echo "\n🎉 Permission template testing completed!\n";

// Summary
echo "\n📋 Test Summary:\n";
echo "================\n";
echo "✅ Login functionality: Working\n";
echo "✅ Authentication: Working\n";

if (file_exists('permission_templates_download.txt')) {
    echo "✅ Permission templates download: Working\n";
} else {
    echo "⚠️ Permission templates download: Needs investigation\n";
}

if (file_exists('import_template_download.csv')) {
    echo "✅ Import template download: Working\n";
} else {
    echo "⚠️ Import template download: May not be available\n";
}

echo "\n";