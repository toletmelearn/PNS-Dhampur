<?php

namespace App\Http\Controllers;

use App\Models\ClassData;
use App\Models\ChangeLog;
use App\Models\ClassDataVersion;
use App\Models\ClassDataApproval;
use App\Models\ClassDataAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class ClassTeacherDataController extends Controller
{
    // List class data for current teacher or by class
    public function index(Request $request)
    {
        $user = $request->user();
        $classModelId = $request->get('class_model_id');

        $query = ClassData::query()->with(['classModel', 'approvedBy', 'lastVersion']);

        if ($classModelId) {
            $query->where('class_model_id', $classModelId);
        }

        // Optionally restrict to classes where user is class_teacher
        if ($user && method_exists($user, 'isTeacher') && $user->isTeacher()) {
            $query->whereHas('classModel', function ($q) use ($user) {
                $q->where('class_teacher_id', $user->id);
            });
        }

        return response()->json($query->orderByDesc('updated_at')->paginate(20));
    }

    // Show specific class data with versions (via audits) and changes
    public function show($id)
    {
        $classData = ClassData::with(['classModel', 'lastVersion', 'changeLogs.user'])->findOrFail($id);
        
        // Collect versions via related audits
        $auditIds = ClassDataAudit::where('auditable_type', ClassData::class)
            ->where('auditable_id', $classData->id)
            ->pluck('id');

        $versions = ClassDataVersion::whereIn('audit_id', $auditIds)
            ->orderByDesc('version_number')
            ->get();

        return response()->json([
            'class_data' => $classData,
            'versions' => $versions,
        ]);
    }

    // Create new class data entry
    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_model_id' => 'required|exists:class_models,id',
            'data' => 'nullable|array',
            'significant_change' => 'sometimes|boolean',
            'change_reason' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $user = $request->user();

            $classData = new ClassData();
            $classData->class_model_id = $validated['class_model_id'];
            $classData->data = $validated['data'] ?? [];
            $classData->original_data = $validated['data'] ?? [];
            $classData->significant_change = (bool)($validated['significant_change'] ?? false);
            $classData->change_reason = $validated['change_reason'] ?? null;
            $classData->approval_required = $classData->significant_change;
            $classData->approval_status = $classData->approval_required ? 'pending' : 'approved';
            $classData->created_by = $user ? $user->id : null;
            $classData->updated_by = $user ? $user->id : null;
            $classData->save();

            // Create audit record
            $audit = new ClassDataAudit();
            $audit->auditable_type = ClassData::class;
            $audit->auditable_id = $classData->id;
            $audit->event_type = 'created';
            $audit->old_values = null;
            $audit->new_values = $classData->data ?? [];
            $audit->changed_fields = array_keys($classData->data ?? []);
            $audit->user_id = $user ? $user->id : null;
            $audit->ip_address = $request->ip();
            $audit->user_agent = $request->userAgent();
            $audit->session_id = $request->hasSession() ? $request->session()->getId() : null;
            $audit->description = $classData->change_reason;
            $audit->requires_approval = $classData->approval_required;
            $audit->approval_status = $classData->approval_required ? 'pending' : 'approved';
            $audit->risk_level = $classData->significant_change ? 'high' : 'low';
            $audit->save();

            // Create initial version snapshot (linked via audit_id)
            $version = new ClassDataVersion();
            $version->audit_id = $audit->id;
            $version->version_number = 1;
            $version->data_snapshot = $classData->data ?? [];
            $version->checksum = hash('sha256', json_encode($version->data_snapshot));
            $version->metadata = ['created' => now()->toIso8601String(), 'user_id' => $user ? $user->id : null];
            $version->created_by = $user ? $user->id : null;
            $version->save();
            $classData->last_version_id = $version->id;
            $classData->save();

            // Log creation with audit linkage
            ChangeLog::create([
                'changeable_type' => ClassData::class,
                'changeable_id' => $classData->id,
                'user_id' => $user ? $user->id : null,
                'action' => 'create',
                'changed_fields' => array_keys($classData->data ?? []),
                'old_values' => null,
                'new_values' => $classData->data ?? [],
                'significant' => $classData->significant_change,
                'approved_by' => null,
                'approved_at' => null,
                'audit_id' => $audit->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => $request->hasSession() ? $request->session()->getId() : null,
                'batch_id' => null,
            ]);

            // If significant, create approval record linked to audit
            if ($classData->approval_required && class_exists(ClassDataApproval::class)) {
                ClassDataApproval::create([
                    'audit_id' => $audit->id,
                    'approval_type' => 'data_change',
                    'status' => 'pending',
                    'requested_by' => $user ? $user->id : null,
                    'request_reason' => $classData->change_reason,
                    'deadline' => null,
                ]);
            }

            return response()->json(['class_data' => $classData->fresh(['lastVersion'])], 201);
        });
    }

    // Update class data with diffing, audit, and versioning
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'data' => 'required|array',
            'significant_change' => 'sometimes|boolean',
            'change_reason' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated, $request, $id) {
            $user = $request->user();
            $classData = ClassData::findOrFail($id);
            $old = $classData->data ?? [];
            $new = $validated['data'];

            // Compute diff keys (simple shallow diff)
            $changedKeys = array_values(array_unique(array_merge(
                array_keys(array_diff_assoc($old, $new)),
                array_keys(array_diff_assoc($new, $old))
            )));

            // Determine significance (explicit flag or heuristic)
            $significant = $validated['significant_change'] ?? (count($changedKeys) >= 3);

            $classData->data = $new;
            $classData->significant_change = $significant;
            $classData->change_reason = $validated['change_reason'] ?? $classData->change_reason;
            $classData->approval_required = $significant;
            $classData->approval_status = $significant ? 'pending' : 'approved';
            $classData->updated_by = $user ? $user->id : null;
            $classData->save();

            // Create audit record for update
            $audit = new ClassDataAudit();
            $audit->auditable_type = ClassData::class;
            $audit->auditable_id = $classData->id;
            $audit->event_type = 'updated';
            $audit->old_values = $old;
            $audit->new_values = $new;
            $audit->changed_fields = $changedKeys;
            $audit->user_id = $user ? $user->id : null;
            $audit->ip_address = $request->ip();
            $audit->user_agent = $request->userAgent();
            $audit->session_id = $request->hasSession() ? $request->session()->getId() : null;
            $audit->description = $classData->change_reason;
            $audit->requires_approval = $classData->approval_required;
            $audit->approval_status = $classData->approval_required ? 'pending' : 'approved';
            $audit->risk_level = $classData->significant_change ? 'high' : 'low';
            $audit->save();

            // Determine next version number across audits for this ClassData
            $auditIds = ClassDataAudit::where('auditable_type', ClassData::class)
                ->where('auditable_id', $classData->id)
                ->pluck('id');
            $maxVersionNumber = ClassDataVersion::whereIn('audit_id', $auditIds)->max('version_number');

            // Version snapshot increment (linked via audit_id)
            $nextVersion = new ClassDataVersion();
            $nextVersion->audit_id = $audit->id;
            $nextVersion->version_number = ($maxVersionNumber ?? 0) + 1;
            $nextVersion->data_snapshot = $classData->data ?? [];
            $nextVersion->checksum = hash('sha256', json_encode($nextVersion->data_snapshot));
            $nextVersion->metadata = ['updated' => now()->toIso8601String(), 'user_id' => $user ? $user->id : null];
            $nextVersion->created_by = $user ? $user->id : null;
            $nextVersion->save();
            $classData->last_version_id = $nextVersion->id;
            $classData->save();

            // Log update with audit linkage
            ChangeLog::create([
                'changeable_type' => ClassData::class,
                'changeable_id' => $classData->id,
                'user_id' => $user ? $user->id : null,
                'action' => 'update',
                'changed_fields' => $changedKeys,
                'old_values' => $old,
                'new_values' => $new,
                'significant' => $significant,
                'approved_by' => null,
                'approved_at' => null,
                'audit_id' => $audit->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => $request->hasSession() ? $request->session()->getId() : null,
                'batch_id' => null,
            ]);

            // Create approval ticket if needed (linked to audit)
            if ($classData->approval_required && class_exists(ClassDataApproval::class)) {
                ClassDataApproval::create([
                    'audit_id' => $audit->id,
                    'approval_type' => 'data_change',
                    'status' => 'pending',
                    'requested_by' => $user ? $user->id : null,
                    'request_reason' => $classData->change_reason,
                ]);
            }

            return response()->json(['class_data' => $classData->fresh(['lastVersion'])]);
        });
    }

    // Audit trail for a class data record
    public function auditTrail($id)
    {
        $classData = ClassData::findOrFail($id);
        $logs = $classData->changeLogs()->with(['user', 'approvedBy'])->orderByDesc('created_at')->paginate(50);
        return response()->json($logs);
    }

    // Approve significant change (admin)
    public function approve(Request $request, $id)
    {
        $user = $request->user();
        $classData = ClassData::findOrFail($id);

        $classData->approval_status = 'approved';
        $classData->approved_by = $user ? $user->id : null;
        $classData->approved_at = now();
        $classData->save();

        // Mark latest change log approved
        $latestLog = $classData->changeLogs()->orderByDesc('created_at')->first();
        if ($latestLog) {
            $latestLog->approved_by = $user ? $user->id : null;
            $latestLog->approved_at = now();
            $latestLog->save();
        }

        // Update audit and approval record if exists
        $audit = ClassDataAudit::where('auditable_type', ClassData::class)
            ->where('auditable_id', $classData->id)
            ->where('approval_status', 'pending')
            ->latest()
            ->first();
        if ($audit) {
            $audit->approval_status = 'approved';
            $audit->approved_by = $user ? $user->id : null;
            $audit->approved_at = now();
            $audit->save();

            if (class_exists(ClassDataApproval::class)) {
                $approval = ClassDataApproval::where('audit_id', $audit->id)->where('status', 'pending')->latest()->first();
                if ($approval) {
                    $approval->status = 'approved';
                    $approval->approved_by = $user ? $user->id : null;
                    $approval->approved_at = now();
                    $approval->save();
                }
            }
        }

        return response()->json(['message' => 'Change approved', 'class_data' => $classData]);
    }

    // Reject significant change (admin)
    public function reject(Request $request, $id)
    {
        $user = $request->user();
        $classData = ClassData::findOrFail($id);
        $reason = $request->get('reason');

        $classData->approval_status = 'rejected';
        $classData->approved_by = $user ? $user->id : null;
        $classData->approved_at = now();
        $classData->save();

        ChangeLog::create([
            'changeable_type' => ClassData::class,
            'changeable_id' => $classData->id,
            'user_id' => $user ? $user->id : null,
            'action' => 'reject',
            'changed_fields' => [],
            'old_values' => null,
            'new_values' => ['reason' => $reason],
            'significant' => true,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
            'batch_id' => null,
        ]);

        // Update audit and approval record if exists
        $audit = ClassDataAudit::where('auditable_type', ClassData::class)
            ->where('auditable_id', $classData->id)
            ->where('approval_status', 'pending')
            ->latest()
            ->first();
        if ($audit) {
            $audit->approval_status = 'rejected';
            $audit->approved_by = $user ? $user->id : null;
            $audit->approved_at = now();
            $audit->description = $reason;
            $audit->save();

            if (class_exists(ClassDataApproval::class)) {
                $approval = ClassDataApproval::where('audit_id', $audit->id)->where('status', 'pending')->latest()->first();
                if ($approval) {
                    $approval->status = 'rejected';
                    $approval->approved_by = $user ? $user->id : null;
                    $approval->rejected_at = now();
                    $approval->approval_reason = $reason;
                    $approval->save();
                }
            }
        }

        return response()->json(['message' => 'Change rejected', 'class_data' => $classData]);
    }

    // Version history (via audits)
    public function history($id)
    {
        $classData = ClassData::findOrFail($id);
        $auditIds = ClassDataAudit::where('auditable_type', ClassData::class)
            ->where('auditable_id', $classData->id)
            ->pluck('id');
        $versions = ClassDataVersion::whereIn('audit_id', $auditIds)
            ->orderByDesc('version_number')
            ->paginate(50);
        return response()->json($versions);
    }
}