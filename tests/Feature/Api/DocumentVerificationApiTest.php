<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\ClassModel;
use App\Models\Section;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

class DocumentVerificationApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $admin;
    protected $teacher;
    protected $student;
    protected $class;
    protected $section;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->teacher = User::factory()->create(['role' => 'teacher']);

        $this->class = ClassModel::factory()->create(['name' => 'Class 10']);
        $this->section = Section::factory()->create([
            'class_id' => $this->class->id,
            'name' => 'A'
        ]);

        $this->student = Student::factory()->create([
            'class_id' => $this->class->id,
            'section_id' => $this->section->id
        ]);

        Storage::fake('public');
    }

    /** @test */
    public function can_upload_aadhaar_document()
    {
        Sanctum::actingAs($this->admin);

        $file = UploadedFile::fake()->image('aadhaar.jpg', 800, 600);

        $response = $this->postJson("/api/students/{$this->student->id}/documents/upload", [
            'document_type' => 'aadhaar_card',
            'document' => $file
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'file_path',
                    'document_id',
                    'virus_scan_status'
                ]);

        Storage::disk('public')->assertExists('documents/students/' . $this->student->id . '/' . $file->hashName());

        $this->assertDatabaseHas('student_documents', [
            'student_id' => $this->student->id,
            'document_type' => 'aadhaar_card',
            'status' => 'uploaded'
        ]);
    }

    /** @test */
    public function can_upload_birth_certificate()
    {
        Sanctum::actingAs($this->admin);

        $file = UploadedFile::fake()->create('birth_certificate.pdf', 500);

        $response = $this->postJson("/api/students/{$this->student->id}/documents/upload", [
            'document_type' => 'birth_certificate',
            'document' => $file
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Document uploaded successfully'
                ]);

        $this->assertDatabaseHas('student_documents', [
            'student_id' => $this->student->id,
            'document_type' => 'birth_certificate'
        ]);
    }

    /** @test */
    public function can_upload_passport_photo()
    {
        Sanctum::actingAs($this->admin);

        $file = UploadedFile::fake()->image('passport_photo.jpg', 300, 400);

        $response = $this->postJson("/api/students/{$this->student->id}/documents/upload", [
            'document_type' => 'passport_photo',
            'document' => $file
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('student_documents', [
            'student_id' => $this->student->id,
            'document_type' => 'passport_photo'
        ]);
    }

    /** @test */
    public function validates_file_type_for_documents()
    {
        Sanctum::actingAs($this->admin);

        $file = UploadedFile::fake()->create('document.txt', 100); // Invalid file type

        $response = $this->postJson("/api/students/{$this->student->id}/documents/upload", [
            'document_type' => 'aadhaar_card',
            'document' => $file
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['document']);
    }

    /** @test */
    public function validates_file_size_limits()
    {
        Sanctum::actingAs($this->admin);

        $file = UploadedFile::fake()->create('large_document.pdf', 3000); // 3MB file (exceeds 2MB limit)

        $response = $this->postJson("/api/students/{$this->student->id}/documents/upload", [
            'document_type' => 'aadhaar_card',
            'document' => $file
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['document']);
    }

    /** @test */
    public function validates_required_document_type()
    {
        Sanctum::actingAs($this->admin);

        $file = UploadedFile::fake()->image('document.jpg');

        $response = $this->postJson("/api/students/{$this->student->id}/documents/upload", [
            'document' => $file
            // Missing document_type
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['document_type']);
    }

    /** @test */
    public function validates_document_type_values()
    {
        Sanctum::actingAs($this->admin);

        $file = UploadedFile::fake()->image('document.jpg');

        $response = $this->postJson("/api/students/{$this->student->id}/documents/upload", [
            'document_type' => 'invalid_type',
            'document' => $file
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['document_type']);
    }

    /** @test */
    public function can_list_student_documents()
    {
        Sanctum::actingAs($this->admin);

        // Create some documents
        StudentDocument::factory()->count(3)->create([
            'student_id' => $this->student->id
        ]);

        $response = $this->getJson("/api/students/{$this->student->id}/documents");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'document_type',
                            'file_name',
                            'file_path',
                            'status',
                            'uploaded_at',
                            'verified_at'
                        ]
                    ]
                ]);

        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function can_download_document()
    {
        Sanctum::actingAs($this->admin);

        $file = UploadedFile::fake()->create('aadhaar.pdf', 100);
        
        $uploadResponse = $this->postJson("/api/students/{$this->student->id}/documents/upload", [
            'document_type' => 'aadhaar_card',
            'document' => $file
        ]);

        $documentId = $uploadResponse->json('document_id');

        $response = $this->getJson("/api/students/{$this->student->id}/documents/{$documentId}/download");

        $response->assertStatus(200)
                ->assertHeader('Content-Type', 'application/pdf');
    }

    /** @test */
    public function can_verify_document()
    {
        Sanctum::actingAs($this->admin);

        $document = StudentDocument::factory()->create([
            'student_id' => $this->student->id,
            'document_type' => 'aadhaar_card',
            'status' => 'uploaded'
        ]);

        $verificationData = [
            'status' => 'verified',
            'verification_notes' => 'Document is clear and valid',
            'extracted_data' => [
                'name' => 'John Doe',
                'aadhaar_number' => '123456789012'
            ]
        ];

        $response = $this->postJson("/api/documents/{$document->id}/verify", $verificationData);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Document verified successfully',
                    'status' => 'verified'
                ]);

        $document->refresh();
        $this->assertEquals('verified', $document->status);
        $this->assertEquals('Document is clear and valid', $document->verification_notes);
    }

    /** @test */
    public function can_reject_document()
    {
        Sanctum::actingAs($this->admin);

        $document = StudentDocument::factory()->create([
            'student_id' => $this->student->id,
            'status' => 'uploaded'
        ]);

        $rejectionData = [
            'status' => 'rejected',
            'verification_notes' => 'Document is not clear, please reupload'
        ];

        $response = $this->postJson("/api/documents/{$document->id}/verify", $rejectionData);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Document verification updated',
                    'status' => 'rejected'
                ]);

        $document->refresh();
        $this->assertEquals('rejected', $document->status);
    }

    /** @test */
    public function can_bulk_upload_documents()
    {
        Sanctum::actingAs($this->admin);

        $files = [
            'aadhaar_card' => UploadedFile::fake()->image('aadhaar.jpg'),
            'birth_certificate' => UploadedFile::fake()->create('birth_cert.pdf', 200),
            'passport_photo' => UploadedFile::fake()->image('photo.jpg')
        ];

        $response = $this->postJson("/api/students/{$this->student->id}/documents/bulk-upload", [
            'documents' => $files
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'uploaded_count',
                    'results' => [
                        '*' => [
                            'document_type',
                            'status',
                            'document_id'
                        ]
                    ]
                ]);

        $this->assertEquals(3, $response->json('uploaded_count'));

        // Verify all documents are in database
        foreach (array_keys($files) as $documentType) {
            $this->assertDatabaseHas('student_documents', [
                'student_id' => $this->student->id,
                'document_type' => $documentType
            ]);
        }
    }

    /** @test */
    public function can_get_document_verification_status()
    {
        Sanctum::actingAs($this->admin);

        $document = StudentDocument::factory()->create([
            'student_id' => $this->student->id,
            'status' => 'verified',
            'verification_notes' => 'All good'
        ]);

        $response = $this->getJson("/api/documents/{$document->id}/status");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'document_id',
                    'status',
                    'verification_notes',
                    'verified_at',
                    'verified_by'
                ]);
    }

    /** @test */
    public function can_delete_document()
    {
        Sanctum::actingAs($this->admin);

        $document = StudentDocument::factory()->create([
            'student_id' => $this->student->id
        ]);

        $response = $this->deleteJson("/api/documents/{$document->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Document deleted successfully'
                ]);

        $this->assertSoftDeleted('student_documents', ['id' => $document->id]);
    }

    /** @test */
    public function prevents_duplicate_document_types()
    {
        Sanctum::actingAs($this->admin);

        // First upload
        StudentDocument::factory()->create([
            'student_id' => $this->student->id,
            'document_type' => 'aadhaar_card'
        ]);

        $file = UploadedFile::fake()->image('aadhaar2.jpg');

        $response = $this->postJson("/api/students/{$this->student->id}/documents/upload", [
            'document_type' => 'aadhaar_card',
            'document' => $file
        ]);

        $response->assertStatus(422)
                ->assertJson([
                    'message' => 'Document of this type already exists for this student'
                ]);
    }

    /** @test */
    public function can_replace_existing_document()
    {
        Sanctum::actingAs($this->admin);

        $existingDocument = StudentDocument::factory()->create([
            'student_id' => $this->student->id,
            'document_type' => 'aadhaar_card'
        ]);

        $file = UploadedFile::fake()->image('new_aadhaar.jpg');

        $response = $this->postJson("/api/students/{$this->student->id}/documents/upload", [
            'document_type' => 'aadhaar_card',
            'document' => $file,
            'replace_existing' => true
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Document replaced successfully'
                ]);

        // Old document should be soft deleted
        $this->assertSoftDeleted('student_documents', ['id' => $existingDocument->id]);

        // New document should exist
        $this->assertDatabaseHas('student_documents', [
            'student_id' => $this->student->id,
            'document_type' => 'aadhaar_card',
            'status' => 'uploaded'
        ]);
    }

    /** @test */
    public function virus_scan_blocks_infected_files()
    {
        Sanctum::actingAs($this->admin);

        // Mock virus scanner to return infected
        $this->mock(\App\Services\VirusScanService::class, function ($mock) {
            $mock->shouldReceive('scan')->andReturn([
                'is_clean' => false,
                'threat_name' => 'Test.Virus'
            ]);
        });

        $file = UploadedFile::fake()->create('infected.pdf', 100);

        $response = $this->postJson("/api/students/{$this->student->id}/documents/upload", [
            'document_type' => 'aadhaar_card',
            'document' => $file
        ]);

        $response->assertStatus(422)
                ->assertJson([
                    'message' => 'File failed virus scan',
                    'threat_detected' => 'Test.Virus'
                ]);

        // File should not be stored
        Storage::disk('public')->assertMissing('documents/students/' . $this->student->id . '/' . $file->hashName());
    }

    /** @test */
    public function can_get_document_statistics()
    {
        Sanctum::actingAs($this->admin);

        // Create documents with different statuses
        StudentDocument::factory()->count(5)->create([
            'student_id' => $this->student->id,
            'status' => 'verified'
        ]);

        StudentDocument::factory()->count(3)->create([
            'student_id' => $this->student->id,
            'status' => 'pending'
        ]);

        StudentDocument::factory()->count(2)->create([
            'student_id' => $this->student->id,
            'status' => 'rejected'
        ]);

        $response = $this->getJson('/api/documents/statistics');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'total_documents',
                    'verified_documents',
                    'pending_documents',
                    'rejected_documents',
                    'by_document_type',
                    'verification_trends'
                ]);
    }

    /** @test */
    public function unauthorized_users_cannot_access_document_endpoints()
    {
        $response = $this->postJson("/api/students/{$this->student->id}/documents/upload", []);
        $response->assertStatus(401);

        $response = $this->getJson("/api/students/{$this->student->id}/documents");
        $response->assertStatus(401);
    }

    /** @test */
    public function teachers_have_limited_document_access()
    {
        Sanctum::actingAs($this->teacher);

        // Teachers can view documents
        $response = $this->getJson("/api/students/{$this->student->id}/documents");
        $response->assertStatus(200);

        // Teachers cannot upload documents (assuming role restriction)
        $file = UploadedFile::fake()->image('document.jpg');
        
        $response = $this->postJson("/api/students/{$this->student->id}/documents/upload", [
            'document_type' => 'aadhaar_card',
            'document' => $file
        ]);
        $response->assertStatus(403);
    }

    /** @test */
    public function can_extract_text_from_documents()
    {
        Sanctum::actingAs($this->admin);

        $document = StudentDocument::factory()->create([
            'student_id' => $this->student->id,
            'document_type' => 'aadhaar_card'
        ]);

        // Mock OCR service
        $this->mock(\App\Services\OcrService::class, function ($mock) {
            $mock->shouldReceive('extractText')->andReturn([
                'text' => 'John Doe\n123456789012\nDOB: 15/01/2005',
                'confidence' => 95,
                'extracted_fields' => [
                    'name' => 'John Doe',
                    'aadhaar_number' => '123456789012',
                    'dob' => '15/01/2005'
                ]
            ]);
        });

        $response = $this->postJson("/api/documents/{$document->id}/extract-text");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'extracted_text',
                    'confidence',
                    'extracted_fields'
                ]);
    }

    protected function tearDown(): void
    {
        Storage::fake('public');
        parent::tearDown();
    }
}