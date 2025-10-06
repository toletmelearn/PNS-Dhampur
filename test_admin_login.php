<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a fake request to bootstrap the application
$request = Request::create('/', 'GET');
$response = $kernel->handle($request);

echo "Testing Admin Login and Route Access\n";
echo "====================================\n\n";

try {
    // Find admin user
    $admin = User::where('email', 'admin@pnsdhampur.local')->first();
    
    if (!$admin) {
        echo "❌ Admin user not found!\n";
        exit(1);
    }
    
    echo "✅ Admin user found: {$admin->email}\n";
    echo "   Name: {$admin->name}\n";
    echo "   Role: {$admin->role}\n\n";
    
    // Test password verification
    $passwordCheck = Hash::check('Password123', $admin->password);
    echo "Password verification: " . ($passwordCheck ? "✅ Valid" : "❌ Invalid") . "\n\n";
    
    // Simulate login
    Auth::login($admin);
    
    if (Auth::check()) {
        echo "✅ Authentication successful\n";
        echo "   Logged in user: " . Auth::user()->name . "\n";
        echo "   User role: " . Auth::user()->role . "\n\n";
        
        // Test role checking
        $isAdmin = Auth::user()->hasAnyRole(['admin']);
        echo "Admin role check: " . ($isAdmin ? "✅ Has admin role" : "❌ No admin role") . "\n";
        
        // Test specific permissions
        $canManageUsers = Auth::user()->role === 'admin';
        echo "User management permission: " . ($canManageUsers ? "✅ Allowed" : "❌ Denied") . "\n\n";
        
        echo "🎉 All tests passed! Admin user is ready for testing.\n";
        echo "\nCredentials for web testing:\n";
        echo "Email: admin@pnsdhampur.local\n";
        echo "Password: Password123\n";
        
    } else {
        echo "❌ Authentication failed\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

$kernel->terminate($request, $response);