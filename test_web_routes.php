<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Http\Controllers\UserController;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "Testing User Management Web Routes\n";
echo "==================================\n\n";

try {
    // Find and authenticate admin user
    $admin = User::where('email', 'admin@pnsdhampur.local')->first();
    
    if (!$admin) {
        echo "❌ Admin user not found!\n";
        exit(1);
    }
    
    // Test routes that should be accessible
    $routesToTest = [
        ['method' => 'GET', 'uri' => '/admin/users', 'name' => 'User Management Index'],
        ['method' => 'GET', 'uri' => '/admin/users/bulk-import', 'name' => 'Bulk Import Form'],
        ['method' => 'GET', 'uri' => '/admin/users/bulk-password-reset', 'name' => 'Bulk Password Reset Form'],
        ['method' => 'GET', 'uri' => '/admin/users/download-import-template', 'name' => 'Download Import Template'],
        ['method' => 'GET', 'uri' => '/admin/users/download-permission-template', 'name' => 'Download Permission Template'],
    ];
    
    foreach ($routesToTest as $route) {
        echo "Testing: {$route['name']}\n";
        echo "Route: {$route['method']} {$route['uri']}\n";
        
        try {
            // Create request with authentication
            $request = Request::create($route['uri'], $route['method']);
            $request->setUserResolver(function () use ($admin) {
                return $admin;
            });
            
            // Set up session and auth
            Auth::login($admin);
            
            // Handle the request
            $response = $kernel->handle($request);
            
            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 200) {
                echo "✅ Status: {$statusCode} - Success\n";
                
                // Check if it's a download response
                $contentType = $response->headers->get('Content-Type');
                if (strpos($contentType, 'text/csv') !== false || strpos($contentType, 'application/') !== false) {
                    echo "   📄 Download response detected\n";
                } else {
                    echo "   📄 HTML response detected\n";
                }
                
            } elseif ($statusCode === 302) {
                $location = $response->headers->get('Location');
                echo "🔄 Status: {$statusCode} - Redirect to: {$location}\n";
            } else {
                echo "⚠️  Status: {$statusCode} - Unexpected response\n";
            }
            
            $kernel->terminate($request, $response);
            
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    echo "🎉 Route testing completed!\n\n";
    
    // Test controller methods directly
    echo "Testing Controller Methods Directly\n";
    echo "===================================\n\n";
    
    $controller = new UserController();
    
    // Test showBulkImport method
    try {
        $request = Request::create('/admin/users/bulk-import', 'GET');
        $request->setUserResolver(function () use ($admin) {
            return $admin;
        });
        Auth::login($admin);
        
        $response = $controller->showBulkImport();
        echo "✅ showBulkImport() method working\n";
        
    } catch (Exception $e) {
        echo "❌ showBulkImport() error: " . $e->getMessage() . "\n";
    }
    
    // Test showBulkPasswordReset method
    try {
        $request = Request::create('/admin/users/bulk-password-reset', 'GET');
        $request->setUserResolver(function () use ($admin) {
            return $admin;
        });
        Auth::login($admin);
        
        $response = $controller->showBulkPasswordReset();
        echo "✅ showBulkPasswordReset() method working\n";
        
    } catch (Exception $e) {
        echo "❌ showBulkPasswordReset() error: " . $e->getMessage() . "\n";
    }
    
    // Test downloadImportTemplate method
    try {
        $request = Request::create('/admin/users/download-import-template', 'GET');
        $request->setUserResolver(function () use ($admin) {
            return $admin;
        });
        Auth::login($admin);
        
        $response = $controller->downloadImportTemplate();
        echo "✅ downloadImportTemplate() method working\n";
        
    } catch (Exception $e) {
        echo "❌ downloadImportTemplate() error: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}