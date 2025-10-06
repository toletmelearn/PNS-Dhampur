<?php

echo "=== Laravel Server Configuration Test ===\n\n";

// Check PHP configuration
echo "1. PHP Configuration:\n";
echo "  - PHP Version: " . PHP_VERSION . "\n";
echo "  - Memory Limit: " . ini_get('memory_limit') . "\n";
echo "  - Max Execution Time: " . ini_get('max_execution_time') . "\n";
echo "  - Output Buffering: " . (ini_get('output_buffering') ? 'Enabled (' . ini_get('output_buffering') . ')' : 'Disabled') . "\n";
echo "  - Implicit Flush: " . (ini_get('implicit_flush') ? 'Enabled' : 'Disabled') . "\n";
echo "  - Zlib Output Compression: " . (ini_get('zlib.output_compression') ? 'Enabled' : 'Disabled') . "\n";
echo "  - Default Socket Timeout: " . ini_get('default_socket_timeout') . "\n";
echo "  - User Agent: " . ini_get('user_agent') . "\n\n";

// Check Laravel configuration
echo "2. Laravel Configuration:\n";
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "  - App Environment: " . config('app.env') . "\n";
echo "  - App Debug: " . (config('app.debug') ? 'true' : 'false') . "\n";
echo "  - App URL: " . config('app.url') . "\n";
echo "  - Database Connection: " . config('database.default') . "\n";

// Check middleware configuration
echo "\n3. Middleware Configuration:\n";
$middlewareGroups = config('app.middleware_groups', []);
if (isset($middlewareGroups['api'])) {
    echo "  - API Middleware:\n";
    foreach ($middlewareGroups['api'] as $middleware) {
        echo "    * $middleware\n";
    }
}

// Check route middleware
$routeMiddleware = config('app.route_middleware', []);
if (isset($routeMiddleware['external.integration'])) {
    echo "  - External Integration Middleware: " . $routeMiddleware['external.integration'] . "\n";
}

// Test basic Laravel response
echo "\n4. Testing Basic Laravel Response:\n";
try {
    $request = Illuminate\Http\Request::create('/api/test', 'GET');
    $response = new Illuminate\Http\JsonResponse(['status' => 'ok', 'message' => 'Test response']);
    
    echo "  - Response Status: " . $response->getStatusCode() . "\n";
    echo "  - Response Headers:\n";
    foreach ($response->headers->all() as $name => $values) {
        echo "    * $name: " . implode(', ', $values) . "\n";
    }
    echo "  - Response Content: " . $response->getContent() . "\n";
    
} catch (Exception $e) {
    echo "  - Error: " . $e->getMessage() . "\n";
}

// Test database connection
echo "\n5. Testing Database Connection:\n";
try {
    $pdo = DB::connection()->getPdo();
    echo "  - Database Connected: Yes\n";
    echo "  - Driver: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n";
    echo "  - Server Version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
    
    // Test a simple query
    $result = DB::select('SELECT COUNT(*) as count FROM biometric_devices');
    echo "  - Biometric Devices Count: " . $result[0]->count . "\n";
    
} catch (Exception $e) {
    echo "  - Database Error: " . $e->getMessage() . "\n";
}

// Check for problematic extensions or configurations
echo "\n6. Checking for Problematic Extensions:\n";
$extensions = get_loaded_extensions();
$problematic = ['xdebug', 'opcache', 'apcu'];
foreach ($problematic as $ext) {
    if (in_array($ext, $extensions)) {
        echo "  - $ext: Loaded";
        if ($ext === 'xdebug') {
            echo " (may cause performance issues)";
        }
        echo "\n";
    } else {
        echo "  - $ext: Not loaded\n";
    }
}

// Check memory usage
echo "\n7. Memory Usage:\n";
echo "  - Current Memory Usage: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB\n";
echo "  - Peak Memory Usage: " . round(memory_get_peak_usage() / 1024 / 1024, 2) . " MB\n";

echo "\n=== Configuration Test Complete ===\n";