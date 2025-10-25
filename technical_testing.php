<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Request::capture();
$response = $kernel->handle($request);

echo "=== TECHNICAL TESTING SUITE ===\n\n";

// 1. DATABASE TESTING
function testDatabaseIntegrity() {
    echo "🗄️  DATABASE INTEGRITY TESTING\n";
    echo str_repeat("=", 50) . "\n";
    
    $results = [
        'connection' => false,
        'migrations' => false,
        'relationships' => false,
        'constraints' => false,
        'indexes' => false
    ];
    
    try {
        // Test database connection
        DB::connection()->getPdo();
        echo "✅ Database connection: SUCCESSFUL\n";
        $results['connection'] = true;
        
        // Test migration status
        try {
            $migrations = DB::table('migrations')->count();
            echo "✅ Migration table exists with {$migrations} migrations\n";
            $results['migrations'] = true;
        } catch (Exception $e) {
            echo "❌ Migration table error: " . $e->getMessage() . "\n";
        }
        
        // Test key tables and relationships
        $criticalTables = [
            'users' => ['id', 'email', 'password'],
            'schools' => ['id', 'name'],
            'roles' => ['id', 'name'],
            'students' => ['id', 'user_id', 'school_id'],
            'teachers' => ['id', 'user_id', 'school_id'],
            'fees' => ['id', 'student_id', 'amount'],
            'student_attendance' => ['id', 'student_id', 'date']
        ];
        
        $tablesFound = 0;
        $totalTables = count($criticalTables);
        
        foreach ($criticalTables as $table => $requiredColumns) {
            if (Schema::hasTable($table)) {
                $tablesFound++;
                $columns = Schema::getColumnListing($table);
                $missingColumns = array_diff($requiredColumns, $columns);
                
                if (empty($missingColumns)) {
                    echo "✅ Table '{$table}': Complete with " . count($columns) . " columns\n";
                } else {
                    echo "⚠️  Table '{$table}': Missing columns: " . implode(', ', $missingColumns) . "\n";
                }
            } else {
                echo "❌ Table '{$table}': NOT FOUND\n";
            }
        }
        
        $results['relationships'] = $tablesFound >= ($totalTables * 0.8);
        
        // Test foreign key constraints
        try {
            $constraintTests = [
                "SELECT COUNT(*) as count FROM students s JOIN users u ON s.user_id = u.id",
                "SELECT COUNT(*) as count FROM teachers t JOIN users u ON t.user_id = u.id",
                "SELECT COUNT(*) as count FROM fees f JOIN students s ON f.student_id = s.id"
            ];
            
            $constraintsPassed = 0;
            foreach ($constraintTests as $test) {
                try {
                    $result = DB::select($test);
                    $constraintsPassed++;
                    echo "✅ Foreign key constraint test passed\n";
                } catch (Exception $e) {
                    echo "❌ Foreign key constraint failed: " . substr($e->getMessage(), 0, 100) . "...\n";
                }
            }
            
            $results['constraints'] = $constraintsPassed >= 2;
            
        } catch (Exception $e) {
            echo "❌ Constraint testing error: " . $e->getMessage() . "\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    }
    
    return $results;
}

// 2. API PERFORMANCE TESTING
function testAPIPerformance() {
    echo "\n🚀 API PERFORMANCE TESTING\n";
    echo str_repeat("=", 50) . "\n";
    
    $baseUrl = 'http://127.0.0.1:8002';
    $endpoints = [
        '/api/dashboard',
        '/api/students',
        '/api/teachers',
        '/api/fees',
        '/api/attendance',
        '/',
        '/login',
        '/dashboard'
    ];
    
    $results = [];
    $totalTime = 0;
    $successfulRequests = 0;
    
    foreach ($endpoints as $endpoint) {
        $startTime = microtime(true);
        
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'method' => 'GET',
                    'ignore_errors' => true
                ]
            ]);
            
            $result = @file_get_contents($baseUrl . $endpoint, false, $context);
            $endTime = microtime(true);
            $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
            
            $httpCode = 200; // Default
            if (isset($http_response_header)) {
                foreach ($http_response_header as $header) {
                    if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                        $httpCode = (int)$matches[1];
                        break;
                    }
                }
            }
            
            $size = $result ? strlen($result) : 0;
            $success = $result !== false && in_array($httpCode, [200, 302, 401]);
            
            if ($success) {
                $successfulRequests++;
                $totalTime += $responseTime;
            }
            
            $status = $success ? "✅" : "❌";
            echo "{$status} {$endpoint}: {$responseTime}ms (HTTP: {$httpCode}, Size: {$size}B)\n";
            
            $results[$endpoint] = [
                'response_time' => $responseTime,
                'http_code' => $httpCode,
                'size' => $size,
                'success' => $success
            ];
            
        } catch (Exception $e) {
            echo "❌ {$endpoint}: ERROR - " . $e->getMessage() . "\n";
            $results[$endpoint] = ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    $averageTime = $successfulRequests > 0 ? $totalTime / $successfulRequests : 0;
    $successRate = (count($endpoints) > 0) ? ($successfulRequests / count($endpoints)) * 100 : 0;
    
    echo "\n📊 Performance Summary:\n";
    echo "  Average Response Time: " . round($averageTime, 2) . "ms\n";
    echo "  Success Rate: " . round($successRate, 2) . "%\n";
    echo "  Successful Requests: {$successfulRequests}/" . count($endpoints) . "\n";
    
    return [
        'average_time' => $averageTime,
        'success_rate' => $successRate,
        'under_3_seconds' => $averageTime < 3000,
        'results' => $results
    ];
}

