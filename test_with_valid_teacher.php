<?php

require_once 'vendor/autoload.php';

use App\Http\Controllers\BiometricController;
use App\Models\User;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Boot the application
$app->boot();

echo "=== BiometricController Test with Valid Teacher ===\n";

try {
    // Create a test user with employee_id
    $user = User::create([
        'name' => 'Test Teacher',
        'email' => 'test.teacher@example.com',
        'employee_id' => 'TEST001',
        'password' => password_hash('password', PASSWORD_DEFAULT),
        'role' => 'teacher'
    ]);
    
    // Create a teacher record
    $teacher = Teacher::create([
        'user_id' => $user->id,
        'qualification' => 'M.Ed',
        'experience_years' => 5,
        'salary' => 50000,
        'joining_date' => now()->format('Y-m-d')
    ]);
    
    echo "✓ Created test teacher with employee_id: TEST001\n";
    
    // Test the biometric API with valid data
    $controller = new BiometricController();
    
    $requestData = [
        'type' => 'real_time',
        'employee_id' => 'TEST001',
        'timestamp' => now()->format('Y-m-d H:i:s'),
        'event_type' => 'check_in',
        'device_id' => 'TEST_DEVICE_001',
        'location' => 'Main Gate'
    ];
    
    $request = new Request($requestData);
    $request->setMethod('POST');
    
    $startTime = microtime(true);
    $response = $controller->importData($request);
    $duration = (microtime(true) - $startTime) * 1000;
    
    echo "\nTesting with valid teacher data:\n";
    echo "  - Duration: " . number_format($duration, 2) . " ms\n";
    echo "  - Status Code: " . $response->getStatusCode() . "\n";
    echo "  - Response: " . $response->getContent() . "\n";
    
    // Clean up test data
    $teacher->delete();
    $user->delete();
    
    echo "\n✓ Test data cleaned up\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    
    // Clean up in case of error
    try {
        if (isset($teacher)) $teacher->delete();
        if (isset($user)) $user->delete();
    } catch (Exception $cleanupError) {
        echo "Cleanup error: " . $cleanupError->getMessage() . "\n";
    }
}

echo "\n=== Test Complete ===\n";