<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

echo "=== Route Debug Test ===\n\n";

// Bootstrap Laravel first
$kernel->bootstrap();

// Create the request
$request = Request::create('/class-data-audit', 'GET');

// Start session
$session = app('session');
$session->start();
$request->setSession($session);

// Create and authenticate admin user
$adminUser = User::factory()->admin()->create();
Auth::guard('web')->setRequest($request);
Auth::login($adminUser);

echo "Authenticated as: " . Auth::user()->email . " (Role: " . Auth::user()->role . ")\n\n";

// Create the exact request that the test makes
$request = Request::create('/class-data-audit', 'GET');
$request->setLaravelSession(app('session.store'));

echo "Request URL: " . $request->getUri() . "\n";
echo "Request Method: " . $request->getMethod() . "\n\n";

// Check if route exists
echo "=== Route Analysis ===\n";
try {
    $route = Route::getRoutes()->match($request);
    echo "Route found: " . $route->uri() . "\n";
    echo "Route name: " . ($route->getName() ?? 'No name') . "\n";
    echo "Route action: " . ($route->getActionName() ?? 'No action') . "\n";
    echo "Route middleware: " . implode(', ', $route->middleware()) . "\n\n";
    
    // Check controller and method
    $action = $route->getAction();
    if (isset($action['controller'])) {
        echo "Controller: " . $action['controller'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Route error: " . $e->getMessage() . "\n\n";
}

// Test the actual HTTP request processing
echo "=== HTTP Request Test ===\n";
try {
    $response = $kernel->handle($request);
    echo "Response status: " . $response->getStatusCode() . "\n";
    
    if ($response->getStatusCode() === 302) {
        echo "Redirect location: " . $response->headers->get('Location') . "\n";
        
        // Check for session errors
        $session = $request->getSession();
        if ($session && $session->has('error')) {
            echo "Session error: " . $session->get('error') . "\n";
        }
        if ($session && $session->has('errors')) {
            echo "Session errors: " . print_r($session->get('errors'), true) . "\n";
        }
    }
    
    // Check response content for clues
    $content = $response->getContent();
    if (strlen($content) < 500) {  // Only show short content
        echo "Response content: " . $content . "\n";
    } else {
        echo "Response content length: " . strlen($content) . " characters\n";
    }
    
} catch (Exception $e) {
    echo "HTTP request error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

// Check if controller exists
echo "\n=== Controller Check ===\n";
if (class_exists('App\Http\Controllers\ClassDataAuditController')) {
    echo "ClassDataAuditController exists\n";
    
    $controller = new App\Http\Controllers\ClassDataAuditController();
    if (method_exists($controller, 'index')) {
        echo "index method exists\n";
    } else {
        echo "index method does NOT exist\n";
    }
} else {
    echo "ClassDataAuditController does NOT exist\n";
}