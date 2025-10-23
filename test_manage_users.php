<?php
// Comprehensive test for Manage Users functionality
require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use App\Models\NewUser;
use App\Models\NewRole;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "=== Manage Users Functionality Test ===\n\n";

// Test 1: Check if admin.users.index route exists
echo "1. Testing route existence...\n";
try {
    $routes = app('router')->getRoutes();
    $adminUsersRoute = null;
    
    foreach ($routes as $route) {
        if ($route->getName() === 'admin.users.index') {
            $adminUsersRoute = $route;
            break;
        }
    }
    
    if ($adminUsersRoute) {
        echo "✅ Route 'admin.users.index' exists\n";
        echo "   URI: " . $adminUsersRoute->uri() . "\n";
        echo "   Methods: " . implode(', ', $adminUsersRoute->methods()) . "\n";
        echo "   Middleware: " . implode(', ', $adminUsersRoute->middleware()) . "\n";
    } else {
        echo "❌ Route 'admin.users.index' not found\n";
    }
} catch (Exception $e) {
    echo "❌ Error checking routes: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Check AdminUserController
echo "2. Testing AdminUserController...\n";
try {
    $controller = new \App\Http\Controllers\Admin\UserController(new \App\Services\RoleService());
    echo "✅ AdminUserController can be instantiated\n";
    
    if (method_exists($controller, 'index')) {
        echo "✅ index method exists\n";
    } else {
        echo "❌ index method not found\n";
    }
} catch (Exception $e) {
    echo "❌ Error with AdminUserController: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Check view file
echo "3. Testing view file...\n";
$viewPath = resource_path('views/admin/users/index.blade.php');
if (file_exists($viewPath)) {
    echo "✅ View file exists: $viewPath\n";
    echo "   File size: " . filesize($viewPath) . " bytes\n";
} else {
    echo "❌ View file not found: $viewPath\n";
}

echo "\n";

// Test 4: Test route accessibility (unauthenticated)
echo "4. Testing route accessibility (unauthenticated)...\n";
try {
    $request = Request::create('/admin/users', 'GET');
    $response = $kernel->handle($request);
    
    echo "Status Code: " . $response->getStatusCode() . "\n";
    
    if ($response->getStatusCode() === 302) {
        $location = $response->headers->get('Location');
        echo "✅ Redirects to: $location (authentication required - expected)\n";
    } elseif ($response->getStatusCode() === 200) {
        echo "⚠️  Route accessible without authentication (unexpected)\n";
    } else {
        echo "❌ Unexpected status code\n";
    }
    
    $kernel->terminate($request, $response);
} catch (Exception $e) {
    echo "❌ Error testing route: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 5: Check if Super Admin users exist
echo "5. Checking Super Admin users...\n";
try {
    $superAdminRole = NewRole::where('name', 'super_admin')->first();
    if ($superAdminRole) {
        echo "✅ Super Admin role exists (ID: {$superAdminRole->id})\n";
        
        $superAdmins = NewUser::whereHas('roles', function($query) use ($superAdminRole) {
            $query->where('role_id', $superAdminRole->id);
        })->get();
        
        echo "   Super Admin users count: " . $superAdmins->count() . "\n";
        
        if ($superAdmins->count() > 0) {
            echo "   Super Admin users:\n";
            foreach ($superAdmins as $admin) {
                echo "   - {$admin->username} ({$admin->email})\n";
            }
        }
    } else {
        echo "❌ Super Admin role not found\n";
    }
} catch (Exception $e) {
    echo "❌ Error checking Super Admin users: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 6: Check dashboard route
echo "6. Testing Super Admin dashboard route...\n";
try {
    $request = Request::create('/dashboard/super-admin', 'GET');
    $response = $kernel->handle($request);
    
    echo "Status Code: " . $response->getStatusCode() . "\n";
    
    if ($response->getStatusCode() === 302) {
        $location = $response->headers->get('Location');
        echo "✅ Redirects to: $location (authentication required - expected)\n";
    } elseif ($response->getStatusCode() === 200) {
        echo "⚠️  Dashboard accessible without authentication (unexpected)\n";
    } else {
        echo "❌ Unexpected status code\n";
    }
    
    $kernel->terminate($request, $response);
} catch (Exception $e) {
    echo "❌ Error testing dashboard route: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";