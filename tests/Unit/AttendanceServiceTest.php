<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AttendanceService;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Classes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Carbon\Carbon;

class AttendanceServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $attendanceService;
    protected $student;
    protected $class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->attendanceService = new AttendanceService();
        
        // Create test data
        $this->class = Classes::factory()->create([
            'name' => 'Test Class',
            'section' => 'A'
        ]);
        
        $this->student = Student::factory()->create([
            'class_id' => $this->class->id,
            'name' => 'Test Student',
            'admission_no' => 'TEST001'
        ]);
    }

    /** @test */
    public function it_marks_attendance_successfully()
    {
        // Arrange
        $date = Carbon::today();
        $attendanceData = [
            'student_id' => $this->student->id,
            'date' => $date->format('Y-m-d'),
            'status' => 'present',
            'remarks' => 'On time'
        ];

        // Act
        $result = $this->attendanceService->markAttendance($attendanceData);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('attendances', [
            'student_id' => $this->student->id,
            'date' => $date->format('Y-m-d'),
            'status' => 'present'
        ]);
    }

    /** @test */
    public function it_prevents_duplicate_attendance_for_same_date()
    {
        // Arrange
        $date = Carbon::today();
        
        // Create existing attendance
        Attendance::create([
            'student_id' => $this->student->id,
            'date' => $date,
            'status' => 'present',
            'class_id' => $this->class->id
        ]);

        $attendanceData = [
            'student_id' => $this->student->id,
            'date' => $date->format('Y-m-d'),
            'status' => 'absent',
            'remarks' => 'Late arrival'
        ];

        // Act
        $result = $this->attendanceService->markAttendance($attendanceData);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('already marked', $result['message']);
        
        // Verify original attendance is unchanged
        $this->assertDatabaseHas('attendances', [
            'student_id' => $this->student->id,
            'date' => $date->format('Y-m-d'),
            'status' => 'present'
        ]);
    }

    /** @test */
    public function it_updates_existing_attendance()
    {
        // Arrange
        $date = Carbon::today();
        
        $attendance = Attendance::create([
            'student_id' => $this->student->id,
            'date' => $date,
            'status' => 'present',
            'class_id' => $this->class->id
        ]);

        $updateData = [
            'status' => 'absent',
            'remarks' => 'Updated to absent'
        ];

        // Act
        $result = $this->attendanceService->updateAttendance($attendance->id, $updateData);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => 'absent',
            'remarks' => 'Updated to absent'
        ]);
    }

    /** @test */
    public function it_calculates_attendance_percentage_correctly()
    {
        // Arrange
        $startDate = Carbon::now()->subDays(9);
        $endDate = Carbon::today();

        // Create 10 days of attendance (7 present, 3 absent)
        for ($i = 0; $i < 10; $i++) {
            $date = $startDate->copy()->addDays($i);
            $status = $i < 7 ? 'present' : 'absent';
            
            Attendance::create([
                'student_id' => $this->student->id,
                'date' => $date,
                'status' => $status,
                'class_id' => $this->class->id
            ]);
        }

        // Act
        $percentage = $this->attendanceService->calculateAttendancePercentage(
            $this->student->id,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );

        // Assert
        $this->assertEquals(70.0, $percentage); // 7/10 * 100 = 70%
    }

    /** @test */
    public function it_gets_class_attendance_for_date()
    {
        // Arrange
        $date = Carbon::today();
        
        // Create multiple students in the same class
        $students = Student::factory()->count(3)->create([
            'class_id' => $this->class->id
        ]);

        // Mark attendance for all students
        foreach ($students as $index => $student) {
            Attendance::create([
                'student_id' => $student->id,
                'date' => $date,
                'status' => $index < 2 ? 'present' : 'absent',
                'class_id' => $this->class->id
            ]);
        }

        // Act
        $result = $this->attendanceService->getClassAttendance(
            $this->class->id,
            $date->format('Y-m-d')
        );

        // Assert
        $this->assertCount(3, $result['attendance']);
        $this->assertEquals(2, $result['summary']['present']);
        $this->assertEquals(1, $result['summary']['absent']);
        $this->assertEquals(66.67, round($result['summary']['percentage'], 2));
    }

    /** @test */
    public function it_generates_monthly_attendance_report()
    {
        // Arrange
        $month = Carbon::now()->month;
        $year = Carbon::now()->year;
        
        // Create attendance for different days in the month
        $daysInMonth = Carbon::create($year, $month)->daysInMonth;
        $presentDays = 20;
        
        for ($day = 1; $day <= $presentDays; $day++) {
            $date = Carbon::create($year, $month, $day);
            
            Attendance::create([
                'student_id' => $this->student->id,
                'date' => $date,
                'status' => 'present',
                'class_id' => $this->class->id
            ]);
        }

        // Act
        $report = $this->attendanceService->generateMonthlyReport(
            $this->student->id,
            $month,
            $year
        );

        // Assert
        $this->assertEquals($presentDays, $report['total_present']);
        $this->assertEquals(0, $report['total_absent']);
        $this->assertEquals($presentDays, $report['total_days']);
        $this->assertEquals(100.0, $report['percentage']);
    }

    /** @test */
    public function it_handles_bulk_attendance_marking()
    {
        // Arrange
        $date = Carbon::today();
        $students = Student::factory()->count(5)->create([
            'class_id' => $this->class->id
        ]);

        $bulkData = [];
        foreach ($students as $index => $student) {
            $bulkData[] = [
                'student_id' => $student->id,
                'date' => $date->format('Y-m-d'),
                'status' => $index % 2 === 0 ? 'present' : 'absent',
                'class_id' => $this->class->id
            ];
        }

        // Act
        $result = $this->attendanceService->markBulkAttendance($bulkData);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(5, $result['processed']);
        $this->assertEquals(0, $result['failed']);
        
        // Verify all records are created
        foreach ($students as $student) {
            $this->assertDatabaseHas('attendances', [
                'student_id' => $student->id,
                'date' => $date->format('Y-m-d')
            ]);
        }
    }

    /** @test */
    public function it_identifies_students_with_low_attendance()
    {
        // Arrange
        $threshold = 75.0;
        $students = Student::factory()->count(3)->create([
            'class_id' => $this->class->id
        ]);

        // Create attendance patterns
        $attendancePatterns = [
            ['present' => 5, 'absent' => 5], // 50% - below threshold
            ['present' => 8, 'absent' => 2], // 80% - above threshold
            ['present' => 6, 'absent' => 4], // 60% - below threshold
        ];

        foreach ($students as $index => $student) {
            $pattern = $attendancePatterns[$index];
            
            // Create present days
            for ($i = 0; $i < $pattern['present']; $i++) {
                Attendance::create([
                    'student_id' => $student->id,
                    'date' => Carbon::now()->subDays($i),
                    'status' => 'present',
                    'class_id' => $this->class->id
                ]);
            }
            
            // Create absent days
            for ($i = 0; $i < $pattern['absent']; $i++) {
                Attendance::create([
                    'student_id' => $student->id,
                    'date' => Carbon::now()->subDays($pattern['present'] + $i),
                    'status' => 'absent',
                    'class_id' => $this->class->id
                ]);
            }
        }

        // Act
        $lowAttendanceStudents = $this->attendanceService->getStudentsWithLowAttendance(
            $this->class->id,
            $threshold
        );

        // Assert
        $this->assertCount(2, $lowAttendanceStudents); // Students with 50% and 60%
        
        foreach ($lowAttendanceStudents as $studentData) {
            $this->assertLessThan($threshold, $studentData['percentage']);
        }
    }

    /** @test */
    public function it_validates_attendance_date_range()
    {
        // Test future date
        $futureDate = Carbon::tomorrow();
        $result = $this->attendanceService->validateAttendanceDate($futureDate->format('Y-m-d'));
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('future', $result['message']);

        // Test valid date (today)
        $today = Carbon::today();
        $result = $this->attendanceService->validateAttendanceDate($today->format('Y-m-d'));
        $this->assertTrue($result['valid']);

        // Test valid date (past)
        $pastDate = Carbon::yesterday();
        $result = $this->attendanceService->validateAttendanceDate($pastDate->format('Y-m-d'));
        $this->assertTrue($result['valid']);
    }

    /** @test */
    public function it_handles_attendance_status_validation()
    {
        $validStatuses = ['present', 'absent', 'late', 'excused'];
        $invalidStatuses = ['invalid', 'unknown', ''];

        foreach ($validStatuses as $status) {
            $this->assertTrue(
                $this->attendanceService->isValidAttendanceStatus($status),
                "Status '{$status}' should be valid"
            );
        }

        foreach ($invalidStatuses as $status) {
            $this->assertFalse(
                $this->attendanceService->isValidAttendanceStatus($status),
                "Status '{$status}' should be invalid"
            );
        }
    }

    /** @test */
    public function it_generates_attendance_summary_statistics()
    {
        // Arrange
        $startDate = Carbon::now()->subDays(29); // 30 days
        $endDate = Carbon::today();

        // Create varied attendance pattern
        $attendanceData = [
            'present' => 20,
            'absent' => 5,
            'late' => 3,
            'excused' => 2
        ];

        $dayCounter = 0;
        foreach ($attendanceData as $status => $count) {
            for ($i = 0; $i < $count; $i++) {
                Attendance::create([
                    'student_id' => $this->student->id,
                    'date' => $startDate->copy()->addDays($dayCounter),
                    'status' => $status,
                    'class_id' => $this->class->id
                ]);
                $dayCounter++;
            }
        }

        // Act
        $summary = $this->attendanceService->getAttendanceSummary(
            $this->student->id,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );

        // Assert
        $this->assertEquals(20, $summary['present']);
        $this->assertEquals(5, $summary['absent']);
        $this->assertEquals(3, $summary['late']);
        $this->assertEquals(2, $summary['excused']);
        $this->assertEquals(30, $summary['total_days']);
        $this->assertEquals(66.67, round($summary['attendance_percentage'], 2)); // 20/30 * 100
    }
}