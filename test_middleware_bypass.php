<?php

// Test to bypass ExternalIntegrationMiddleware by creating a simple route
// This will help us determine if the middleware is causing the server crashes

echo "Testing middleware bypass...\n";

// Create a temporary route file to test without middleware
$routeContent = '<?php

use Illuminate\Support\Facades\Route;

// Test route without any middleware
Route::get("/api/test-no-middleware", function() {
    return response()->json(["message" => "No middleware test working!"]);
});

// Test route with only basic middleware
Route::middleware("api")->get("/api/test-basic-middleware", function() {
    return response()->json(["message" => "Basic middleware test working!"]);
});

// Include original routes
include __DIR__ . "/api.php.backup2";
';

// Backup original routes file
if (file_exists('routes/api.php')) {
    copy('routes/api.php', 'routes/api.php.backup2');
    echo "Backed up original routes/api.php\n";
}

// Write test routes
file_put_contents('routes/api_test.php', $routeContent);
echo "Created test routes file\n";

// Temporarily replace the routes file
copy('routes/api_test.php', 'routes/api.php');
echo "Replaced routes file with test version\n";

echo "Now test the following URLs:\n";
echo "1. http://127.0.0.1:8080/api/test-no-middleware\n";
echo "2. http://127.0.0.1:8080/api/test-basic-middleware\n";
echo "3. http://127.0.0.1:8080/api/test (original route)\n";

echo "\nAfter testing, run restore_routes.php to restore original routes.\n";