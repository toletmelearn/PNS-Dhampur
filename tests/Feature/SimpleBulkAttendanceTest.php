<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Attendance;
// RefreshDatabase removed - using DatabaseTransactions in base TestCase
use Illuminate\Support\Facades\DB;

class SimpleBulkAttendanceTest extends TestCase
{
    // RefreshDatabase removed - using DatabaseTransactions in base TestCase

    protected $user;
    protected $students;
    protected $class;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = User::create([
            'name' => 'Test Teacher',
            'email' => 'teacher@test.com',
            'password' => bcrypt('password'),
            'role' => 'teacher'
        ]);
        
        // Create a test class
        $this->class = ClassModel::create([
            'name' => 'Test Class',
            'section' => 'A',
            'capacity' => 50
        ]);
        
        // Create test students
        $this->students = collect();
        for ($i = 1; $i <= 3; $i++) {
            $student = Student::create([
                'name' => "Test Student {$i}",
                'admission_no' => "S00{$i}",
                'class_id' => $this->class->id,
                'father_name' => "Father {$i}",
                'mother_name' => "Mother {$i}",
                'dob' => '2010-01-01',
                'aadhaar' => str_pad($i, 12, '0', STR_PAD_LEFT),
                'verification_status' => 'verified',
                'status' => 'active'
            ]);
            $this->students->push($student);
        }
    }

    /**
     * Test basic bulk attendance functionality
     */
    public function test_bulk_attendance_creates_records()
    {
        $this->actingAs($this->user);
        
        $date = now()->format('Y-m-d');
        $studentIds = $this->students->pluck('id')->toArray();
        
        $response = $this->postJson('/students/bulk-attendance', [
            'student_ids' => $studentIds,
            'date' => $date,
            'status' => 'present',
            'remarks' => 'Test attendance'
        ]);
        
        $response->assertStatus(200);
        
        // Verify attendance records were created
        $attendanceCount = Attendance::where('date', $date)
            ->whereIn('student_id', $studentIds)
            ->count();
            
        $this->assertEquals(count($studentIds), $attendanceCount);
    }

    /**
     * Test bulk attendance updates existing records
     */
    public function test_bulk_attendance_updates_existing_records()
    {
        $this->actingAs($this->user);
        
        $date = now()->format('Y-m-d');
        $studentIds = $this->students->pluck('id')->toArray();
        
        // Create existing attendance record for first student
        Attendance::create([
            'student_id' => $studentIds[0],
            'date' => $date,
            'status' => 'absent',
            'marked_by' => $this->user->id
        ]);
        
        // Update attendance for all students
        $response = $this->postJson('/students/bulk-attendance', [
            'student_ids' => $studentIds,
            'date' => $date,
            'status' => 'present',
            'remarks' => 'Updated attendance'
        ]);
        
        $response->assertStatus(200);
        
        // Verify all students have 'present' status
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
        
        // Test invalid status
        $response = $this->postJson('/students/bulk-attendance', [
            'student_ids' => $this->students->pluck('id')->toArray(),
            'date' => now()->format('Y-m-d'),
            'status' => 'invalid_status'
        ]);
        $response->assertStatus(422);
    }

    /**
     * Test that duplicate requests don't create duplicate records
     */
    public function test_bulk_attendance_prevents_duplicates()
    {
        $this->actingAs($this->user);
        
        $date = now()->format('Y-m-d');
        $studentIds = $this->students->pluck('id')->toArray();
        
        $requestData = [
            'student_ids' => $studentIds,
            'date' => $date,
            'status' => 'present',
            'remarks' => 'Test attendance'
        ];
        
        // Make the same request multiple times
        $response1 = $this->postJson('/students/bulk-attendance', $requestData);
        $response2 = $this->postJson('/students/bulk-attendance', $requestData);
        $response3 = $this->postJson('/students/bulk-attendance', $requestData);
        
        $response1->assertStatus(200);
        $response2->assertStatus(200);
        $response3->assertStatus(200);
        
        // Verify no duplicate records were created
        $attendanceCount = Attendance::where('date', $date)
            ->whereIn('student_id', $studentIds)
            ->count();
            
        $this->assertEquals(count($studentIds), $attendanceCount, 
            'Duplicate attendance records were created');
        
        // Verify each student has exactly one record
        foreach ($studentIds as $studentId) {
            $studentAttendanceCount = Attendance::where('student_id', $studentId)
                ->where('date', $date)
                ->count();
                
            $this->assertEquals(1, $studentAttendanceCount, 
                "Student {$studentId} has {$studentAttendanceCount} attendance records instead of 1");
        }
    }
}