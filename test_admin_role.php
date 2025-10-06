<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

echo "=== Admin Role Test ===\n\n";

$adminUser = User::where('email', 'admin@pnsdhampur.local')->first();
if ($adminUser) {
    echo "Admin user found:\n";
    echo "Email: " . $adminUser->email . "\n";
    echo "Role: " . $adminUser->role . "\n";
    echo "hasRole('admin'): " . ($adminUser->hasRole('admin') ? 'Yes' : 'No') . "\n";
    echo "hasAnyRole(['admin', 'principal', 'teacher']): " . ($adminUser->hasAnyRole(['admin', 'principal', 'teacher']) ? 'Yes' : 'No') . "\n";
    echo "canAccessAttendance(): " . ($adminUser->canAccessAttendance() ? 'Yes' : 'No') . "\n";
    
    // Test the exact roles from the biometric route
    $biometricRoles = ['admin', 'principal', 'teacher'];
    echo "\nTesting biometric route roles:\n";
    echo "Required roles: " . implode(', ', $biometricRoles) . "\n";
    echo "User has any required role: " . ($adminUser->hasAnyRole($biometricRoles) ? 'Yes' : 'No') . "\n";
    
    // Check individual roles
    echo "\nIndividual role checks:\n";
    foreach ($biometricRoles as $role) {
        echo "hasRole('$role'): " . ($adminUser->hasRole($role) ? 'Yes' : 'No') . "\n";
    }
    
} else {
    echo "Admin user not found!\n";
    
    // Try to find any admin users
    $adminUsers = User::where('role', 'admin')->get();
    echo "Found " . $adminUsers->count() . " admin users:\n";
    foreach ($adminUsers as $user) {
        echo "- " . $user->email . " (role: " . $user->role . ")\n";
    }
}

echo "\n=== Test Complete ===\n";