<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use App\Models\NewRole;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Request::capture()
);

echo "=== SETTING UP ROLE PERMISSIONS ===\n\n";

// Define comprehensive permissions using the proper structure
$permissionsToCreate = [
    // User Management
    ['name' => 'users.view.all', 'display_name' => 'View All Users', 'module' => 'users', 'action' => 'view', 'scope' => 'all'],
    ['name' => 'users.view.school', 'display_name' => 'View School Users', 'module' => 'users', 'action' => 'view', 'scope' => 'school'],
    ['name' => 'users.create', 'display_name' => 'Create Users', 'module' => 'users', 'action' => 'create', 'scope' => 'all'],
    ['name' => 'users.edit.all', 'display_name' => 'Edit All Users', 'module' => 'users', 'action' => 'edit', 'scope' => 'all'],
    ['name' => 'users.edit.school', 'display_name' => 'Edit School Users', 'module' => 'users', 'action' => 'edit', 'scope' => 'school'],
    ['name' => 'users.delete.all', 'display_name' => 'Delete All Users', 'module' => 'users', 'action' => 'delete', 'scope' => 'all'],
    
    // Role Management
    ['name' => 'roles.view', 'display_name' => 'View Roles', 'module' => 'roles', 'action' => 'view', 'scope' => 'all'],
    ['name' => 'roles.create', 'display_name' => 'Create Roles', 'module' => 'roles', 'action' => 'create', 'scope' => 'all'],
    ['name' => 'roles.edit', 'display_name' => 'Edit Roles', 'module' => 'roles', 'action' => 'edit', 'scope' => 'all'],
    ['name' => 'roles.delete', 'display_name' => 'Delete Roles', 'module' => 'roles', 'action' => 'delete', 'scope' => 'all'],
    ['name' => 'roles.assign', 'display_name' => 'Assign Roles', 'module' => 'roles', 'action' => 'assign', 'scope' => 'all'],
    
    // School Management
    ['name' => 'schools.view.all', 'display_name' => 'View All Schools', 'module' => 'schools', 'action' => 'view', 'scope' => 'all'],
    ['name' => 'schools.view.own', 'display_name' => 'View Own School', 'module' => 'schools', 'action' => 'view', 'scope' => 'own'],
    ['name' => 'schools.create', 'display_name' => 'Create Schools', 'module' => 'schools', 'action' => 'create', 'scope' => 'all'],
    ['name' => 'schools.edit.all', 'display_name' => 'Edit All Schools', 'module' => 'schools', 'action' => 'edit', 'scope' => 'all'],
    ['name' => 'schools.edit.own', 'display_name' => 'Edit Own School', 'module' => 'schools', 'action' => 'edit', 'scope' => 'own'],
    ['name' => 'schools.delete', 'display_name' => 'Delete Schools', 'module' => 'schools', 'action' => 'delete', 'scope' => 'all'],
    
    // Attendance Management
    ['name' => 'attendance.view.all', 'display_name' => 'View All Attendance', 'module' => 'attendance', 'action' => 'view', 'scope' => 'all'],
    ['name' => 'attendance.view.school', 'display_name' => 'View School Attendance', 'module' => 'attendance', 'action' => 'view', 'scope' => 'school'],
    ['name' => 'attendance.view.assigned', 'display_name' => 'View Assigned Attendance', 'module' => 'attendance', 'action' => 'view', 'scope' => 'assigned'],
    ['name' => 'attendance.view.own', 'display_name' => 'View Own Attendance', 'module' => 'attendance', 'action' => 'view', 'scope' => 'own'],
    ['name' => 'attendance.mark.all', 'display_name' => 'Mark All Attendance', 'module' => 'attendance', 'action' => 'mark', 'scope' => 'all'],
    ['name' => 'attendance.mark.school', 'display_name' => 'Mark School Attendance', 'module' => 'attendance', 'action' => 'mark', 'scope' => 'school'],
    ['name' => 'attendance.mark.assigned', 'display_name' => 'Mark Assigned Attendance', 'module' => 'attendance', 'action' => 'mark', 'scope' => 'assigned'],
    ['name' => 'attendance.edit.all', 'display_name' => 'Edit All Attendance', 'module' => 'attendance', 'action' => 'edit', 'scope' => 'all'],
    ['name' => 'attendance.edit.school', 'display_name' => 'Edit School Attendance', 'module' => 'attendance', 'action' => 'edit', 'scope' => 'school'],
    ['name' => 'attendance.edit.assigned', 'display_name' => 'Edit Assigned Attendance', 'module' => 'attendance', 'action' => 'edit', 'scope' => 'assigned'],
    
    // Student Management
    ['name' => 'students.view.all', 'display_name' => 'View All Students', 'module' => 'students', 'action' => 'view', 'scope' => 'all'],
    ['name' => 'students.view.school', 'display_name' => 'View School Students', 'module' => 'students', 'action' => 'view', 'scope' => 'school'],
    ['name' => 'students.view.assigned', 'display_name' => 'View Assigned Students', 'module' => 'students', 'action' => 'view', 'scope' => 'assigned'],
    ['name' => 'students.view.own', 'display_name' => 'View Own Data', 'module' => 'students', 'action' => 'view', 'scope' => 'own'],
    ['name' => 'students.create', 'display_name' => 'Create Students', 'module' => 'students', 'action' => 'create', 'scope' => 'all'],
    ['name' => 'students.edit.all', 'display_name' => 'Edit All Students', 'module' => 'students', 'action' => 'edit', 'scope' => 'all'],
    ['name' => 'students.edit.school', 'display_name' => 'Edit School Students', 'module' => 'students', 'action' => 'edit', 'scope' => 'school'],
    ['name' => 'students.edit.assigned', 'display_name' => 'Edit Assigned Students', 'module' => 'students', 'action' => 'edit', 'scope' => 'assigned'],
    ['name' => 'students.delete.all', 'display_name' => 'Delete All Students', 'module' => 'students', 'action' => 'delete', 'scope' => 'all'],
    
    // Teacher Management
    ['name' => 'teachers.view.all', 'display_name' => 'View All Teachers', 'module' => 'teachers', 'action' => 'view', 'scope' => 'all'],
    ['name' => 'teachers.view.school', 'display_name' => 'View School Teachers', 'module' => 'teachers', 'action' => 'view', 'scope' => 'school'],
    ['name' => 'teachers.create', 'display_name' => 'Create Teachers', 'module' => 'teachers', 'action' => 'create', 'scope' => 'all'],
    ['name' => 'teachers.edit.all', 'display_name' => 'Edit All Teachers', 'module' => 'teachers', 'action' => 'edit', 'scope' => 'all'],
    ['name' => 'teachers.edit.school', 'display_name' => 'Edit School Teachers', 'module' => 'teachers', 'action' => 'edit', 'scope' => 'school'],
    ['name' => 'teachers.delete.all', 'display_name' => 'Delete All Teachers', 'module' => 'teachers', 'action' => 'delete', 'scope' => 'all'],
    
    // Fee Management
    ['name' => 'fees.view.all', 'display_name' => 'View All Fees', 'module' => 'fees', 'action' => 'view', 'scope' => 'all'],
    ['name' => 'fees.view.school', 'display_name' => 'View School Fees', 'module' => 'fees', 'action' => 'view', 'scope' => 'school'],
    ['name' => 'fees.view.own', 'display_name' => 'View Own Fees', 'module' => 'fees', 'action' => 'view', 'scope' => 'own'],
    ['name' => 'fees.create', 'display_name' => 'Create Fees', 'module' => 'fees', 'action' => 'create', 'scope' => 'all'],
    ['name' => 'fees.edit.all', 'display_name' => 'Edit All Fees', 'module' => 'fees', 'action' => 'edit', 'scope' => 'all'],
    ['name' => 'fees.pay', 'display_name' => 'Pay Fees', 'module' => 'fees', 'action' => 'pay', 'scope' => 'own'],
    
    // Results Management
    ['name' => 'results.view.all', 'display_name' => 'View All Results', 'module' => 'results', 'action' => 'view', 'scope' => 'all'],
    ['name' => 'results.view.school', 'display_name' => 'View School Results', 'module' => 'results', 'action' => 'view', 'scope' => 'school'],
    ['name' => 'results.view.assigned', 'display_name' => 'View Assigned Results', 'module' => 'results', 'action' => 'view', 'scope' => 'assigned'],
    ['name' => 'results.view.own', 'display_name' => 'View Own Results', 'module' => 'results', 'action' => 'view', 'scope' => 'own'],
    ['name' => 'results.enter', 'display_name' => 'Enter Results', 'module' => 'results', 'action' => 'enter', 'scope' => 'assigned'],
    ['name' => 'results.edit.all', 'display_name' => 'Edit All Results', 'module' => 'results', 'action' => 'edit', 'scope' => 'all'],
    ['name' => 'results.edit.assigned', 'display_name' => 'Edit Assigned Results', 'module' => 'results', 'action' => 'edit', 'scope' => 'assigned'],
    
    // Syllabus Management
    ['name' => 'syllabus.view.all', 'display_name' => 'View All Syllabus', 'module' => 'syllabus', 'action' => 'view', 'scope' => 'all'],
    ['name' => 'syllabus.view.school', 'display_name' => 'View School Syllabus', 'module' => 'syllabus', 'action' => 'view', 'scope' => 'school'],
    ['name' => 'syllabus.view.assigned', 'display_name' => 'View Assigned Syllabus', 'module' => 'syllabus', 'action' => 'view', 'scope' => 'assigned'],
    ['name' => 'syllabus.upload', 'display_name' => 'Upload Syllabus', 'module' => 'syllabus', 'action' => 'upload', 'scope' => 'assigned'],
    ['name' => 'syllabus.edit.all', 'display_name' => 'Edit All Syllabus', 'module' => 'syllabus', 'action' => 'edit', 'scope' => 'all'],
    ['name' => 'syllabus.edit.assigned', 'display_name' => 'Edit Assigned Syllabus', 'module' => 'syllabus', 'action' => 'edit', 'scope' => 'assigned'],
    
    // Reports
    ['name' => 'reports.view.all', 'display_name' => 'View All Reports', 'module' => 'reports', 'action' => 'view', 'scope' => 'all'],
    ['name' => 'reports.view.school', 'display_name' => 'View School Reports', 'module' => 'reports', 'action' => 'view', 'scope' => 'school'],
    ['name' => 'reports.view.own', 'display_name' => 'View Own Reports', 'module' => 'reports', 'action' => 'view', 'scope' => 'own'],
    ['name' => 'reports.generate', 'display_name' => 'Generate Reports', 'module' => 'reports', 'action' => 'generate', 'scope' => 'all'],
    
    // System Management
    ['name' => 'system.manage', 'display_name' => 'Manage System', 'module' => 'system', 'action' => 'manage', 'scope' => 'all'],
    ['name' => 'system.configure', 'display_name' => 'Configure System', 'module' => 'system', 'action' => 'configure', 'scope' => 'all'],
];

