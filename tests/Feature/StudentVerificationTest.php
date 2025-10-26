<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use App\Models\StudentVerification;
use App\Models\ClassModel;
use App\Models\Section;
// RefreshDatabase removed - using DatabaseTransactions in base TestCase
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

class StudentVerificationTest extends TestCase
{
    use  WithFaker;

    protected $admin;
    protected $teacher;
    protected $student;
    protected $class;
    protected $section;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users with proper roles
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@test.com'
        ]);
        
        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'email' => 'teacher@test.com'
        ]);

        // Create class and section
        $this->class = ClassModel::factory()->create([
            'name' => 'Class 10',
            'section' => 'A'
        ]);

        $this->section = Section::factory()->create([
            'class_id' => $this->class->id,
            'name' => 'A'
        ]);

        // Create test student
        $this->student = Student::factory()->create([
            'name' => 'John Doe',
            'father_name' => 'Robert Doe',
            'mother_name' => 'Jane Doe',
            'date_of_birth' => '2005-01-15',
            'aadhaar_number' => '123456789012',
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'verification_status' => 'pending'
        ]);

        Storage::fake('public');
    }

    /** @test */
    public function admin_can_view_student_verification_index()
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/student-verifications');

        $response->assertStatus(200);
        $response->assertViewIs('admin.student-verifications.index');
    }

    /** @test */
    public function admin_can_upload_document_for_verification()
    {
        $this->actingAs($this->admin);

        $file = UploadedFile::fake()->image('birth_certificate.jpg', 800, 600);

        $response = $this->postJson('/api/student-verifications/upload', [
            'student_id' => $this->student->id,
            'document_type' => 'birth_certificate',
            'document' => $file
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Document uploaded and verification started successfully.'
        ]);

        $this->assertDatabaseHas('student_verifications', [
            'student_id' => $this->student->id,
            'document_type' => 'birth_certificate'
        ]);
    }

    /** @test */
    public function document_upload_validates_required_fields()
    {
        $this->actingAs($this->admin);

        $response = $this->postJson('/api/student-verifications/upload', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['student_id', 'document_type', 'document']);
    }

    /** @test */
    public function document_upload_validates_file_type()
    {
        $this->actingAs($this->admin);

        $file = UploadedFile::fake()->create('document.txt', 100);

        $response = $this->postJson('/api/student-verifications/upload', [
            'student_id' => $this->student->id,
            'document_type' => 'birth_certificate',
            'document' => $file
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['document']);
    }

    /** @test */
    public function admin_can_verify_aadhaar_with_valid_data()
    {
        $this->actingAs($this->admin);

        $response = $this->postJson('/api/student-verifications/verify-aadhaar', [
            'student_id' => $this->student->id,
            'aadhaar_number' => '123456789012',
            'name' => 'Test Student',
            'father_name' => 'Test Father',
            'date_of_birth' => '2005-01-15'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Aadhaar verification completed successfully.'
        ]);

        $this->assertDatabaseHas('student_verifications', [
            'student_id' => $this->student->id,
            'document_type' => 'aadhaar_card'
        ]);
    }

    /** @test */
    public function aadhaar_verification_validates_required_fields()
    {
        $this->actingAs($this->admin);

        $response = $this->postJson('/api/student-verifications/verify-aadhaar', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'student_id', 
            'aadhaar_number', 
            'name', 
            'father_name', 
            'date_of_birth'
        ]);
    }

    /** @test */
    public function aadhaar_verification_validates_aadhaar_format()
    {
        $this->actingAs($this->admin);

        $response = $this->postJson('/api/student-verifications/verify-aadhaar', [
            'student_id' => $this->student->id,
            'aadhaar_number' => '123', // Invalid format
            'name' => 'Test Student',
            'father_name' => 'Test Father',
            'date_of_birth' => '2005-01-15'
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['aadhaar_number']);
    }

    /** @test */
    public function admin_can_get_aadhaar_verification_status()
    {
        $this->actingAs($this->admin);

        // Create a verification record
        $verification = StudentVerification::create([
            'student_id' => $this->student->id,
            'document_type' => 'aadhaar_card',
            'verification_status' => StudentVerification::STATUS_VERIFIED,
            'confidence_score' => 95.5,
            'uploaded_by' => $this->admin->id
        ]);

        $response = $this->getJson("/api/students/{$this->student->id}/aadhaar-status");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 'verified',
            'confidence_score' => 95.5
        ]);
    }

    /** @test */
    public function admin_can_approve_verification_manually()
    {
        $this->actingAs($this->admin);

        $verification = StudentVerification::create([
            'student_id' => $this->student->id,
            'document_type' => 'birth_certificate',
            'verification_status' => StudentVerification::STATUS_MANUAL_REVIEW,
            'uploaded_by' => $this->admin->id
        ]);

        $response = $this->postJson("/api/student-verifications/{$verification->id}/approve", [
            'comments' => 'Manually verified - documents are valid'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Document verification approved successfully.'
        ]);

        $verification->refresh();
        $this->assertEquals(StudentVerification::STATUS_VERIFIED, $verification->verification_status);
        $this->assertEquals($this->admin->id, $verification->reviewed_by);
        $this->assertNotNull($verification->reviewed_at);
    }

    /** @test */
    public function admin_can_reject_verification_manually()
    {
        $this->actingAs($this->admin);

        $verification = StudentVerification::create([
            'student_id' => $this->student->id,
            'document_type' => 'birth_certificate',
            'verification_status' => StudentVerification::STATUS_MANUAL_REVIEW,
            'uploaded_by' => $this->admin->id
        ]);

        $response = $this->postJson("/api/student-verifications/{$verification->id}/reject", [
            'comments' => 'Document quality is poor, please resubmit'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Document verification rejected successfully.'
        ]);

        $verification->refresh();
        $this->assertEquals(StudentVerification::STATUS_FAILED, $verification->verification_status);
    }

    /** @test */
    public function admin_can_perform_bulk_approval()
    {
        $this->actingAs($this->admin);

        $verification1 = StudentVerification::create([
            'student_id' => $this->student->id,
            'document_type' => 'birth_certificate',
            'verification_status' => StudentVerification::STATUS_MANUAL_REVIEW,
            'uploaded_by' => $this->admin->id
        ]);

        $verification2 = StudentVerification::create([
            'student_id' => $this->student->id,
            'document_type' => 'aadhaar_card',
            'verification_status' => StudentVerification::STATUS_MANUAL_REVIEW,
            'uploaded_by' => $this->admin->id
        ]);

        $response = $this->postJson('/api/student-verifications/bulk-approve', [
            'verification_ids' => [$verification1->id, $verification2->id],
            'comments' => 'Bulk approval - all documents verified'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Successfully approved 2 document(s).'
        ]);

        $verification1->refresh();
        $verification2->refresh();
        
        $this->assertEquals(StudentVerification::STATUS_VERIFIED, $verification1->verification_status);
        $this->assertEquals(StudentVerification::STATUS_VERIFIED, $verification2->verification_status);
    }

    /** @test */
    public function admin_can_get_verification_status()
    {
        $this->actingAs($this->admin);

        $verification = StudentVerification::create([
            'student_id' => $this->student->id,
            'document_type' => 'birth_certificate',
            'verification_status' => StudentVerification::STATUS_PROCESSING,
            'confidence_score' => 75.0,
            'uploaded_by' => $this->admin->id
        ]);

        $response = $this->getJson("/api/student-verifications/{$verification->id}/status");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'status',
            'confidence_score',
            'confidence_level',
            'is_complete',
            'requires_manual_review',
            'updated_at'
        ]);
    }

    /** @test */
    public function admin_can_view_verification_statistics()
    {
        $this->actingAs($this->admin);

        // Create various verification records
        StudentVerification::create([
            'student_id' => $this->student->id,
            'document_type' => 'birth_certificate',
            'verification_status' => StudentVerification::STATUS_VERIFIED,
            'uploaded_by' => $this->admin->id
        ]);

        StudentVerification::create([
            'student_id' => $this->student->id,
            'document_type' => 'aadhaar_card',
            'verification_status' => StudentVerification::STATUS_MANUAL_REVIEW,
            'uploaded_by' => $this->admin->id
        ]);

        $response = $this->getJson('/api/student-verifications/statistics');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'total_verifications',
            'verified_count',
            'pending_count',
            'failed_count',
            'manual_review_count',
            'verification_rate'
        ]);
    }

    /** @test */
    public function non_admin_cannot_access_verification_endpoints()
    {
        $user = User::factory()->create(['role' => 'teacher']);
        $this->actingAs($user);

        $response = $this->get('/admin/student-verifications');
        $response->assertStatus(403);

        $response = $this->postJson('/api/student-verifications/upload', []);
        $response->assertStatus(403);
    }

    /** @test */
    public function verification_model_has_correct_confidence_levels()
    {
        $highConfidence = StudentVerification::create([
            'student_id' => $this->student->id,
            'document_type' => 'birth_certificate',
            'verification_status' => StudentVerification::STATUS_VERIFIED,
            'confidence_score' => 90.0,
            'uploaded_by' => $this->admin->id
        ]);

        $mediumConfidence = StudentVerification::create([
            'student_id' => $this->student->id,
            'document_type' => 'aadhaar_card',
            'verification_status' => StudentVerification::STATUS_VERIFIED,
            'confidence_score' => 70.0,
            'uploaded_by' => $this->admin->id
        ]);

        $lowConfidence = StudentVerification::create([
            'student_id' => $this->student->id,
            'document_type' => 'transfer_certificate',
            'verification_status' => StudentVerification::STATUS_MANUAL_REVIEW,
            'confidence_score' => 30.0,
            'uploaded_by' => $this->admin->id
        ]);

        $this->assertEquals('high', $highConfidence->confidence_level);
        $this->assertEquals('medium', $mediumConfidence->confidence_level);
        $this->assertEquals('low', $lowConfidence->confidence_level);
    }

    /** @test */
    public function verification_model_determines_manual_review_requirement()
    {
        $autoVerified = StudentVerification::create([
            'student_id' => $this->student->id,
            'document_type' => 'birth_certificate',
            'verification_status' => StudentVerification::STATUS_VERIFIED,
            'confidence_score' => 90.0,
            'uploaded_by' => $this->admin->id
        ]);

        $needsReview = StudentVerification::create([
            'student_id' => $this->student->id,
            'document_type' => 'aadhaar_card',
            'verification_status' => StudentVerification::STATUS_MANUAL_REVIEW,
            'confidence_score' => 50.0,
            'uploaded_by' => $this->admin->id
        ]);

        $this->assertFalse($autoVerified->requires_manual_review);
        $this->assertTrue($needsReview->requires_manual_review);
    }

    /** @test */
    public function admin_can_process_document_with_ocr()
    {
        $this->actingAs($this->admin);

        $file = UploadedFile::fake()->image('birth_certificate.jpg', 800, 600);

        $response = $this->postJson('/api/student-verifications/process-document', [
            'student_id' => $this->student->id,
            'document_type' => 'birth_certificate',
            'document' => $file,
            'enable_ocr' => true
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'verification_id',
            'ocr_data',
            'message'
        ]);

        $this->assertDatabaseHas('student_verifications', [
            'student_id' => $this->student->id,
            'document_type' => 'birth_certificate'
        ]);
    }

    /** @test */
    public function admin_can_delete_verification_record()
    {
        $this->actingAs($this->admin);

        $verification = StudentVerification::create([
            'student_id' => $this->student->id,
            'document_type' => 'birth_certificate',
            'verification_status' => StudentVerification::STATUS_FAILED,
            'uploaded_by' => $this->admin->id
        ]);

        $response = $this->deleteJson("/api/student-verifications/{$verification->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Verification record deleted successfully.'
        ]);

        $this->assertDatabaseMissing('student_verifications', [
            'id' => $verification->id
        ]);
    }

    /** @test */
    public function admin_can_create_new_verification()
    {
        $this->actingAs($this->admin);

        $response = $this->postJson('/api/student-verifications/create', [
            'student_id' => $this->student->id,
            'document_type' => 'transfer_certificate',
            'verification_method' => 'manual',
            'priority' => 'high'
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'message' => 'Verification record created successfully.'
        ]);

        $this->assertDatabaseHas('student_verifications', [
            'student_id' => $this->student->id,
            'document_type' => 'transfer_certificate'
        ]);
    }

    /** @test */
    public function admin_can_compare_verification_documents()
    {
        $this->actingAs($this->admin);

        $verification1 = StudentVerification::create([
            'student_id' => $this->student->id,
            'document_type' => 'birth_certificate',
            'verification_status' => StudentVerification::STATUS_VERIFIED,
            'uploaded_by' => $this->admin->id
        ]);

        $verification2 = StudentVerification::create([
            'student_id' => $this->student->id,
            'document_type' => 'aadhaar_card',
            'verification_status' => StudentVerification::STATUS_VERIFIED,
            'uploaded_by' => $this->admin->id
        ]);

        $response = $this->postJson('/api/student-verifications/compare', [
            'verification_id_1' => $verification1->id,
            'verification_id_2' => $verification2->id,
            'comparison_fields' => ['name', 'date_of_birth', 'father_name']
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'comparison_result',
            'similarity_score',
            'field_matches'
        ]);
    }

    /** @test */
    public function admin_can_process_birth_certificate_ocr()
    {
        $this->actingAs($this->admin);

        $file = UploadedFile::fake()->image('birth_certificate.jpg', 800, 600);

        $response = $this->postJson('/api/student-verifications/process-birth-certificate-ocr', [
            'student_id' => $this->student->id,
            'document' => $file,
            'extract_fields' => ['name', 'date_of_birth', 'father_name', 'mother_name', 'place_of_birth']
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'ocr_data' => [
                'extracted_text',
                'structured_data',
                'confidence_scores'
            ],
            'verification_id'
        ]);
    }

    /** @test */
    public function admin_can_view_bulk_verification_page()
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/student-verifications/bulk-verification');

        $response->assertStatus(200);
        $response->assertViewIs('student-verifications.bulk-verification');
        $response->assertViewHas(['students', 'verificationTypes']);
    }

    /** @test */
    public function admin_can_process_bulk_verification()
    {
        $this->actingAs($this->admin);

        $student2 = Student::factory()->create([
            'class_id' => $this->schoolClass->id,
            'admission_number' => 'ADM002'
        ]);

        $response = $this->postJson('/api/student-verifications/bulk-verification', [
            'student_ids' => [$this->student->id, $student2->id],
            'verification_types' => ['aadhaar', 'birth_certificate'],
            'batch_size' => 10,
            'max_retries' => 3
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'batch_id',
            'total_students',
            'estimated_completion_time'
        ]);
    }

    /** @test */
    public function admin_can_apply_batch_automatic_resolution()
    {
        $this->actingAs($this->admin);

        $verification1 = StudentVerification::create([
            'student_id' => $this->student->id,
            'document_type' => 'birth_certificate',
            'verification_status' => StudentVerification::STATUS_MANUAL_REVIEW,
            'confidence_score' => 85.0,
            'uploaded_by' => $this->admin->id
        ]);

        $verification2 = StudentVerification::create([
            'student_id' => $this->student->id,
            'document_type' => 'aadhaar_card',
            'verification_status' => StudentVerification::STATUS_MANUAL_REVIEW,
            'confidence_score' => 90.0,
            'uploaded_by' => $this->admin->id
        ]);

        $response = $this->postJson('/api/student-verifications/batch-auto-resolve', [
            'verification_ids' => [$verification1->id, $verification2->id],
            'confidence_threshold' => 80.0,
            'auto_approve_high_confidence' => true
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'processed_count',
            'auto_approved_count',
            'still_pending_count',
            'results'
        ]);
    }

    /** @test */
    public function admin_can_view_verification_history()
    {
        $this->actingAs($this->admin);

        // Create some verification history
        StudentVerification::create([
            'student_id' => $this->student->id,
            'document_type' => 'birth_certificate',
            'verification_status' => StudentVerification::STATUS_VERIFIED,
            'uploaded_by' => $this->admin->id,
            'created_at' => now()->subDays(5)
        ]);

        $response = $this->get('/admin/student-verifications/history');

        $response->assertStatus(200);
        $response->assertViewIs('student-verifications.history');
    }

    /** @test */
    public function admin_can_view_student_specific_verification_history()
    {
        $this->actingAs($this->admin);

        $response = $this->get("/admin/student-verifications/student/{$this->student->id}/history");

        $response->assertStatus(200);
        $response->assertViewIs('student-verifications.student-history');
        $response->assertViewHas('student');
    }

    /** @test */
    public function bulk_verification_validates_required_fields()
    {
        $this->actingAs($this->admin);

        $response = $this->postJson('/api/student-verifications/bulk-verification', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['student_ids', 'verification_types']);
    }

    /** @test */
    public function bulk_verification_validates_student_existence()
    {
        $this->actingAs($this->admin);

        $response = $this->postJson('/api/student-verifications/bulk-verification', [
            'student_ids' => [99999], // Non-existent student
            'verification_types' => ['aadhaar']
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['student_ids.0']);
    }

    /** @test */
    public function batch_auto_resolve_validates_confidence_threshold()
    {
        $this->actingAs($this->admin);

        $verification = StudentVerification::create([
            'student_id' => $this->student->id,
            'document_type' => 'birth_certificate',
            'verification_status' => StudentVerification::STATUS_MANUAL_REVIEW,
            'uploaded_by' => $this->admin->id
        ]);

        $response = $this->postJson('/api/student-verifications/batch-auto-resolve', [
            'verification_ids' => [$verification->id],
            'confidence_threshold' => 150.0 // Invalid threshold
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['confidence_threshold']);
    }

    /** @test */
    public function document_comparison_handles_missing_documents()
    {
        $this->actingAs($this->admin);

        $response = $this->postJson('/api/student-verifications/compare', [
            'verification_id_1' => 99999, // Non-existent
            'verification_id_2' => 99998, // Non-existent
            'comparison_fields' => ['name']
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'One or both verification records not found.'
        ]);
    }

    /** @test */
    public function ocr_processing_handles_invalid_file_formats()
    {
        $this->actingAs($this->admin);

        $file = UploadedFile::fake()->create('document.txt', 100);

        $response = $this->postJson('/api/student-verifications/process-birth-certificate-ocr', [
            'student_id' => $this->student->id,
            'document' => $file
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['document']);
    }

    /** @test */
    public function verification_deletion_requires_proper_permissions()
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $this->actingAs($teacher);

        $verification = StudentVerification::create([
            'student_id' => $this->student->id,
            'document_type' => 'birth_certificate',
            'verification_status' => StudentVerification::STATUS_FAILED,
            'uploaded_by' => $this->admin->id
        ]);

        $response = $this->deleteJson("/api/student-verifications/{$verification->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function bulk_operations_handle_concurrent_access()
    {
        $this->actingAs($this->admin);

        $verification = StudentVerification::create([
            'student_id' => $this->student->id,
            'document_type' => 'birth_certificate',
            'verification_status' => StudentVerification::STATUS_MANUAL_REVIEW,
            'confidence_score' => 85.0,
            'uploaded_by' => $this->admin->id
        ]);

        // Simulate concurrent requests
        $response1 = $this->postJson('/api/student-verifications/bulk-approve', [
            'verification_ids' => [$verification->id],
            'comments' => 'First approval attempt'
        ]);

        $response2 = $this->postJson('/api/student-verifications/bulk-approve', [
            'verification_ids' => [$verification->id],
            'comments' => 'Second approval attempt'
        ]);

        // First request should succeed
        $response1->assertStatus(200);
        
        // Second request should handle the already-processed state gracefully
        $response2->assertStatus(200);
        $response2->assertJsonFragment(['message' => 'Successfully approved 0 document(s).']);
    }
}
