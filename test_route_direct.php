<?php
// Direct route test
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "=== Direct Route Test ===\n\n";

// Test the actual URL that should work
echo "Testing /admin/users directly...\n";
try {
    $request = \Illuminate\Http\Request::create('/admin/users', 'GET');
    $response = $kernel->handle($request);
    
    echo "Status Code: " . $response->getStatusCode() . "\n";
    
    if ($response->getStatusCode() === 302) {
        $location = $response->headers->get('Location');
        echo "Redirects to: $location\n";
    } elseif ($response->getStatusCode() === 200) {
        echo "✅ Route is accessible and returns content\n";
        echo "Content length: " . strlen($response->getContent()) . " bytes\n";
    } elseif ($response->getStatusCode() === 404) {
        echo "❌ Route not found (404)\n";
    } else {
        echo "Status: " . $response->getStatusCode() . "\n";
    }
    
    $kernel->terminate($request, $response);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test route() helper function
echo "Testing route() helper function...\n";
try {
    $url = route('admin.users.index');
    echo "✅ route('admin.users.index') = $url\n";
} catch (Exception $e) {
    echo "❌ route('admin.users.index') failed: " . $e->getMessage() . "\n";
}

echo "\n";

// List all routes with 'admin' in the name
echo "Searching for admin routes...\n";
try {
    $routes = app('router')->getRoutes();
    $adminRoutes = [];
    
    foreach ($routes as $route) {
        $name = $route->getName();
        if ($name && strpos($name, 'admin') !== false) {
            $adminRoutes[] = $name . ' -> ' . $route->uri();
        }
    }
    
    if (count($adminRoutes) > 0) {
        echo "Found admin routes:\n";
        foreach ($adminRoutes as $route) {
            echo "  - $route\n";
        }
    } else {
        echo "❌ No admin routes found\n";
    }
} catch (Exception $e) {
    echo "❌ Error listing routes: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";