// Define role permissions mapping
$rolePermissions = [
    'super_admin' => [
        'users.view.all', 'users.create', 'users.edit.all', 'users.delete.all',
        'roles.view', 'roles.create', 'roles.edit', 'roles.delete', 'roles.assign',
        'schools.view.all', 'schools.create', 'schools.edit.all', 'schools.delete',
        'attendance.view.all', 'attendance.mark.all', 'attendance.edit.all',
        'students.view.all', 'students.create', 'students.edit.all', 'students.delete.all',
        'teachers.view.all', 'teachers.create', 'teachers.edit.all', 'teachers.delete.all',
        'fees.view.all', 'fees.create', 'fees.edit.all',
        'results.view.all', 'results.edit.all',
        'syllabus.view.all', 'syllabus.edit.all',
        'reports.view.all', 'reports.generate',
        'system.manage', 'system.configure'
    ],
    'admin' => [
        'users.view.school', 'users.create', 'users.edit.school',
        'schools.view.own', 'schools.edit.own',
        'teachers.view.school', 'teachers.create', 'teachers.edit.school',
        'students.view.school', 'students.create', 'students.edit.school',
        'fees.view.school', 'fees.create', 'fees.edit.all',
        'attendance.view.school', 'attendance.mark.school',
        'results.view.school', 'syllabus.view.school',
        'reports.view.school', 'reports.generate'
    ],
    'principal' => [
        'schools.view.own', 'schools.edit.own',
        'teachers.view.school', 'teachers.create', 'teachers.edit.school',
        'students.view.school', 'students.create', 'students.edit.school',
        'attendance.view.school', 'attendance.mark.school', 'attendance.edit.school',
        'results.view.school', 'results.edit.all',
        'syllabus.view.school', 'syllabus.edit.all',
        'fees.view.school', 'fees.create',
        'reports.view.school', 'reports.generate'
    ],
    'teacher' => [
        'attendance.view.assigned', 'attendance.mark.assigned', 'attendance.edit.assigned',
        'students.view.assigned', 'students.edit.assigned',
        'results.view.assigned', 'results.enter', 'results.edit.assigned',
        'syllabus.view.assigned', 'syllabus.upload', 'syllabus.edit.assigned'
    ],
    'student' => [
        'attendance.view.own', 'results.view.own', 'syllabus.view.assigned',
        'fees.view.own', 'students.view.own', 'reports.view.own'
    ],
    'parent' => [
        'attendance.view.own', 'results.view.own', 'fees.view.own', 'fees.pay',
        'students.view.assigned', 'reports.view.own'
    ]
];