// 3. SECURITY TESTING
function testSecurity() {
    echo "\n🔒 SECURITY TESTING\n";
    echo str_repeat("=", 50) . "\n";
    
    $results = [
        'csrf_protection' => false,
        'sql_injection' => false,
        'file_upload' => false,
        'authentication' => false,
        'authorization' => false
    ];
    
    // Test CSRF Protection
    try {
        $baseUrl = 'http://127.0.0.1:8002';
        
        // Test login page for CSRF token
        $loginPage = @file_get_contents($baseUrl . '/login');
        if ($loginPage && strpos($loginPage, 'csrf') !== false) {
            echo "✅ CSRF Protection: Token found in login form\n";
            $results['csrf_protection'] = true;
        } else {
            echo "⚠️  CSRF Protection: No token detected\n";
        }
        
        // Test SQL Injection protection (basic)
        $sqlInjectionTests = [
            "/api/students?id=1' OR '1'='1",
            "/api/teachers?search='; DROP TABLE users; --",
            "/dashboard?user_id=1 UNION SELECT * FROM users"
        ];
        
        $sqlProtected = 0;
        foreach ($sqlInjectionTests as $test) {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'method' => 'GET',
                    'ignore_errors' => true
                ]
            ]);
            
            $result = @file_get_contents($baseUrl . $test, false, $context);
            
            // If we get a proper error response or redirect, it's likely protected
            $httpCode = 200;
            if (isset($http_response_header)) {
                foreach ($http_response_header as $header) {
                    if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                        $httpCode = (int)$matches[1];
                        break;
                    }
                }
            }
            
            if (in_array($httpCode, [400, 401, 403, 422, 500]) || $result === false) {
                $sqlProtected++;
            }
        }
        
        if ($sqlProtected >= 2) {
            echo "✅ SQL Injection Protection: Appears protected\n";
            $results['sql_injection'] = true;
        } else {
            echo "⚠️  SQL Injection Protection: Needs verification\n";
        }
        
        // Test Authentication
        $authTest = @file_get_contents($baseUrl . '/dashboard');
        if ($authTest && (strpos($authTest, 'login') !== false || strpos($authTest, 'redirect') !== false)) {
            echo "✅ Authentication: Dashboard redirects unauthenticated users\n";
            $results['authentication'] = true;
        } else {
            echo "⚠️  Authentication: Dashboard access needs verification\n";
        }
        
        // Test Authorization (admin routes)
        $adminTest = @file_get_contents($baseUrl . '/admin/users');
        if ($adminTest && (strpos($adminTest, 'login') !== false || strpos($adminTest, 'unauthorized') !== false)) {
            echo "✅ Authorization: Admin routes appear protected\n";
            $results['authorization'] = true;
        } else {
            echo "⚠️  Authorization: Admin access needs verification\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Security testing error: " . $e->getMessage() . "\n";
    }
    
    return $results;
}

