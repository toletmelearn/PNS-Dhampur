<?php
// Simple test script to verify Laravel application works
$url = 'http://127.0.0.1:9001';
$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'method' => 'GET'
    ]
]);

echo "Testing Laravel application at: $url\n";
echo "===========================================\n";

$content = @file_get_contents($url, false, $context);

if ($content !== false) {
    echo "SUCCESS: Application is responding!\n";
    echo "Response length: " . strlen($content) . " bytes\n";
    echo "First 300 characters:\n";
    echo substr($content, 0, 300) . "\n";
    echo "===========================================\n";
    
    // Check if it's a valid HTML response
    if (strpos($content, '<html') !== false || strpos($content, '<!DOCTYPE') !== false) {
        echo "✓ Valid HTML response detected\n";
    }
    
    // Check for Laravel-specific content
    if (strpos($content, 'Laravel') !== false) {
        echo "✓ Laravel framework detected in response\n";
    }
    
} else {
    echo "ERROR: Could not connect to application\n";
    $error = error_get_last();
    if ($error) {
        echo "Error details: " . $error['message'] . "\n";
    }
}
?>