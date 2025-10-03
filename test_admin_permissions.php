<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Role;

$adminUser = User::factory()->admin()->create();
echo "Admin user role: " . $adminUser->role . "\n";
echo "Has view_audit_trails permission: " . ($adminUser->hasPermission('view_audit_trails') ? 'Yes' : 'No') . "\n";
echo "Can access attendance: " . ($adminUser->canAccessAttendance() ? 'Yes' : 'No') . "\n";

$permissions = Role::getAttendancePermissions($adminUser->role);
echo "Admin permissions: " . implode(', ', $permissions) . "\n";

// Check if view_audit_trails is in the permissions array
echo "view_audit_trails in permissions: " . (in_array('view_audit_trails', $permissions) ? 'Yes' : 'No') . "\n";