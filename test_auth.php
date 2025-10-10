<?php

// Simple authentication test script
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "=== Authentication Test ===\n";

echo "Testing API login endpoint...\n";

// Test data for login
$loginData = [
    'email' => 'admin@pnsdhampur.local',
    'password' => 'Password123'
];

// Create a test request
$request = Illuminate\Http\Request::create('/api/login', 'POST', $loginData);
$request->headers->set('Content-Type', 'application/json');
$request->headers->set('Accept', 'application/json');

try {
    $response = $kernel->handle($request);
    
    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Response Content: " . $response->getContent() . "\n";
    
    if ($response->getStatusCode() === 200) {
        $data = json_decode($response->getContent(), true);
        if (isset($data['token'])) {
            echo "✅ Login successful! Token obtained.\n";
        } else {
            echo "❌ Login failed - no token in response\n";
        }
    } else {
        echo "❌ Login failed with status: " . $response->getStatusCode() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception during login test: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}

echo "=== Authentication Test Complete ===\n";