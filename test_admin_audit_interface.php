<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Gate;

echo "Testing admin audit interface permissions...\n\n";

try {
    // Find or create an admin user
    $adminUser = User::where('role', 'admin')->first();
    if (!$adminUser) {
        $adminUser = User::factory()->admin()->create();
        echo "Created new admin user with ID: " . $adminUser->id . "\n";
    } else {
        echo "Using existing admin user with ID: " . $adminUser->id . "\n";
    }
    
    echo "Admin user role: " . $adminUser->role . "\n";
    echo "Admin can access attendance: " . ($adminUser->canAccessAttendance() ? 'Yes' : 'No') . "\n";
    
    // Check permissions
    $permissions = Role::getAttendancePermissions($adminUser->role);
    echo "Admin permissions: " . implode(', ', $permissions) . "\n\n";
    
    echo "Admin has view_audit_logs permission: " . ($adminUser->hasPermission('view_audit_logs') ? 'Yes' : 'No') . "\n";
    echo "Admin has view_audit_trails permission: " . ($adminUser->hasPermission('view_audit_trails') ? 'Yes' : 'No') . "\n";
    
    // Check if view_audit_logs is in permissions array
    echo "view_audit_logs in permissions array: " . (in_array('view_audit_logs', $permissions) ? 'Yes' : 'No') . "\n";
    echo "view_audit_trails in permissions array: " . (in_array('view_audit_trails', $permissions) ? 'Yes' : 'No') . "\n\n";
    
    // Define a gate for view_audit_logs that maps to view_audit_trails
    Gate::define('view_audit_logs', function ($user) {
        return $user->hasPermission('view_audit_trails');
    });
    
    echo "Defined gate for view_audit_logs -> view_audit_trails\n";
    
    // Test the gate
    echo "Gate check for view_audit_logs: " . (Gate::forUser($adminUser)->allows('view_audit_logs') ? 'Allowed' : 'Denied') . "\n";
    
    echo "\nPermission analysis completed!\n";
    echo "Issue: AuditController checks for 'view_audit_logs' but Role model defines 'view_audit_trails'\n";
    echo "Solution: Either update AuditController to use 'view_audit_trails' or add 'view_audit_logs' to Role permissions\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}