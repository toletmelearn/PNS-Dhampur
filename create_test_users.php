<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use App\Models\NewUser;
use App\Models\NewRole;
use App\Models\UserRoleAssignment;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Request::capture()
);

echo "=== CREATING TEST USERS FOR COMPREHENSIVE TESTING ===\n\n";

// Check existing users
echo "Checking existing users...\n";
$existingUsers = NewUser::all();
echo "Found " . $existingUsers->count() . " users in NewUser table:\n";
foreach ($existingUsers as $user) {
    echo "- {$user->email} (ID: {$user->id})\n";
}

// Also check legacy User table
$legacyUsers = \App\Models\User::all();
echo "\nFound " . $legacyUsers->count() . " users in legacy User table:\n";
foreach ($legacyUsers as $user) {
    echo "- {$user->email} (ID: {$user->id}, Role: {$user->role})\n";
}

// Check existing roles
echo "\nChecking existing roles...\n";
$existingRoles = NewRole::all();
echo "Found " . $existingRoles->count() . " roles:\n";
foreach ($existingRoles as $role) {
    echo "- {$role->name} (Display: {$role->display_name})\n";
}

// Create missing roles if needed
$requiredRoles = [
    'super_admin' => 'Super Administrator',
    'admin' => 'Administrator', 
    'principal' => 'Principal',
    'teacher' => 'Teacher',
    'student' => 'Student',
    'parent' => 'Parent'
];

echo "\nCreating missing roles...\n";
foreach ($requiredRoles as $roleName => $displayName) {
    $role = NewRole::where('name', $roleName)->first();
    if (!$role) {
        $role = NewRole::create([
            'name' => $roleName,
            'display_name' => $displayName,
            'description' => "System role for {$displayName}",
            'hierarchy_level' => NewRole::HIERARCHY_LEVELS[$roleName] ?? 5,
            'is_system_role' => true,
            'is_active' => true
        ]);
        echo "✅ Created role: {$roleName}\n";
    } else {
        echo "✅ Role exists: {$roleName}\n";
    }
}

// Test users to create
$testUsers = [
    'super_admin' => [
        'name' => 'Super Administrator',
        'email' => 'superadmin@pnsdhampur.com',
        'username' => 'superadmin',
        'password' => 'password123',
        'role' => 'super_admin'
    ],
    'principal' => [
        'name' => 'School Principal',
        'email' => 'principal@pnsdhampur.com',
        'username' => 'principal',
        'password' => 'password123',
        'role' => 'principal'
    ],
    'teacher' => [
        'name' => 'Test Teacher',
        'email' => 'teacher@pnsdhampur.com',
        'username' => 'teacher',
        'password' => 'password123',
        'role' => 'teacher'
    ],
    'student' => [
        'name' => 'Test Student',
        'email' => 'student@pnsdhampur.com',
        'username' => 'student',
        'password' => 'password123',
        'role' => 'student'
    ],
    'parent' => [
        'name' => 'Test Parent',
        'email' => 'parent@pnsdhampur.com',
        'username' => 'parent',
        'password' => 'password123',
        'role' => 'parent'
    ]
];

echo "\nCreating test users...\n";

DB::beginTransaction();

try {
    foreach ($testUsers as $userType => $userData) {
        // Check if user already exists
        $existingUser = NewUser::where('email', $userData['email'])->first();
        
        if ($existingUser) {
            echo "✅ User already exists: {$userData['email']}\n";
            continue;
        }
        
        // Create user
        $user = NewUser::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'username' => $userData['username'],
            'password' => Hash::make($userData['password']),
            'email_verified_at' => now(),
            'status' => 'active',
            'is_active' => true,
            'must_change_password' => false,
            'password_changed_at' => now(),
            'created_by' => 1
        ]);
        
        echo "✅ Created user: {$userData['email']} (ID: {$user->id})\n";
        
        // Assign role
        $role = NewRole::where('name', $userData['role'])->first();
        if ($role) {
            UserRoleAssignment::create([
                'user_id' => $user->id,
                'role_id' => $role->id,
                'assigned_by' => 1,
                'assigned_at' => now(),
                'is_active' => true,
                'is_primary' => true,
                'status' => 'active'
            ]);
            echo "✅ Assigned role {$userData['role']} to {$userData['email']}\n";
        }
    }
    
    DB::commit();
    echo "\n✅ All test users created successfully!\n";
    
} catch (Exception $e) {
    DB::rollBack();
    echo "\n❌ Error creating test users: " . $e->getMessage() . "\n";
}

echo "\n=== TEST USER CREATION COMPLETED ===\n";