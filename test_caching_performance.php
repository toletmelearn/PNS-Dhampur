<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing Cache Performance Implementation\n";
echo "=====================================\n\n";

// Test 1: Check if Cache facade is working
echo "1. Testing Cache Facade...\n";
try {
    Cache::put('test_key', 'test_value', 60);
    $value = Cache::get('test_key');
    if ($value === 'test_value') {
        echo "   ✓ Cache facade is working correctly\n";
    } else {
        echo "   ✗ Cache facade test failed\n";
    }
} catch (Exception $e) {
    echo "   ✗ Cache facade error: " . $e->getMessage() . "\n";
}

// Test 2: Check database connection
echo "\n2. Testing Database Connection...\n";
try {
    DB::connection()->getPdo();
    echo "   ✓ Database connection is working\n";
} catch (Exception $e) {
    echo "   ✗ Database connection error: " . $e->getMessage() . "\n";
}

// Test 3: Test caching constants in ReportsController
echo "\n3. Testing ReportsController Caching Constants...\n";
try {
    $reflection = new ReflectionClass('App\Http\Controllers\ReportsController');
    $constants = $reflection->getConstants();
    
    $expectedConstants = ['CACHE_SHORT', 'CACHE_MEDIUM', 'CACHE_LONG', 'CACHE_DAILY'];
    $foundConstants = [];
    
    foreach ($expectedConstants as $constant) {
        if (array_key_exists($constant, $constants)) {
            $foundConstants[] = $constant;
            echo "   ✓ Found constant: $constant = " . $constants[$constant] . " seconds\n";
        } else {
            echo "   ✗ Missing constant: $constant\n";
        }
    }
    
    if (count($foundConstants) === count($expectedConstants)) {
        echo "   ✓ All caching constants are properly defined\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error checking constants: " . $e->getMessage() . "\n";
}

// Test 4: Test if Cache is imported in ReportsController
echo "\n4. Testing Cache Import in ReportsController...\n";
try {
    $controllerFile = file_get_contents(__DIR__ . '/app/Http/Controllers/ReportsController.php');
    if (strpos($controllerFile, 'use Illuminate\Support\Facades\Cache;') !== false) {
        echo "   ✓ Cache facade is properly imported\n";
    } else {
        echo "   ✗ Cache facade import not found\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error reading controller file: " . $e->getMessage() . "\n";
}

// Test 5: Check for Cache::remember usage
echo "\n5. Testing Cache::remember Implementation...\n";
try {
    $controllerFile = file_get_contents(__DIR__ . '/app/Http/Controllers/ReportsController.php');
    $cacheRememberCount = substr_count($controllerFile, 'Cache::remember');
    
    if ($cacheRememberCount > 0) {
        echo "   ✓ Found $cacheRememberCount Cache::remember implementations\n";
        
        // Check specific methods
        $cachedMethods = [
            'financialReports' => 'financial_reports',
            'attendanceReports' => 'attendance_reports', 
            'performanceReports' => 'performance_reports',
            'administrativeReports' => 'administrative_reports',
            'getTeacherEffectiveness' => 'teacher_effectiveness',
            'getSeasonalAttendancePatterns' => 'seasonal_attendance_patterns',
            'getAbsenteeismAnalysis' => 'absenteeism_analysis',
            'getClassWiseAttendance' => 'class_wise_attendance',
            'getTopPerformers' => 'top_performers',
            'getSubjectAverages' => 'subject_averages',
            'getGradeDistribution' => 'grade_distribution'
        ];
        
        foreach ($cachedMethods as $method => $cacheKey) {
            if (strpos($controllerFile, "'$cacheKey'") !== false) {
                echo "   ✓ $method method has caching implemented\n";
            } else {
                echo "   ✗ $method method missing cache key '$cacheKey'\n";
            }
        }
    } else {
        echo "   ✗ No Cache::remember implementations found\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error checking cache implementations: " . $e->getMessage() . "\n";
}

echo "\n=====================================\n";
echo "Cache Performance Test Complete\n";
echo "=====================================\n";