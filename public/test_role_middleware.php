<?php

echo "=== Testing Role Middleware in Isolation ===\n\n";

// Bootstrap Laravel
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Middleware\RoleMiddleware;
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
    
    // Test user role methods
    echo "User role methods test:\n";
    echo "- hasRole('admin'): " . ($adminUser->hasRole('admin') ? 'Yes' : 'No') . "\n";
    echo "- hasAnyRole(['admin', 'principal', 'teacher']): " . ($adminUser->hasAnyRole(['admin', 'principal', 'teacher']) ? 'Yes' : 'No') . "\n";
    echo "- canAccessAttendance(): " . ($adminUser->canAccessAttendance() ? 'Yes' : 'No') . "\n\n";
    
    // Create a mock request for the biometric endpoint
    $request = Request::create('/api/external/biometric/devices', 'GET');
    $request->headers->set('Accept', 'application/json');
    $request->headers->set('User-Agent', 'Test-Client/1.0');
    
    echo "Testing RoleMiddleware with request:\n";
    echo "- URL: " . $request->fullUrl() . "\n";
    echo "- Method: " . $request->method() . "\n";
    echo "- User: " . Auth::user()->email . " (Role: " . Auth::user()->role . ")\n\n";
    
    // Test the role middleware with the exact roles from the route
    $roleMiddleware = new RoleMiddleware();
    $requiredRoles = ['admin', 'principal', 'teacher'];
    
    $startTime = microtime(true);
    $startMemory = memory_get_usage(true);
    
    echo "1. Testing RoleMiddleware execution...\n";
    echo "Required roles: " . implode(', ', $requiredRoles) . "\n";
    
    $response = $roleMiddleware->handle($request, function($req) {
        echo "✅ RoleMiddleware passed successfully!\n";
        return response()->json([
            'success' => true, 
            'message' => 'Role middleware test passed',
            'timestamp' => now()->toISOString(),
            'user_id' => Auth::id(),
            'user_role' => Auth::user()->role,
            'endpoint' => $req->path()
        ]);
    }, ...$requiredRoles);
    
    $endTime = microtime(true);
    $endMemory = memory_get_usage(true);
    
    echo "✅ RoleMiddleware execution completed\n";
    echo "- Execution time: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
    echo "- Memory used: " . round(($endMemory - $startMemory) / 1024 / 1024, 2) . " MB\n";
    echo "- Response status: " . $response->getStatusCode() . "\n";
    
    // Check response headers
    echo "- Response headers:\n";
    foreach ($response->headers->all() as $name => $values) {
        echo "  * $name: " . implode(', ', $values) . "\n";
    }
    
    echo "- Response content: " . $response->getContent() . "\n\n";
    
    // Test with different user roles
    echo "2. Testing with different user roles...\n";
    
    // Test with teacher role
    $teacherUser = User::where('role', 'teacher')->first();
    if ($teacherUser) {
        Auth::login($teacherUser);
        echo "Testing as teacher: " . $teacherUser->email . "\n";
        
        $teacherResponse = $roleMiddleware->handle($request, function($req) {
            return response()->json(['message' => 'Teacher access granted']);
        }, ...$requiredRoles);
        
        echo "Teacher response status: " . $teacherResponse->getStatusCode() . "\n";
        if ($teacherResponse->getStatusCode() !== 200) {
            echo "Teacher response content: " . $teacherResponse->getContent() . "\n";
        }
    }
    
    // Test with student role (should fail)
    $studentUser = User::where('role', 'student')->first();
    if ($studentUser) {
        Auth::login($studentUser);
        echo "Testing as student: " . $studentUser->email . "\n";
        
        $studentResponse = $roleMiddleware->handle($request, function($req) {
            return response()->json(['message' => 'Student access granted']);
        }, ...$requiredRoles);
        
        echo "Student response status: " . $studentResponse->getStatusCode() . "\n";
        if ($studentResponse->getStatusCode() !== 200) {
            echo "Student response content: " . substr($studentResponse->getContent(), 0, 200) . "...\n";
        }
    }
    
    // Re-authenticate as admin for final test
    Auth::login($adminUser);
    
    echo "\n3. Testing multiple rapid requests with RoleMiddleware...\n";
    
    for ($i = 1; $i <= 5; $i++) {
        $rapidRequest = Request::create('/api/external/biometric/devices', 'GET');
        $rapidRequest->headers->set('Accept', 'application/json');
        
        $rapidStartTime = microtime(true);
        
        $rapidResponse = $roleMiddleware->handle($rapidRequest, function($req) use ($i) {
            return response()->json(['test' => 'rapid_request_' . $i]);
        }, ...$requiredRoles);
        
        $rapidEndTime = microtime(true);
        
        echo "  Request $i: Status " . $rapidResponse->getStatusCode() . 
             " (" . round(($rapidEndTime - $rapidStartTime) * 1000, 2) . " ms)\n";
    }
    
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