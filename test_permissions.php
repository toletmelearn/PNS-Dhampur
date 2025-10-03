<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Role;
use App\Models\ClassDataAudit;
use App\Models\ClassDataApproval;

// Create admin user
$adminUser = User::factory()->admin()->create();
echo "Admin user created with ID: " . $adminUser->id . "\n";
echo "Admin user role: " . $adminUser->role . "\n";

// Check if admin has permission
$hasPermission = $adminUser->hasPermission('approve_audit_changes');
echo "Admin has approve_audit_changes permission: " . ($hasPermission ? 'Yes' : 'No') . "\n";

// Check hasAnyRole method
$hasAnyRole = $adminUser->hasAnyRole(['admin', 'principal', 'class_teacher']);
echo "Admin hasAnyRole(['admin', 'principal', 'class_teacher']): " . ($hasAnyRole ? 'Yes' : 'No') . "\n";

// Check canAccessAttendance method
$canAccessAttendance = $adminUser->canAccessAttendance();
echo "Admin canAccessAttendance: " . ($canAccessAttendance ? 'Yes' : 'No') . "\n";

// Check hasAnyPermission method with approve_audit_changes
$hasAnyPermissionAudit = $adminUser->hasAnyPermission(['approve_audit_changes']);
echo "Admin hasAnyPermission(['approve_audit_changes']): " . ($hasAnyPermissionAudit ? 'Yes' : 'No') . "\n";

// List all permissions for admin role
$permissions = Role::getAttendancePermissions($adminUser->role);
echo "Admin role permissions: " . implode(', ', $permissions) . "\n";

// Create audit and approval records like in the test
$audit = ClassDataAudit::factory()->create();
echo "Audit created with ID: " . $audit->id . "\n";

$approval = ClassDataApproval::factory()->create([
    'audit_id' => $audit->id,
    'assigned_to' => $adminUser->id,
    'status' => 'pending'
]);
echo "Approval created with ID: " . $approval->id . "\n";
echo "Approval status: " . $approval->status . "\n";
echo "Approval assigned to user ID: " . $approval->assigned_to . "\n";

// Test canApprove logic manually
echo "\n--- Testing canApprove logic ---\n";
echo "1. Approval status is pending: " . ($approval->status === 'pending' ? 'Yes' : 'No') . "\n";
echo "2. User assigned to approval: " . ($approval->assigned_to == $adminUser->id ? 'Yes' : 'No') . "\n";
echo "3. User has approve_audit_changes permission: " . ($adminUser->hasPermission('approve_audit_changes') ? 'Yes' : 'No') . "\n";
echo "4. User role is admin or principal: " . (in_array($adminUser->role, ['admin', 'principal']) ? 'Yes' : 'No') . "\n";

$canApprove = ($approval->status === 'pending') && 
              (($approval->assigned_to == $adminUser->id) || 
               $adminUser->hasPermission('approve_audit_changes') || 
               in_array($adminUser->role, ['admin', 'principal']));
echo "Final canApprove result: " . ($canApprove ? 'Yes' : 'No') . "\n";

// Test ApprovalActionRequest authorization logic
echo "\n--- Testing ApprovalActionRequest authorization ---\n";
$authCheck = $adminUser->hasAnyRole(['admin', 'principal', 'class_teacher']) ||
             $adminUser->hasPermission('approve_audit_changes');
echo "ApprovalActionRequest authorization result: " . ($authCheck ? 'Yes' : 'No') . "\n";

// Test PermissionMiddleware logic
echo "\n--- Testing PermissionMiddleware logic ---\n";
echo "canAccessAttendance: " . ($adminUser->canAccessAttendance() ? 'Yes' : 'No') . "\n";
echo "hasAnyPermission(['approve_audit_changes']): " . ($adminUser->hasAnyPermission(['approve_audit_changes']) ? 'Yes' : 'No') . "\n";

echo "\nTest completed.\n";