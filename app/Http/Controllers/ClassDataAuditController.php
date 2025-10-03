<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ClassDataAuditRequest;
use App\Http\Requests\ApprovalActionRequest;
use App\Http\Requests\RollbackRequest;
use App\Models\ClassDataAudit;
use App\Models\ClassDataVersion;
use App\Models\ClassDataApproval;
use App\Models\ClassModel;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class ClassDataAuditController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view-class-audit')->only(['index', 'show', 'auditTrail']);
        $this->middleware('permission:manage-class-audit')->only(['store', 'update', 'destroy']);
        $this->middleware('permission:approve_audit_changes')->only(['approve', 'reject', 'bulkApprove']);
        $this->middleware('permission:view_audit_statistics')->only(['analytics']);
        $this->middleware('permission:export_audit_reports')->only(['export']);
    }

    /**
     * Perform rollback operation for an audit record.
     *
     * @param Request $request
     * @param ClassDataAudit $audit
     * @return \Illuminate\Http\JsonResponse
     */
    public function rollback(Request $request, ClassDataAudit $audit)
    {
        try {
            DB::beginTransaction();

            // Validate the request
            $validated = $request->validate([
                'rollback_reason' => 'required|string|max:500',
                'rollback_type' => 'required|in:full,partial,selective',
                'priority' => 'required|in:low,medium,high,critical',
                'create_backup' => 'boolean',
                'selective_fields' => 'array',
                'approval_required' => 'boolean',
                'notify_stakeholders' => 'boolean',
                'scheduled_at' => 'nullable|date|after:now',
                'estimated_downtime' => 'nullable|integer|min:0',
                'emergency_contact' => 'nullable|string|max:255'
            ]);

            // Create backup if requested
            if ($request->input('create_backup', true)) {
                $this->createAuditBackup($audit);
            }

            // Perform the rollback based on audit data
            $rollbackResult = $this->performAuditRollback($audit, $request);

            // Create audit record for the rollback
            $rollbackAudit = ClassDataAudit::create([
                'auditable_type' => $audit->auditable_type,
                'auditable_id' => $audit->auditable_id,
                'event_type' => 'rollback',
                'old_values' => $rollbackResult['current_data'] ?? [],
                'new_values' => $rollbackResult['rollback_data'] ?? [],
                'risk_level' => $request->input('priority') === 'critical' ? 'critical' : 'high',
                'description' => $request->input('rollback_reason'),
                'user_id' => Auth::id(),
                'user_name' => Auth::user()->name,
                'user_role' => Auth::user()->role ?? 'unknown',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => session()->getId(),
                'metadata' => [
                    'rollback_type' => $request->input('rollback_type'),
                    'selective_fields' => $request->input('selective_fields'),
                    'priority' => $request->input('priority'),
                    'scheduled_at' => $request->input('scheduled_at'),
                    'estimated_downtime' => $request->input('estimated_downtime'),
                    'emergency_contact' => $request->input('emergency_contact'),
                    'original_audit_id' => $audit->id
                ]
            ]);

            // Create approval workflow if required
            if ($request->input('approval_required')) {
                // TODO: Implement approval workflow system
                Log::info('Rollback approval workflow requested', [
                    'rollback_audit_id' => $rollbackAudit->id,
                    'original_audit_id' => $audit->id
                ]);
            }

            // Send notifications if requested
            if ($request->input('notify_stakeholders', true)) {
                // TODO: Implement notification system
                Log::info('Rollback stakeholder notification requested', [
                    'rollback_audit_id' => $rollbackAudit->id,
                    'original_audit_id' => $audit->id
                ]);
            }

            DB::commit();

            Log::info('Audit rollback operation completed', [
                'rollback_audit_id' => $rollbackAudit->id,
                'original_audit_id' => $audit->id,
                'rollback_type' => $request->input('rollback_type'),
                'priority' => $request->input('priority'),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Rollback operation completed successfully.',
                'data' => [
                    'rollback_audit_id' => $rollbackAudit->id,
                    'original_audit_id' => $audit->id,
                    'rollback_type' => $request->input('rollback_type'),
                    'affected_records' => $rollbackResult['affected_count'] ?? 0,
                    'backup_created' => $request->input('create_backup', true),
                    'requires_approval' => $request->input('approval_required', false)
                ]
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Audit rollback operation failed', [
                'error' => $e->getMessage(),
                'audit_id' => $audit->id,
                'user_id' => Auth::id(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Rollback operation failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Create backup for audit rollback
     */
    private function createAuditBackup(ClassDataAudit $audit)
    {
        // Create a backup version of the current state
        ClassDataVersion::create([
            'audit_id' => $audit->id,
            'version_number' => ClassDataVersion::where('audit_id', $audit->id)->count() + 1,
            'data_snapshot' => $audit->new_values,
            'created_by' => Auth::id(),
            'created_at' => now(),
            'backup_type' => 'pre_rollback'
        ]);
    }

    /**
     * Perform the actual rollback operation
     */
    private function performAuditRollback(ClassDataAudit $audit, Request $request)
    {
        $rollbackType = $request->input('rollback_type', 'full');
        $currentData = [];
        $rollbackData = [];
        $affectedCount = 0;

        try {
            // Get the auditable model
            $modelClass = $audit->auditable_type;
            $model = $modelClass::find($audit->auditable_id);

            if (!$model) {
                throw new \Exception("Model not found for rollback");
            }

            $currentData = $model->toArray();

            switch ($rollbackType) {
                case 'full':
                    // Rollback all changes to old values
                    if ($audit->old_values) {
                        $oldValues = is_string($audit->old_values) ? json_decode($audit->old_values, true) : $audit->old_values;
                        $model->fill($oldValues);
                        $model->save();
                        $rollbackData = $oldValues;
                        $affectedCount = 1;
                    }
                    break;

                case 'selective':
                    // Rollback only selected fields
                    $selectiveFields = $request->input('selective_fields', []);
                    if (!empty($selectiveFields) && $audit->old_values) {
                        $oldValues = is_string($audit->old_values) ? json_decode($audit->old_values, true) : $audit->old_values;
                        foreach ($selectiveFields as $field) {
                            if (isset($oldValues[$field])) {
                                $model->$field = $oldValues[$field];
                                $rollbackData[$field] = $oldValues[$field];
                            }
                        }
                        $model->save();
                        $affectedCount = 1;
                    }
                    break;

                case 'partial':
                    // Custom partial rollback logic
                    // This would depend on specific business requirements
                    $rollbackData = $this->performPartialRollback($model, $audit, $request);
                    $affectedCount = 1;
                    break;
            }

        } catch (\Exception $e) {
            Log::error('Rollback operation failed', [
                'audit_id' => $audit->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }

        return [
            'current_data' => $currentData,
            'rollback_data' => $rollbackData,
            'affected_count' => $affectedCount
        ];
    }

    /**
     * Perform partial rollback with custom logic
     */
    private function performPartialRollback($model, ClassDataAudit $audit, Request $request)
    {
        // Implement custom partial rollback logic based on business requirements
        // This is a placeholder implementation
        return $audit->old_values ?? [];
    }

    /**
     * Display the class data audit dashboard
     */
    public function index(Request $request)
    {
        $query = ClassDataAudit::with(['user', 'approvedBy', 'versions', 'approvals']);

        // Apply filters
        if ($request->filled('auditable_type')) {
            $query->where('auditable_type', $request->auditable_type);
        }

        if ($request->filled('auditable_id')) {
            $query->where('auditable_id', $request->auditable_id);
        }

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('risk_level')) {
            $query->where('risk_level', $request->risk_level);
        }

        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->approval_status);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $audits = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get dashboard statistics
        $statistics = $this->getDashboardStatistics();

        // Get filter options
        $auditableTypes = ClassDataAudit::distinct()->pluck('auditable_type')->filter();
        $eventTypes = ClassDataAudit::distinct()->pluck('event_type')->filter();
        $users = User::orderBy('name')->get();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'audits' => $audits,
                    'statistics' => $statistics,
                    'filters' => [
                        'auditable_types' => $auditableTypes,
                        'event_types' => $eventTypes,
                        'users' => $users
                    ]
                ]
            ]);
        }

        return view('class-data-audit.index', compact(
            'audits', 'statistics', 'auditableTypes', 'eventTypes', 'users'
        ));
    }

    /**
     * Show detailed audit information
     */
    public function show(ClassDataAudit $audit)
    {
        $audit->load(['user', 'approvedBy', 'versions', 'approvals.requestedBy', 'approvals.assignedTo', 'approvals.approvedBy']);

        // Get related audits (same entity)
        $relatedAudits = ClassDataAudit::where('auditable_type', $audit->auditable_type)
                                     ->where('auditable_id', $audit->auditable_id)
                                     ->where('id', '!=', $audit->id)
                                     ->with(['user', 'approvedBy'])
                                     ->orderBy('created_at', 'desc')
                                     ->limit(10)
                                     ->get();

        // Get version history
        $versions = $audit->versions()->with(['createdBy'])->orderBy('version_number', 'desc')->get();

        // Get approval workflow
        $approvals = $audit->approvals()->with(['requestedBy', 'assignedTo', 'approvedBy'])->orderBy('created_at', 'desc')->get();

        return view('class-data-audit.show', compact('audit', 'relatedAudits', 'versions', 'approvals'));
    }

    /**
     * Store a new audit record.
     *
     * @param ClassDataAuditRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ClassDataAuditRequest $request)
    {
        try {
            DB::beginTransaction();

            $auditData = $request->validated();
            $auditData['user_id'] = Auth::id();
            $auditData['ip_address'] = $request->ip();
            $auditData['user_agent'] = $request->userAgent();
            $auditData['session_id'] = session()->getId();

            $audit = ClassDataAudit::create($auditData);

            // Create version if data changes are provided
            if ($request->has('data_changes') && !empty($request->data_changes)) {
                $this->createVersion($audit, $request->data_changes);
            }

            // Create approval workflow if required
            if ($this->requiresApproval($audit)) {
                $this->createApprovalWorkflow($audit, $request);
            }

            DB::commit();

            Log::info('Class data audit record created', [
                'audit_id' => $audit->id,
                'user_id' => Auth::id(),
                'entity_type' => $audit->entity_type,
                'action' => $audit->action
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Audit record created successfully.',
                'data' => [
                    'audit_id' => $audit->id,
                    'requires_approval' => $audit->requires_approval,
                    'risk_level' => $audit->risk_level
                ]
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create audit record', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create audit record. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Create audit log for class data changes
     */
    public function createAuditLog(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'auditable_type' => 'required|string',
            'auditable_id' => 'required|integer',
            'event_type' => 'required|in:created,updated,deleted,restored,bulk_update,bulk_delete,import,export,merge,split',
            'old_values' => 'nullable|array',
            'new_values' => 'nullable|array',
            'changed_fields' => 'nullable|array',
            'description' => 'nullable|string',
            'metadata' => 'nullable|array',
            'risk_level' => 'nullable|in:low,medium,high,critical',
            'requires_approval' => 'boolean',
            'batch_id' => 'nullable|string',
            'parent_audit_id' => 'nullable|exists:class_data_audits,id',
            'tags' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $audit = ClassDataAudit::createAuditLog(
                $request->auditable_type,
                $request->auditable_id,
                $request->event_type,
                $request->old_values,
                $request->new_values,
                $request->changed_fields,
                $request->description,
                $request->metadata,
                $request->risk_level ?? 'low',
                $request->requires_approval ?? false,
                $request->batch_id,
                $request->parent_audit_id,
                $request->tags
            );

            // Create version if needed
            if ($request->create_version) {
                $version = ClassDataVersion::createVersion(
                    $audit->id,
                    $request->new_values ?? [],
                    $request->changes_summary ?? 'Automatic version creation',
                    $request->version_type ?? 'automatic'
                );
            }

            // Create approval request if needed
            if ($request->requires_approval) {
                $approval = ClassDataApproval::createApprovalRequest(
                    $audit->id,
                    $request->approval_type ?? 'data_change',
                    $request->assigned_to,
                    $request->priority ?? 'normal',
                    $request->deadline,
                    $request->request_reason
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Audit log created successfully',
                'data' => [
                    'audit' => $audit,
                    'version' => $version ?? null,
                    'approval' => $approval ?? null
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create audit log: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get version history for an audit
     */
    public function versionHistory(ClassDataAudit $audit)
    {
        $versions = $audit->versions()
                         ->with(['createdBy'])
                         ->orderBy('version_number', 'desc')
                         ->get();

        return response()->json([
            'success' => true,
            'data' => $versions
        ]);
    }

    /**
     * Compare two versions
     */
    public function compareVersions(Request $request, ClassDataAudit $audit)
    {
        $validator = Validator::make($request->all(), [
            'version1_id' => 'required|exists:class_data_versions,id',
            'version2_id' => 'required|exists:class_data_versions,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $version1 = ClassDataVersion::find($request->version1_id);
        $version2 = ClassDataVersion::find($request->version2_id);

        $comparison = $version1->compareWith($version2);

        return response()->json([
            'success' => true,
            'data' => [
                'version1' => $version1,
                'version2' => $version2,
                'comparison' => $comparison
            ]
        ]);
    }

    /**
     * Perform rollback operation.
     *
     * @param RollbackRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function rollbackToVersion(RollbackRequest $request, ClassDataAudit $audit)
    {
        try {
            DB::beginTransaction();

            $versionId = $request->validated()['version_id'];
            $version = ClassDataVersion::findOrFail($versionId);

            // Create backup if requested
            if ($request->input('create_backup', true)) {
                $this->createRollbackBackup($version);
            }

            // Perform the rollback based on type
            $rollbackResult = $this->performRollback($version, $request);

            // Create audit record for the rollback
            $rollbackAudit = ClassDataAudit::create([
                'entity_type' => $version->entity_type,
                'entity_id' => $version->entity_id,
                'action' => 'rollback',
                'old_data' => json_encode($rollbackResult['current_data'] ?? []),
                'new_data' => json_encode($rollbackResult['rollback_data'] ?? []),
                'risk_level' => $request->input('priority') === 'critical' ? 'critical' : 'high',
                'reason' => $request->input('rollback_reason'),
                'user_id' => Auth::id(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => session()->getId(),
                'metadata' => json_encode([
                    'rollback_type' => $request->input('rollback_type'),
                    'selective_fields' => $request->input('selective_fields'),
                    'priority' => $request->input('priority'),
                    'scheduled_at' => $request->input('scheduled_at'),
                    'estimated_downtime' => $request->input('estimated_downtime'),
                    'emergency_contact' => $request->input('emergency_contact'),
                ])
            ]);

            // Create approval workflow if required
            if ($request->input('approval_required')) {
                // TODO: Implement approval workflow system
                Log::info('Rollback approval workflow requested', [
                    'rollback_audit_id' => $rollbackAudit->id,
                    'original_audit_id' => $audit->id
                ]);
            }

            // Send notifications if requested
            if ($request->input('notify_stakeholders', true)) {
                // TODO: Implement notification system
                Log::info('Rollback stakeholder notification requested', [
                    'rollback_audit_id' => $rollbackAudit->id,
                    'original_audit_id' => $audit->id
                ]);
            }

            DB::commit();

            Log::info('Rollback operation completed', [
                'rollback_audit_id' => $rollbackAudit->id,
                'version_id' => $version->id,
                'rollback_type' => $request->input('rollback_type'),
                'priority' => $request->input('priority'),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Rollback operation completed successfully.',
                'data' => [
                    'rollback_audit_id' => $rollbackAudit->id,
                    'version_id' => $version->id,
                    'rollback_type' => $request->input('rollback_type'),
                    'affected_records' => $rollbackResult['affected_count'] ?? 0,
                    'backup_created' => $request->input('create_backup', true),
                    'requires_approval' => $request->input('approval_required', false)
                ]
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Rollback operation failed', [
                'error' => $e->getMessage(),
                'version_id' => $request->input('version_id'),
                'user_id' => Auth::id(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Rollback operation failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get approval workflow status
     */
    public function approvalStatus(ClassDataAudit $audit)
    {
        $approvals = $audit->approvals()
                          ->with(['requestedBy', 'assignedTo', 'approvedBy'])
                          ->orderBy('created_at', 'desc')
                          ->get();

        $currentApproval = $approvals->where('status', 'pending')->first();

        return response()->json([
            'success' => true,
            'data' => [
                'current_approval' => $currentApproval,
                'approval_history' => $approvals,
                'can_approve' => $this->canApprove($currentApproval),
                'can_reject' => $this->canReject($currentApproval)
            ]
        ]);
    }

    /**
     * Approve an audit record.
     *
     * @param ApprovalActionRequest $request
     * @param ClassDataAudit $audit
     * @return \Illuminate\Http\JsonResponse
     */
    public function approve(ApprovalActionRequest $request, ClassDataAudit $audit)
    {
        try {
            DB::beginTransaction();

            $approval = ClassDataApproval::where('audit_id', $audit->id)->first();
            if (!$approval) {
                return response()->json(['error' => 'Approval record not found'], 404);
            }

            // Check if user can approve this specific audit
            if (!$this->canApprove($approval)) {
                return response()->json(['error' => 'Unauthorized to approve this audit'], 403);
            }

            // Update approval status
            $approval->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'comments' => $request->comments
            ]);

            // Update audit status
            $audit->update([
                'approval_status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now()
            ]);

            // Notify stakeholders
            $this->notifyStakeholders($audit, 'approved');

            // Log the approval
            Log::info('Class data audit approved', [
                'audit_id' => $audit->id,
                'approved_by' => auth()->id(),
                'comments' => $request->comments
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Audit approved successfully',
                'audit' => $audit->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error approving audit: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to approve audit'], 500);
        }
    }

    /**
     * Reject an audit record.
     *
     * @param ApprovalActionRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reject(ApprovalActionRequest $request)
    {
        try {
            DB::beginTransaction();

            $approvalId = $request->validated()['approval_id'];
            $approval = ClassDataApproval::findOrFail($approvalId);

            // Verify approval is in pending status
            if ($approval->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'This approval has already been processed.'
                ], 400);
            }

            // Update approval status
            $approval->update([
                'status' => 'rejected',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'approval_reason' => $request->input('rejection_reason'),
                'alternative_suggestions' => $request->input('alternative_suggestions'),
            ]);

            // Update related audit record
            $approval->audit->update([
                'status' => 'rejected',
                'approved_at' => now(),
                'approved_by' => Auth::id()
            ]);

            // Send notifications if requested
            if ($request->input('notify_requester', true)) {
                $this->notifyRequester($approval, 'rejected');
            }

            DB::commit();

            Log::info('Audit approval rejected', [
                'approval_id' => $approval->id,
                'audit_id' => $approval->audit_id,
                'rejected_by' => Auth::id(),
                'reason' => $request->input('rejection_reason')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Audit record rejected successfully.',
                'data' => [
                    'approval_id' => $approval->id,
                    'status' => $approval->status,
                    'rejected_at' => $approval->approved_at ? $approval->approved_at->toISOString() : null
                ]
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reject audit record', [
                'error' => $e->getMessage(),
                'approval_id' => $request->input('approval_id'),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process rejection. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Delegate approval to another user
     */
    public function delegate(Request $request, ClassDataAudit $audit)
    {
        $validator = Validator::make($request->all(), [
            'approval_id' => 'required|exists:class_data_approvals,id',
            'delegate_to' => 'required|exists:users,id',
            'delegation_reason' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $approval = ClassDataApproval::find($request->approval_id);
            
            $result = $approval->delegate(
                $request->delegate_to,
                $request->delegation_reason
            );

            return response()->json([
                'success' => true,
                'message' => 'Approval delegated successfully',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delegate approval: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk approve multiple changes
     */
    public function bulkApprove(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'approval_ids' => 'required|array',
            'approval_ids.*' => 'exists:class_data_approvals,id',
            'approval_reason' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $results = [];
            $approvals = ClassDataApproval::whereIn('id', $request->approval_ids)->get();

            foreach ($approvals as $approval) {
                if ($this->canApprove($approval)) {
                    $result = $approval->approve($request->approval_reason);
                    
                    // Update related audit
                    $approval->audit->update([
                        'approval_status' => 'approved',
                        'approved_by' => Auth::id(),
                        'approved_at' => now()
                    ]);

                    $results[] = [
                        'approval_id' => $approval->id,
                        'status' => 'approved',
                        'result' => $result
                    ];
                } else {
                    $results[] = [
                        'approval_id' => $approval->id,
                        'status' => 'skipped',
                        'reason' => 'No permission to approve'
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bulk approval completed',
                'data' => $results
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk approve: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle bulk actions on audit records
     */
    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:bulkApprove,bulkReject,bulkDelete',
            'audit_ids' => 'required|array',
            'audit_ids.*' => 'exists:class_data_audits,id',
            'comments' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $audits = ClassDataAudit::whereIn('id', $request->audit_ids)->get();
            $results = [];

            foreach ($audits as $audit) {
                switch ($request->action) {
                    case 'bulkApprove':
                        if ($this->canApproveAudit($audit)) {
                            $audit->update([
                                'status' => 'approved',
                                'approved_by' => Auth::id(),
                                'approved_at' => now(),
                                'approval_comments' => $request->comments
                            ]);
                            $results[] = [
                                'audit_id' => $audit->id,
                                'status' => 'approved'
                            ];
                        } else {
                            $results[] = [
                                'audit_id' => $audit->id,
                                'status' => 'skipped',
                                'reason' => 'No permission to approve'
                            ];
                        }
                        break;

                    case 'bulkReject':
                        if ($this->canApproveAudit($audit)) {
                            $audit->update([
                                'status' => 'rejected',
                                'rejected_by' => Auth::id(),
                                'rejected_at' => now(),
                                'rejection_reason' => $request->comments
                            ]);
                            $results[] = [
                                'audit_id' => $audit->id,
                                'status' => 'rejected'
                            ];
                        } else {
                            $results[] = [
                                'audit_id' => $audit->id,
                                'status' => 'skipped',
                                'reason' => 'No permission to reject'
                            ];
                        }
                        break;

                    case 'bulkDelete':
                        if ($this->canDeleteAudit($audit)) {
                            $audit->delete();
                            $results[] = [
                                'audit_id' => $audit->id,
                                'status' => 'deleted'
                            ];
                        } else {
                            $results[] = [
                                'audit_id' => $audit->id,
                                'status' => 'skipped',
                                'reason' => 'No permission to delete'
                            ];
                        }
                        break;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bulk action completed successfully',
                'data' => $results
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to perform bulk action: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if user can approve an audit
     */
    private function canApproveAudit(ClassDataAudit $audit)
    {
        $user = Auth::user();
        return in_array($user->role, ['admin', 'principal']) || 
               $user->hasPermission('approve_audit_changes');
    }

    /**
     * Check if user can delete an audit
     */
    private function canDeleteAudit(ClassDataAudit $audit)
    {
        $user = Auth::user();
        return $user->role === 'admin' || 
               $user->hasPermission('delete_audit_records');
    }

    /**
     * Get audit statistics for dashboard
     */
    public function analytics(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->subDays(30));
        $dateTo = $request->get('date_to', now());

        $statistics = $this->getDashboardStatistics();

        // Additional analytics data
        $analytics = [
            'audit_trends' => ClassDataAudit::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
            
            'risk_trends' => ClassDataAudit::selectRaw('risk_level, DATE(created_at) as date, COUNT(*) as count')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->groupBy('risk_level', 'date')
                ->orderBy('date')
                ->get(),
            
            'user_activity' => ClassDataAudit::join('users', 'class_data_audits.user_id', '=', 'users.id')
                ->selectRaw('users.name, COUNT(*) as audit_count')
                ->whereBetween('class_data_audits.created_at', [$dateFrom, $dateTo])
                ->groupBy('users.id', 'users.name')
                ->orderBy('audit_count', 'desc')
                ->limit(10)
                ->get(),
        ];

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => array_merge($statistics, $analytics)
            ]);
        }

        return view('class-data-audit.analytics', compact('statistics', 'analytics'));
    }

    /**
     * Export audit data
     */
    public function export(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'format' => 'required|in:csv,excel,pdf',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'filters' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $format = $request->get('format', 'csv');
            
            if ($format === 'csv') {
                // Return CSV directly
                $audits = ClassDataAudit::with(['user', 'class'])->get();
                
                $csvData = "ID,User,Class,Action,Field,Old Value,New Value,Created At\n";
                
                foreach ($audits as $audit) {
                    $csvData .= sprintf(
                        "%d,%s,%s,%s,%s,%s,%s,%s\n",
                        $audit->id,
                        $audit->user ? $audit->user->name : 'Unknown',
                        $audit->class ? $audit->class->name : 'Unknown',
                        $audit->action,
                        $audit->field_name ?? '',
                        $audit->old_value ?? '',
                        $audit->new_value ?? '',
                        $audit->created_at->format('Y-m-d H:i:s')
                    );
                }
                
                return response($csvData)
                    ->header('Content-Type', 'text/csv; charset=UTF-8')
                    ->header('Content-Disposition', 'attachment; filename="class_data_audit_export.csv"');
            }
            
            // For other formats, return JSON response with download URL
            return response()->json([
                'success' => true,
                'message' => 'Export initiated successfully',
                'download_url' => route('class-data-audit.download-export', ['token' => 'temp-token'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get dashboard statistics
     */
    private function getDashboardStatistics()
    {
        $today = now()->startOfDay();
        $thisWeek = now()->startOfWeek();
        $thisMonth = now()->startOfMonth();

        return [
            'total_audits' => ClassDataAudit::count(),
            'today_audits' => ClassDataAudit::where('created_at', '>=', $today)->count(),
            'week_audits' => ClassDataAudit::where('created_at', '>=', $thisWeek)->count(),
            'month_audits' => ClassDataAudit::where('created_at', '>=', $thisMonth)->count(),
            
            'pending_approvals' => ClassDataAudit::where('approval_status', 'pending')->count(),
            'approved_changes' => ClassDataAudit::where('approval_status', 'approved')->count(),
            'rejected_changes' => ClassDataAudit::where('approval_status', 'rejected')->count(),
            
            'high_risk_changes' => ClassDataAudit::where('risk_level', 'high')->count(),
            'critical_changes' => ClassDataAudit::where('risk_level', 'critical')->count(),
            
            'total_versions' => ClassDataVersion::count(),
            'active_approvals' => ClassDataApproval::whereIn('status', ['pending', 'delegated'])->count(),
            
            'event_types' => ClassDataAudit::selectRaw('event_type, COUNT(*) as count')
                                         ->groupBy('event_type')
                                         ->pluck('count', 'event_type'),
            
            'risk_distribution' => ClassDataAudit::selectRaw('risk_level, COUNT(*) as count')
                                                ->groupBy('risk_level')
                                                ->pluck('count', 'risk_level'),
        ];
    }

    /**
     * Download exported file
     */
    public function downloadExport($token)
    {
        // For now, return a simple CSV response
        // In a real implementation, you would validate the token and serve the actual file
        $audits = ClassDataAudit::with(['user', 'class'])->get();
        
        $csvData = "ID,User,Class,Action,Field,Old Value,New Value,Created At\n";
        
        foreach ($audits as $audit) {
            $csvData .= sprintf(
                "%d,%s,%s,%s,%s,%s,%s,%s\n",
                $audit->id,
                $audit->user ? $audit->user->name : 'Unknown',
                $audit->class ? $audit->class->name : 'Unknown',
                $audit->action,
                $audit->field_name ?? '',
                $audit->old_value ?? '',
                $audit->new_value ?? '',
                $audit->created_at->format('Y-m-d H:i:s')
            );
        }
        
        return response($csvData)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="class_data_audit_export.csv"');
    }

    /**
     * Check if user can approve a change
     */
    private function canApprove($approval)
    {
        if (!$approval || $approval->status !== 'pending') {
            return false;
        }

        $user = Auth::user();
        
        // Check if user is assigned to this approval
        if ($approval->assigned_to && $approval->assigned_to === $user->id) {
            return true;
        }

        // Check if user has general approval permission
        if ($user->hasPermission('approve_audit_changes')) {
            return true;
        }

        // Check if user is admin or principal (fallback)
        return in_array($user->role, ['admin', 'principal']);
    }

    /**
     * Check if user can reject a change
     */
    private function canReject($approval)
    {
        return $this->canApprove($approval); // Same logic for now
    }

    /**
     * Notify stakeholders about audit status changes
     */
    private function notifyStakeholders($audit, $status)
    {
        try {
            // Get the audit requester
            $requester = $audit->user;
            
            // Prepare notification data
            $notificationData = [
                'audit_id' => $audit->id,
                'status' => $status,
                'entity_type' => $audit->auditable_type,
                'entity_id' => $audit->auditable_id,
                'approved_by' => auth()->user()->name ?? 'System',
                'approved_at' => now()->format('Y-m-d H:i:s')
            ];

            // Notify the requester if they exist and are different from the approver
            if ($requester && $requester->id !== auth()->id()) {
                // You can implement actual notification logic here
                // For now, we'll just log it
                Log::info('Audit status notification sent', [
                    'recipient' => $requester->email,
                    'audit_id' => $audit->id,
                    'status' => $status
                ]);
            }

            // Notify other stakeholders (admins, principals) if needed
            // This is a placeholder for future notification implementation
            
        } catch (\Exception $e) {
            // Log notification errors but don't fail the approval process
            Log::warning('Failed to send audit notification', [
                'audit_id' => $audit->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Notify the requester about the approval/rejection status
     *
     * @param ClassDataApproval $approval
     * @param string $status
     * @return void
     */
    private function notifyRequester(ClassDataApproval $approval, string $status): void
    {
        try {
            // Get the audit record to find the requester
            $audit = $approval->audit;
            
            if (!$audit || !$audit->user) {
                Log::warning('Cannot notify requester - audit or user not found', [
                    'approval_id' => $approval->id,
                    'audit_id' => $approval->audit_id
                ]);
                return;
            }

            $requester = $audit->user;
            $message = $status === 'approved' 
                ? "Your audit request has been approved." 
                : "Your audit request has been rejected.";

            // Create notification record
            Notification::create([
                'user_id' => $requester->id,
                'title' => 'Audit Request ' . ucfirst($status),
                'message' => $message,
                'type' => 'audit_' . $status,
                'data' => json_encode([
                    'audit_id' => $audit->id,
                    'approval_id' => $approval->id,
                    'status' => $status,
                    'reason' => $approval->approval_reason
                ]),
                'read_at' => null
            ]);

            Log::info('Requester notified about audit status', [
                'approval_id' => $approval->id,
                'audit_id' => $audit->id,
                'requester_id' => $requester->id,
                'status' => $status
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to notify requester', [
                'approval_id' => $approval->id,
                'status' => $status,
                'error' => $e->getMessage()
            ]);
        }
    }
}