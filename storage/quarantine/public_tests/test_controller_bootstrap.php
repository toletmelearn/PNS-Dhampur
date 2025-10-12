<?php

echo "=== Testing Controller Bootstrap Process ===\n\n";

$totalStart = microtime(true);

echo "1. Loading autoloader...\n";
$stepStart = microtime(true);
require_once __DIR__ . '/../vendor/autoload.php';
$stepEnd = microtime(true);
echo "   Time: " . round(($stepEnd - $stepStart) * 1000, 2) . " ms\n\n";

echo "2. Loading Laravel app...\n";
$stepStart = microtime(true);
$app = require_once __DIR__ . '/../bootstrap/app.php';
$stepEnd = microtime(true);
echo "   Time: " . round(($stepEnd - $stepStart) * 1000, 2) . " ms\n\n";

echo "3. Making kernel...\n";
$stepStart = microtime(true);
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$stepEnd = microtime(true);
echo "   Time: " . round(($stepEnd - $stepStart) * 1000, 2) . " ms\n\n";

echo "4. Bootstrapping kernel...\n";
$stepStart = microtime(true);
$kernel->bootstrap();
$stepEnd = microtime(true);
echo "   Time: " . round(($stepEnd - $stepStart) * 1000, 2) . " ms\n\n";

echo "5. Loading Eloquent models...\n";
$stepStart = microtime(true);
use App\Models\BiometricDevice;
use App\Models\BiometricAttendance;
use App\Models\User;
$stepEnd = microtime(true);
echo "   Time: " . round(($stepEnd - $stepStart) * 1000, 2) . " ms\n\n";

echo "6. First database connection...\n";
$stepStart = microtime(true);
$connection = \Illuminate\Support\Facades\DB::connection();
$stepEnd = microtime(true);
echo "   Time: " . round(($stepEnd - $stepStart) * 1000, 2) . " ms\n\n";

echo "7. First database query...\n";
$stepStart = microtime(true);
try {
    $deviceCount = BiometricDevice::count();
    echo "   Device count: $deviceCount\n";
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}
$stepEnd = microtime(true);
echo "   Time: " . round(($stepEnd - $stepStart) * 1000, 2) . " ms\n\n";

echo "8. Loading controller dependencies...\n";
$stepStart = microtime(true);
use App\Services\BiometricDeviceService;
use App\Services\BiometricRealTimeProcessor;
use App\Http\Controllers\BiometricController;
$stepEnd = microtime(true);
echo "   Time: " . round(($stepEnd - $stepStart) * 1000, 2) . " ms\n\n";

echo "9. Resolving BiometricDeviceService...\n";
$stepStart = microtime(true);
$deviceService = app(BiometricDeviceService::class);
$stepEnd = microtime(true);
echo "   Time: " . round(($stepEnd - $stepStart) * 1000, 2) . " ms\n\n";

echo "10. Resolving BiometricRealTimeProcessor...\n";
$stepStart = microtime(true);
$realTimeProcessor = app(BiometricRealTimeProcessor::class);
$stepEnd = microtime(true);
echo "   Time: " . round(($stepEnd - $stepStart) * 1000, 2) . " ms\n\n";

echo "11. Creating BiometricController...\n";
$stepStart = microtime(true);
$controller = new BiometricController($deviceService, $realTimeProcessor);
$stepEnd = microtime(true);
echo "   Time: " . round(($stepEnd - $stepStart) * 1000, 2) . " ms\n\n";

echo "12. First call to getRegisteredDevices...\n";
$stepStart = microtime(true);
try {
    $request = new \Illuminate\Http\Request();
    $response = $controller->getRegisteredDevices($request);
    echo "   Response status: " . $response->getStatusCode() . "\n";
    $data = json_decode($response->getContent(), true);
    echo "   Device count in response: " . count($data['devices'] ?? []) . "\n";
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}
$stepEnd = microtime(true);
echo "   Time: " . round(($stepEnd - $stepStart) * 1000, 2) . " ms\n\n";

echo "13. Second call to getRegisteredDevices...\n";
$stepStart = microtime(true);
try {
    $request = new \Illuminate\Http\Request();
    $response = $controller->getRegisteredDevices($request);
    echo "   Response status: " . $response->getStatusCode() . "\n";
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}
$stepEnd = microtime(true);
echo "   Time: " . round(($stepEnd - $stepStart) * 1000, 2) . " ms\n\n";

echo "14. Testing cache operations...\n";
$stepStart = microtime(true);
\Illuminate\Support\Facades\Cache::put('test_bootstrap', 'value', 60);
$value = \Illuminate\Support\Facades\Cache::get('test_bootstrap');
$stepEnd = microtime(true);
echo "   Cache value: $value\n";
echo "   Time: " . round(($stepEnd - $stepStart) * 1000, 2) . " ms\n\n";

$totalEnd = microtime(true);
echo "=== TOTAL TIME: " . round(($totalEnd - $totalStart) * 1000, 2) . " ms ===\n";