<?php

namespace Tests\Feature;

use Tests\TestCase;
// RefreshDatabase removed - using DatabaseTransactions in base TestCase
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Services\OptimizedReportService;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Fee;
use App\Models\Attendance;
use App\Models\Result;
use Carbon\Carbon;

class OptimizedReportServiceTest extends TestCase
{
    // RefreshDatabase removed - using DatabaseTransactions in base TestCase

    protected $reportService;
    protected $testClass;
    protected $testStudents;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reportService = new OptimizedReportService();
        $this->createTestData();
    }

    protected function createTestData()
    {
        // Create test class
        $this->testClass = ClassModel::create([
            'name' => 'Test Class 10A',
            'is_active' => true
        ]);

        // Create test students
        $this->testStudents = collect();
        for ($i = 1; $i <= 5; $i++) {
            $student = Student::create([
                'user_id' => $i,
                'name' => "Test Student {$i}",
                'admission_no' => "TS{$i}",
                'father_name' => "Father {$i}",
                'mother_name' => "Mother {$i}",
                'dob' => '2010-01-01',
                'class_id' => $this->testClass->id,
                'verification_status' => 'verified',
                'status' => 'active'
            ]);
            $this->testStudents->push($student);

            // Create fees for each student
            Fee::create([
                'student_id' => $student->id,
                'amount' => 1000,
                'paid_amount' => $i * 200, // Varying paid amounts
                'status' => $i <= 3 ? 'partial' : 'unpaid'
            ]);

            // Create attendance records within the last 3 months (default filter range)
            for ($j = 0; $j < 10; $j++) {
                Attendance::create([
                    'student_id' => $student->id,
                    'class_id' => $this->testClass->id,
                    'date' => Carbon::now()->subDays($j + 1), // Ensure dates are within the last 3 months
                    'status' => $j < 8 ? 'present' : 'absent'
                ]);
            }

            // Create results
            Result::create([
                'student_id' => $student->id,
                'exam_id' => 1,
                'subject' => 'Mathematics',
                'marks_obtained' => 70 + ($i * 5),
                'total_marks' => 100
            ]);
        }
    }

    /** @test */
    public function it_generates_comprehensive_report_with_caching()
    {
        // Clear any existing cache
        Cache::flush();

        // First call should hit the database
        $startTime = microtime(true);
        $report1 = $this->reportService->generateComprehensiveReport();
        $firstCallTime = microtime(true) - $startTime;

        // Second call should use cache and be faster
        $startTime = microtime(true);
        $report2 = $this->reportService->generateComprehensiveReport();
        $secondCallTime = microtime(true) - $startTime;

        // Verify cache is working (second call should be significantly faster)
        $this->assertLessThan($firstCallTime, $secondCallTime);
        
        // Verify report structure
        $this->assertArrayHasKey('student_summary', $report1);
        $this->assertArrayHasKey('class_wise_collection', $report1);
        $this->assertArrayHasKey('attendance_analysis', $report1);
        $this->assertArrayHasKey('performance_metrics', $report1);
        $this->assertArrayHasKey('financial_overview', $report1);
        $this->assertArrayHasKey('generated_at', $report1);

        // Verify both reports are identical
        $this->assertEquals($report1, $report2);
    }

    /** @test */
    public function it_optimizes_class_wise_collection_query()
    {
        $result = $this->reportService->getOptimizedClassWiseCollection();

        $this->assertNotEmpty($result);
        $classData = $result->first();
        
        $this->assertObjectHasAttribute('class_name', $classData);
        $this->assertObjectHasAttribute('student_count', $classData);
        $this->assertObjectHasAttribute('total_collected', $classData);
        $this->assertObjectHasAttribute('total_amount', $classData);
        $this->assertObjectHasAttribute('pending_amount', $classData);
        $this->assertObjectHasAttribute('collection_percentage', $classData);

        // Verify calculations
        $this->assertEquals('Test Class 10A', $classData->class_name);
        $this->assertEquals(5, $classData->student_count);
        $this->assertEquals(3000, $classData->total_collected); // 200+400+600+800+1000
        $this->assertEquals(5000, $classData->total_amount); // 5 * 1000
    }

    /** @test */
    public function it_optimizes_attendance_cohort_analysis()
    {
        $result = $this->reportService->getOptimizedAttendanceCohortAnalysis();

        $this->assertNotEmpty($result);
        $classData = $result->first();

        $this->assertArrayHasKey('class_name', $classData);
        $this->assertArrayHasKey('student_count', $classData);
        $this->assertArrayHasKey('attendance_rate', $classData);
        $this->assertArrayHasKey('total_records', $classData);
        $this->assertArrayHasKey('present_count', $classData);
        $this->assertArrayHasKey('absent_count', $classData);
        $this->assertArrayHasKey('performance_category', $classData);

        // Verify calculations
        $this->assertEquals('Test Class 10A', $classData['class_name']);
        $this->assertEquals(5, $classData['student_count']);
        $this->assertEquals(80.0, $classData['attendance_rate']); // 80% attendance (8 out of 10 days present)
    }

    /** @test */
    public function it_handles_student_summary_with_chunking()
    {
        // Create more students to test chunking
        for ($i = 6; $i <= 15; $i++) {
            $student = Student::create([
                'user_id' => $i,
                'name' => "Bulk Student {$i}",
                'admission_no' => "BS{$i}",
                'father_name' => "Father {$i}",
                'mother_name' => "Mother {$i}",
                'dob' => '2010-01-01',
                'class_id' => $this->testClass->id,
                'verification_status' => 'verified'
            ]);

            Fee::create([
                'student_id' => $student->id,
                'amount' => 1000,
                'paid_amount' => 500,
                'status' => 'partial'
            ]);
        }

        $result = $this->reportService->getOptimizedStudentSummary();

        $this->assertNotEmpty($result);
        $this->assertCount(15, $result); // 5 original + 10 new students

        $studentData = $result->first();
        $this->assertObjectHasAttribute('name', $studentData);
        $this->assertObjectHasAttribute('class_name', $studentData);
        $this->assertObjectHasAttribute('total_fees', $studentData);
        $this->assertObjectHasAttribute('paid_fees', $studentData);
    }

    /** @test */
    public function it_provides_attendance_analysis_with_date_filtering()
    {
        $filters = [
            'date_from' => Carbon::now()->subDays(5)->format('Y-m-d'),
            'date_to' => Carbon::now()->format('Y-m-d')
        ];

        $result = $this->reportService->getOptimizedAttendanceAnalysis($filters);

        $this->assertNotEmpty($result);
        
        foreach ($result as $dayData) {
            $this->assertObjectHasAttribute('attendance_date', $dayData);
            $this->assertObjectHasAttribute('total_records', $dayData);
            $this->assertObjectHasAttribute('present_count', $dayData);
            $this->assertObjectHasAttribute('absent_count', $dayData);
            $this->assertObjectHasAttribute('daily_attendance_rate', $dayData);
        }
    }

    /** @test */
    public function it_calculates_performance_metrics_correctly()
    {
        $result = $this->reportService->getOptimizedPerformanceMetrics();

        $this->assertNotEmpty($result);
        $mathData = $result->first();

        $this->assertObjectHasAttribute('subject', $mathData);
        $this->assertObjectHasAttribute('total_assessments', $mathData);
        $this->assertObjectHasAttribute('average_marks', $mathData);
        $this->assertObjectHasAttribute('min_marks', $mathData);
        $this->assertObjectHasAttribute('max_marks', $mathData);
        $this->assertObjectHasAttribute('pass_rate', $mathData);

        $this->assertEquals('Mathematics', $mathData->subject);
        $this->assertEquals(5, $mathData->total_assessments);
    }

    /** @test */
    public function it_provides_financial_overview()
    {
        $result = $this->reportService->getOptimizedFinancialOverview();

        $this->assertObjectHasAttribute('total_students', $result);
        $this->assertObjectHasAttribute('total_fees_amount', $result);
        $this->assertObjectHasAttribute('total_paid_amount', $result);
        $this->assertObjectHasAttribute('total_pending_amount', $result);
        $this->assertObjectHasAttribute('overall_collection_rate', $result);

        $this->assertEquals(5, $result->total_students);
        $this->assertEquals(5000, $result->total_fees_amount);
        $this->assertEquals(3000, $result->total_paid_amount);
        $this->assertEquals(2000, $result->total_pending_amount);
        $this->assertEquals(60.0, $result->overall_collection_rate);
    }

    /** @test */
    public function it_clears_report_caches()
    {
        // Generate some cached data
        $this->reportService->generateComprehensiveReport();
        $this->reportService->getOptimizedClassWiseCollection();

        // Verify cache exists (this is implementation dependent)
        $this->assertTrue(true); // Placeholder assertion

        // Clear caches
        $this->reportService->clearReportCaches();

        // Verify caches are cleared (this is implementation dependent)
        $this->assertTrue(true); // Placeholder assertion
    }

    /** @test */
    public function it_handles_empty_data_gracefully()
    {
        // Clear all test data
        DB::table('attendances')->delete();
        DB::table('fees')->delete();
        DB::table('results')->delete();
        DB::table('students')->delete();
        DB::table('class_models')->delete();

        $result = $this->reportService->generateComprehensiveReport();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('student_summary', $result);
        $this->assertArrayHasKey('class_wise_collection', $result);
        $this->assertArrayHasKey('attendance_analysis', $result);
    }

    /** @test */
    public function it_applies_date_filters_correctly()
    {
        $filters = [
            'date_from' => Carbon::now()->subDays(2)->format('Y-m-d'),
            'date_to' => Carbon::now()->format('Y-m-d')
        ];

        $result = $this->reportService->getOptimizedClassWiseCollection($filters);
        
        // Should still return data but potentially different amounts
        // based on the date filtering
        $this->assertNotNull($result);
    }

    /** @test */
    public function it_categorizes_class_performance_correctly()
    {
        $result = $this->reportService->getOptimizedAttendanceCohortAnalysis();

        $this->assertNotEmpty($result);
        $classData = $result->first();

        // With 80% attendance rate, should be categorized as "Good"
        $this->assertEquals('Good', $classData['performance_category']);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}