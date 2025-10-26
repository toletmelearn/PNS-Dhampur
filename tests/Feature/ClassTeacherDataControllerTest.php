<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ClassData;
use App\Models\ClassDataAudit;
use App\Models\ClassDataVersion;
use App\Models\ClassDataApproval;
use App\Models\ChangeLog;
// RefreshDatabase removed - using DatabaseTransactions in base TestCase
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;

class ClassTeacherDataControllerTest extends TestCase
{
    use  WithFaker;

    protected $teacher;
    protected $admin;
    protected $classData;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users
        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'email' => 'teacher@test.com'
        ]);
        
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@test.com'
        ]);

        // Create test class data
        $this->classData = ClassData::factory()->create([
            'created_by' => $this->teacher->id,
            'updated_by' => $this->teacher->id
        ]);
    }

    /** @test */
    public function teacher_can_view_class_data_index()
    {
        Sanctum::actingAs($this->teacher);

        $response = $this->getJson('/api/class-data');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'class_name',
                            'subject',
                            'data',
                            'created_at',
                            'updated_at'
                        ]
                    ]
                ]);
    }

    /** @test */
    public function teacher_can_create_class_data_with_audit_trail()
    {
        Sanctum::actingAs($this->teacher);

        $classDataPayload = [
            'class_name' => 'Mathematics 10A',
            'subject' => 'Mathematics',
            'data' => [
                'lesson_plan' => 'Algebra basics',
                'homework' => 'Chapter 1 exercises'
            ],
            'metadata' => [
                'academic_year' => '2024-25',
                'term' => 'First'
            ]
        ];

        $response = $this->postJson('/api/class-data', $classDataPayload);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'message',
                    'class_data' => [
                        'id',
                        'class_name',
                        'subject',
                        'data',
                        'created_at'
                    ],
                    'audit_id'
                ]);

        // Verify audit trail was created
        $this->assertDatabaseHas('class_data_audits', [
            'auditable_type' => 'App\\Models\\ClassData',
            'event_type' => 'created',
            'user_id' => $this->teacher->id
        ]);

        // Verify version was created
        $this->assertDatabaseHas('class_data_versions', [
            'class_data_id' => $response->json('class_data.id'),
            'version_number' => 1
        ]);

        // Verify change log was created
        $this->assertDatabaseHas('change_logs', [
            'changeable_type' => 'App\\Models\\ClassData',
            'changeable_id' => $response->json('class_data.id'),
            'action' => 'created'
        ]);
    }

    /** @test */
    public function teacher_can_update_class_data_with_audit_trail()
    {
        Sanctum::actingAs($this->teacher);

        $updatePayload = [
            'class_name' => 'Updated Mathematics 10A',
            'subject' => 'Advanced Mathematics',
            'data' => [
                'lesson_plan' => 'Updated algebra basics',
                'homework' => 'Updated Chapter 1 exercises'
            ]
        ];

        $response = $this->putJson("/api/class-data/{$this->classData->id}", $updatePayload);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'class_data' => [
                        'id',
                        'class_name',
                        'subject',
                        'data'
                    ],
                    'audit_id'
                ]);

        // Verify audit trail was created for update
        $this->assertDatabaseHas('class_data_audits', [
            'auditable_type' => 'App\\Models\\ClassData',
            'auditable_id' => $this->classData->id,
            'event_type' => 'updated',
            'user_id' => $this->teacher->id
        ]);

        // Verify new version was created
        $this->assertDatabaseHas('class_data_versions', [
            'class_data_id' => $this->classData->id,
            'version_number' => 2
        ]);
    }

    /** @test */
    public function teacher_can_view_class_data_with_versions()
    {
        Sanctum::actingAs($this->teacher);

        // Create some audit records and versions
        $audit = ClassDataAudit::create([
            'auditable_type' => 'App\\Models\\ClassData',
            'auditable_id' => $this->classData->id,
            'event_type' => 'created',
            'user_id' => $this->teacher->id,
            'old_values' => null,
            'new_values' => $this->classData->toArray(),
            'changed_fields' => array_keys($this->classData->toArray()),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
            'timestamp' => now()->toIso8601String()
        ]);

        ClassDataVersion::create([
            'class_data_id' => $this->classData->id,
            'audit_id' => $audit->id,
            'version_number' => 1,
            'data_snapshot' => $this->classData->toArray(),
            'created_by' => $this->teacher->id
        ]);

        $response = $this->getJson("/api/class-data/{$this->classData->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'class_data' => [
                        'id',
                        'class_name',
                        'subject',
                        'data'
                    ],
                    'versions' => [
                        '*' => [
                            'id',
                            'version_number',
                            'data_snapshot',
                            'created_at'
                        ]
                    ]
                ]);
    }

    /** @test */
    public function teacher_can_view_audit_trail()
    {
        Sanctum::actingAs($this->teacher);

        // Create audit record
        ClassDataAudit::create([
            'auditable_type' => 'App\\Models\\ClassData',
            'auditable_id' => $this->classData->id,
            'event_type' => 'created',
            'user_id' => $this->teacher->id,
            'old_values' => null,
            'new_values' => $this->classData->toArray(),
            'changed_fields' => array_keys($this->classData->toArray()),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
            'timestamp' => now()->toIso8601String()
        ]);

        $response = $this->getJson("/api/class-data/{$this->classData->id}/audit");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'event_type',
                            'user_id',
                            'old_values',
                            'new_values',
                            'changed_fields',
                            'timestamp'
                        ]
                    ]
                ]);
    }

    /** @test */
    public function teacher_can_view_version_history()
    {
        Sanctum::actingAs($this->teacher);

        // Create audit and version records
        $audit = ClassDataAudit::create([
            'auditable_type' => 'App\\Models\\ClassData',
            'auditable_id' => $this->classData->id,
            'event_type' => 'created',
            'user_id' => $this->teacher->id,
            'old_values' => null,
            'new_values' => $this->classData->toArray(),
            'changed_fields' => array_keys($this->classData->toArray()),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
            'timestamp' => now()->toIso8601String()
        ]);

        ClassDataVersion::create([
            'class_data_id' => $this->classData->id,
            'audit_id' => $audit->id,
            'version_number' => 1,
            'data_snapshot' => $this->classData->toArray(),
            'created_by' => $this->teacher->id
        ]);

        $response = $this->getJson("/api/class-data/{$this->classData->id}/history");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'version_number',
                            'data_snapshot',
                            'created_at',
                            'audit' => [
                                'event_type',
                                'user_id',
                                'timestamp'
                            ]
                        ]
                    ]
                ]);
    }

    /** @test */
    public function admin_can_approve_class_data_changes()
    {
        Sanctum::actingAs($this->admin);

        // Create audit and approval records
        $audit = ClassDataAudit::create([
            'auditable_type' => 'App\\Models\\ClassData',
            'auditable_id' => $this->classData->id,
            'event_type' => 'updated',
            'user_id' => $this->teacher->id,
            'old_values' => ['class_name' => 'Old Name'],
            'new_values' => ['class_name' => 'New Name'],
            'changed_fields' => ['class_name'],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
            'timestamp' => now()->toIso8601String()
        ]);

        ClassDataApproval::create([
            'audit_id' => $audit->id,
            'approval_type' => 'data_change',
            'status' => 'pending',
            'requested_by' => $this->teacher->id,
            'assigned_to' => $this->admin->id,
            'priority' => 'medium',
            'request_reason' => 'Update class information'
        ]);

        $response = $this->postJson("/api/class-data/{$this->classData->id}/approve", [
            'approval_reason' => 'Changes look good',
            'digital_signature' => 'admin_signature_hash'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Class data changes approved successfully'
                ]);

        // Verify approval status was updated
        $this->assertDatabaseHas('class_data_approvals', [
            'audit_id' => $audit->id,
            'status' => 'approved',
            'approved_by' => $this->admin->id
        ]);

        // Verify audit status was updated
        $this->assertDatabaseHas('class_data_audits', [
            'id' => $audit->id,
            'approval_status' => 'approved'
        ]);
    }

    /** @test */
    public function admin_can_reject_class_data_changes()
    {
        Sanctum::actingAs($this->admin);

        // Create audit and approval records
        $audit = ClassDataAudit::create([
            'auditable_type' => 'App\\Models\\ClassData',
            'auditable_id' => $this->classData->id,
            'event_type' => 'updated',
            'user_id' => $this->teacher->id,
            'old_values' => ['class_name' => 'Old Name'],
            'new_values' => ['class_name' => 'New Name'],
            'changed_fields' => ['class_name'],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
            'timestamp' => now()->toIso8601String()
        ]);

        ClassDataApproval::create([
            'audit_id' => $audit->id,
            'approval_type' => 'data_change',
            'status' => 'pending',
            'requested_by' => $this->teacher->id,
            'assigned_to' => $this->admin->id,
            'priority' => 'medium',
            'request_reason' => 'Update class information'
        ]);

        $response = $this->postJson("/api/class-data/{$this->classData->id}/reject", [
            'rejection_reason' => 'Changes need more review',
            'digital_signature' => 'admin_signature_hash'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Class data changes rejected'
                ]);

        // Verify approval status was updated
        $this->assertDatabaseHas('class_data_approvals', [
            'audit_id' => $audit->id,
            'status' => 'rejected',
            'approved_by' => $this->admin->id
        ]);

        // Verify audit status was updated
        $this->assertDatabaseHas('class_data_audits', [
            'id' => $audit->id,
            'approval_status' => 'rejected'
        ]);
    }

    /** @test */
    public function unauthorized_user_cannot_access_class_data()
    {
        $response = $this->getJson('/api/class-data');
        $response->assertStatus(401);
    }

    /** @test */
    public function non_teacher_cannot_create_class_data()
    {
        $student = User::factory()->create(['role' => 'student']);
        Sanctum::actingAs($student);

        $response = $this->postJson('/api/class-data', [
            'class_name' => 'Test Class',
            'subject' => 'Test Subject',
            'data' => ['test' => 'data']
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function non_admin_cannot_approve_changes()
    {
        Sanctum::actingAs($this->teacher);

        $response = $this->postJson("/api/class-data/{$this->classData->id}/approve", [
            'approval_reason' => 'Test approval'
        ]);

        $response->assertStatus(403);
    }
}