<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Models\AuditTrail;
use App\Models\User;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // Get first user
    $user = User::first();
    
    if ($user) {
        // Create a test audit log using correct parameter order
        $testModel = new \stdClass();
        $testModel->id = 1;
        
        AuditTrail::logActivity(
            $testModel,
            'test_activity',
            ['old' => 'value'],
            ['test' => 'data', 'message' => 'Testing audit system'],
            ['tags' => ['test']]
        );
        
        echo "Test audit log created successfully for user: " . $user->name . "\n";
        
        // Count total audit logs
        $count = AuditTrail::count();
        echo "Total audit logs in database: " . $count . "\n";
        
        // Show recent logs
        $recent = AuditTrail::with('user')->latest()->take(5)->get();
        echo "\nRecent audit logs:\n";
        foreach ($recent as $log) {
            echo "- ID: {$log->id}, User: " . ($log->user ? $log->user->name : 'N/A') . ", Event: {$log->event}, Date: {$log->created_at}\n";
        }
        
    } else {
        echo "No users found in database\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}