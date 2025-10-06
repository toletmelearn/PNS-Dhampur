<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Middleware\ExternalIntegrationMiddleware;
use App\Models\User;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing External Integration Middleware ===\n\n";

try {
    // Find admin user
    $adminUser = User::where('email', 'admin@pns-dhampur.edu')->first();
    if (!$adminUser) {
        echo "Admin user not found! Trying alternative...\n";
        $adminUser = User::where('role', 'admin')->first();
        if (!$adminUser) {
            echo "No admin user found!\n";
            exit(1);
        }
    }
    
    // Authenticate as admin
    Auth::login($adminUser);
    echo "Authenticated as: " . Auth::user()->email . " (Role: " . Auth::user()->role . ")\n\n";
    
    // Create a mock request for the biometric endpoint
    $request = Request::create('/api/external/biometric/devices', 'GET');
    $request->headers->set('Authorization', 'Bearer test-token');
    $request->headers->set('Accept', 'application/json');
    
    echo "Testing middleware with request:\n";
    echo "- URL: " . $request->fullUrl() . "\n";
    echo "- Method: " . $request->method() . "\n";
    echo "- IP: " . $request->ip() . "\n";
    echo "- User Agent: " . $request->userAgent() . "\n\n";
    
    // Test the middleware
    $middleware = new ExternalIntegrationMiddleware();
    
    $startTime = microtime(true);
    $startMemory = memory_get_usage(true);
    
    $response = $middleware->handle($request, function($req) {
        echo "✅ Middleware passed successfully!\n";
        return response()->json(['success' => true, 'message' => 'Middleware test passed']);
    });
    
    $endTime = microtime(true);
    $endMemory = memory_get_usage(true);
    
    echo "Middleware execution time: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
    echo "Memory used: " . round(($endMemory - $startMemory) / 1024 / 1024, 2) . " MB\n";
    
    if ($response) {
        echo "Response status: " . $response->getStatusCode() . "\n";
        echo "Response content: " . $response->getContent() . "\n";
        
        // Check response headers
        echo "Response headers:\n";
        foreach ($response->headers->all() as $name => $values) {
            echo "  $name: " . implode(', ', $values) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Exception caught: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "❌ Fatal error caught: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";