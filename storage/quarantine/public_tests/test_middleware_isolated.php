<?php

echo "=== Testing External Integration Middleware in Isolation ===\n\n";

// Bootstrap Laravel
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
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
    
    // Create a mock request for the biometric endpoint
    $request = Request::create('/api/external/biometric/devices', 'GET');
    $request->headers->set('Accept', 'application/json');
    $request->headers->set('User-Agent', 'Test-Client/1.0');
    
    echo "Testing middleware with request:\n";
    echo "- URL: " . $request->fullUrl() . "\n";
    echo "- Method: " . $request->method() . "\n";
    echo "- IP: " . $request->ip() . "\n";
    echo "- User Agent: " . $request->userAgent() . "\n\n";
    
    // Test the middleware
    $middleware = new ExternalIntegrationMiddleware();
    
    $startTime = microtime(true);
    $startMemory = memory_get_usage(true);
    
    echo "1. Testing middleware execution...\n";
    
    $response = $middleware->handle($request, function($req) {
        echo "✅ Middleware passed successfully!\n";
        return response()->json([
            'success' => true, 
            'message' => 'Middleware test passed',
            'timestamp' => now()->toISOString(),
            'user_id' => Auth::id(),
            'endpoint' => $req->path()
        ]);
    });
    
    $endTime = microtime(true);
    $endMemory = memory_get_usage(true);
    
    echo "✅ Middleware execution completed\n";
    echo "- Execution time: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
    echo "- Memory used: " . round(($endMemory - $startMemory) / 1024 / 1024, 2) . " MB\n";
    echo "- Response status: " . $response->getStatusCode() . "\n";
    
    // Check response headers
    echo "- Response headers:\n";
    foreach ($response->headers->all() as $name => $values) {
        echo "  * $name: " . implode(', ', $values) . "\n";
    }
    
    echo "- Response content: " . $response->getContent() . "\n\n";
    
    // Test multiple rapid requests to check for suspicious activity detection
    echo "2. Testing rapid requests (suspicious activity detection)...\n";
    
    for ($i = 1; $i <= 5; $i++) {
        $rapidRequest = Request::create('/api/external/biometric/devices', 'GET');
        $rapidRequest->headers->set('Accept', 'application/json');
        
        $rapidStartTime = microtime(true);
        
        $rapidResponse = $middleware->handle($rapidRequest, function($req) use ($i) {
            return response()->json(['test' => 'rapid_request_' . $i]);
        });
        
        $rapidEndTime = microtime(true);
        
        echo "  Request $i: Status " . $rapidResponse->getStatusCode() . 
             " (" . round(($rapidEndTime - $rapidStartTime) * 1000, 2) . " ms)\n";
        
        if ($rapidResponse->getStatusCode() === 429) {
            echo "  ⚠️ Rate limiting triggered at request $i\n";
            break;
        }
    }
    
    echo "\n3. Testing with file upload simulation...\n";
    
    // Create a mock file upload request
    $fileRequest = Request::create('/api/external/biometric/import', 'POST');
    $fileRequest->headers->set('Accept', 'application/json');
    
    // Simulate file upload (without actual file)
    $fileResponse = $middleware->handle($fileRequest, function($req) {
        return response()->json(['message' => 'File upload test passed']);
    });
    
    echo "- File upload test status: " . $fileResponse->getStatusCode() . "\n";
    echo "- File upload response: " . $fileResponse->getContent() . "\n";
    
} catch (Exception $e) {
    echo "❌ Exception caught: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "❌ Fatal error caught: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";