<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Exam;
use App\Models\Result;
use App\Models\Fee;
use App\Models\Role;
// RefreshDatabase removed - using DatabaseTransactions in base TestCase
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PerformanceIntegrationTest extends TestCase
{
    // RefreshDatabase removed - using DatabaseTransactions in base TestCase

    protected $admin;
    protected $performanceThresholds = [
        'database_query' => 0.1, // 100ms
        'api_response' => 1.0,   // 1 second
        'bulk_operation' => 5.0,  // 5 seconds
        'report_generation' => 3.0, // 3 seconds
        'cache_operation' => 0.05   // 50ms
    ];

    protected function setUp(): void
    {
        parent::setUp();
        
        $adminRole = Role::create(['name' => 'admin', 'display_name' => 'Administrator']);
        
        $this->admin = User::create([
            'name' => 'Performance Admin',
            'email' => 'perf.admin@test.com',
            'password' => bcrypt('password'),
            'role_id' => $adminRole->id,
            'is_active' => true
        ]);
    }

    /** @test */
    public function database_query_performance_is_acceptable()
    {
        $this->actingAs($this->admin);

        // Create test data
        $this->createLargeDataset(1000);

        // Test single student query performance
        $startTime = microtime(true);
        $student = Student::with(['attendances', 'results', 'fees'])->first();
        $queryTime = microtime(true) - $startTime;

        $this->assertLessThan(
            $this->performanceThresholds['database_query'],
            $queryTime,
            "Single student query took {$queryTime}s, exceeds threshold"
        );

        // Test paginated query performance
        $startTime = microtime(true);
        $students = Student::with(['attendances' => function($query) {
            $query->where('date', '>=', now()->subDays(30));
        }])->paginate(50);
        $paginationTime = microtime(true) - $startTime;

        $this->assertLessThan(
            $this->performanceThresholds['database_query'] * 2,
            $paginationTime,
            "Paginated query took {$paginationTime}s, exceeds threshold"
        );

        // Test complex aggregation query performance
        $startTime = microtime(true);
        $stats = DB::table('students')
            ->join('attendances', 'students.id', '=', 'attendances.student_id')
            ->select(
                'students.class_id',
                DB::raw('COUNT(DISTINCT students.id) as total_students'),
                DB::raw('COUNT(attendances.id) as total_attendance_records'),
                DB::raw('AVG(CASE WHEN attendances.status = "present" THEN 1 ELSE 0 END) * 100 as attendance_percentage')
            )
            ->groupBy('students.class_id')
            ->get();
        $aggregationTime = microtime(true) - $startTime;

        $this->assertLessThan(
            $this->performanceThresholds['database_query'] * 3,
            $aggregationTime,
            "Aggregation query took {$aggregationTime}s, exceeds threshold"
        );
    }

    /** @test */
    public function api_response_performance_is_acceptable()
    {
        $this->actingAs($this->admin);
        $this->createLargeDataset(500);

        // Test student list API performance
        $startTime = microtime(true);
        $response = $this->getJson('/api/students?per_page=50');
        $apiTime = microtime(true) - $startTime;

        $response->assertStatus(200);
        $this->assertLessThan(
            $this->performanceThresholds['api_response'],
            $apiTime,
            "Student list API took {$apiTime}s, exceeds threshold"
        );

        // Test search API performance
        $startTime = microtime(true);
        $response = $this->getJson('/api/students?search=Student&per_page=20');
        $searchTime = microtime(true) - $startTime;

        $response->assertStatus(200);
        $this->assertLessThan(
            $this->performanceThresholds['api_response'],
            $searchTime,
            "Search API took {$searchTime}s, exceeds threshold"
        );

        // Test attendance report API performance
        $startTime = microtime(true);
        $response = $this->getJson('/api/attendance/report?class_id=1&month=' . now()->format('Y-m'));
        $reportTime = microtime(true) - $startTime;

        $response->assertStatus(200);
        $this->assertLessThan(
            $this->performanceThresholds['report_generation'],
            $reportTime,
            "Attendance report API took {$reportTime}s, exceeds threshold"
        );
    }

    /** @test */
    public function bulk_operations_performance_is_acceptable()
    {
        $this->actingAs($this->admin);

        // Test bulk student creation performance
        $studentsData = [];
        for ($i = 1; $i <= 100; $i++) {
            $studentsData[] = [
                'name' => "Bulk Student {$i}",
                'email' => "bulk{$i}@test.com",
                'class_id' => ($i % 3) + 1,
                'section' => chr(65 + ($i % 3)),
                'roll_number' => str_pad($i, 3, '0', STR_PAD_LEFT),
                'admission_no' => "BULK{$i}",
                'father_name' => "Father {$i}",
                'mother_name' => "Mother {$i}",
                'address' => "Address {$i}",
                'date_of_birth' => '2010-01-01',
                'gender' => $i % 2 == 0 ? 'female' : 'male',
                'admission_date' => now()->format('Y-m-d'),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        $startTime = microtime(true);
        Student::insert($studentsData);
        $bulkInsertTime = microtime(true) - $startTime;

        $this->assertLessThan(
            $this->performanceThresholds['bulk_operation'],
            $bulkInsertTime,
            "Bulk student insert took {$bulkInsertTime}s, exceeds threshold"
        );

        // Test bulk attendance marking performance
        $students = Student::limit(100)->get();
        $attendanceData = [];
        
        foreach ($students as $student) {
            $attendanceData[] = [
                'student_id' => $student->id,
                'date' => now()->format('Y-m-d'),
                'status' => 'present',
                'class_id' => $student->class_id,
                'marked_by' => $this->admin->id,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        $startTime = microtime(true);
        Attendance::insert($attendanceData);
        $bulkAttendanceTime = microtime(true) - $startTime;

        $this->assertLessThan(
            $this->performanceThresholds['bulk_operation'],
            $bulkAttendanceTime,
            "Bulk attendance insert took {$bulkAttendanceTime}s, exceeds threshold"
        );

        // Test bulk update performance
        $startTime = microtime(true);
        Student::where('class_id', 1)->update(['section' => 'Updated']);
        $bulkUpdateTime = microtime(true) - $startTime;

        $this->assertLessThan(
            $this->performanceThresholds['bulk_operation'],
            $bulkUpdateTime,
            "Bulk update took {$bulkUpdateTime}s, exceeds threshold"
        );
    }

    /** @test */
    public function cache_performance_is_acceptable()
    {
        $this->actingAs($this->admin);
        Cache::flush();

        // Test cache write performance
        $testData = ['key' => 'value', 'number' => 12345, 'array' => [1, 2, 3, 4, 5]];
        
        $startTime = microtime(true);
        Cache::put('performance_test', $testData, 3600);
        $cacheWriteTime = microtime(true) - $startTime;

        $this->assertLessThan(
            $this->performanceThresholds['cache_operation'],
            $cacheWriteTime,
            "Cache write took {$cacheWriteTime}s, exceeds threshold"
        );

        // Test cache read performance
        $startTime = microtime(true);
        $cachedData = Cache::get('performance_test');
        $cacheReadTime = microtime(true) - $startTime;

        $this->assertLessThan(
            $this->performanceThresholds['cache_operation'],
            $cacheReadTime,
            "Cache read took {$cacheReadTime}s, exceeds threshold"
        );

        $this->assertEquals($testData, $cachedData);

        // Test cache with large dataset
        $largeData = [];
        for ($i = 0; $i < 1000; $i++) {
            $largeData[] = [
                'id' => $i,
                'name' => "Item {$i}",
                'data' => str_repeat('x', 100)
            ];
        }

        $startTime = microtime(true);
        Cache::put('large_dataset', $largeData, 3600);
        $largeCacheWriteTime = microtime(true) - $startTime;

        $this->assertLessThan(
            $this->performanceThresholds['cache_operation'] * 10,
            $largeCacheWriteTime,
            "Large cache write took {$largeCacheWriteTime}s, exceeds threshold"
        );

        $startTime = microtime(true);
        $retrievedLargeData = Cache::get('large_dataset');
        $largeCacheReadTime = microtime(true) - $startTime;

        $this->assertLessThan(
            $this->performanceThresholds['cache_operation'] * 5,
            $largeCacheReadTime,
            "Large cache read took {$largeCacheReadTime}s, exceeds threshold"
        );
    }

    /** @test */
    public function report_generation_performance_is_acceptable()
    {
        $this->actingAs($this->admin);
        $this->createLargeDataset(200);

        // Test attendance report performance
        $startTime = microtime(true);
        $response = $this->get('/admin/reports/attendance?class_id=1&month=' . now()->format('Y-m'));
        $attendanceReportTime = microtime(true) - $startTime;

        $response->assertStatus(200);
        $this->assertLessThan(
            $this->performanceThresholds['report_generation'],
            $attendanceReportTime,
            "Attendance report took {$attendanceReportTime}s, exceeds threshold"
        );

        // Test exam results report performance
        $exam = Exam::create([
            'name' => 'Performance Test Exam',
            'class_id' => 1,
            'subject' => 'Mathematics',
            'exam_date' => now()->format('Y-m-d'),
            'total_marks' => 100,
            'passing_marks' => 35
        ]);

        // Create exam results
        $students = Student::where('class_id', 1)->limit(50)->get();
        foreach ($students as $student) {
            Result::create([
                'exam_id' => $exam->id,
                'student_id' => $student->id,
                'marks_obtained' => rand(35, 95),
                'grade' => 'A',
                'percentage' => rand(35, 95)
            ]);
        }

        $startTime = microtime(true);
        $response = $this->get("/admin/reports/exam-results/{$exam->id}");
        $examReportTime = microtime(true) - $startTime;

        $response->assertStatus(200);
        $this->assertLessThan(
            $this->performanceThresholds['report_generation'],
            $examReportTime,
            "Exam results report took {$examReportTime}s, exceeds threshold"
        );

        // Test comprehensive report performance
        $startTime = microtime(true);
        $response = $this->get('/admin/reports/comprehensive?class_id=1&academic_year=' . now()->format('Y'));
        $comprehensiveReportTime = microtime(true) - $startTime;

        $response->assertStatus(200);
        $this->assertLessThan(
            $this->performanceThresholds['report_generation'] * 2,
            $comprehensiveReportTime,
            "Comprehensive report took {$comprehensiveReportTime}s, exceeds threshold"
        );
    }

    /** @test */
    public function concurrent_operations_performance_is_acceptable()
    {
        $this->actingAs($this->admin);
        $this->createLargeDataset(100);

        // Simulate concurrent read operations
        $startTime = microtime(true);
        
        $responses = [];
        for ($i = 0; $i < 10; $i++) {
            $responses[] = $this->getJson('/api/students?per_page=10&page=' . ($i + 1));
        }
        
        $concurrentReadTime = microtime(true) - $startTime;

        foreach ($responses as $response) {
            $response->assertStatus(200);
        }

        $this->assertLessThan(
            $this->performanceThresholds['api_response'] * 3,
            $concurrentReadTime,
            "Concurrent read operations took {$concurrentReadTime}s, exceeds threshold"
        );

        // Test concurrent write operations (attendance marking)
        $students = Student::limit(20)->get();
        $startTime = microtime(true);

        foreach ($students as $student) {
            $this->postJson('/api/attendance', [
                'student_id' => $student->id,
                'date' => now()->format('Y-m-d'),
                'status' => 'present',
                'class_id' => $student->class_id
            ]);
        }

        $concurrentWriteTime = microtime(true) - $startTime;

        $this->assertLessThan(
            $this->performanceThresholds['bulk_operation'],
            $concurrentWriteTime,
            "Concurrent write operations took {$concurrentWriteTime}s, exceeds threshold"
        );
    }

    /** @test */
    public function memory_usage_is_within_acceptable_limits()
    {
        $this->actingAs($this->admin);
        
        $initialMemory = memory_get_usage(true);
        
        // Create and process large dataset
        $this->createLargeDataset(500);
        
        // Perform memory-intensive operations
        $students = Student::with(['attendances', 'results'])->get();
        $processedData = $students->map(function ($student) {
            return [
                'id' => $student->id,
                'name' => $student->name,
                'attendance_count' => $student->attendances->count(),
                'result_count' => $student->results->count(),
                'attendance_percentage' => $this->calculateAttendancePercentage($student)
            ];
        });
        
        $peakMemory = memory_get_peak_usage(true);
        $memoryUsed = $peakMemory - $initialMemory;
        
        // Memory usage should not exceed 128MB for this operation
        $maxMemoryLimit = 128 * 1024 * 1024; // 128MB
        
        $this->assertLessThan(
            $maxMemoryLimit,
            $memoryUsed,
            "Memory usage of " . ($memoryUsed / 1024 / 1024) . "MB exceeds limit"
        );
    }

    /** @test */
    public function database_connection_pool_performance_is_acceptable()
    {
        $this->actingAs($this->admin);
        
        // Test multiple database connections
        $startTime = microtime(true);
        
        for ($i = 0; $i < 50; $i++) {
            DB::table('students')->count();
        }
        
        $connectionPoolTime = microtime(true) - $startTime;
        
        $this->assertLessThan(
            $this->performanceThresholds['database_query'] * 10,
            $connectionPoolTime,
            "Database connection pool operations took {$connectionPoolTime}s, exceeds threshold"
        );
    }

    /** @test */
    public function file_operations_performance_is_acceptable()
    {
        $this->actingAs($this->admin);
        
        // Test CSV export performance
        $this->createLargeDataset(200);
        
        $startTime = microtime(true);
        $response = $this->get('/admin/students/export?format=csv&class_id=1');
        $exportTime = microtime(true) - $startTime;
        
        $this->assertLessThan(
            $this->performanceThresholds['report_generation'] * 2,
            $exportTime,
            "CSV export took {$exportTime}s, exceeds threshold"
        );
        
        // Test PDF generation performance
        $startTime = microtime(true);
        $response = $this->get('/admin/reports/student-list?format=pdf&class_id=1');
        $pdfTime = microtime(true) - $startTime;
        
        $this->assertLessThan(
            $this->performanceThresholds['report_generation'] * 3,
            $pdfTime,
            "PDF generation took {$pdfTime}s, exceeds threshold"
        );
    }

    private function createLargeDataset($count)
    {
        $students = [];
        $attendances = [];
        
        for ($i = 1; $i <= $count; $i++) {
            $students[] = [
                'name' => "Performance Student {$i}",
                'email' => "perf{$i}@test.com",
                'class_id' => ($i % 5) + 1,
                'section' => chr(65 + ($i % 3)),
                'roll_number' => str_pad($i, 4, '0', STR_PAD_LEFT),
                'admission_no' => "PERF{$i}",
                'father_name' => "Father {$i}",
                'mother_name' => "Mother {$i}",
                'address' => "Address {$i}",
                'date_of_birth' => Carbon::now()->subYears(rand(6, 18))->format('Y-m-d'),
                'gender' => $i % 2 == 0 ? 'female' : 'male',
                'admission_date' => Carbon::now()->subMonths(rand(1, 12))->format('Y-m-d'),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }
        
        // Insert in chunks for better performance
        $chunks = array_chunk($students, 100);
        foreach ($chunks as $chunk) {
            Student::insert($chunk);
        }
        
        // Create attendance records
        $studentIds = Student::pluck('id')->toArray();
        for ($day = 0; $day < 30; $day++) {
            $date = Carbon::now()->subDays($day)->format('Y-m-d');
            foreach (array_slice($studentIds, 0, min(50, count($studentIds))) as $studentId) {
                $attendances[] = [
                    'student_id' => $studentId,
                    'date' => $date,
                    'status' => rand(1, 10) > 2 ? 'present' : 'absent', // 80% attendance
                    'class_id' => rand(1, 5),
                    'marked_by' => $this->admin->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
        }
        
        // Insert attendance in chunks
        $attendanceChunks = array_chunk($attendances, 500);
        foreach ($attendanceChunks as $chunk) {
            Attendance::insert($chunk);
        }
    }

    private function calculateAttendancePercentage($student)
    {
        $totalDays = $student->attendances->count();
        if ($totalDays == 0) return 0;
        
        $presentDays = $student->attendances->where('status', 'present')->count();
        return round(($presentDays / $totalDays) * 100, 2);
    }
}