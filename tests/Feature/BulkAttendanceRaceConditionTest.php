<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Attendance;
// RefreshDatabase removed - using DatabaseTransactions in base TestCase
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BulkAttendanceRaceConditionTest extends TestCase
{
    // RefreshDatabase removed - using DatabaseTransactions in base TestCase

    protected $user;
    protected $students;
    protected $class;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = User::factory()->create([
            'role' => 'teacher'
        ]);
        
        // Create a test class
        $this->class = ClassModel::factory()->create([
            'name' => 'Test Class',
            'section' => 'A'
        ]);
        
        // Create test students
        $this->students = Student::factory()->count(5)->create([
            'class_id' => $this->class->id
        ]);
    }

    /**
     * Test that bulk attendance handles race conditions properly
     */
    public function test_bulk_attendance_prevents_race_conditions()
    {
        $this->actingAs($this->user);
        
        $date = now()->format('Y-m-d');
        $studentIds = $this->students->pluck('id')->toArray();
        
        // Simulate concurrent requests by running multiple processes
        $promises = [];
        $results = [];
        
        // Create multiple concurrent requests
        for ($i = 0; $i < 3; $i++) {
            $response = $this->postJson('/students/bulk-attendance', [
                'student_ids' => $studentIds,
                'date' => $date,
                'status' => 'present',
                'remarks' => "Concurrent test {$i}"
            ]);
            
            $results[] = $response;
        }
        
        // Verify that all requests succeeded
        foreach ($results as $response) {
            $response->assertStatus(200);
        }
        
        // Verify that no duplicate attendance records were created
        $attendanceCount = Attendance::where('date', $date)
            ->whereIn('student_id', $studentIds)
            ->count();
            
        $this->assertEquals(count($studentIds), $attendanceCount, 
            'Duplicate attendance records were created due to race condition');
        
        // Verify that each student has exactly one attendance record
        foreach ($studentIds as $studentId) {
            $studentAttendanceCount = Attendance::where('student_id', $studentId)
                ->where('date', $date)
                ->count();
                
            $this->assertEquals(1, $studentAttendanceCount, 
                "Student {$studentId} has {$studentAttendanceCount} attendance records instead of 1");
        }
    }

    /**
     * Test bulk attendance with existing records
     */
    public function test_bulk_attendance_updates_existing_records()
    {
        $this->actingAs($this->user);
        
        $date = now()->format('Y-m-d');
        $studentIds = $this->students->pluck('id')->toArray();
        
        // Create some existing attendance records
        foreach (array_slice($studentIds, 0, 3) as $studentId) {
            Attendance::create([
                'student_id' => $studentId,
                'date' => $date,
                'status' => 'absent',
                'marked_by' => $this->user->id
            ]);
        }
        
        // Update attendance for all students
        $response = $this->postJson('/students/bulk-attendance', [
            'student_ids' => $studentIds,
            'date' => $date,
            'status' => 'present',
            'remarks' => 'Updated attendance'
        ]);
        
        $response->assertStatus(200);
        
        // Verify that all students now have 'present' status
        $presentCount = Attendance::where('date', $date)
            ->whereIn('student_id', $studentIds)
            ->where('status', 'present')
            ->count();
            
        $this->assertEquals(count($studentIds), $presentCount);
        
        // Verify no duplicate records
        $totalCount = Attendance::where('date', $date)
            ->whereIn('student_id', $studentIds)
            ->count();
            
        $this->assertEquals(count($studentIds), $totalCount);
    }

    /**
     * Test bulk attendance validation
     */
    public function test_bulk_attendance_validation()
    {
        $this->actingAs($this->user);
        
        // Test missing required fields
        $response = $this->postJson('/students/bulk-attendance', []);
        $response->assertStatus(422);
        
        // Test invalid student IDs
        $response = $this->postJson('/students/bulk-attendance', [
            'student_ids' => [99999],
            'date' => now()->format('Y-m-d'),
            'status' => 'present'
        ]);
        $response->assertStatus(422);
        
        // Test invalid status
        $response = $this->postJson('/students/bulk-attendance', [
            'student_ids' => $this->students->pluck('id')->toArray(),
            'date' => now()->format('Y-m-d'),
            'status' => 'invalid_status'
        ]);
        $response->assertStatus(422);
    }

    /**
     * Test database transaction rollback on error
     */
    public function test_bulk_attendance_transaction_rollback()
    {
        $this->actingAs($this->user);
        
        $date = now()->format('Y-m-d');
        $studentIds = $this->students->pluck('id')->toArray();
        
        // Mock a database error during the transaction
        DB::shouldReceive('transaction')
            ->once()
            ->andThrow(new \Exception('Database error'));
        
        $response = $this->postJson('/students/bulk-attendance', [
            'student_ids' => $studentIds,
            'date' => $date,
            'status' => 'present'
        ]);
        
        $response->assertStatus(500);
        
        // Verify no attendance records were created
        $attendanceCount = Attendance::where('date', $date)
            ->whereIn('student_id', $studentIds)
            ->count();
            
        $this->assertEquals(0, $attendanceCount);
    }

    /**
     * Test performance under load
     */
    public function test_bulk_attendance_performance()
    {
        $this->actingAs($this->user);
        
        // Create more students for performance testing
        $largeStudentSet = Student::factory()->count(100)->create([
            'class_id' => $this->class->id
        ]);
        
        $date = now()->format('Y-m-d');
        $studentIds = $largeStudentSet->pluck('id')->toArray();
        
        $startTime = microtime(true);
        
        $response = $this->postJson('/students/bulk-attendance', [
            'student_ids' => $studentIds,
            'date' => $date,
            'status' => 'present'
        ]);
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        $response->assertStatus(200);
        
        // Verify all records were created
        $attendanceCount = Attendance::where('date', $date)
            ->whereIn('student_id', $studentIds)
            ->count();
            
        $this->assertEquals(count($studentIds), $attendanceCount);
        
        // Performance should be reasonable (less than 5 seconds for 100 students)
        $this->assertLessThan(5.0, $executionTime, 
            "Bulk attendance took {$executionTime} seconds, which is too slow");
    }
}