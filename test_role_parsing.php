<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "=== Role Parsing Test ===\n\n";

// Authenticate as admin
$adminUser = User::where('email', 'admin@pnsdhampur.local')->first();
Auth::login($adminUser);

echo "Authenticated as: " . Auth::user()->email . " (Role: " . Auth::user()->role . ")\n\n";

// Test how the middleware handles the role string
$request = Request::create('/api/external/biometric/devices', 'GET');
$request->headers->set('Accept', 'application/json');

$roleMiddleware = new RoleMiddleware();

echo "Testing different role parameter formats:\n\n";

// Test 1: Individual parameters (how Laravel passes them)
echo "1. Testing with individual parameters: 'admin', 'principal', 'teacher'\n";
try {
    $response = $roleMiddleware->handle($request, function($req) {
        return response()->json(['success' => true]);
    }, 'admin', 'principal', 'teacher');
    
    echo "   Status: " . $response->getStatusCode() . "\n";
    if ($response->getStatusCode() !== 200) {
        echo "   Content: " . $response->getContent() . "\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n2. Testing with comma-separated string: 'admin,principal,teacher'\n";
try {
    $response = $roleMiddleware->handle($request, function($req) {
        return response()->json(['success' => true]);
    }, 'admin,principal,teacher');
    
    echo "   Status: " . $response->getStatusCode() . "\n";
    if ($response->getStatusCode() !== 200) {
        echo "   Content: " . $response->getContent() . "\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n3. Testing with array: ['admin', 'principal', 'teacher']\n";
try {
    $roles = ['admin', 'principal', 'teacher'];
    $response = $roleMiddleware->handle($request, function($req) {
        return response()->json(['success' => true]);
    }, ...$roles);
    
    echo "   Status: " . $response->getStatusCode() . "\n";
    if ($response->getStatusCode() !== 200) {
        echo "   Content: " . $response->getContent() . "\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n4. Debug: What does hasAnyRole receive?\n";
$testRoles1 = ['admin', 'principal', 'teacher'];
$testRoles2 = ['admin,principal,teacher'];

echo "   hasAnyRole(['admin', 'principal', 'teacher']): " . ($adminUser->hasAnyRole($testRoles1) ? 'Yes' : 'No') . "\n";
echo "   hasAnyRole(['admin,principal,teacher']): " . ($adminUser->hasAnyRole($testRoles2) ? 'Yes' : 'No') . "\n";

echo "\n=== Test Complete ===\n";