<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
// RefreshDatabase removed - using DatabaseTransactions in base TestCase
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;

class StudentApiTest extends TestCase
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
    }

    /** @test */
    public function can_list_students_with_pagination()
    {
        Sanctum::actingAs($this->admin);

        Student::factory()->count(15)->create([
            'class_id' => $this->class->id,
            'section_id' => $this->section->id
        ]);

        $response = $this->getJson('/api/students?per_page=10');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'admission_number',
                            'class_name',
                            'section_name',
                            'verification_status'
                        ]
                    ],
                    'meta' => [
                        'current_page',
                        'per_page',
                        'total',
                        'last_page'
                    ]
                ]);

        $this->assertEquals(10, count($response->json('data')));
    }

    /** @test */
    public function can_search_students_by_name()
    {
        Sanctum::actingAs($this->admin);

        $searchStudent = Student::factory()->create([
            'name' => 'John Doe',
            'class_id' => $this->class->id,
            'section_id' => $this->section->id
        ]);

        Student::factory()->create([
            'name' => 'Jane Smith',
            'class_id' => $this->class->id,
            'section_id' => $this->section->id
        ]);

        $response = $this->getJson('/api/students?search=John');

        $response->assertStatus(200);
        $students = $response->json('data');
        
        $this->assertCount(1, $students);
        $this->assertEquals('John Doe', $students[0]['name']);
    }

    /** @test */
    public function can_filter_students_by_class()
    {
        Sanctum::actingAs($this->admin);

        $class2 = ClassModel::factory()->create(['name' => 'Class 11']);
        $section2 = Section::factory()->create([
            'class_id' => $class2->id,
            'name' => 'A'
        ]);

        Student::factory()->count(3)->create([
            'class_id' => $this->class->id,
            'section_id' => $this->section->id
        ]);

        Student::factory()->count(2)->create([
            'class_id' => $class2->id,
            'section_id' => $section2->id
        ]);

        $response = $this->getJson("/api/students?class_id={$this->class->id}");

        $response->assertStatus(200);
        $students = $response->json('data');
        
        $this->assertCount(4, $students); // 3 + 1 from setUp
    }

    /** @test */
    public function can_create_new_student()
    {
        Sanctum::actingAs($this->admin);

        $studentData = [
            'name' => 'New Student',
            'father_name' => 'Father Name',
            'mother_name' => 'Mother Name',
            'date_of_birth' => '2005-01-15',
            'gender' => 'male',
            'aadhaar_number' => '123456789012',
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'address' => '123 Test Street',
            'phone' => '9876543210',
            'email' => 'student@test.com'
        ];

        $response = $this->postJson('/api/students', $studentData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'name',
                        'admission_number',
                        'verification_status'
                    ]
                ]);

        $this->assertDatabaseHas('students', [
            'name' => 'New Student',
            'aadhaar_number' => '123456789012'
        ]);
    }

    /** @test */
    public function can_update_existing_student()
    {
        Sanctum::actingAs($this->admin);

        $updateData = [
            'name' => 'Updated Name',
            'phone' => '9876543211'
        ];

        $response = $this->putJson("/api/students/{$this->student->id}", $updateData);

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'name' => 'Updated Name'
                    ]
                ]);

        $this->student->refresh();
        $this->assertEquals('Updated Name', $this->student->name);
    }

    /** @test */
    public function can_delete_student()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->deleteJson("/api/students/{$this->student->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Student deleted successfully'
                ]);

        $this->assertSoftDeleted('students', ['id' => $this->student->id]);
    }

    /** @test */
    public function can_get_single_student_details()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson("/api/students/{$this->student->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'name',
                        'father_name',
                        'mother_name',
                        'date_of_birth',
                        'class_name',
                        'section_name',
                        'verification_status',
                        'documents'
                    ]
                ]);
    }

    /** @test */
    public function validation_fails_for_invalid_student_data()
    {
        Sanctum::actingAs($this->admin);

        $invalidData = [
            'name' => '', // Required field
            'aadhaar_number' => '123', // Invalid format
            'email' => 'invalid-email', // Invalid email
            'phone' => 'abc123' // Invalid phone
        ];

        $response = $this->postJson('/api/students', $invalidData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'name',
                    'aadhaar_number',
                    'email',
                    'phone'
                ]);
    }

    /** @test */
    public function teacher_has_limited_access_to_student_operations()
    {
        Sanctum::actingAs($this->teacher);

        // Teachers can view students
        $response = $this->getJson('/api/students');
        $response->assertStatus(200);

        // Teachers can view individual student
        $response = $this->getJson("/api/students/{$this->student->id}");
        $response->assertStatus(200);

        // Teachers cannot create students (assuming role-based restriction)
        $studentData = [
            'name' => 'New Student',
            'class_id' => $this->class->id
        ];

        $response = $this->postJson('/api/students', $studentData);
        $response->assertStatus(403);
    }

    /** @test */
    public function unauthorized_access_is_blocked()
    {
        $response = $this->getJson('/api/students');
        $response->assertStatus(401);

        $response = $this->postJson('/api/students', []);
        $response->assertStatus(401);
    }

    /** @test */
    public function can_bulk_update_students()
    {
        Sanctum::actingAs($this->admin);

        $students = Student::factory()->count(3)->create([
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'verification_status' => 'pending'
        ]);

        $bulkData = [
            'student_ids' => $students->pluck('id')->toArray(),
            'updates' => [
                'verification_status' => 'verified'
            ]
        ];

        $response = $this->postJson('/api/students/bulk-update', $bulkData);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Students updated successfully',
                    'updated_count' => 3
                ]);

        foreach ($students as $student) {
            $student->refresh();
            $this->assertEquals('verified', $student->verification_status);
        }
    }

    /** @test */
    public function can_export_students_data()
    {
        Sanctum::actingAs($this->admin);

        Student::factory()->count(5)->create([
            'class_id' => $this->class->id,
            'section_id' => $this->section->id
        ]);

        $response = $this->getJson('/api/students/export?format=csv');

        $response->assertStatus(200)
                ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    /** @test */
    public function can_get_student_statistics()
    {
        Sanctum::actingAs($this->admin);

        Student::factory()->count(10)->create([
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'verification_status' => 'verified'
        ]);

        Student::factory()->count(5)->create([
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'verification_status' => 'pending'
        ]);

        $response = $this->getJson('/api/students/statistics');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'total_students',
                    'verified_students',
                    'pending_verification',
                    'by_class',
                    'by_gender'
                ]);
    }

    /** @test */
    public function rate_limiting_works_for_student_endpoints()
    {
        Sanctum::actingAs($this->admin);

        // Make multiple requests to test rate limiting
        for ($i = 0; $i < 61; $i++) {
            $response = $this->getJson('/api/students');
            
            if ($i < 60) {
                $this->assertNotEquals(429, $response->getStatusCode());
            }
        }

        // The 61st request should be rate limited
        $response = $this->getJson('/api/students');
        $response->assertStatus(429);
    }
}