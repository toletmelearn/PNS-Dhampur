<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\AuditTrail;
use App\Http\Controllers\AuditController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "Testing audit interface with proper model handling...\n\n";

try {
    // Find or create an admin user
    $adminUser = User::where('role', 'admin')->first();
    if (!$adminUser) {
        $adminUser = User::factory()->admin()->create();
        echo "Created new admin user with ID: " . $adminUser->id . "\n";
    } else {
        echo "Using existing admin user with ID: " . $adminUser->id . "\n";
    }
    
    echo "Admin user role: " . $adminUser->role . "\n";
    echo "Admin has view_audit_trails permission: " . ($adminUser->hasPermission('view_audit_trails') ? 'Yes' : 'No') . "\n";
    
    // Create a proper audit log without stdClass
    echo "Creating a test audit log...\n";
    AuditTrail::create([
        'user_id' => $adminUser->id,
        'auditable_type' => null, // Avoid stdClass issue
        'auditable_id' => null,
        'event' => 'test_login',
        'old_values' => [],
        'new_values' => ['test' => 'data'],
        'url' => 'http://127.0.0.1:8000/test',
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Test Agent',
        'tags' => ['test'],
        'status' => 'normal'
    ]);
    echo "Test audit log created successfully\n";
    
    // Simulate login
    Auth::login($adminUser);
    echo "Admin user logged in successfully\n";
    
    // Create a mock request
    $request = Request::create('/audit', 'GET');
    
    // Test the AuditController
    $controller = new AuditController();
    
    echo "Calling AuditController index method...\n";
    $response = $controller->index($request);
    
    echo "Response type: " . get_class($response) . "\n";
    
    if ($response instanceof \Illuminate\Http\Response) {
        echo "Response status: " . $response->getStatusCode() . "\n";
    } elseif ($response instanceof \Illuminate\View\View) {
        echo "View returned successfully\n";
        echo "View name: " . $response->getName() . "\n";
    } else {
        echo "Unexpected response type\n";
    }
    
    // Check if we can get audit logs directly
    echo "\nTesting direct audit log retrieval...\n";
    $auditLogs = AuditTrail::with('user')->latest()->take(5)->get();
    echo "Retrieved " . $auditLogs->count() . " audit logs\n";
    
    if ($auditLogs->count() > 0) {
        echo "Recent audit logs:\n";
        foreach ($auditLogs as $log) {
            $userName = $log->user ? $log->user->name : 'System';
            echo "- ID: {$log->id}, Event: {$log->event}, User: {$userName}, Date: {$log->created_at}\n";
        }
    }
    
    echo "\nAudit interface test completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}