<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\ClassDataAudit;
use App\Models\ClassDataVersion;
use App\Models\ClassDataApproval;
use App\Http\Requests\ClassDataAuditRequest;
use App\Http\Requests\ApprovalActionRequest;
use App\Http\Requests\RollbackRequest;
// RefreshDatabase removed - using DatabaseTransactions in base TestCase
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ClassDataAuditUnitTest extends TestCase
{
    // RefreshDatabase removed - using DatabaseTransactions in base TestCase

    /** @test */
    public function test_class_data_audit_model_relationships()
    {
        $audit = ClassDataAudit::factory()->create();
        
        // Test user relationship
        $this->assertInstanceOf(\App\Models\User::class, $audit->user);
        
        // Test versions relationship
        $version = ClassDataVersion::factory()->create([
            'entity_type' => $audit->entity_type,
            'entity_id' => $audit->entity_id
        ]);
        
        $this->assertTrue($audit->versions()->exists());
        
        // Test approvals relationship
        $approval = ClassDataApproval::factory()->create([
            'audit_id' => $audit->id
        ]);
        
        $this->assertTrue($audit->approvals()->exists());
    }

    /** @test */
    public function test_class_data_audit_model_scopes()
    {
        // Create test data
        ClassDataAudit::factory()->create(['risk_level' => 'low']);
        ClassDataAudit::factory()->create(['risk_level' => 'high']);
        ClassDataAudit::factory()->create(['status' => 'pending_approval']);
        ClassDataAudit::factory()->create(['status' => 'approved']);

        // Test risk level scope
        $highRiskAudits = ClassDataAudit::highRisk()->get();
        $this->assertEquals(1, $highRiskAudits->count());
        $this->assertEquals('high', $highRiskAudits->first()->risk_level);

        // Test pending approval scope
        $pendingAudits = ClassDataAudit::pendingApproval()->get();
        $this->assertEquals(1, $pendingAudits->count());
        $this->assertEquals('pending_approval', $pendingAudits->first()->status);
    }

    /** @test */
    public function test_class_data_audit_model_mutators_and_accessors()
    {
        $audit = ClassDataAudit::factory()->create([
            'old_data' => json_encode(['name' => 'John']),
            'new_data' => json_encode(['name' => 'Jane']),
            'metadata' => json_encode(['source' => 'manual'])
        ]);

        // Test JSON accessors
        $this->assertIsArray($audit->old_data);
        $this->assertIsArray($audit->new_data);
        $this->assertIsArray($audit->metadata);
        
        $this->assertEquals(['name' => 'John'], $audit->old_data);
        $this->assertEquals(['name' => 'Jane'], $audit->new_data);
        $this->assertEquals(['source' => 'manual'], $audit->metadata);
    }

    /** @test */
    public function test_class_data_version_model_functionality()
    {
        $version = ClassDataVersion::factory()->create([
            'data_snapshot' => json_encode(['test' => 'data']),
            'version_number' => 1
        ]);

        // Test data snapshot accessor
        $this->assertIsArray($version->data_snapshot);
        $this->assertEquals(['test' => 'data'], $version->data_snapshot);

        // Test version increment
        $nextVersion = $version->createNextVersion(['updated' => 'data']);
        $this->assertEquals(2, $nextVersion->version_number);
    }

    /** @test */
    public function test_class_data_approval_model_workflow()
    {
        $approval = ClassDataApproval::factory()->create([
            'status' => 'pending',
            'approval_level' => 1
        ]);

        // Test approval status methods
        $this->assertTrue($approval->isPending());
        $this->assertFalse($approval->isApproved());
        $this->assertFalse($approval->isRejected());

        // Test approval action
        $approval->approve(1, 'Approved for testing');
        $this->assertTrue($approval->isApproved());
        $this->assertEquals('approved', $approval->status);
        $this->assertEquals(1, $approval->approved_by);
    }

    /** @test */
    public function test_class_data_audit_request_validation_rules()
    {
        $request = new ClassDataAuditRequest();
        $rules = $request->rules();

        // Test required fields
        $this->assertArrayHasKey('entity_type', $rules);
        $this->assertArrayHasKey('action', $rules);
        $this->assertArrayHasKey('risk_level', $rules);

        // Test validation with valid data
        $validData = [
            'entity_type' => 'student',
            'entity_id' => 1,
            'action' => 'update',
            'risk_level' => 'medium',
            'reason' => 'Test reason'
        ];

        $validator = Validator::make($validData, $rules);
        $this->assertFalse($validator->fails());

        // Test validation with invalid data
        $invalidData = [
            'entity_type' => '',
            'action' => 'invalid_action',
            'risk_level' => 'invalid_risk'
        ];

        $validator = Validator::make($invalidData, $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('entity_type', $validator->errors()->toArray());
        $this->assertArrayHasKey('action', $validator->errors()->toArray());
        $this->assertArrayHasKey('risk_level', $validator->errors()->toArray());
    }

    /** @test */
    public function test_approval_action_request_validation()
    {
        $request = new ApprovalActionRequest();
        $rules = $request->rules();

        // Test valid approval data
        $validData = [
            'action' => 'approve',
            'comments' => 'Looks good',
            'approval_id' => 1
        ];

        $validator = Validator::make($validData, $rules);
        $this->assertFalse($validator->fails());

        // Test bulk approval data
        $bulkData = [
            'action' => 'bulkApprove',
            'audit_ids' => [1, 2, 3],
            'comments' => 'Bulk approval'
        ];

        $validator = Validator::make($bulkData, $rules);
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function test_rollback_request_validation()
    {
        $request = new RollbackRequest();
        $rules = $request->rules();

        // Test valid rollback data
        $validData = [
            'version_id' => 1,
            'rollback_reason' => 'Data corruption detected',
            'rollback_type' => 'full',
            'priority' => 'high'
        ];

        $validator = Validator::make($validData, $rules);
        $this->assertFalse($validator->fails());

        // Test selective rollback data
        $selectiveData = [
            'version_id' => 1,
            'rollback_reason' => 'Partial rollback needed',
            'rollback_type' => 'selective',
            'selective_fields' => ['name', 'email'],
            'priority' => 'medium'
        ];

        $validator = Validator::make($selectiveData, $rules);
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function test_audit_risk_assessment_calculation()
    {
        // Test low risk calculation
        $lowRiskAudit = ClassDataAudit::factory()->create([
            'action' => 'view',
            'entity_type' => 'student'
        ]);
        $this->assertEquals('low', $lowRiskAudit->calculateRiskLevel());

        // Test high risk calculation
        $highRiskAudit = ClassDataAudit::factory()->create([
            'action' => 'delete',
            'entity_type' => 'class'
        ]);
        $this->assertEquals('high', $highRiskAudit->calculateRiskLevel());
    }

    /** @test */
    public function test_audit_data_comparison_methods()
    {
        $audit = ClassDataAudit::factory()->create([
            'old_data' => json_encode(['name' => 'John', 'grade' => 'A', 'email' => 'john@test.com']),
            'new_data' => json_encode(['name' => 'Jane', 'grade' => 'A', 'email' => 'jane@test.com'])
        ]);

        // Test changed fields detection
        $changedFields = $audit->getChangedFields();
        $this->assertContains('name', $changedFields);
        $this->assertContains('email', $changedFields);
        $this->assertNotContains('grade', $changedFields);

        // Test field change details
        $nameChange = $audit->getFieldChange('name');
        $this->assertEquals('John', $nameChange['old']);
        $this->assertEquals('Jane', $nameChange['new']);
    }

    /** @test */
    public function test_version_rollback_capability_check()
    {
        $version = ClassDataVersion::factory()->create([
            'created_at' => Carbon::now()->subDays(5)
        ]);

        // Test rollback capability
        $this->assertTrue($version->canRollback());

        // Test rollback with dependencies
        $dependentVersion = ClassDataVersion::factory()->create([
            'created_at' => Carbon::now()->subDays(1),
            'has_dependencies' => true
        ]);

        $this->assertFalse($dependentVersion->canRollback());
    }

    /** @test */
    public function test_approval_workflow_progression()
    {
        $audit = ClassDataAudit::factory()->create([
            'approval_required' => true
        ]);

        $approval = ClassDataApproval::factory()->create([
            'audit_id' => $audit->id,
            'approval_level' => 1,
            'status' => 'pending'
        ]);

        // Test workflow progression
        $nextLevel = $approval->getNextApprovalLevel();
        $this->assertEquals(2, $nextLevel);

        // Test completion check
        $this->assertFalse($approval->isWorkflowComplete());

        $approval->approve(1, 'First level approved');
        $this->assertTrue($approval->canProgressToNextLevel());
    }

    /** @test */
    public function test_audit_search_and_filtering()
    {
        // Create test data with different attributes
        ClassDataAudit::factory()->create([
            'entity_type' => 'student',
            'action' => 'create',
            'risk_level' => 'low',
            'created_at' => Carbon::now()->subDays(1)
        ]);

        ClassDataAudit::factory()->create([
            'entity_type' => 'class',
            'action' => 'update',
            'risk_level' => 'high',
            'created_at' => Carbon::now()->subDays(2)
        ]);

        // Test entity type filtering
        $studentAudits = ClassDataAudit::where('entity_type', 'student')->get();
        $this->assertEquals(1, $studentAudits->count());

        // Test risk level filtering
        $highRiskAudits = ClassDataAudit::where('risk_level', 'high')->get();
        $this->assertEquals(1, $highRiskAudits->count());

        // Test date range filtering
        $recentAudits = ClassDataAudit::where('created_at', '>=', Carbon::now()->subDays(1))->get();
        $this->assertEquals(1, $recentAudits->count());
    }

    /** @test */
    public function test_audit_export_data_formatting()
    {
        $audit = ClassDataAudit::factory()->create([
            'old_data' => json_encode(['name' => 'John']),
            'new_data' => json_encode(['name' => 'Jane']),
            'metadata' => json_encode(['source' => 'manual'])
        ]);

        // Test export data formatting
        $exportData = $audit->toExportArray();
        
        $this->assertArrayHasKey('id', $exportData);
        $this->assertArrayHasKey('entity_type', $exportData);
        $this->assertArrayHasKey('action', $exportData);
        $this->assertArrayHasKey('changes_summary', $exportData);
        $this->assertArrayHasKey('formatted_created_at', $exportData);
    }
}