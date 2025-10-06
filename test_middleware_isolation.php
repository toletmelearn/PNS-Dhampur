<?php

echo "=== Middleware Isolation Test ===\n\n";

// Bootstrap Laravel
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

// Authenticate as admin user
$adminUser = User::where('email', 'admin@pnsdhampur.local')->first();
if ($adminUser) {
    Auth::login($adminUser);
    echo "✅ Authenticated as: " . Auth::user()->email . "\n\n";
} else {
    echo "❌ No admin user found!\n";
    exit(1);
}

// Test each middleware individually
$middlewareTests = [
    [
        'name' => 'ExternalIntegrationMiddleware',
        'class' => \App\Http\Middleware\ExternalIntegrationMiddleware::class
    ],
    [
        'name' => 'RateLimitMiddleware',
        'class' => \App\Http\Middleware\RateLimitMiddleware::class,
        'params' => [3, 1] // 3 requests per 1 minute
    ],
    [
        'name' => 'RoleMiddleware',
        'class' => \App\Http\Middleware\RoleMiddleware::class,
        'params' => ['admin,principal,teacher']
    ]
];

foreach ($middlewareTests as $test) {
    echo "Testing: {$test['name']}\n";
    echo str_repeat('-', 50) . "\n";
    
    try {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        // Create request
        $request = Request::create('/api/external/biometric/devices', 'GET');
        $request->headers->set('Authorization', 'Bearer test-token');
        $request->headers->set('Accept', 'application/json');
        
        // Instantiate middleware
        $middleware = new $test['class']();
        
        // Handle request with middleware
        $response = null;
        if (isset($test['params'])) {
            $response = $middleware->handle($request, function($req) {
                return response()->json(['success' => true, 'message' => 'Middleware test passed']);
            }, ...$test['params']);
        } else {
            $response = $middleware->handle($request, function($req) {
                return response()->json(['success' => true, 'message' => 'Middleware test passed']);
            });
        }
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        
        $executionTime = ($endTime - $startTime) * 1000;
        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024;
        
        echo "✅ SUCCESS\n";
        echo "- Execution time: " . round($executionTime, 2) . " ms\n";
        echo "- Memory used: " . round($memoryUsed, 2) . " MB\n";
        echo "- Response status: " . $response->getStatusCode() . "\n";
        echo "- Response size: " . strlen($response->getContent()) . " bytes\n";
        
        // Check for specific response headers that might indicate issues
        $headers = $response->headers->all();
        if (isset($headers['x-ratelimit-remaining'])) {
            echo "- Rate limit remaining: " . $headers['x-ratelimit-remaining'][0] . "\n";
        }
        
    } catch (\Exception $e) {
        echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
        echo "- File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        
        // Check if it's a timeout or connection issue
        if (strpos($e->getMessage(), 'timeout') !== false || 
            strpos($e->getMessage(), 'connection') !== false) {
            echo "- ⚠️  CONNECTION/TIMEOUT ISSUE DETECTED\n";
        }
        
    } catch (\Error $e) {
        echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
        echo "- File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
    
    echo "\n";
}

// Test middleware stack combination
echo "Testing Combined Middleware Stack\n";
echo str_repeat('-', 50) . "\n";

try {
    $startTime = microtime(true);
    
    $request = Request::create('/api/external/biometric/devices', 'GET');
    $request->headers->set('Authorization', 'Bearer test-token');
    $request->headers->set('Accept', 'application/json');
    
    // Chain middlewares like Laravel does
    $externalMiddleware = new \App\Http\Middleware\ExternalIntegrationMiddleware();
    $rateLimitMiddleware = new \App\Http\Middleware\RateLimitMiddleware();
    $roleMiddleware = new \App\Http\Middleware\RoleMiddleware();
    
    $response = $externalMiddleware->handle($request, function($req1) use ($rateLimitMiddleware, $roleMiddleware) {
        return $rateLimitMiddleware->handle($req1, function($req2) use ($roleMiddleware) {
            return $roleMiddleware->handle($req2, function($req3) {
                return response()->json(['success' => true, 'message' => 'All middleware passed']);
            }, 'admin,principal,teacher');
        }, 3, 1);
    });
    
    $endTime = microtime(true);
    $executionTime = ($endTime - $startTime) * 1000;
    
    echo "✅ SUCCESS - Combined middleware stack\n";
    echo "- Total execution time: " . round($executionTime, 2) . " ms\n";
    echo "- Response status: " . $response->getStatusCode() . "\n";
    echo "- Response content: " . $response->getContent() . "\n";
    
} catch (\Exception $e) {
    echo "❌ EXCEPTION in combined stack: " . $e->getMessage() . "\n";
    echo "- This suggests the issue is in middleware interaction\n";
} catch (\Error $e) {
    echo "❌ FATAL ERROR in combined stack: " . $e->getMessage() . "\n";
}

echo "\n=== Cache Status ===\n";
try {
    // Check cache status
    $cacheStore = app('cache')->getStore();
    echo "Cache driver: " . get_class($cacheStore) . "\n";
    
    // Test cache operations
    $testKey = 'middleware_test_' . time();
    app('cache')->put($testKey, 'test_value', 60);
    $retrieved = app('cache')->get($testKey);
    
    if ($retrieved === 'test_value') {
        echo "✅ Cache is working properly\n";
        app('cache')->forget($testKey);
    } else {
        echo "❌ Cache is not working properly\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Cache error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";