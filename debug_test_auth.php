<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\PermissionMiddleware;

echo "=== Test Authentication Debug ===\n\n";

// Create admin user exactly like in the test
$adminUser = User::factory()->admin()->create();
echo "Created admin user:\n";
echo "- ID: " . $adminUser->id . "\n";
echo "- Role: " . $adminUser->role . "\n";
echo "- Email: " . $adminUser->email . "\n\n";

// Test the hasAnyRole method with exact route middleware roles
$routeRoles = ['admin', 'principal', 'class_teacher'];
echo "Route requires roles: " . implode(', ', $routeRoles) . "\n";
echo "User hasAnyRole result: " . ($adminUser->hasAnyRole($routeRoles) ? 'PASS' : 'FAIL') . "\n\n";

// Test individual role checks
foreach ($routeRoles as $role) {
    echo "User has role '$role': " . ($adminUser->hasRole($role) ? 'Yes' : 'No') . "\n";
}
echo "\n";

// Test attendance access
echo "Can access attendance: " . ($adminUser->canAccessAttendance() ? 'Yes' : 'No') . "\n";

// Test view_audit_trails permission
echo "Has view_audit_trails permission: " . ($adminUser->hasPermission('view_audit_trails') ? 'Yes' : 'No') . "\n\n";

// Simulate the middleware check step by step
echo "=== Middleware Simulation ===\n";

// Step 1: Authentication check (simulated as passed)
echo "1. Authentication: PASS (user exists)\n";

// Step 2: Attendance access check
$attendanceAccess = $adminUser->canAccessAttendance();
echo "2. Attendance access: " . ($attendanceAccess ? 'PASS' : 'FAIL') . "\n";

// Step 3: Role check
$roleCheck = $adminUser->hasAnyRole($routeRoles);
echo "3. Role check: " . ($roleCheck ? 'PASS' : 'FAIL') . "\n";

// Step 4: Permission check
$permissionCheck = $adminUser->hasPermission('view_audit_trails');
echo "4. Permission check: " . ($permissionCheck ? 'PASS' : 'FAIL') . "\n\n";

// Overall result
$overallPass = $attendanceAccess && $roleCheck && $permissionCheck;
echo "Overall middleware result: " . ($overallPass ? 'SHOULD PASS' : 'SHOULD FAIL') . "\n\n";

// Test what happens when we authenticate as this user
echo "=== Authentication Test ===\n";
Auth::login($adminUser);
echo "Logged in as user ID: " . Auth::id() . "\n";
echo "Authenticated user role: " . Auth::user()->role . "\n";
echo "Auth::check(): " . (Auth::check() ? 'Yes' : 'No') . "\n\n";

// Test the actual middleware classes
echo "=== Actual Middleware Test ===\n";

$request = Request::create('/class-data-audit', 'GET');

// Test RoleMiddleware
$roleMiddleware = new RoleMiddleware();
echo "Testing RoleMiddleware with roles: " . implode(', ', $routeRoles) . "\n";

try {
    $result = $roleMiddleware->handle($request, function($req) {
        return response('Success', 200);
    }, ...$routeRoles);
    
    echo "RoleMiddleware result: " . $result->getStatusCode() . "\n";
    if ($result->getStatusCode() === 302) {
        echo "Redirect location: " . $result->headers->get('Location') . "\n";
    }
} catch (Exception $e) {
    echo "RoleMiddleware error: " . $e->getMessage() . "\n";
}

// Test PermissionMiddleware
$permissionMiddleware = new PermissionMiddleware();
echo "\nTesting PermissionMiddleware with permission: view_audit_trails\n";

try {
    $result = $permissionMiddleware->handle($request, function($req) {
        return response('Success', 200);
    }, 'view_audit_trails');
    
    echo "PermissionMiddleware result: " . $result->getStatusCode() . "\n";
    if ($result->getStatusCode() === 302) {
        echo "Redirect location: " . $result->headers->get('Location') . "\n";
    }
} catch (Exception $e) {
    echo "PermissionMiddleware error: " . $e->getMessage() . "\n";
}