DB::beginTransaction();

try {
    // Create permissions if they don't exist
    echo "Creating permissions...\n";
    foreach ($permissionsToCreate as $permissionData) {
        $permission = Permission::where('name', $permissionData['name'])->first();
        if (!$permission) {
            $permission = Permission::create($permissionData);
            echo "✅ Created permission: {$permissionData['name']}\n";
        } else {
            echo "⚪ Permission exists: {$permissionData['name']}\n";
        }
    }
    
    // Assign permissions to roles
    echo "\nAssigning permissions to roles...\n";
    foreach ($rolePermissions as $roleName => $permissions) {
        $role = NewRole::where('name', $roleName)->first();
        if (!$role) {
            echo "❌ Role not found: {$roleName}\n";
            continue;
        }
        
        echo "Setting up permissions for {$roleName}...\n";
        
        // Clear existing permissions
        DB::table('role_permissions')->where('role_id', $role->id)->delete();
        
        // Add new permissions
        $permissionIds = [];
        foreach ($permissions as $permissionName) {
            $permission = Permission::where('name', $permissionName)->first();
            if ($permission) {
                $permissionIds[] = [
                    'role_id' => $role->id,
                    'permission_id' => $permission->id,
                    'is_granted' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            } else {
                echo "⚠️ Permission not found: {$permissionName}\n";
            }
        }
        
        if (!empty($permissionIds)) {
            DB::table('role_permissions')->insert($permissionIds);
        }
        
        echo "✅ Assigned " . count($permissionIds) . " permissions to {$roleName}\n";
    }
    
    DB::commit();
    echo "\n✅ All role permissions set up successfully!\n";
    
} catch (Exception $e) {
    DB::rollBack();
    echo "\n❌ Error setting up permissions: " . $e->getMessage() . "\n";
}

// Verify permissions
echo "\n=== VERIFYING ROLE PERMISSIONS ===\n";
foreach ($rolePermissions as $roleName => $expectedPermissions) {
    $role = NewRole::where('name', $roleName)->first();
    if ($role) {
        $actualPermissions = DB::table('role_permissions')
            ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
            ->where('role_permissions.role_id', $role->id)
            ->where('role_permissions.is_granted', true)
            ->pluck('permissions.name')
            ->toArray();
        
        $missingPermissions = array_diff($expectedPermissions, $actualPermissions);
        
        echo "{$roleName}: " . count($actualPermissions) . "/" . count($expectedPermissions) . " permissions";
        if (empty($missingPermissions)) {
            echo " ✅\n";
        } else {
            echo " ❌ (Missing: " . implode(', ', array_slice($missingPermissions, 0, 3)) . 
                 (count($missingPermissions) > 3 ? '...' : '') . ")\n";
        }
    }
}

echo "\n=== ROLE PERMISSIONS SETUP COMPLETED ===\n";