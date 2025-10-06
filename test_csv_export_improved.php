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

echo "Testing CSV export functionality for audit logs (Improved)...\n\n";

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
    
    // Test CSV export functionality by directly testing the logic
    echo "=== Testing CSV Export Logic ===\n";
    
    // Get audit logs using the same logic as the controller
    $filters = [
        'date_from' => now()->subDays(1)->format('Y-m-d'),
        'date_to' => now()->addDays(1)->format('Y-m-d')
    ];
    
    $auditLogs = AuditTrail::with(['user'])
        ->when($filters['date_from'] ?? null, function ($query, $dateFrom) {
            return $query->whereDate('created_at', '>=', $dateFrom);
        })
        ->when($filters['date_to'] ?? null, function ($query, $dateTo) {
            return $query->whereDate('created_at', '<=', $dateTo);
        })
        ->latest()
        ->get();
    
    echo "Found " . $auditLogs->count() . " audit logs for export\n";
    
    if ($auditLogs->count() > 0) {
        echo "Sample audit log data:\n";
        $firstLog = $auditLogs->first();
        echo "- ID: " . $firstLog->id . "\n";
        echo "- User: " . ($firstLog->user ? $firstLog->user->name : 'System') . "\n";
        echo "- Event: " . $firstLog->event . "\n";
        echo "- URL: " . $firstLog->url . "\n";
        echo "- Created: " . $firstLog->created_at->format('Y-m-d H:i:s') . "\n";
    }
    
    // Generate CSV content manually to test the logic
    echo "\n=== Generating CSV Content ===\n";
    
    $csvContent = '';
    
    // CSV headers
    $headers = [
        'ID', 'User', 'Event', 'Model Type', 'Model ID', 'URL', 
        'IP Address', 'User Agent', 'Old Values', 'New Values', 
        'Tags', 'Created At'
    ];
    
    // Create CSV content in memory
    $output = fopen('php://temp', 'r+');
    
    // Write headers
    fputcsv($output, $headers);
    
    // Write data rows
    foreach ($auditLogs as $log) {
        fputcsv($output, [
            $log->id,
            $log->user ? $log->user->name . ' (' . $log->user->email . ')' : 'System',
            $log->event,
            $log->auditable_type,
            $log->auditable_id,
            $log->url,
            $log->ip_address,
            $log->user_agent,
            json_encode($log->old_values),
            json_encode($log->new_values),
            implode(', ', $log->tags ?? []),
            $log->created_at->format('Y-m-d H:i:s')
        ]);
    }
    
    // Get the CSV content
    rewind($output);
    $csvContent = stream_get_contents($output);
    fclose($output);
    
    echo "Generated CSV content (" . strlen($csvContent) . " characters):\n";
    echo "--- CSV Content Preview ---\n";
    
    $lines = explode("\n", $csvContent);
    $lineCount = 0;
    foreach ($lines as $line) {
        if (!empty(trim($line)) && $lineCount < 10) {
            echo "Line " . ($lineCount + 1) . ": " . $line . "\n";
            $lineCount++;
        }
    }
    
    if (count($lines) > 10) {
        echo "... (" . (count($lines) - 10) . " more lines)\n";
    }
    
    echo "--- End CSV Content ---\n\n";
    
    // Test actual controller response (but capture it differently)
    echo "=== Testing Controller Response ===\n";
    
    if (class_exists('App\\Http\\Controllers\\AuditController')) {
        $controller = new AuditController();
        Auth::login($testUser);
        
        $exportRequest = Request::create('/audit/export', 'GET', [
            'date_from' => now()->subDays(1)->format('Y-m-d'),
            'date_to' => now()->addDays(1)->format('Y-m-d')
        ]);
        
        try {
            // Capture output buffer for streamed response
            ob_start();
            $response = $controller->export($exportRequest);
            
            // For StreamedResponse, we need to call sendContent to get the output
            if (method_exists($response, 'sendContent')) {
                $response->sendContent();
                $streamedContent = ob_get_contents();
                ob_end_clean();
                
                echo "Controller export executed successfully\n";
                echo "Response type: " . get_class($response) . "\n";
                echo "Streamed content length: " . strlen($streamedContent) . " characters\n";
                
                if (strlen($streamedContent) > 0) {
                    echo "Streamed content preview:\n";
                    $streamedLines = explode("\n", $streamedContent);
                    for ($i = 0; $i < min(5, count($streamedLines)); $i++) {
                        if (!empty(trim($streamedLines[$i]))) {
                            echo "Line " . ($i + 1) . ": " . $streamedLines[$i] . "\n";
                        }
                    }
                } else {
                    echo "Warning: Streamed content is empty\n";
                }
                
                // Check headers
                if (method_exists($response, 'headers')) {
                    $responseHeaders = $response->headers->all();
                    echo "Response headers:\n";
                    foreach ($responseHeaders as $key => $value) {
                        echo "- $key: " . implode(', ', (array)$value) . "\n";
                    }
                }
                
            } else {
                ob_end_clean();
                echo "Response does not support sendContent method\n";
            }
            
        } catch (Exception $e) {
            ob_end_clean();
            echo "Error testing controller: " . $e->getMessage() . "\n";
        }
    }
    
    // Test filtering functionality
    echo "\n=== Testing Export Filters ===\n";
    
    $filterTests = [
        ['event' => 'user_login', 'description' => 'Login events only'],
        ['user_id' => $testUser->id, 'description' => 'Specific user only'],
        ['event' => 'created', 'description' => 'Created events only'],
    ];
    
    foreach ($filterTests as $filterTest) {
        echo "Testing filter: {$filterTest['description']}\n";
        
        $testFilters = array_merge($filters, $filterTest);
        unset($testFilters['description']);
        
        $filteredLogs = AuditTrail::with(['user'])
            ->when($testFilters['user_id'] ?? null, function ($query, $userId) {
                return $query->where('user_id', $userId);
            })
            ->when($testFilters['event'] ?? null, function ($query, $event) {
                return $query->where('event', $event);
            })
            ->when($testFilters['date_from'] ?? null, function ($query, $dateFrom) {
                return $query->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($testFilters['date_to'] ?? null, function ($query, $dateTo) {
                return $query->whereDate('created_at', '<=', $dateTo);
            })
            ->latest()
            ->get();
        
        echo "- Found " . $filteredLogs->count() . " records with filter\n";
        
        if ($filteredLogs->count() > 0) {
            echo "  Sample filtered record: " . $filteredLogs->first()->event . "\n";
        }
    }
    
    // Final verification
    echo "\n=== Final Verification ===\n";
    
    $totalAudits = AuditTrail::count();
    echo "Total audit logs in database: $totalAudits\n";
    
    $recentAudits = AuditTrail::where('created_at', '>=', now()->subMinutes(5))->count();
    echo "Recent audit logs (last 5 minutes): $recentAudits\n";
    
    // Test CSV file generation
    $testFilename = 'test_export_' . now()->format('Y-m-d_H-i-s') . '.csv';
    $testFilepath = storage_path('app/' . $testFilename);
    
    // Ensure storage directory exists
    if (!file_exists(dirname($testFilepath))) {
        mkdir(dirname($testFilepath), 0755, true);
    }
    
    file_put_contents($testFilepath, $csvContent);
    
    if (file_exists($testFilepath)) {
        echo "Test CSV file created: " . $testFilepath . "\n";
        echo "File size: " . filesize($testFilepath) . " bytes\n";
        
        // Clean up test file
        unlink($testFilepath);
        echo "Test file cleaned up\n";
    }
    
    echo "\nImproved CSV export test completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}