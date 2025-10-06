<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\AuditTrail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

echo "Testing AuditMiddleware functionality...\n\n";

try {
    // Clear existing audit logs for clean test
    AuditTrail::where('event', 'LIKE', '%request%')->orWhere('event', 'LIKE', '%activity%')->delete();
    echo "Cleared existing audit logs for clean test\n";
    
    // Find or create a test user
    $testUser = User::where('role', 'admin')->first();
    if (!$testUser) {
        $testUser = User::factory()->admin()->create();
        echo "Created new test user with ID: " . $testUser->id . "\n";
    } else {
        echo "Using existing test user with ID: " . $testUser->id . "\n";
    }
    
    echo "Test user: " . $testUser->name . " (Role: " . $testUser->role . ")\n\n";
    
    // Test 1: Simulate GET request through middleware
    echo "=== Testing GET Request through AuditMiddleware ===\n";
    
    $getRequest = Request::create('/dashboard', 'GET', [], [], [], [
        'REMOTE_ADDR' => '127.0.0.1',
        'HTTP_USER_AGENT' => 'Test Browser/1.0'
    ]);
    
    // Set authenticated user
    Auth::login($testUser);
    
    // Create a mock response
    $response = response()->json(['status' => 'success', 'message' => 'Dashboard loaded']);
    
    // Manually trigger the middleware logic (since we can't easily simulate full HTTP pipeline)
    // We'll check if the middleware class exists and its methods
    $middlewareClass = 'App\\Http\\Middleware\\AuditMiddleware';
    if (class_exists($middlewareClass)) {
        echo "AuditMiddleware class found\n";
        
        $middleware = new $middlewareClass();
        echo "AuditMiddleware instance created\n";
        
        // Check if handle method exists
        if (method_exists($middleware, 'handle')) {
            echo "AuditMiddleware handle method exists\n";
            
            try {
                // Try to call the middleware handle method
                $result = $middleware->handle($getRequest, function($request) use ($response) {
                    return $response;
                });
                echo "AuditMiddleware handle method executed successfully\n";
            } catch (Exception $e) {
                echo "Error executing AuditMiddleware: " . $e->getMessage() . "\n";
            }
        } else {
            echo "AuditMiddleware handle method not found\n";
        }
    } else {
        echo "AuditMiddleware class not found\n";
    }
    
    // Check for audit logs created by middleware
    $middlewareAudits = AuditTrail::where('user_id', $testUser->id)
        ->where('created_at', '>=', now()->subMinutes(1))
        ->latest()
        ->get();
    
    echo "Found " . $middlewareAudits->count() . " audit logs from middleware in the last minute\n";
    foreach ($middlewareAudits as $audit) {
        echo "- Event: {$audit->event}, Action: {$audit->action}, URL: {$audit->url}\n";
        echo "  IP: {$audit->ip_address}, Time: {$audit->created_at}\n";
        if ($audit->additional_data) {
            echo "  Additional data: " . json_encode($audit->additional_data) . "\n";
        }
    }
    
    echo "\n=== Testing POST Request through AuditMiddleware ===\n";
    
    $postRequest = Request::create('/api/test', 'POST', [
        'name' => 'Test Data',
        'value' => 'Test Value'
    ], [], [], [
        'REMOTE_ADDR' => '192.168.1.100',
        'HTTP_USER_AGENT' => 'API Client/2.0'
    ]);
    
    // Simulate POST request through middleware
    if (class_exists($middlewareClass)) {
        $middleware = new $middlewareClass();
        
        try {
            $postResponse = response()->json(['status' => 'created', 'id' => 123]);
            $result = $middleware->handle($postRequest, function($request) use ($postResponse) {
                return $postResponse;
            });
            echo "POST request processed through AuditMiddleware\n";
        } catch (Exception $e) {
            echo "Error processing POST request: " . $e->getMessage() . "\n";
        }
    }
    
    // Check for new audit logs
    $newAudits = AuditTrail::where('user_id', $testUser->id)
        ->where('created_at', '>=', now()->subMinutes(1))
        ->latest()
        ->get();
    
    echo "Total audit logs from middleware: " . $newAudits->count() . "\n";
    
    echo "\n=== Testing Different HTTP Methods ===\n";
    
    $methods = ['PUT', 'PATCH', 'DELETE'];
    foreach ($methods as $method) {
        echo "Testing $method request...\n";
        
        $request = Request::create('/api/resource/1', $method, [
            'data' => 'test'
        ], [], [], [
            'REMOTE_ADDR' => '10.0.0.1',
            'HTTP_USER_AGENT' => 'Test Client/1.0'
        ]);
        
        if (class_exists($middlewareClass)) {
            $middleware = new $middlewareClass();
            
            try {
                $response = response()->json(['status' => 'success']);
                $result = $middleware->handle($request, function($req) use ($response) {
                    return $response;
                });
                echo "- $method request processed\n";
            } catch (Exception $e) {
                echo "- Error processing $method request: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Final audit count
    $finalAudits = AuditTrail::where('user_id', $testUser->id)
        ->where('created_at', '>=', now()->subMinutes(1))
        ->latest()
        ->get();
    
    echo "\nFinal Results:\n";
    echo "Total audit logs created by middleware: " . $finalAudits->count() . "\n";
    
    // Group by event type
    $eventCounts = $finalAudits->groupBy('event')->map(function($group) {
        return $group->count();
    });
    
    echo "Audit logs by event type:\n";
    foreach ($eventCounts as $event => $count) {
        echo "- $event: $count\n";
    }
    
    echo "\n=== Testing Middleware Registration ===\n";
    
    // Check if middleware is registered in the HTTP kernel
    $kernelClass = 'App\\Http\\Kernel';
    if (class_exists($kernelClass)) {
        echo "HTTP Kernel class found\n";
        
        $kernel = new $kernelClass(app(), app('router'));
        
        // Check middleware groups and global middleware
        $reflection = new ReflectionClass($kernel);
        
        if ($reflection->hasProperty('middleware')) {
            $middlewareProperty = $reflection->getProperty('middleware');
            $middlewareProperty->setAccessible(true);
            $globalMiddleware = $middlewareProperty->getValue($kernel);
            
            echo "Global middleware count: " . count($globalMiddleware) . "\n";
            
            $auditMiddlewareFound = false;
            foreach ($globalMiddleware as $mw) {
                if (strpos($mw, 'AuditMiddleware') !== false) {
                    echo "- AuditMiddleware found in global middleware: $mw\n";
                    $auditMiddlewareFound = true;
                }
            }
            
            if (!$auditMiddlewareFound) {
                echo "- AuditMiddleware not found in global middleware\n";
            }
        }
        
        if ($reflection->hasProperty('middlewareGroups')) {
            $middlewareGroupsProperty = $reflection->getProperty('middlewareGroups');
            $middlewareGroupsProperty->setAccessible(true);
            $middlewareGroups = $middlewareGroupsProperty->getValue($kernel);
            
            echo "Middleware groups: " . implode(', ', array_keys($middlewareGroups)) . "\n";
            
            foreach ($middlewareGroups as $group => $middlewares) {
                foreach ($middlewares as $mw) {
                    if (strpos($mw, 'AuditMiddleware') !== false) {
                        echo "- AuditMiddleware found in '$group' group: $mw\n";
                    }
                }
            }
        }
    }
    
    echo "\nAuditMiddleware test completed!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}