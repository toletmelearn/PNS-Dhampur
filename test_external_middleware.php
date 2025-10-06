<?php

echo "=== Testing External Integration Middleware ===\n\n";

// Bootstrap Laravel
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Middleware\ExternalIntegrationMiddleware;
use App\Models\User;

try {
    // Find and authenticate as admin user
    $adminUser = User::where('email', 'admin@pns-dhampur.edu')->first();
    if (!$adminUser) {
        $adminUser = User::where('role', 'admin')->first();
    }
    
    if ($adminUser) {
        Auth::login($adminUser);
        echo "✅ Authenticated as: " . Auth::user()->email . " (Role: " . Auth::user()->role . ")\n\n";
    } else {
        echo "❌ No admin user found!\n";
        exit(1);
    }
    
    // Test 1: Simple request without file
    echo "1. Testing simple request (no file):\n";
    $request = Request::create('/api/external/biometric/import-data', 'POST');
    $request->headers->set('Accept', 'application/json');
    $request->headers->set('Content-Type', 'application/json');
    $request->merge(['type' => 'real_time', 'data' => []]);
    
    $middleware = new ExternalIntegrationMiddleware();
    
    $startTime = microtime(true);
    
    $response = $middleware->handle($request, function($req) {
        echo "  ✅ Middleware passed - no file upload\n";
        return response()->json(['success' => true, 'message' => 'Test passed']);
    });
    
    $endTime = microtime(true);
    echo "  - Execution time: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
    echo "  - Response status: " . $response->getStatusCode() . "\n\n";
    
    // Test 2: Request with suspicious activity simulation
    echo "2. Testing suspicious activity detection:\n";
    
    // Simulate multiple rapid requests
    for ($i = 1; $i <= 3; $i++) {
        $rapidRequest = Request::create('/api/external/biometric/import-data', 'POST');
        $rapidRequest->headers->set('Accept', 'application/json');
        $rapidRequest->merge(['type' => 'real_time', 'data' => []]);
        
        $startTime = microtime(true);
        
        $response = $middleware->handle($rapidRequest, function($req) use ($i) {
            echo "  ✅ Request $i passed\n";
            return response()->json(['success' => true, 'message' => "Request $i passed"]);
        });
        
        $endTime = microtime(true);
        echo "  - Request $i time: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
        echo "  - Request $i status: " . $response->getStatusCode() . "\n";
        
        if ($response->getStatusCode() === 429) {
            echo "  ⚠️  Suspicious activity detected on request $i\n";
            break;
        }
    }
    
    echo "\n3. Testing cache operations:\n";
    
    // Test cache directly
    $cacheKey = 'test_middleware_' . time();
    app('cache')->put($cacheKey, 'test_value', 60);
    $retrieved = app('cache')->get($cacheKey);
    
    if ($retrieved === 'test_value') {
        echo "  ✅ Cache is working properly\n";
        app('cache')->forget($cacheKey);
    } else {
        echo "  ❌ Cache is not working properly\n";
    }
    
    // Check current cache entries for external requests
    $userId = Auth::id();
    $ip = '127.0.0.1';
    $requestKey = "external_requests:{$userId}:{$ip}";
    $currentCount = app('cache')->get($requestKey, 0);
    echo "  - Current request count for user: $currentCount\n";
    
    echo "\n4. Testing with timeout simulation:\n";
    
    $timeoutRequest = Request::create('/api/external/biometric/import-data', 'POST');
    $timeoutRequest->headers->set('Accept', 'application/json');
    $timeoutRequest->merge(['type' => 'real_time', 'data' => []]);
    
    $startTime = microtime(true);
    
    $response = $middleware->handle($timeoutRequest, function($req) {
        echo "  ✅ Starting slow operation simulation...\n";
        
        // Simulate a slow operation that might cause timeout
        for ($i = 0; $i < 5; $i++) {
            echo "  - Processing step " . ($i + 1) . "/5...\n";
            usleep(500000); // 0.5 seconds
        }
        
        echo "  ✅ Slow operation completed\n";
        return response()->json(['success' => true, 'message' => 'Slow operation completed']);
    });
    
    $endTime = microtime(true);
    echo "  - Total time: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
    echo "  - Response status: " . $response->getStatusCode() . "\n";
    
} catch (\Exception $e) {
    echo "❌ Exception caught: " . $e->getMessage() . "\n";
    echo "- File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "- Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (\Error $e) {
    echo "❌ Fatal error caught: " . $e->getMessage() . "\n";
    echo "- File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== Test Complete ===\n";