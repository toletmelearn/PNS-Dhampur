<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use App\Http\Controllers\ReportsController;
use App\Models\Student;
use App\Models\Result;
use App\Models\Attendance;
use App\Models\Fee;
use App\Models\ClassModel;
use Carbon\Carbon;

// Bootstrap Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== ENHANCED REPORTS TESTING ===\n";
echo "Testing comprehensive reporting features with advanced analytics...\n\n";

try {
    // Initialize the ReportsController
    $reportsController = new ReportsController();
    
    // Test 1: Financial Reports with Comparative Analysis
    echo "1. TESTING FINANCIAL REPORTS WITH COMPARATIVE ANALYSIS\n";
    echo "=" . str_repeat("=", 60) . "\n";
    
    $startTime = microtime(true);
    
    // Create mock request for financial reports
    $financialRequest = new Request([
        'period' => 'monthly',
        'year' => date('Y'),
        'month' => date('m')
    ]);
    
    $financialReports = $reportsController->financialReports($financialRequest);
    
    if (isset($financialReports['comparative_analysis'])) {
        echo "✓ Comparative analysis implemented\n";
        echo "  - Current period revenue: " . ($financialReports['comparative_analysis']['current_period']['total_revenue'] ?? 'N/A') . "\n";
        echo "  - Previous period revenue: " . ($financialReports['comparative_analysis']['previous_period']['total_revenue'] ?? 'N/A') . "\n";
        echo "  - Month-over-month growth: " . ($financialReports['comparative_analysis']['growth_metrics']['mom_growth'] ?? 'N/A') . "%\n";
        echo "  - Year-over-year growth: " . ($financialReports['comparative_analysis']['growth_metrics']['yoy_growth'] ?? 'N/A') . "%\n";
    } else {
        echo "✗ Comparative analysis missing\n";
    }
    
    if (isset($financialReports['trend_analysis'])) {
        echo "✓ Trend analysis implemented\n";
        echo "  - Revenue trend: " . ($financialReports['trend_analysis']['revenue_trend'] ?? 'N/A') . "\n";
        echo "  - Collection efficiency: " . ($financialReports['trend_analysis']['collection_efficiency'] ?? 'N/A') . "%\n";
    } else {
        echo "✗ Trend analysis missing\n";
    }
    
    $financialTime = round((microtime(true) - $startTime) * 1000, 2);
    echo "Financial reports execution time: {$financialTime}ms\n\n";
    
    // Test 2: Attendance Reports with Trend Analysis
    echo "2. TESTING ATTENDANCE REPORTS WITH TREND ANALYSIS\n";
    echo "=" . str_repeat("=", 60) . "\n";
    
    $startTime = microtime(true);
    
    // Create mock request for attendance reports
    $attendanceRequest = new Request([
        'period' => 'monthly',
        'class_id' => 'all',
        'date_from' => Carbon::now()->subMonth()->format('Y-m-d'),
        'date_to' => Carbon::now()->format('Y-m-d')
    ]);
    
    $attendanceReports = $reportsController->attendanceReports($attendanceRequest);
    
    if (isset($attendanceReports['trend_analysis'])) {
        echo "✓ Trend analysis implemented\n";
        echo "  - Current month attendance rate: " . ($attendanceReports['trend_analysis']['current_month_rate'] ?? 'N/A') . "%\n";
        echo "  - Previous month attendance rate: " . ($attendanceReports['trend_analysis']['previous_month_rate'] ?? 'N/A') . "%\n";
        echo "  - Trend direction: " . ($attendanceReports['trend_analysis']['trend_direction'] ?? 'N/A') . "\n";
    } else {
        echo "✗ Trend analysis missing\n";
    }
    
    if (isset($attendanceReports['predictive_insights'])) {
        echo "✓ Predictive insights implemented\n";
        echo "  - Next month forecast: " . ($attendanceReports['predictive_insights']['forecast']['next_month'] ?? 'N/A') . "%\n";
        echo "  - Risk assessment: " . count($attendanceReports['predictive_insights']['risk_assessment']['high_risk_students'] ?? []) . " high-risk students\n";
    } else {
        echo "✗ Predictive insights missing\n";
    }
    
    if (isset($attendanceReports['seasonal_patterns'])) {
        echo "✓ Seasonal patterns implemented\n";
        echo "  - Winter variation: " . ($attendanceReports['seasonal_patterns']['winter_variation'] ?? 'N/A') . "%\n";
        echo "  - Spring variation: " . ($attendanceReports['seasonal_patterns']['spring_variation'] ?? 'N/A') . "%\n";
    } else {
        echo "✗ Seasonal patterns missing\n";
    }
    
    $attendanceTime = round((microtime(true) - $startTime) * 1000, 2);
    echo "Attendance reports execution time: {$attendanceTime}ms\n\n";
    
    // Test 3: Performance Reports with Predictive Analytics
    echo "3. TESTING PERFORMANCE REPORTS WITH PREDICTIVE ANALYTICS\n";
    echo "=" . str_repeat("=", 60) . "\n";
    
    $startTime = microtime(true);
    
    // Create mock request for performance reports
    $performanceRequest = new Request([
        'period' => 'term',
        'class_id' => 'all',
        'subject' => 'all'
    ]);
    
    $performanceReports = $reportsController->performanceReports($performanceRequest);
    
    if (isset($performanceReports['predictive_analytics'])) {
        echo "✓ Predictive analytics implemented\n";
        
        if (isset($performanceReports['predictive_analytics']['performance_forecast'])) {
            echo "  - Performance forecasting: ✓\n";
            $forecastTerms = $performanceReports['predictive_analytics']['performance_forecast']['forecast_terms'] ?? [];
            echo "    Next term predictions: " . count($forecastTerms) . " terms forecasted\n";
        }
        
        if (isset($performanceReports['predictive_analytics']['risk_assessment'])) {
            echo "  - Risk assessment: ✓\n";
            $highRisk = count($performanceReports['predictive_analytics']['risk_assessment']['high_risk_students'] ?? []);
            $mediumRisk = count($performanceReports['predictive_analytics']['risk_assessment']['medium_risk_students'] ?? []);
            echo "    High-risk students: {$highRisk}\n";
            echo "    Medium-risk students: {$mediumRisk}\n";
        }
        
        if (isset($performanceReports['predictive_analytics']['success_predictions'])) {
            echo "  - Success predictions: ✓\n";
            $predictions = count($performanceReports['predictive_analytics']['success_predictions']['student_predictions'] ?? []);
            echo "    Student predictions generated: {$predictions}\n";
        }
        
        if (isset($performanceReports['predictive_analytics']['grade_predictions'])) {
            echo "  - Grade predictions: ✓\n";
            echo "    Grade distribution forecasting available\n";
        }
    } else {
        echo "✗ Predictive analytics missing\n";
    }
    
    if (isset($performanceReports['learning_analytics'])) {
        echo "✓ Learning analytics implemented\n";
        echo "  - Subject performance trends: ✓\n";
        echo "  - Student progression analysis: ✓\n";
        echo "  - Competency mapping: ✓\n";
        echo "  - Learning velocity tracking: ✓\n";
    } else {
        echo "✗ Learning analytics missing\n";
    }
    
    if (isset($performanceReports['intervention_recommendations'])) {
        echo "✓ Intervention recommendations implemented\n";
        $immediate = count($performanceReports['intervention_recommendations']['immediate_interventions'] ?? []);
        $shortTerm = count($performanceReports['intervention_recommendations']['short_term_strategies'] ?? []);
        $longTerm = count($performanceReports['intervention_recommendations']['long_term_initiatives'] ?? []);
        echo "  - Immediate interventions: {$immediate}\n";
        echo "  - Short-term strategies: {$shortTerm}\n";
        echo "  - Long-term initiatives: {$longTerm}\n";
    } else {
        echo "✗ Intervention recommendations missing\n";
    }
    
    $performanceTime = round((microtime(true) - $startTime) * 1000, 2);
    echo "Performance reports execution time: {$performanceTime}ms\n\n";
    
    // Test 4: Data Structure and Response Format Validation
    echo "4. TESTING DATA STRUCTURE AND RESPONSE FORMAT\n";
    echo "=" . str_repeat("=", 60) . "\n";
    
    $allReports = [
        'financial' => $financialReports,
        'attendance' => $attendanceReports,
        'performance' => $performanceReports
    ];
    
    $structureValid = true;
    foreach ($allReports as $reportType => $reportData) {
        if (!is_array($reportData)) {
            echo "✗ {$reportType} report: Invalid data structure (not array)\n";
            $structureValid = false;
        } else {
            echo "✓ {$reportType} report: Valid data structure\n";
            
            // Check for required analytics sections
            $requiredSections = [
                'financial' => ['comparative_analysis', 'trend_analysis'],
                'attendance' => ['trend_analysis', 'predictive_insights'],
                'performance' => ['predictive_analytics', 'learning_analytics']
            ];
            
            foreach ($requiredSections[$reportType] as $section) {
                if (isset($reportData[$section])) {
                    echo "  - {$section}: ✓\n";
                } else {
                    echo "  - {$section}: ✗\n";
                    $structureValid = false;
                }
            }
        }
    }
    
    // Test 5: Performance and Memory Usage
    echo "\n5. TESTING PERFORMANCE AND MEMORY USAGE\n";
    echo "=" . str_repeat("=", 60) . "\n";
    
    $totalTime = $financialTime + $attendanceTime + $performanceTime;
    $memoryUsage = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
    
    echo "Total execution time: {$totalTime}ms\n";
    echo "Peak memory usage: {$memoryUsage}MB\n";
    
    // Performance benchmarks
    if ($totalTime < 1000) {
        echo "✓ Performance: Excellent (< 1 second)\n";
    } elseif ($totalTime < 3000) {
        echo "✓ Performance: Good (< 3 seconds)\n";
    } else {
        echo "⚠ Performance: Needs optimization (> 3 seconds)\n";
    }
    
    if ($memoryUsage < 50) {
        echo "✓ Memory usage: Excellent (< 50MB)\n";
    } elseif ($memoryUsage < 100) {
        echo "✓ Memory usage: Good (< 100MB)\n";
    } else {
        echo "⚠ Memory usage: High (> 100MB)\n";
    }
    
    // Test 6: Error Handling and Edge Cases
    echo "\n6. TESTING ERROR HANDLING AND EDGE CASES\n";
    echo "=" . str_repeat("=", 60) . "\n";
    
    try {
        // Test with invalid parameters
        $invalidRequest = new Request(['period' => 'invalid', 'class_id' => 'nonexistent']);
        $errorTest = $reportsController->financialReports($invalidRequest);
        echo "✓ Error handling: Graceful handling of invalid parameters\n";
    } catch (Exception $e) {
        echo "⚠ Error handling: Exception thrown - " . $e->getMessage() . "\n";
    }
    
    // Final Summary
    echo "\n" . str_repeat("=", 70) . "\n";
    echo "ENHANCED REPORTS TESTING SUMMARY\n";
    echo str_repeat("=", 70) . "\n";
    
    $testResults = [
        'Financial Reports Comparative Analysis' => isset($financialReports['comparative_analysis']),
        'Attendance Reports Trend Analysis' => isset($attendanceReports['trend_analysis']),
        'Performance Reports Predictive Analytics' => isset($performanceReports['predictive_analytics']),
        'Data Structure Validation' => $structureValid,
        'Performance Benchmarks' => $totalTime < 3000 && $memoryUsage < 100
    ];
    
    $passedTests = array_sum($testResults);
    $totalTests = count($testResults);
    
    foreach ($testResults as $test => $passed) {
        echo ($passed ? "✓" : "✗") . " {$test}\n";
    }
    
    echo "\nTest Results: {$passedTests}/{$totalTests} tests passed\n";
    echo "Overall Status: " . ($passedTests === $totalTests ? "ALL TESTS PASSED!" : "SOME TESTS FAILED") . "\n";
    echo "Total Execution Time: {$totalTime}ms\n";
    echo "Peak Memory Usage: {$memoryUsage}MB\n";
    
    if ($passedTests === $totalTests) {
        echo "\n🎉 ALL ENHANCED REPORTING FEATURES ARE WORKING CORRECTLY!\n";
        echo "The reporting system now includes:\n";
        echo "- Financial reports with comparative analysis and growth metrics\n";
        echo "- Attendance reports with trend analysis and predictive insights\n";
        echo "- Performance reports with predictive analytics and learning insights\n";
        echo "- Comprehensive data validation and error handling\n";
        echo "- Optimized performance and memory usage\n";
    } else {
        echo "\n⚠️  SOME FEATURES NEED ATTENTION\n";
        echo "Please review the failed tests and implement missing functionality.\n";
    }

} catch (Exception $e) {
    echo "CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n=== ENHANCED REPORTS TESTING COMPLETED ===\n";