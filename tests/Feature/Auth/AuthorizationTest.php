<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;

class AuthorizationTest extends TestCase
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
    }

    /** @test */
    public function admin_can_access_all_student_endpoints()
    {
        Sanctum::actingAs($this->admin);

        // Can view students
        $response = $this->getJson('/api/students');
        $response->assertStatus(200);

        // Can create students
        $response = $this->postJson('/api/students', [
            'name' => 'New Student',
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'admission_number' => 'ADM001'
        ]);
        $response->assertStatus(201);

        // Can update students
        $response = $this->putJson("/api/students/{$this->student->id}", [
            'name' => 'Updated Student Name'
        ]);
        $response->assertStatus(200);

        // Can delete students
        $response = $this->deleteJson("/api/students/{$this->student->id}");
        $response->assertStatus(200);
    }

    /** @test */
    public function teacher_has_limited_student_access()
    {
        Sanctum::actingAs($this->teacher);

        // Can view students
        $response = $this->getJson('/api/students');
        $response->assertStatus(200);

        // Can view specific student
        $response = $this->getJson("/api/students/{$this->student->id}");
        $response->assertStatus(200);

        // Cannot create students (assuming role restriction)
        $response = $this->postJson('/api/students', [
            'name' => 'New Student',
            'class_id' => $this->class->id,
            'section_id' => $this->section->id
        ]);
        $response->assertStatus(403);

        // Cannot delete students
        $response = $this->deleteJson("/api/students/{$this->student->id}");
        $response->assertStatus(403);
    }

    /** @test */
    public function unauthorized_users_cannot_access_protected_endpoints()
    {
        // No authentication
        $response = $this->getJson('/api/students');
        $response->assertStatus(401);

        $response = $this->postJson('/api/students', []);
        $response->assertStatus(401);

        $response = $this->putJson("/api/students/{$this->student->id}", []);
        $response->assertStatus(401);

        $response = $this->deleteJson("/api/students/{$this->student->id}");
        $response->assertStatus(401);
    }

    /** @test */
    public function admin_can_manage_user_accounts()
    {
        Sanctum::actingAs($this->admin);

        // Can view all users
        $response = $this->getJson('/api/users');
        $response->assertStatus(200);

        // Can create users
        $response = $this->postJson('/api/users', [
            'name' => 'New Teacher',
            'email' => 'newteacher@example.com',
            'password' => 'password123',
            'role' => 'teacher'
        ]);
        $response->assertStatus(201);

        // Can update users
        $response = $this->putJson("/api/users/{$this->teacher->id}", [
            'name' => 'Updated Teacher Name'
        ]);
        $response->assertStatus(200);

        // Can deactivate users
        $response = $this->putJson("/api/users/{$this->teacher->id}/deactivate");
        $response->assertStatus(200);
    }

    /** @test */
    public function teacher_cannot_manage_user_accounts()
    {
        Sanctum::actingAs($this->teacher);

        // Cannot view all users
        $response = $this->getJson('/api/users');
        $response->assertStatus(403);

        // Cannot create users
        $response = $this->postJson('/api/users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'role' => 'teacher'
        ]);
        $response->assertStatus(403);

        // Cannot update other users
        $response = $this->putJson("/api/users/{$this->admin->id}", [
            'name' => 'Updated Name'
        ]);
        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_access_verification_endpoints()
    {
        Sanctum::actingAs($this->admin);

        // Can verify students
        $response = $this->postJson("/api/students/{$this->student->id}/verify", [
            'verified_data' => [
                'name' => $this->student->name,
                'father_name' => $this->student->father_name,
                'dob' => $this->student->dob->format('Y-m-d')
            ]
        ]);
        $response->assertStatus(200);

        // Can access Aadhaar verification
        $response = $this->postJson('/api/aadhaar/verify', [
            'student_id' => $this->student->id,
            'aadhaar_number' => '123456789012'
        ]);
        $response->assertStatus(200);

        // Can view verification history
        $response = $this->getJson("/api/students/{$this->student->id}/verification-history");
        $response->assertStatus(200);
    }

    /** @test */
    public function teacher_has_limited_verification_access()
    {
        Sanctum::actingAs($this->teacher);

        // Can view verification status
        $response = $this->getJson("/api/students/{$this->student->id}/verification-status");
        $response->assertStatus(200);

        // Cannot perform verification (assuming role restriction)
        $response = $this->postJson("/api/students/{$this->student->id}/verify", [
            'verified_data' => [
                'name' => $this->student->name
            ]
        ]);
        $response->assertStatus(403);

        // Cannot access Aadhaar verification
        $response = $this->postJson('/api/aadhaar/verify', [
            'student_id' => $this->student->id,
            'aadhaar_number' => '123456789012'
        ]);
        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_manage_documents()
    {
        Sanctum::actingAs($this->admin);

        // Can upload documents
        $response = $this->postJson("/api/students/{$this->student->id}/documents/upload", [
            'document_type' => 'aadhaar_card',
            'document' => \Illuminate\Http\UploadedFile::fake()->image('aadhaar.jpg')
        ]);
        $response->assertStatus(200);

        // Can verify documents
        $response = $this->getJson("/api/students/{$this->student->id}/documents");
        $response->assertStatus(200);

        // Can download documents
        if ($response->json('data')) {
            $documentId = $response->json('data.0.id');
            $response = $this->getJson("/api/students/{$this->student->id}/documents/{$documentId}/download");
            $response->assertStatus(200);
        }
    }

    /** @test */
    public function teacher_has_read_only_document_access()
    {
        Sanctum::actingAs($this->teacher);

        // Can view documents
        $response = $this->getJson("/api/students/{$this->student->id}/documents");
        $response->assertStatus(200);

        // Cannot upload documents
        $response = $this->postJson("/api/students/{$this->student->id}/documents/upload", [
            'document_type' => 'aadhaar_card',
            'document' => \Illuminate\Http\UploadedFile::fake()->image('aadhaar.jpg')
        ]);
        $response->assertStatus(403);
    }

    /** @test */
    public function users_can_only_access_their_own_profile()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Sanctum::actingAs($user1);

        // Can access own profile
        $response = $this->getJson('/api/auth/user');
        $response->assertStatus(200)
                ->assertJson(['id' => $user1->id]);

        // Cannot access other user's profile directly
        $response = $this->getJson("/api/users/{$user2->id}");
        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_access_system_settings()
    {
        Sanctum::actingAs($this->admin);

        // Can view system settings
        $response = $this->getJson('/api/settings');
        $response->assertStatus(200);

        // Can update system settings
        $response = $this->putJson('/api/settings', [
            'school_name' => 'Updated School Name',
            'academic_year' => '2024-25'
        ]);
        $response->assertStatus(200);

        // Can manage academic years
        $response = $this->postJson('/api/academic-years', [
            'year' => '2025-26',
            'start_date' => '2025-04-01',
            'end_date' => '2026-03-31'
        ]);
        $response->assertStatus(201);
    }

    /** @test */
    public function teacher_cannot_access_system_settings()
    {
        Sanctum::actingAs($this->teacher);

        // Cannot view system settings
        $response = $this->getJson('/api/settings');
        $response->assertStatus(403);

        // Cannot update system settings
        $response = $this->putJson('/api/settings', [
            'school_name' => 'Updated Name'
        ]);
        $response->assertStatus(403);

        // Cannot manage academic years
        $response = $this->postJson('/api/academic-years', [
            'year' => '2025-26'
        ]);
        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_access_reports_and_analytics()
    {
        Sanctum::actingAs($this->admin);

        // Can view student reports
        $response = $this->getJson('/api/reports/students');
        $response->assertStatus(200);

        // Can view verification statistics
        $response = $this->getJson('/api/reports/verification-stats');
        $response->assertStatus(200);

        // Can export data
        $response = $this->getJson('/api/exports/students');
        $response->assertStatus(200);

        // Can view audit logs
        $response = $this->getJson('/api/audit-logs');
        $response->assertStatus(200);
    }

    /** @test */
    public function teacher_has_limited_report_access()
    {
        Sanctum::actingAs($this->teacher);

        // Can view basic student reports
        $response = $this->getJson('/api/reports/students');
        $response->assertStatus(200);

        // Cannot view system audit logs
        $response = $this->getJson('/api/audit-logs');
        $response->assertStatus(403);

        // Cannot export sensitive data
        $response = $this->getJson('/api/exports/users');
        $response->assertStatus(403);
    }

    /** @test */
    public function role_based_middleware_works_correctly()
    {
        // Create routes that require specific roles
        $adminOnlyEndpoints = [
            '/api/users',
            '/api/settings',
            '/api/audit-logs'
        ];

        $teacherAllowedEndpoints = [
            '/api/students',
            '/api/reports/students'
        ];

        // Test admin access
        Sanctum::actingAs($this->admin);
        foreach ($adminOnlyEndpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            $this->assertNotEquals(403, $response->getStatusCode(), "Admin should access {$endpoint}");
        }

        // Test teacher restrictions
        Sanctum::actingAs($this->teacher);
        foreach ($adminOnlyEndpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            $this->assertEquals(403, $response->getStatusCode(), "Teacher should not access {$endpoint}");
        }

        // Test teacher allowed access
        foreach ($teacherAllowedEndpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            $this->assertNotEquals(403, $response->getStatusCode(), "Teacher should access {$endpoint}");
        }
    }

    /** @test */
    public function permission_based_access_control_works()
    {
        // Create custom permissions
        $viewStudentsPermission = Permission::create(['name' => 'view_students']);
        $editStudentsPermission = Permission::create(['name' => 'edit_students']);
        $deleteStudentsPermission = Permission::create(['name' => 'delete_students']);

        // Create custom role with specific permissions
        $customRole = Role::create(['name' => 'student_manager']);
        $customRole->permissions()->attach([
            $viewStudentsPermission->id,
            $editStudentsPermission->id
            // Note: No delete permission
        ]);

        $customUser = User::factory()->create(['role' => 'student_manager']);
        $customUser->roles()->attach($customRole->id);

        Sanctum::actingAs($customUser);

        // Should be able to view students
        $response = $this->getJson('/api/students');
        $response->assertStatus(200);

        // Should be able to edit students
        $response = $this->putJson("/api/students/{$this->student->id}", [
            'name' => 'Updated Name'
        ]);
        $response->assertStatus(200);

        // Should NOT be able to delete students
        $response = $this->deleteJson("/api/students/{$this->student->id}");
        $response->assertStatus(403);
    }

    /** @test */
    public function resource_ownership_is_enforced()
    {
        // Create two teachers
        $teacher1 = User::factory()->create(['role' => 'teacher']);
        $teacher2 = User::factory()->create(['role' => 'teacher']);

        // Assign students to teacher1's class
        $class1 = ClassModel::factory()->create(['teacher_id' => $teacher1->id]);
        $student1 = Student::factory()->create(['class_id' => $class1->id]);

        // Teacher1 can access their students
        Sanctum::actingAs($teacher1);
        $response = $this->getJson("/api/students/{$student1->id}");
        $response->assertStatus(200);

        // Teacher2 cannot access teacher1's students
        Sanctum::actingAs($teacher2);
        $response = $this->getJson("/api/students/{$student1->id}");
        $response->assertStatus(403);
    }

    /** @test */
    public function api_rate_limiting_is_enforced()
    {
        Sanctum::actingAs($this->admin);

        // Make multiple requests quickly
        $responses = [];
        for ($i = 0; $i < 100; $i++) {
            $responses[] = $this->getJson('/api/students');
        }

        // At least one should be rate limited
        $rateLimitedResponses = array_filter($responses, function ($response) {
            return $response->getStatusCode() === 429;
        });

        $this->assertGreaterThan(0, count($rateLimitedResponses), 'Rate limiting should be enforced');
    }

    /** @test */
    public function cors_headers_are_properly_set()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/students');

        $response->assertHeader('Access-Control-Allow-Origin');
        $response->assertHeader('Access-Control-Allow-Methods');
        $response->assertHeader('Access-Control-Allow-Headers');
    }

    /** @test */
    public function sensitive_data_is_not_exposed_in_responses()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/auth/user');

        $responseData = $response->json();

        // Should not contain sensitive fields
        $this->assertArrayNotHasKey('password', $responseData);
        $this->assertArrayNotHasKey('remember_token', $responseData);
        $this->assertArrayNotHasKey('two_factor_secret', $responseData);
    }

    /** @test */
    public function inactive_users_are_denied_access()
    {
        $inactiveUser = User::factory()->create([
            'is_active' => false
        ]);

        Sanctum::actingAs($inactiveUser);

        $response = $this->getJson('/api/students');
        $response->assertStatus(401);
    }

    /** @test */
    public function expired_tokens_are_rejected()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['*'], now()->subDay());

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken
        ])->getJson('/api/students');

        $response->assertStatus(401);
    }
}