<?php
echo "=== Testing Apache/PHP Configuration for Connection Issues ===\n\n";

// Test 1: Check PHP configuration
echo "1. PHP Configuration:\n";
echo "- PHP Version: " . phpversion() . "\n";
echo "- Max Execution Time: " . ini_get('max_execution_time') . " seconds\n";
echo "- Max Input Time: " . ini_get('max_input_time') . " seconds\n";
echo "- Memory Limit: " . ini_get('memory_limit') . "\n";
echo "- Post Max Size: " . ini_get('post_max_size') . "\n";
echo "- Upload Max Filesize: " . ini_get('upload_max_filesize') . "\n";
echo "- Max Input Vars: " . ini_get('max_input_vars') . "\n";
echo "- Default Socket Timeout: " . ini_get('default_socket_timeout') . " seconds\n\n";

// Test 2: Check Apache modules (if available)
echo "2. Apache Configuration (if available):\n";
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    $relevant_modules = ['mod_rewrite', 'mod_ssl', 'mod_headers', 'mod_timeout'];
    foreach ($relevant_modules as $module) {
        echo "- $module: " . (in_array($module, $modules) ? "✅ Loaded" : "❌ Not loaded") . "\n";
    }
} else {
    echo "- Apache module info not available (running via CLI or different SAPI)\n";
}
echo "\n";

// Test 3: Test connection handling with different request sizes
echo "3. Testing Connection Handling:\n";

// Small request test
echo "- Testing small request handling...\n";
$start = microtime(true);
$small_data = json_encode(['test' => 'small_data', 'size' => 'minimal']);
$small_time = microtime(true) - $start;
echo "  ✅ Small data processed in " . round($small_time * 1000, 2) . " ms\n";

// Medium request test
echo "- Testing medium request handling...\n";
$start = microtime(true);
$medium_data = json_encode([
    'test' => 'medium_data',
    'data' => array_fill(0, 100, ['employee_id' => 'TEST001', 'timestamp' => date('c'), 'event_type' => 'check_in'])
]);
$medium_time = microtime(true) - $start;
echo "  ✅ Medium data processed in " . round($medium_time * 1000, 2) . " ms (Size: " . strlen($medium_data) . " bytes)\n";

// Large request test
echo "- Testing large request handling...\n";
$start = microtime(true);
$large_data = json_encode([
    'test' => 'large_data',
    'data' => array_fill(0, 1000, ['employee_id' => 'TEST001', 'timestamp' => date('c'), 'event_type' => 'check_in', 'biometric_data' => str_repeat('A', 100)])
]);
$large_time = microtime(true) - $start;
echo "  ✅ Large data processed in " . round($large_time * 1000, 2) . " ms (Size: " . strlen($large_data) . " bytes)\n\n";

// Test 4: Check for potential timeout issues
echo "4. Timeout Simulation:\n";
echo "- Testing 1 second delay...\n";
$start = microtime(true);
sleep(1);
$delay_time = microtime(true) - $start;
echo "  ✅ 1 second delay completed in " . round($delay_time, 2) . " seconds\n";

echo "- Testing 5 second delay...\n";
$start = microtime(true);
sleep(5);
$delay_time = microtime(true) - $start;
echo "  ✅ 5 second delay completed in " . round($delay_time, 2) . " seconds\n\n";

// Test 5: Check error reporting and logging
echo "5. Error Handling Configuration:\n";
echo "- Error Reporting: " . error_reporting() . "\n";
echo "- Display Errors: " . (ini_get('display_errors') ? 'On' : 'Off') . "\n";
echo "- Log Errors: " . (ini_get('log_errors') ? 'On' : 'Off') . "\n";
echo "- Error Log: " . ini_get('error_log') . "\n\n";

// Test 6: Check output buffering
echo "6. Output Buffering:\n";
echo "- Output Buffering: " . (ob_get_level() > 0 ? 'Active (Level: ' . ob_get_level() . ')' : 'Inactive') . "\n";
echo "- Output Buffer Size: " . ini_get('output_buffering') . "\n\n";

echo "=== Configuration Test Complete ===\n";