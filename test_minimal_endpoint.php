<?php

// Minimal test endpoint to isolate the connection reset issue
// This will create a simple HTTP server without Laravel

echo "=== Creating Minimal Test Server ===\n\n";

// Create a simple PHP built-in server test
$testScript = '<?php
// Minimal test endpoint
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

$uri = $_SERVER["REQUEST_URI"];
$method = $_SERVER["REQUEST_METHOD"];

// Log the request
error_log("Request: $method $uri");

if ($uri === "/test") {
    echo json_encode([
        "status" => "success",
        "message" => "Minimal server working",
        "timestamp" => date("Y-m-d H:i:s"),
        "memory" => memory_get_usage(),
        "method" => $method
    ]);
} elseif ($uri === "/biometric-test") {
    // Simulate the biometric endpoint
    echo json_encode([
        "status" => "success",
        "data" => [
            [
                "id" => 1,
                "device_name" => "Test Device",
                "device_type" => "fingerprint",
                "status" => "active",
                "is_online" => true,
                "needs_maintenance" => false,
                "todays_scan_count" => 0,
                "uptime_percentage" => 100.0
            ]
        ],
        "timestamp" => date("Y-m-d H:i:s")
    ]);
} else {
    http_response_code(404);
    echo json_encode([
        "status" => "error",
        "message" => "Endpoint not found"
    ]);
}
?>';

file_put_contents('minimal_server.php', $testScript);
echo "✅ Created minimal_server.php\n";

// Test with the minimal server
echo "\n=== Testing Minimal Server ===\n";

// Start the minimal server in background
$command = 'php -S 127.0.0.1:8002 minimal_server.php';
echo "Starting server: $command\n";

// Use popen to start the server in background
$process = popen("start /B $command", 'r');
if ($process) {
    echo "✅ Server started on port 8002\n";
    pclose($process);
} else {
    echo "❌ Failed to start server\n";
    exit(1);
}

// Wait a moment for server to start
sleep(2);

// Test the minimal endpoints
echo "\n1. Testing /test endpoint...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8002/test');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);

$startTime = microtime(true);
$response = curl_exec($ch);
$endTime = microtime(true);

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "  - HTTP Code: $httpCode\n";
echo "  - Time: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
echo "  - Error: " . ($error ?: 'None') . "\n";
echo "  - Response: " . substr($response, 0, 100) . "\n";

echo "\n2. Testing /biometric-test endpoint...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8002/biometric-test');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);

$startTime = microtime(true);
$response = curl_exec($ch);
$endTime = microtime(true);

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "  - HTTP Code: $httpCode\n";
echo "  - Time: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
echo "  - Error: " . ($error ?: 'None') . "\n";
echo "  - Response: " . substr($response, 0, 100) . "\n";

echo "\n=== Minimal Server Test Complete ===\n";
echo "Note: The minimal server is still running on port 8002\n";
echo "You can test it manually with: curl http://127.0.0.1:8002/test\n";