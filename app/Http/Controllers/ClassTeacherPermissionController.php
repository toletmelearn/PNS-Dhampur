<?php

namespace App\Http\Controllers;

use App\Models\ClassTeacherPermission;
use App\Models\AuditTrail;
use App\Models\User;
use App\Models\ClassModel;
use App\Models\Subject;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ClassTeacherPermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // Note: Permission checks are handled at the route level or within methods
    }

    public function index(Request $request)
    {
        $query = ClassTeacherPermission::with(['teacher', 'class', 'subject', 'grantedBy'])
                                     ->where('is_active', true);

        // Apply filters
        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->filled('academic_year')) {
            $query->where('academic_year', $request->academic_year);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('teacher', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $permissions = $query->orderBy('created_at', 'desc')->paginate(15);

        $teachers = User::role('teacher')->get();
        // Fix N+1 query by adding eager loading for students relationship
        $classes = ClassModel::with(['students'])->get();
        $subjects = Subject::all();
        $academicYears = ClassTeacherPermission::distinct()->pluck('academic_year');

        return view('class-teacher-permissions.index', compact(
            'permissions', 'teachers', 'classes', 'subjects', 'academicYears'
        ));
    }

    public function create()
    {
        $teachers = User::role('teacher')->get();
        // Fix N+1 query by adding eager loading for students relationship
        $classes = ClassModel::with(['students'])->get();
        $subjects = Subject::all();

        return view('class-teacher-permissions.create', compact('teachers', 'classes', 'subjects'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'teacher_id' => 'required|exists:users,id',
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'academic_year' => 'required|string|max:10',
            'valid_from' => 'required|date',
            'valid_until' => 'nullable|date|after:valid_from',
            'permissions' => 'required|array',
            'permissions.*' => 'boolean',
            'notes' => 'nullable|string|max:1000'
        ]);

        // Check for existing active permission
        $existing = ClassTeacherPermission::where('teacher_id', $request->teacher_id)
                                        ->where('class_id', $request->class_id)
                                        ->where('subject_id', $request->subject_id)
                                        ->where('academic_year', $request->academic_year)
                                        ->where('is_active', true)
                                        ->first();

        if ($existing) {
            return back()->withErrors(['error' => 'Permission already exists for this teacher, class, and subject combination.']);
        }

        $permissionData = array_merge($request->only([
            'teacher_id', 'class_id', 'subject_id', 'academic_year', 
            'valid_from', 'valid_until', 'notes'
        ]), $request->permissions);

        $permissionData['granted_by'] = Auth::id();

        $permission = ClassTeacherPermission::create($permissionData);

        // Log the activity
        AuditTrail::logActivity($permission, 'created', [], $permissionData, [
            'tags' => ['permission_granted'],
            'class_id' => $request->class_id,
            'subject_id' => $request->subject_id,
            'academic_year' => $request->academic_year
        ]);

        return redirect()->route('class-teacher-permissions.index')
                        ->with('success', 'Permission granted successfully.');
    }

    public function show(ClassTeacherPermission $classTeacherPermission)
    {
        $classTeacherPermission->load(['teacher', 'class', 'subject', 'grantedBy', 'revokedBy']);
        
        // Get audit trail for this permission
        $auditTrail = AuditTrail::forModel($classTeacherPermission)
                               ->with(['user', 'approvedBy', 'rejectedBy'])
                               ->orderBy('created_at', 'desc')
                               ->get();

        return view('class-teacher-permissions.show', compact('classTeacherPermission', 'auditTrail'));
    }

    public function edit(ClassTeacherPermission $classTeacherPermission)
    {
        if (!$classTeacherPermission->is_active) {
            return back()->withErrors(['error' => 'Cannot edit revoked permission.']);
        }

        $teachers = User::role('teacher')->get();
        // Fix N+1 query by adding eager loading for students relationship
        $classes = ClassModel::with(['students'])->get();
        $subjects = Subject::all();

        return view('class-teacher-permissions.edit', compact('classTeacherPermission', 'teachers', 'classes', 'subjects'));
    }

    public function update(Request $request, ClassTeacherPermission $classTeacherPermission)
    {
        if (!$classTeacherPermission->is_active) {
            return back()->withErrors(['error' => 'Cannot update revoked permission.']);
        }

        $request->validate([
            'valid_from' => 'required|date',
            'valid_until' => 'nullable|date|after:valid_from',
            'permissions' => 'required|array',
            'permissions.*' => 'boolean',
            'notes' => 'nullable|string|max:1000'
        ]);

        $oldValues = $classTeacherPermission->toArray();
        
        $updateData = array_merge($request->only([
            'valid_from', 'valid_until', 'notes'
        ]), $request->permissions);

        $classTeacherPermission->update($updateData);

        // Log the activity
        AuditTrail::logActivity($classTeacherPermission, 'updated', $oldValues, $updateData, [
            'tags' => ['permission_updated'],
            'class_id' => $classTeacherPermission->class_id,
            'subject_id' => $classTeacherPermission->subject_id,
            'academic_year' => $classTeacherPermission->academic_year
        ]);

        return redirect()->route('class-teacher-permissions.index')
                        ->with('success', 'Permission updated successfully.');
    }

    public function revoke(Request $request, ClassTeacherPermission $classTeacherPermission)
    {
        $request->validate([
            'revocation_reason' => 'required|string|max:500'
        ]);

        $oldValues = $classTeacherPermission->toArray();

        $classTeacherPermission->update([
            'is_active' => false,
            'revoked_by' => Auth::id(),
            'revoked_at' => now(),
            'notes' => $classTeacherPermission->notes . "\nRevoked: " . $request->revocation_reason
        ]);

        // Log the activity
        AuditTrail::logActivity($classTeacherPermission, 'updated', $oldValues, $classTeacherPermission->toArray(), [
            'tags' => ['permission_revoked'],
            'class_id' => $classTeacherPermission->class_id,
            'subject_id' => $classTeacherPermission->subject_id,
            'academic_year' => $classTeacherPermission->academic_year,
            'correction_reason' => 'Permission revoked: ' . $request->revocation_reason
        ]);

        return back()->with('success', 'Permission revoked successfully.');
    }

    public function auditTrail(Request $request)
    {
        $query = AuditTrail::with(['user', 'student', 'class', 'subject', 'approvedBy', 'rejectedBy']);

        // Apply filters
        if ($request->filled('user_id')) {
            $query->forUser($request->user_id);
        }

        if ($request->filled('class_id')) {
            $query->forClass($request->class_id);
        }

        if ($request->filled('subject_id')) {
            $query->forSubject($request->subject_id);
        }

        if ($request->filled('student_id')) {
            $query->forStudent($request->student_id);
        }

        if ($request->filled('event')) {
            $query->byEvent($request->event);
        }

        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('academic_year')) {
            $query->forAcademicYear($request->academic_year);
        }

        if ($request->filled('term')) {
            $query->forTerm($request->term);
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
                $q->whereHas('user', function($subQ) use ($search) {
                    $subQ->where('name', 'like', "%{$search}%");
                })->orWhereHas('student', function($subQ) use ($search) {
                    $subQ->where('name', 'like', "%{$search}%")
                         ->orWhere('admission_number', 'like', "%{$search}%");
                })->orWhere('correction_reason', 'like', "%{$search}%");
            });
        }

        $auditTrails = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get filter options
        $teachers = User::role('teacher')->get();
        // Fix N+1 query by adding eager loading for students relationship
        $classes = ClassModel::with(['students'])->get();
        $subjects = Subject::all();
        $students = Student::select('id', 'name', 'admission_number')->get();
        $events = AuditTrail::distinct()->pluck('event');
        $statuses = AuditTrail::distinct()->pluck('status');
        $academicYears = AuditTrail::distinct()->pluck('academic_year');
        $terms = AuditTrail::distinct()->whereNotNull('term')->pluck('term');

        return view('class-teacher-permissions.audit-trail', compact(
            'auditTrails', 'teachers', 'classes', 'subjects', 'students', 
            'events', 'statuses', 'academicYears', 'terms'
        ));
    }

    public function approveCorrection(Request $request, AuditTrail $auditTrail)
    {
        if ($auditTrail->status !== 'pending_approval') {
            return back()->withErrors(['error' => 'This correction is not pending approval.']);
        }

        $auditTrail->approve(Auth::id(), $request->approval_notes);

        return back()->with('success', 'Correction approved successfully.');
    }

    public function rejectCorrection(Request $request, AuditTrail $auditTrail)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500'
        ]);

        if ($auditTrail->status !== 'pending_approval') {
            return back()->withErrors(['error' => 'This correction is not pending approval.']);
        }

        $auditTrail->reject(Auth::id(), $request->rejection_reason);

        return back()->with('success', 'Correction rejected successfully.');
    }

    public function bulkApproveCorrections(Request $request)
    {
        $request->validate([
            'audit_trail_ids' => 'required|array',
            'audit_trail_ids.*' => 'exists:audit_trails,id'
        ]);

        $auditTrails = AuditTrail::whereIn('id', $request->audit_trail_ids)
                                ->where('status', 'pending_approval')
                                ->get();

        foreach ($auditTrails as $auditTrail) {
            $auditTrail->approve(Auth::id(), 'Bulk approved');
        }

        return back()->with('success', count($auditTrails) . ' corrections approved successfully.');
    }

    public function exportAuditReport(Request $request)
    {
        $query = AuditTrail::with(['user', 'student', 'class', 'subject']);

        // Apply same filters as audit trail
        if ($request->filled('user_id')) {
            $query->forUser($request->user_id);
        }

        if ($request->filled('class_id')) {
            $query->forClass($request->class_id);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        $auditTrails = $query->orderBy('created_at', 'desc')->get();

        $filename = 'audit_report_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($auditTrails) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Date/Time', 'User', 'Event', 'Model', 'Student', 'Class', 'Subject',
                'Changes', 'Status', 'Correction Reason', 'Approved By', 'Approved At'
            ]);

            foreach ($auditTrails as $trail) {
                fputcsv($file, [
                    $trail->created_at->format('Y-m-d H:i:s'),
                    $trail->user ? $trail->user->name : 'System',
                    ucfirst($trail->event),
                    class_basename($trail->auditable_type),
                    $trail->student ? $trail->student->name : '',
                    $trail->class ? $trail->class->name : '',
                    $trail->subject ? $trail->subject->name : '',
                    $trail->formatted_changes,
                    ucfirst(str_replace('_', ' ', $trail->status)),
                    $trail->correction_reason ?? '',
                    $trail->approvedBy ? $trail->approvedBy->name : '',
                    $trail->approved_at ? $trail->approved_at->format('Y-m-d H:i:s') : ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function dashboard()
    {
        $user = Auth::user();
        
        // Get user's permissions
        $permissions = ClassTeacherPermission::where('teacher_id', $user->id)
                                           ->where('is_active', true)
                                           ->where('valid_from', '<=', now())
                                           ->where(function($q) {
                                               $q->whereNull('valid_until')
                                                 ->orWhere('valid_until', '>=', now());
                                           })
                                           ->with(['class', 'subject'])
                                           ->get();

        // Get recent audit trail for user's activities
        $recentActivities = AuditTrail::forUser($user->id)
                                    ->with(['student', 'class', 'subject'])
                                    ->orderBy('created_at', 'desc')
                                    ->limit(10)
                                    ->get();

        // Get pending corrections that need approval (if user has permission)
        $pendingCorrections = collect();
        if ($user->can('approve-corrections')) {
            $pendingCorrections = AuditTrail::pendingApproval()
                                          ->with(['user', 'student', 'class', 'subject'])
                                          ->orderBy('created_at', 'desc')
                                          ->limit(10)
                                          ->get();
        }

        // Get activity summary
        $activitySummary = AuditTrail::getActivitySummary($user->id);

        return view('class-teacher-permissions.dashboard', compact(
            'permissions', 'recentActivities', 'pendingCorrections', 'activitySummary'
        ));
    }
}