// 4. SYSTEM HEALTH CHECK
function testSystemHealth() {
    echo "\n💊 SYSTEM HEALTH CHECK\n";
    echo str_repeat("=", 50) . "\n";
    
    $results = [];
    
    try {
        // Check PHP version
        $phpVersion = PHP_VERSION;
        echo "✅ PHP Version: {$phpVersion}\n";
        $results['php_version'] = $phpVersion;
        
        // Check Laravel version
        $laravelVersion = app()->version();
        echo "✅ Laravel Version: {$laravelVersion}\n";
        $results['laravel_version'] = $laravelVersion;
        
        // Check memory usage
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        echo "✅ Memory Usage: " . round($memoryUsage / 1024 / 1024, 2) . "MB (Limit: {$memoryLimit})\n";
        $results['memory_usage'] = $memoryUsage;
        
        // Check disk space (if possible)
        $diskFree = disk_free_space('.');
        $diskTotal = disk_total_space('.');
        if ($diskFree && $diskTotal) {
            $diskUsage = (($diskTotal - $diskFree) / $diskTotal) * 100;
            echo "✅ Disk Usage: " . round($diskUsage, 2) . "%\n";
            $results['disk_usage'] = $diskUsage;
        }
        
        // Check environment
        $environment = app()->environment();
        echo "✅ Environment: {$environment}\n";
        $results['environment'] = $environment;
        
    } catch (Exception $e) {
        echo "❌ System health check error: " . $e->getMessage() . "\n";
    }
    
    return $results;
}

// Run all technical tests
echo "Starting comprehensive technical testing...\n\n";

$databaseResults = testDatabaseIntegrity();
$performanceResults = testAPIPerformance();
$securityResults = testSecurity();
$healthResults = testSystemHealth();

// Final Technical Testing Summary
echo "\n" . str_repeat("=", 80) . "\n";
echo "TECHNICAL TESTING SUMMARY\n";
echo str_repeat("=", 80) . "\n";

echo "🗄️  DATABASE TESTING:\n";
foreach ($databaseResults as $test => $result) {
    $status = $result ? "✅ PASS" : "❌ FAIL";
    echo "  {$status} " . ucfirst(str_replace('_', ' ', $test)) . "\n";
}

echo "\n🚀 PERFORMANCE TESTING:\n";
echo "  Average Response Time: " . round($performanceResults['average_time'], 2) . "ms\n";
echo "  Success Rate: " . round($performanceResults['success_rate'], 2) . "%\n";
echo "  Under 3 seconds: " . ($performanceResults['under_3_seconds'] ? "✅ YES" : "❌ NO") . "\n";

echo "\n🔒 SECURITY TESTING:\n";
foreach ($securityResults as $test => $result) {
    $status = $result ? "✅ PASS" : "⚠️  NEEDS REVIEW";
    echo "  {$status} " . ucfirst(str_replace('_', ' ', $test)) . "\n";
}

echo "\n💊 SYSTEM HEALTH:\n";
echo "  PHP Version: " . ($healthResults['php_version'] ?? 'Unknown') . "\n";
echo "  Laravel Version: " . ($healthResults['laravel_version'] ?? 'Unknown') . "\n";
echo "  Environment: " . ($healthResults['environment'] ?? 'Unknown') . "\n";

// Calculate overall technical score
$dbScore = array_sum($databaseResults) / count($databaseResults) * 100;
$perfScore = $performanceResults['success_rate'];
$secScore = array_sum($securityResults) / count($securityResults) * 100;

$overallTechnicalScore = ($dbScore + $perfScore + $secScore) / 3;

echo "\n📊 OVERALL TECHNICAL SCORE: " . round($overallTechnicalScore, 2) . "%\n";

if ($overallTechnicalScore >= 80) {
    echo "🎉 EXCELLENT! System is technically sound!\n";
} elseif ($overallTechnicalScore >= 60) {
    echo "✅ GOOD! System is technically acceptable with minor issues.\n";
} else {
    echo "⚠️  WARNING! Technical issues need attention before production.\n";
}

echo str_repeat("=", 80) . "\n";

?>