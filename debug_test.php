<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\PermissionMiddleware;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a test request
$request = Request::create('/class-data-audit', 'GET');

// Create admin user
$adminUser = User::factory()->admin()->create();

echo "Admin user created:\n";
echo "ID: " . $adminUser->id . "\n";
echo "Role: " . $adminUser->role . "\n";
echo "Can access attendance: " . ($adminUser->canAccessAttendance() ? 'Yes' : 'No') . "\n";
echo "Has admin role: " . ($adminUser->hasRole('admin') ? 'Yes' : 'No') . "\n";
echo "Has any role [admin,principal,class_teacher]: " . ($adminUser->hasAnyRole(['admin', 'principal', 'class_teacher']) ? 'Yes' : 'No') . "\n";

// Test role middleware
echo "\nTesting RoleMiddleware:\n";
$roleMiddleware = new RoleMiddleware();

// Simulate authentication
auth()->login($adminUser);

try {
    $response = $roleMiddleware->handle($request, function($req) {
        return response('Success', 200);
    }, 'admin', 'principal', 'class_teacher');
    
    echo "Role middleware result: " . $response->getStatusCode() . "\n";
    echo "Content: " . $response->getContent() . "\n";
} catch (Exception $e) {
    echo "Role middleware error: " . $e->getMessage() . "\n";
}

// Test permission middleware
echo "\nTesting PermissionMiddleware:\n";
$permissionMiddleware = new PermissionMiddleware();

try {
    $response = $permissionMiddleware->handle($request, function($req) {
        return response('Success', 200);
    }, 'view_audit_trails');
    
    echo "Permission middleware result: " . $response->getStatusCode() . "\n";
    echo "Content: " . $response->getContent() . "\n";
} catch (Exception $e) {
    echo "Permission middleware error: " . $e->getMessage() . "\n";
}

// Clean up
$adminUser->delete();