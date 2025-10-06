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

echo "Testing CSV export functionality for audit logs...\n\n";

try {
    // Clear existing audit logs and create test data
    AuditTrail::truncate();
    echo "Cleared existing audit logs for clean test\n";
    
    // Find or create a test user
    $testUser = User::where('role', 'admin')->first();
    if (!$testUser) {
        $testUser = User::factory()->admin()->create();
        echo "Created new test user with ID: " . $testUser->id . "\n";
    } else {
        echo "Using existing test user with ID: " . $testUser->id . "\n";
    }
    
    echo "Test user: " . $testUser->name . " (Role: " . $testUser->role . ")\n\n";
    
    // Create sample audit logs for testing
    echo "=== Creating Sample Audit Logs ===\n";
    
    $sampleLogs = [
        [
            'event' => 'user_login',
            'action' => 'User logged in',
            'url' => 'http://localhost:8000/login',
            'additional_data' => ['login_method' => 'web', 'ip' => '127.0.0.1']
        ],
        [
            'event' => 'viewed',
            'action' => 'Viewed dashboard',
            'url' => 'http://localhost:8000/dashboard',
            'additional_data' => ['page' => 'dashboard', 'section' => 'overview']
        ],
        [
            'event' => 'created',
            'action' => 'Created new record',
            'url' => 'http://localhost:8000/api/records',
            'additional_data' => ['record_type' => 'student', 'record_id' => 123]
        ],
        [
            'event' => 'updated',
            'action' => 'Updated record',
            'url' => 'http://localhost:8000/api/records/123',
            'additional_data' => ['record_type' => 'student', 'changes' => ['name', 'email']]
        ],
        [
            'event' => 'deleted',
            'action' => 'Deleted record',
            'url' => 'http://localhost:8000/api/records/456',
            'additional_data' => ['record_type' => 'student', 'record_id' => 456]
        ],
        [
            'event' => 'user_logout',
            'action' => 'User logged out',
            'url' => 'http://localhost:8000/logout',
            'additional_data' => ['logout_method' => 'manual', 'session_duration' => 3600]
        ]
    ];
    
    foreach ($sampleLogs as $index => $logData) {
        AuditTrail::logActivity(
            $testUser,
            $logData['event'],
            null, // auditable
            null, // old_values
            $logData['additional_data'],
            $logData['url'],
            '127.0.0.1',
            'Test Browser/1.0',
            ['test', 'csv_export']
        );
        
        echo "Created audit log " . ($index + 1) . ": {$logData['event']}\n";
        
        // Add some time variation
        usleep(100000); // 0.1 seconds
    }
    
    echo "Created " . count($sampleLogs) . " sample audit logs\n\n";
    
    // Test CSV export functionality
    echo "=== Testing CSV Export ===\n";
    
    // Check if AuditController exists and has export method
    if (class_exists('App\\Http\\Controllers\\AuditController')) {
        echo "AuditController class found\n";
        
        $controller = new AuditController();
        
        // Check if export method exists
        if (method_exists($controller, 'export')) {
            echo "AuditController export method found\n";
            
            // Create a mock request for CSV export
            $exportRequest = Request::create('/audit/export', 'GET', [
                'format' => 'csv',
                'start_date' => now()->subDays(1)->format('Y-m-d'),
                'end_date' => now()->addDays(1)->format('Y-m-d')
            ]);
            
            // Set authenticated user
            Auth::login($testUser);
            
            try {
                // Call the export method
                $response = $controller->export($exportRequest);
                
                echo "Export method executed successfully\n";
                echo "Response type: " . get_class($response) . "\n";
                
                // Check if it's a download response
                if (method_exists($response, 'getFile')) {
                    echo "Response is a file download\n";
                    $file = $response->getFile();
                    echo "File path: " . $file->getPathname() . "\n";
                    echo "File size: " . $file->getSize() . " bytes\n";
                    
                    // Read and display first few lines of CSV
                    $content = file_get_contents($file->getPathname());
                    $lines = explode("\n", $content);
                    
                    echo "CSV content preview (first 10 lines):\n";
                    for ($i = 0; $i < min(10, count($lines)); $i++) {
                        if (!empty(trim($lines[$i]))) {
                            echo "Line " . ($i + 1) . ": " . $lines[$i] . "\n";
                        }
                    }
                    
                    echo "Total lines in CSV: " . count(array_filter($lines, function($line) {
                        return !empty(trim($line));
                    })) . "\n";
                    
                } elseif (method_exists($response, 'getContent')) {
                    echo "Response has content\n";
                    $content = $response->getContent();
                    echo "Content length: " . strlen($content) . " characters\n";
                    
                    // Display first few lines
                    $lines = explode("\n", $content);
                    echo "Content preview (first 5 lines):\n";
                    for ($i = 0; $i < min(5, count($lines)); $i++) {
                        if (!empty(trim($lines[$i]))) {
                            echo "Line " . ($i + 1) . ": " . $lines[$i] . "\n";
                        }
                    }
                } else {
                    echo "Response type not recognized for content extraction\n";
                    echo "Available methods: " . implode(', ', get_class_methods($response)) . "\n";
                }
                
            } catch (Exception $e) {
                echo "Error calling export method: " . $e->getMessage() . "\n";
                echo "File: " . $e->getFile() . "\n";
                echo "Line: " . $e->getLine() . "\n";
            }
            
        } else {
            echo "AuditController export method not found\n";
            echo "Available methods: " . implode(', ', get_class_methods($controller)) . "\n";
        }
        
    } else {
        echo "AuditController class not found\n";
    }
    
    echo "\n=== Testing Different Export Formats ===\n";
    
    // Test different export formats if available
    $formats = ['csv', 'excel', 'pdf'];
    
    foreach ($formats as $format) {
        echo "Testing $format export...\n";
        
        if (class_exists('App\\Http\\Controllers\\AuditController')) {
            $controller = new AuditController();
            
            if (method_exists($controller, 'export')) {
                $formatRequest = Request::create('/audit/export', 'GET', [
                    'format' => $format,
                    'start_date' => now()->subDays(1)->format('Y-m-d'),
                    'end_date' => now()->addDays(1)->format('Y-m-d')
                ]);
                
                try {
                    $response = $controller->export($formatRequest);
                    echo "- $format export successful\n";
                    
                    if (method_exists($response, 'headers')) {
                        $headers = $response->headers->all();
                        if (isset($headers['content-type'])) {
                            echo "  Content-Type: " . implode(', ', $headers['content-type']) . "\n";
                        }
                        if (isset($headers['content-disposition'])) {
                            echo "  Content-Disposition: " . implode(', ', $headers['content-disposition']) . "\n";
                        }
                    }
                    
                } catch (Exception $e) {
                    echo "- $format export failed: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    echo "\n=== Testing Export Filters ===\n";
    
    // Test export with different filters
    $filterTests = [
        ['event' => 'user_login', 'description' => 'Login events only'],
        ['user_id' => $testUser->id, 'description' => 'Specific user only'],
        ['start_date' => now()->subHours(1)->format('Y-m-d H:i:s'), 'description' => 'Last hour only'],
    ];
    
    foreach ($filterTests as $filterTest) {
        echo "Testing filter: {$filterTest['description']}\n";
        
        if (class_exists('App\\Http\\Controllers\\AuditController')) {
            $controller = new AuditController();
            
            if (method_exists($controller, 'export')) {
                $params = array_merge(['format' => 'csv'], $filterTest);
                unset($params['description']);
                
                $filterRequest = Request::create('/audit/export', 'GET', $params);
                
                try {
                    $response = $controller->export($filterRequest);
                    echo "- Filter test successful\n";
                    
                    // Try to count records in response
                    if (method_exists($response, 'getContent')) {
                        $content = $response->getContent();
                        $lines = explode("\n", $content);
                        $recordCount = count(array_filter($lines, function($line) {
                            return !empty(trim($line));
                        })) - 1; // Subtract header row
                        echo "  Records in filtered export: $recordCount\n";
                    }
                    
                } catch (Exception $e) {
                    echo "- Filter test failed: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    // Final verification
    echo "\n=== Final Verification ===\n";
    
    $totalAudits = AuditTrail::count();
    echo "Total audit logs in database: $totalAudits\n";
    
    $recentAudits = AuditTrail::where('created_at', '>=', now()->subMinutes(5))->count();
    echo "Recent audit logs (last 5 minutes): $recentAudits\n";
    
    echo "\nCSV export test completed!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}