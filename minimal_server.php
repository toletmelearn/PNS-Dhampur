<?php
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
?>