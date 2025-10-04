<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use App\Models\StudentVerification;
use App\Models\SchoolClass;
use App\Services\AadhaarVerificationService;
use App\Services\DocumentVerificationService;

class StudentVerificationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $admin;
    protected $student;
    protected $schoolClass;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@test.com'
        ]);

        // Create school class
        $this->schoolClass = SchoolClass::factory()->create([
            'name' => 'Class 10',
            'section' => 'A'
        ]);

        // Create student
        $this->student = Student::factory()->create([
            'name' => 'Test Student',
            'father_name' => 'Test Father',
            'mother_name' => 'Test Mother',
            'dob' => '2005-01-15',
            'aadhaar' => '123456789012',
            'class_id' => $this->schoolClass->id,
            'admission_number' => 'ADM001'
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
}
