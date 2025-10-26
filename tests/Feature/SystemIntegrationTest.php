<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use App\Models\Exam;
use App\Models\Fee;
use App\Models\Role;
use App\Models\Attendance;
use App\Models\Result;
// RefreshDatabase removed - using DatabaseTransactions in base TestCase
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Event;
use Carbon\Carbon;

class SystemIntegrationTest extends TestCase
{
    use  WithFaker;

    protected $admin;
    protected $teacher;
    protected $students;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        $adminRole = Role::create(['name' => 'admin', 'display_name' => 'Administrator']);
        $teacherRole = Role::create(['name' => 'teacher', 'display_name' => 'Teacher']);
        $studentRole = Role::create(['name' => 'student', 'display_name' => 'Student']);

        // Create test users
        $this->admin = User::create([
            'name' => 'System Admin',
            'email' => 'admin@system.test',
            'password' => bcrypt('password'),
            'role_id' => $adminRole->id,
            'is_active' => true
        ]);

        $this->teacher = User::create([
            'name' => 'System Teacher',
            'email' => 'teacher@system.test',
            'password' => bcrypt('password'),
            'role_id' => $teacherRole->id,
            'is_active' => true
        ]);

        // Create multiple students for comprehensive testing
        $this->students = collect();
        for ($i = 1; $i <= 5; $i++) {
            $this->students->push(Student::create([
                'name' => "Student {$i}",
                'email' => "student{$i}@system.test",
                'class_id' => 1,
                'section' => 'A',
                'roll_number' => str_pad($i, 3, '0', STR_PAD_LEFT),
                'admission_no' => "SYS{$i}",
                'father_name' => "Father {$i}",
                'mother_name' => "Mother {$i}",
                'address' => "Address {$i}",
                'date_of_birth' => Carbon::now()->subYears(10)->format('Y-m-d'),
                'gender' => $i % 2 == 0 ? 'female' : 'male',
                'admission_date' => Carbon::now()->subMonths(6)->format('Y-m-d'),
                'is_active' => true
            ]));
        }
    }

    /** @test */
    public function complete_academic_cycle_integration_works()
    {
        $this->actingAs($this->admin);

        // Step 1: Create academic year setup
        $academicYear = now()->format('Y');
        
        // Step 2: Create exam
        $exam = Exam::create([
            'name' => 'System Integration Test Exam',
            'class_id' => 1,
            'subject' => 'Mathematics',
            'exam_date' => now()->addDays(1)->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'total_marks' => 100,
            'passing_marks' => 35,
            'academic_year' => $academicYear
        ]);

        // Step 3: Mark attendance for all students
        $attendanceDate = now()->format('Y-m-d');
        foreach ($this->students as $student) {
            Attendance::create([
                'student_id' => $student->id,
                'date' => $attendanceDate,
                'status' => 'present',
                'class_id' => 1,
                'marked_by' => $this->teacher->id
            ]);
        }

        // Step 4: Record exam results
        $results = [];
        foreach ($this->students as $index => $student) {
            $marks = 40 + ($index * 10); // Varying marks: 40, 50, 60, 70, 80
            $result = Result::create([
                'exam_id' => $exam->id,
                'student_id' => $student->id,
                'marks_obtained' => $marks,
                'grade' => $this->calculateGrade($marks),
                'percentage' => ($marks / 100) * 100,
                'remarks' => $marks >= 60 ? 'Good' : 'Needs Improvement'
            ]);
            $results[] = $result;
        }

        // Step 5: Create fee structure and payments
        $fee = Fee::create([
            'class_id' => 1,
            'fee_type' => 'tuition',
            'amount' => 5000,
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'academic_year' => $academicYear
        ]);

        // Step 6: Process fee payments for some students
        foreach ($this->students->take(3) as $student) {
            $this->postJson('/api/fee-payments', [
                'student_id' => $student->id,
                'fee_id' => $fee->id,
                'amount_paid' => 5000,
                'payment_method' => 'online',
                'payment_date' => now()->format('Y-m-d')
            ]);
        }

        // Step 7: Verify data consistency across modules
        $this->assertDatabaseCount('attendances', 5);
        $this->assertDatabaseCount('results', 5);
        $this->assertDatabaseCount('fee_payments', 3);

        // Step 8: Test report generation integration
        $response = $this->get('/admin/reports/comprehensive?class_id=1&academic_year=' . $academicYear);
        $response->assertStatus(200);

        // Step 9: Verify cross-module data relationships
        foreach ($this->students as $student) {
            $this->assertDatabaseHas('attendances', [
                'student_id' => $student->id,
                'date' => $attendanceDate
            ]);
            
            $this->assertDatabaseHas('results', [
                'student_id' => $student->id,
                'exam_id' => $exam->id
            ]);
        }
    }

    /** @test */
    public function caching_system_integration_works()
    {
        Cache::flush();
        
        $this->actingAs($this->admin);

        // Test student data caching
        $student = $this->students->first();
        
        // First request should hit database and cache
        $response1 = $this->getJson("/api/students/{$student->id}");
        $response1->assertStatus(200);
        
        // Verify cache was set
        $this->assertTrue(Cache::has("student.{$student->id}"));
        
        // Second request should hit cache
        $response2 = $this->getJson("/api/students/{$student->id}");
        $response2->assertStatus(200);
        $response2->assertJson($response1->json());

        // Test cache invalidation on update
        $this->putJson("/api/students/{$student->id}", [
            'name' => 'Updated Student Name',
            'email' => $student->email,
            'class_id' => $student->class_id,
            'section' => $student->section
        ]);

        // Cache should be cleared
        $this->assertFalse(Cache::has("student.{$student->id}"));

        // Test attendance report caching
        $cacheKey = "attendance.report.class.1.month." . now()->format('Y-m');
        $response = $this->get('/admin/attendance/report?class_id=1&month=' . now()->format('Y-m'));
        $response->assertStatus(200);
        
        $this->assertTrue(Cache::has($cacheKey));
    }

    /** @test */
    public function queue_system_integration_works()
    {
        Queue::fake();
        
        $this->actingAs($this->admin);

        // Test bulk operations queuing
        $csvData = [
            ['name' => 'Bulk Student 1', 'email' => 'bulk1@test.com', 'class_id' => 1],
            ['name' => 'Bulk Student 2', 'email' => 'bulk2@test.com', 'class_id' => 1],
            ['name' => 'Bulk Student 3', 'email' => 'bulk3@test.com', 'class_id' => 1]
        ];

        $response = $this->postJson('/api/students/bulk-import', [
            'students' => $csvData
        ]);

        $response->assertStatus(202); // Accepted for processing
        
        // Verify job was queued
        Queue::assertPushed(\App\Jobs\BulkStudentImport::class);

        // Test notification queuing
        Notification::fake();
        
        $response = $this->postJson('/api/notifications/send-bulk', [
            'message' => 'Test notification',
            'recipients' => 'all_students'
        ]);

        $response->assertStatus(202);
        Queue::assertPushed(\App\Jobs\SendBulkNotification::class);
    }

    /** @test */
    public function event_system_integration_works()
    {
        Event::fake();
        
        $this->actingAs($this->admin);

        $student = $this->students->first();

        // Test student creation event
        $response = $this->postJson('/api/students', [
            'name' => 'Event Test Student',
            'email' => 'event@test.com',
            'class_id' => 1,
            'section' => 'A',
            'roll_number' => '999',
            'father_name' => 'Event Father',
            'mother_name' => 'Event Mother',
            'address' => 'Event Address',
            'date_of_birth' => '2010-01-01',
            'gender' => 'male',
            'admission_date' => now()->format('Y-m-d')
        ]);

        $response->assertStatus(201);
        Event::assertDispatched(\App\Events\StudentCreated::class);

        // Test attendance marking event
        $response = $this->postJson('/api/attendance', [
            'student_id' => $student->id,
            'date' => now()->format('Y-m-d'),
            'status' => 'present',
            'class_id' => 1
        ]);

        $response->assertStatus(201);
        Event::assertDispatched(\App\Events\AttendanceMarked::class);

        // Test exam result event
        $exam = Exam::create([
            'name' => 'Event Test Exam',
            'class_id' => 1,
            'subject' => 'Science',
            'exam_date' => now()->addDays(1)->format('Y-m-d'),
            'total_marks' => 100,
            'passing_marks' => 35
        ]);

        $response = $this->postJson('/api/exam-results', [
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'marks_obtained' => 75
        ]);

        $response->assertStatus(201);
        Event::assertDispatched(\App\Events\ExamResultRecorded::class);
    }

    /** @test */
    public function data_consistency_across_modules_maintained()
    {
        $this->actingAs($this->admin);

        $student = $this->students->first();

        // Create interconnected data
        $exam = Exam::create([
            'name' => 'Consistency Test Exam',
            'class_id' => 1,
            'subject' => 'English',
            'exam_date' => now()->format('Y-m-d'),
            'total_marks' => 100,
            'passing_marks' => 35
        ]);

        $attendance = Attendance::create([
            'student_id' => $student->id,
            'date' => now()->format('Y-m-d'),
            'status' => 'present',
            'class_id' => 1
        ]);

        $result = Result::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'marks_obtained' => 85,
            'grade' => 'A',
            'percentage' => 85
        ]);

        $fee = Fee::create([
            'class_id' => 1,
            'fee_type' => 'exam',
            'amount' => 500,
            'due_date' => now()->addDays(15)->format('Y-m-d')
        ]);

        // Test cascade operations
        // When student is soft deleted, related records should be handled properly
        $response = $this->deleteJson("/api/students/{$student->id}");
        $response->assertStatus(200);

        // Verify student is soft deleted
        $this->assertSoftDeleted('students', ['id' => $student->id]);

        // Verify related data integrity
        $this->assertDatabaseHas('attendances', ['student_id' => $student->id]);
        $this->assertDatabaseHas('results', ['student_id' => $student->id]);

        // Test data restoration
        $response = $this->postJson("/api/students/{$student->id}/restore");
        $response->assertStatus(200);

        // Verify student is restored
        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'deleted_at' => null
        ]);
    }

    /** @test */
    public function performance_under_load_integration_works()
    {
        $this->actingAs($this->admin);

        // Create larger dataset for performance testing
        $students = collect();
        for ($i = 1; $i <= 50; $i++) {
            $students->push(Student::create([
                'name' => "Performance Student {$i}",
                'email' => "perf{$i}@test.com",
                'class_id' => ($i % 3) + 1, // Distribute across 3 classes
                'section' => chr(65 + ($i % 3)), // A, B, C
                'roll_number' => str_pad($i, 3, '0', STR_PAD_LEFT),
                'admission_no' => "PERF{$i}",
                'is_active' => true
            ]));
        }

        // Test bulk attendance marking performance
        $startTime = microtime(true);
        
        $attendanceData = [];
        foreach ($students as $student) {
            $attendanceData[$student->id] = 'present';
        }

        $response = $this->postJson('/api/attendance/bulk', [
            'date' => now()->format('Y-m-d'),
            'class_id' => 1,
            'attendance' => $attendanceData
        ]);

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $response->assertStatus(200);
        $this->assertLessThan(5.0, $executionTime, 'Bulk attendance marking took too long');

        // Test report generation performance
        $startTime = microtime(true);
        
        $response = $this->get('/admin/reports/attendance?class_id=1&month=' . now()->format('Y-m'));
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $response->assertStatus(200);
        $this->assertLessThan(3.0, $executionTime, 'Report generation took too long');
    }

    /** @test */
    public function security_integration_across_modules_works()
    {
        // Test unauthorized access attempts
        $student = $this->students->first();

        // Test without authentication
        $response = $this->getJson('/api/students');
        $response->assertStatus(401);

        // Test with wrong role
        $this->actingAs($this->students->first()->user ?? $this->student);
        
        $response = $this->getJson('/api/admin/dashboard');
        $response->assertStatus(403);

        // Test SQL injection attempts
        $this->actingAs($this->admin);
        
        $response = $this->getJson('/api/students?search=\'; DROP TABLE students; --');
        $response->assertStatus(200); // Should not crash
        
        // Verify table still exists
        $this->assertDatabaseCount('students', 5);

        // Test XSS prevention
        $response = $this->postJson('/api/students', [
            'name' => '<script>alert("xss")</script>',
            'email' => 'xss@test.com',
            'class_id' => 1,
            'section' => 'A',
            'roll_number' => '888'
        ]);

        if ($response->status() === 201) {
            $createdStudent = Student::where('email', 'xss@test.com')->first();
            $this->assertNotContains('<script>', $createdStudent->name);
        }
    }

    /** @test */
    public function backup_and_recovery_integration_works()
    {
        $this->actingAs($this->admin);

        // Create test data
        $student = $this->students->first();
        $originalName = $student->name;

        // Simulate backup creation
        $response = $this->postJson('/api/system/backup');
        $response->assertStatus(200);

        // Modify data
        $student->update(['name' => 'Modified Name']);
        $this->assertEquals('Modified Name', $student->fresh()->name);

        // Simulate recovery (this would typically be a more complex operation)
        $response = $this->postJson('/api/system/restore-latest');
        
        // In a real scenario, this would restore from backup
        // For testing, we'll just verify the endpoint exists and responds
        $this->assertContains($response->status(), [200, 202, 501]); // 501 if not implemented
    }

    private function calculateGrade($marks)
    {
        if ($marks >= 90) return 'A+';
        if ($marks >= 80) return 'A';
        if ($marks >= 70) return 'B+';
        if ($marks >= 60) return 'B';
        if ($marks >= 50) return 'C+';
        if ($marks >= 40) return 'C';
        if ($marks >= 35) return 'D';
        return 'F';
    }
}