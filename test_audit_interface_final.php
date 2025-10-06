<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Http\Controllers\AuditController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "Testing audit interface after permission fix...\n\n";

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
    
    // Simulate login
    Auth::login($adminUser);
    echo "Admin user logged in successfully\n";
    
    // Create a mock request
    $request = Request::create('/audit', 'GET');
    
    // Test the AuditController
    $controller = new AuditController();
    
    echo "Calling AuditController index method...\n";
    $response = $controller->index($request);
    
    echo "Response status: " . $response->getStatusCode() . "\n";
    echo "Response type: " . get_class($response) . "\n";
    
    // If it's a view response, get the data
    if (method_exists($response, 'getData')) {
        $data = $response->getData();
        echo "Response has data: " . (empty($data) ? 'No' : 'Yes') . "\n";
        
        if (isset($data['auditLogs'])) {
            $auditLogs = $data['auditLogs'];
            echo "Audit logs count: " . $auditLogs->count() . "\n";
            
            if ($auditLogs->count() > 0) {
                echo "Recent audit logs:\n";
                foreach ($auditLogs->take(3) as $log) {
                    echo "- ID: {$log->id}, Event: {$log->event}, User: {$log->user_name}, Date: {$log->created_at}\n";
                }
            }
        }
    }
    
    echo "\nAudit interface test completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}