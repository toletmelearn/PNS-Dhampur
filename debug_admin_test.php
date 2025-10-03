<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\PermissionMiddleware;

// Bootstrap Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a test request
$request = Request::create('/class-data-audit', 'GET');

// Create admin user
$adminUser = new User();
$adminUser->id = 1;
$adminUser->name = 'Test Admin';
$adminUser->email = 'admin@test.com';
$adminUser->role = 'admin';

echo "=== Admin User Debug ===\n";
echo "User Role: " . $adminUser->role . "\n";

// Test canAccessAttendance
$canAccess = $adminUser->canAccessAttendance();
echo "Can Access Attendance: " . ($canAccess ? 'Yes' : 'No') . "\n";

// Test Role::canAccessAttendance
$roleCanAccess = Role::canAccessAttendance($adminUser->role);
echo "Role Can Access Attendance: " . ($roleCanAccess ? 'Yes' : 'No') . "\n";

// Check if role is in ALL_ROLES
$allRoles = Role::ALL_ROLES;
echo "ALL_ROLES: " . implode(', ', $allRoles) . "\n";
echo "Admin in ALL_ROLES: " . (in_array('admin', $allRoles) ? 'Yes' : 'No') . "\n";

// Test hasAnyRole method
$hasRole = $adminUser->hasAnyRole(['admin']);
echo "Has Admin Role: " . ($hasRole ? 'Yes' : 'No') . "\n";

// Test permissions
$permissions = Role::getAttendancePermissions('admin');
echo "Has view_audit_trails permission: " . (in_array('view_audit_trails', $permissions) ? 'Yes' : 'No') . "\n";

// Test middleware logic simulation
echo "\n=== Middleware Simulation ===\n";

// Simulate RoleMiddleware check
$requiredRoles = ['admin', 'teacher', 'principal'];
$userHasRole = false;
foreach ($requiredRoles as $role) {
    if ($adminUser->role === $role) {
        $userHasRole = true;
        break;
    }
}
echo "User has required role: " . ($userHasRole ? 'Yes' : 'No') . "\n";

// Simulate PermissionMiddleware check
$requiredPermissions = ['view_audit_trails'];
$userHasPermission = false;
foreach ($requiredPermissions as $permission) {
    if (in_array($permission, $permissions)) {
        $userHasPermission = true;
        break;
    }
}
echo "User has required permission: " . ($userHasPermission ? 'Yes' : 'No') . "\n";

echo "\n=== Route Analysis ===\n";
// Check if the route exists
try {
    $route = app('router')->getRoutes()->match($request);
    echo "Route found: " . $route->uri() . "\n";
    echo "Route name: " . ($route->getName() ?? 'No name') . "\n";
    echo "Route middleware: " . implode(', ', $route->middleware()) . "\n";
} catch (Exception $e) {
    echo "Route error: " . $e->getMessage() . "\n";
}