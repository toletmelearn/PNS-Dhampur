<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Attendance;
use App\Models\StudentVerification;
use App\Models\Fee;
use App\Models\Syllabus;
use App\Services\BulkVerificationService;
use App\Services\AuditTrailService;
// RefreshDatabase removed - using DatabaseTransactions in base TestCase
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class BulkOperationsTest extends TestCase
{
    use  WithFaker;

    protected $admin;
    protected $teacher;
    protected $students;
    protected $class;
    protected $bulkVerificationService;
    protected $auditTrailService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@test.com'
        ]);

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'employee_id' => 'TEACH001'
        ]);
        
        // Create test class
        $this->class = ClassModel::factory()->create([
            'name' => 'Test Class',
            'section' => 'A'
        ]);
        
        // Create test students
        $this->students = Student::factory()->count(10)->create([
            'class_id' => $this->class->id
        ]);

        // Initialize services with mocks
        $this->bulkVerificationService = $this->createMock(BulkVerificationService::class);
        $this->auditTrailService = $this->createMock(AuditTrailService::class);
    }

    /** @test */
    public function admin_can_perform_bulk_attendance_marking()
    {
        $this->actingAs($this->admin);
        
        $date = now()->format('Y-m-d');
        $studentIds = $this->students->pluck('id')->toArray();
        
        $response = $this->postJson('/api/students/bulk-attendance', [
            'student_ids' => $studentIds,
            'date' => $date,
            'status' => 'present',
            'remarks' => 'Bulk attendance test'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Bulk attendance marked successfully'
            ]);

        // Verify attendance records were created
        foreach ($studentIds as $studentId) {
            $this->assertDatabaseHas('attendances', [
                'student_id' => $studentId,
                'date' => $date,
                'status' => 'present',
                'marked_by' => $this->admin->id
            ]);
        }
    }

    /** @test */
    public function bulk_attendance_handles_partial_failures()
    {
        $this->actingAs($this->admin);
        
        $date = now()->format('Y-m-d');
        $validStudentIds = $this->students->take(5)->pluck('id')->toArray();
        $invalidStudentIds = [99999, 99998]; // Non-existent student IDs
        $allStudentIds = array_merge($validStudentIds, $invalidStudentIds);
        
        $response = $this->postJson('/api/students/bulk-attendance', [
            'student_ids' => $allStudentIds,
            'date' => $date,
            'status' => 'present'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'processed',
                'failed',
                'errors'
            ]);

        // Verify valid students have attendance records
        foreach ($validStudentIds as $studentId) {
            $this->assertDatabaseHas('attendances', [
                'student_id' => $studentId,
                'date' => $date,
                'status' => 'present'
            ]);
        }

        // Verify invalid students don't have records
        foreach ($invalidStudentIds as $studentId) {
            $this->assertDatabaseMissing('attendances', [
                'student_id' => $studentId,
                'date' => $date
            ]);
        }
    }

    /** @test */
    public function admin_can_perform_bulk_student_verification()
    {
        $this->actingAs($this->admin);
        
        // Create verification records for students
        $verifications = [];
        foreach ($this->students->take(5) as $student) {
            $verifications[] = StudentVerification::create([
                'student_id' => $student->id,
                'document_type' => 'aadhaar',
                'document_number' => $this->faker->numerify('############'),
                'verification_status' => 'pending',
                'confidence_score' => 85,
                'submitted_by' => $this->admin->id
            ]);
        }

        $verificationIds = collect($verifications)->pluck('id')->toArray();

        $response = $this->postJson('/student-verification/bulk-approve', [
            'verification_ids' => $verificationIds,
            'action' => 'approve',
            'remarks' => 'Bulk approval test'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Bulk verification completed successfully'
            ]);

        // Verify all verifications were approved
        foreach ($verificationIds as $verificationId) {
            $this->assertDatabaseHas('student_verifications', [
                'id' => $verificationId,
                'verification_status' => 'approved',
                'verified_by' => $this->admin->id
            ]);
        }
    }

    /** @test */
    public function admin_can_perform_bulk_student_verification_rejection()
    {
        $this->actingAs($this->admin);
        
        // Create verification records
        $verifications = [];
        foreach ($this->students->take(3) as $student) {
            $verifications[] = StudentVerification::create([
                'student_id' => $student->id,
                'document_type' => 'birth_certificate',
                'verification_status' => 'pending',
                'confidence_score' => 45,
                'submitted_by' => $this->admin->id
            ]);
        }

        $verificationIds = collect($verifications)->pluck('id')->toArray();

        $response = $this->postJson('/student-verification/bulk-approve', [
            'verification_ids' => $verificationIds,
            'action' => 'reject',
            'remarks' => 'Insufficient documentation'
        ]);

        $response->assertStatus(200);

        // Verify all verifications were rejected
        foreach ($verificationIds as $verificationId) {
            $this->assertDatabaseHas('student_verifications', [
                'id' => $verificationId,
                'verification_status' => 'rejected',
                'verified_by' => $this->admin->id
            ]);
        }
    }

    /** @test */
    public function bulk_verification_service_processes_in_batches()
    {
        $studentIds = $this->students->pluck('id')->toArray();
        
        // Create verification records
        $verificationIds = [];
        foreach ($studentIds as $studentId) {
            $verification = StudentVerification::create([
                'student_id' => $studentId,
                'document_type' => 'aadhaar',
                'verification_status' => 'pending',
                'confidence_score' => 75,
                'submitted_by' => $this->admin->id
            ]);
            $verificationIds[] = $verification->id;
        }

        $result = $this->bulkVerificationService->processBulkVerification(
            $verificationIds,
            'approve',
            $this->admin->id,
            'Batch processing test'
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(count($verificationIds), $result['processed']);
        $this->assertEquals(0, $result['failed']);
    }

    /** @test */
    public function admin_can_perform_bulk_fee_collection()
    {
        $this->actingAs($this->admin);
        
        // Create fee records for students
        $fees = [];
        foreach ($this->students->take(5) as $student) {
            $fees[] = Fee::create([
                'student_id' => $student->id,
                'fee_type' => 'tuition',
                'amount' => 5000,
                'due_date' => now()->addDays(30),
                'status' => 'pending'
            ]);
        }

        $feeIds = collect($fees)->pluck('id')->toArray();

        $response = $this->postJson('/api/students/bulk-fee-collection', [
            'fee_ids' => $feeIds,
            'payment_method' => 'cash',
            'collected_by' => $this->admin->id,
            'collection_date' => now()->format('Y-m-d')
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);

        // Verify fees were marked as paid
        foreach ($feeIds as $feeId) {
            $this->assertDatabaseHas('fees', [
                'id' => $feeId,
                'status' => 'paid'
            ]);
        }
    }

    /** @test */
    public function admin_can_perform_bulk_document_upload()
    {
        Storage::fake('local');
        $this->actingAs($this->admin);
        
        $studentIds = $this->students->take(3)->pluck('id')->toArray();
        $files = [];
        
        foreach ($studentIds as $index => $studentId) {
            $files["documents.{$index}"] = UploadedFile::fake()->create("document_{$studentId}.pdf", 100);
        }

        $response = $this->postJson('/api/students/bulk-document-upload', array_merge([
            'student_ids' => $studentIds,
            'document_type' => 'id_proof'
        ], $files));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);

        // Verify documents were uploaded for each student
        foreach ($studentIds as $studentId) {
            $this->assertDatabaseHas('student_documents', [
                'student_id' => $studentId,
                'document_type' => 'id_proof'
            ]);
        }
    }

    /** @test */
    public function admin_can_perform_bulk_status_updates()
    {
        $this->actingAs($this->admin);
        
        $studentIds = $this->students->take(5)->pluck('id')->toArray();

        $response = $this->postJson('/api/students/bulk-status-update', [
            'student_ids' => $studentIds,
            'status' => 'active',
            'reason' => 'Bulk activation test'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'updated' => 5
            ]);

        // Verify student statuses were updated
        foreach ($studentIds as $studentId) {
            $this->assertDatabaseHas('students', [
                'id' => $studentId,
                'status' => 'active'
            ]);
        }
    }

    /** @test */
    public function bulk_operations_are_logged_in_audit_trail()
    {
        $this->actingAs($this->admin);
        
        $studentIds = $this->students->take(3)->pluck('id')->toArray();
        
        $this->auditTrailService->logBulkOperation(
            'bulk_attendance',
            $this->admin->id,
            [
                'student_ids' => $studentIds,
                'date' => now()->format('Y-m-d'),
                'status' => 'present'
            ],
            'Bulk attendance marked for 3 students'
        );

        $this->assertDatabaseHas('audit_trails', [
            'user_id' => $this->admin->id,
            'action' => 'bulk_attendance',
            'description' => 'Bulk attendance marked for 3 students'
        ]);
    }

    /** @test */
    public function bulk_operations_handle_concurrent_access()
    {
        $this->actingAs($this->admin);
        
        $date = now()->format('Y-m-d');
        $studentIds = $this->students->take(5)->pluck('id')->toArray();
        
        // Simulate concurrent bulk operations
        $responses = [];
        for ($i = 0; $i < 3; $i++) {
            $responses[] = $this->postJson('/api/students/bulk-attendance', [
                'student_ids' => $studentIds,
                'date' => $date,
                'status' => 'present',
                'remarks' => "Concurrent test {$i}"
            ]);
        }

        // All requests should succeed
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }

        // Verify no duplicate records were created
        foreach ($studentIds as $studentId) {
            $attendanceCount = Attendance::where('student_id', $studentId)
                ->where('date', $date)
                ->count();
            
            $this->assertEquals(1, $attendanceCount, 
                "Student {$studentId} has {$attendanceCount} attendance records instead of 1");
        }
    }

    /** @test */
    public function bulk_operations_validate_permissions()
    {
        $student = User::factory()->create(['role' => 'student']);
        $this->actingAs($student);
        
        $studentIds = $this->students->pluck('id')->toArray();

        $response = $this->postJson('/students/bulk-attendance', [
            'student_ids' => $studentIds,
            'date' => now()->format('Y-m-d'),
            'status' => 'present'
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function bulk_operations_handle_database_transactions()
    {
        $this->actingAs($this->admin);
        
        $studentIds = $this->students->pluck('id')->toArray();
        
        // Mock database transaction failure
        DB::shouldReceive('transaction')
            ->once()
            ->andThrow(new \Exception('Database transaction failed'));

        $response = $this->postJson('/students/bulk-attendance', [
            'student_ids' => $studentIds,
            'date' => now()->format('Y-m-d'),
            'status' => 'present'
        ]);

        $response->assertStatus(500);

        // Verify no partial data was saved
        $attendanceCount = Attendance::whereIn('student_id', $studentIds)
            ->where('date', now()->format('Y-m-d'))
            ->count();
        
        $this->assertEquals(0, $attendanceCount);
    }

    /** @test */
    public function admin_can_perform_bulk_syllabus_actions()
    {
        $this->actingAs($this->admin);
        
        // Create test syllabi
        $syllabi = Syllabus::factory()->count(5)->create([
            'status' => 'draft'
        ]);

        $syllabusIds = $syllabi->pluck('id')->toArray();

        $response = $this->postJson('/syllabus/bulk-action', [
            'syllabus_ids' => $syllabusIds,
            'action' => 'activate'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Bulk action completed successfully'
            ]);

        // Verify syllabi were activated
        foreach ($syllabusIds as $syllabusId) {
            $this->assertDatabaseHas('syllabi', [
                'id' => $syllabusId,
                'status' => 'active'
            ]);
        }
    }

    /** @test */
    public function bulk_operations_provide_detailed_feedback()
    {
        $this->actingAs($this->admin);
        
        $validStudentIds = $this->students->take(3)->pluck('id')->toArray();
        $invalidStudentIds = [99999, 99998];
        $allStudentIds = array_merge($validStudentIds, $invalidStudentIds);

        $response = $this->postJson('/students/bulk-attendance', [
            'student_ids' => $allStudentIds,
            'date' => now()->format('Y-m-d'),
            'status' => 'present'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'processed',
                'failed',
                'errors' => [
                    '*' => [
                        'student_id',
                        'error'
                    ]
                ]
            ]);

        $responseData = $response->json();
        $this->assertEquals(3, $responseData['processed']);
        $this->assertEquals(2, $responseData['failed']);
        $this->assertCount(2, $responseData['errors']);
    }

    /** @test */
    public function bulk_operations_handle_large_datasets()
    {
        $this->actingAs($this->admin);
        
        // Create a large number of students
        $largeStudentSet = Student::factory()->count(100)->create([
            'class_id' => $this->class->id
        ]);

        $studentIds = $largeStudentSet->pluck('id')->toArray();
        $date = now()->format('Y-m-d');

        $startTime = microtime(true);

        $response = $this->postJson('/students/bulk-attendance', [
            'student_ids' => $studentIds,
            'date' => $date,
            'status' => 'present'
        ]);

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $response->assertStatus(200);

        // Verify all records were processed
        $attendanceCount = Attendance::where('date', $date)
            ->whereIn('student_id', $studentIds)
            ->count();
        
        $this->assertEquals(100, $attendanceCount);

        // Performance should be reasonable (less than 10 seconds for 100 students)
        $this->assertLessThan(10.0, $executionTime, 
            "Bulk operation took {$executionTime} seconds, which is too slow");
    }

    /** @test */
    public function bulk_operations_support_queued_processing()
    {
        Queue::fake();
        $this->actingAs($this->admin);
        
        $studentIds = $this->students->pluck('id')->toArray();

        $response = $this->postJson('/api/students/bulk-status-update', [
            'student_ids' => $studentIds,
            'status' => 'inactive',
            'async' => true // Request asynchronous processing
        ]);

        $response->assertStatus(202) // Accepted for processing
            ->assertJson([
                'success' => true,
                'message' => 'Bulk operation queued for processing'
            ]);

        // Verify job was queued
        Queue::assertPushed(\App\Jobs\BulkStudentStatusUpdate::class);
    }

    /** @test */
    public function bulk_operations_validate_input_limits()
    {
        $this->actingAs($this->admin);
        
        // Create too many student IDs (exceeding limit)
        $tooManyStudentIds = range(1, 1001); // Assuming limit is 1000

        $response = $this->postJson('/students/bulk-attendance', [
            'student_ids' => $tooManyStudentIds,
            'date' => now()->format('Y-m-d'),
            'status' => 'present'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['student_ids']);
    }

    /** @test */
    public function bulk_operations_handle_network_timeouts()
    {
        $this->actingAs($this->admin);
        
        // Set a very short timeout to simulate timeout scenario
        ini_set('max_execution_time', 1);
        
        $studentIds = $this->students->pluck('id')->toArray();

        try {
            $response = $this->postJson('/students/bulk-attendance', [
                'student_ids' => $studentIds,
                'date' => now()->format('Y-m-d'),
                'status' => 'present'
            ]);

            // If we reach here, the operation completed within timeout
            $response->assertStatus(200);
        } catch (\Exception $e) {
            // Timeout occurred, which is expected behavior
            $this->assertStringContains('timeout', strtolower($e->getMessage()));
        } finally {
            // Reset timeout
            ini_set('max_execution_time', 30);
        }
    }

    /** @test */
    public function bulk_verification_handles_automatic_resolution()
    {
        $this->actingAs($this->admin);
        
        // Create verifications with high confidence scores
        $verifications = [];
        foreach ($this->students->take(5) as $student) {
            $verifications[] = StudentVerification::create([
                'student_id' => $student->id,
                'document_type' => 'aadhaar',
                'verification_status' => 'pending',
                'confidence_score' => 95, // High confidence
                'submitted_by' => $this->admin->id
            ]);
        }

        $verificationIds = collect($verifications)->pluck('id')->toArray();

        $response = $this->postJson('/student-verification/batch-apply-automatic-resolution', [
            'verification_ids' => $verificationIds,
            'confidence_threshold' => 90
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);

        // Verify high-confidence verifications were auto-approved
        foreach ($verificationIds as $verificationId) {
            $this->assertDatabaseHas('student_verifications', [
                'id' => $verificationId,
                'verification_status' => 'approved'
            ]);
        }
    }
}