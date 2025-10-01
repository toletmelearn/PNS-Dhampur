<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ClassTeacherPermission;
use App\Models\AuditTrail;

class CheckClassTeacherPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission = null): Response
    {
        $user = $request->user();
        
        // Skip check for super admin or admin roles
        if ($user && in_array($user->role, ['super_admin', 'admin'])) {
            return $next($request);
        }
        
        // Skip check if user is not a teacher
        if (!$user || $user->role !== 'teacher') {
            abort(403, 'Access denied. Teacher role required.');
        }
        
        // Get class and subject from request parameters
        $classId = $request->route('class_id') ?? $request->input('class_id');
        $subjectId = $request->route('subject_id') ?? $request->input('subject_id');
        $academicYear = $request->input('academic_year') ?? config('app.current_academic_year', date('Y'));
        
        // If no specific class is being accessed, allow general access
        if (!$classId && !$permission) {
            return $next($request);
        }
        
        // Check if teacher has permission for the specific class/subject
        $hasPermission = false;
        
        if ($classId) {
            $query = ClassTeacherPermission::where('teacher_id', $user->id)
                ->where('class_id', $classId)
                ->where('academic_year', $academicYear)
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('valid_until')
                      ->orWhere('valid_until', '>=', now()->toDateString());
                })
                ->where('valid_from', '<=', now()->toDateString());
            
            // If subject is specified, check for subject-specific or general permissions
            if ($subjectId) {
                $query->where(function ($q) use ($subjectId) {
                    $q->where('subject_id', $subjectId)
                      ->orWhereNull('subject_id');
                });
            }
            
            $permissions = $query->get();
            
            // Check specific permission if provided
            if ($permission && $permissions->isNotEmpty()) {
                $hasPermission = $permissions->some(function ($perm) use ($permission) {
                    return $perm->hasPermission($permission);
                });
            } elseif (!$permission) {
                // General access check - at least view permission required
                $hasPermission = $permissions->some(function ($perm) {
                    return $perm->can_view_records;
                });
            }
        }
        
        // Log access attempt
        AuditTrail::logActivity([
            'event' => 'access_attempt',
            'auditable_type' => 'permission_check',
            'auditable_id' => 0,
            'class_id' => $classId,
            'subject_id' => $subjectId,
            'academic_year' => $academicYear,
            'description' => "Permission check for {$permission} on class {$classId}",
            'tags' => ['permission_check', $permission ?? 'general_access'],
            'old_values' => [
                'permission_requested' => $permission,
                'class_id' => $classId,
                'subject_id' => $subjectId,
                'result' => $hasPermission ? 'granted' : 'denied'
            ]
        ]);
        
        if (!$hasPermission) {
            abort(403, 'Access denied. You do not have permission to perform this action on the specified class/subject.');
        }
        
        return $next($request);
    }
    
    /**
     * Get permission name from route action
     */
    private function getPermissionFromAction(string $action): ?string
    {
        $permissionMap = [
            'index' => 'can_view_records',
            'show' => 'can_view_records',
            'create' => 'can_add_records',
            'store' => 'can_add_records',
            'edit' => 'can_edit_records',
            'update' => 'can_edit_records',
            'destroy' => 'can_delete_records',
            'export' => 'can_export_reports',
            'bulkEntry' => 'can_bulk_operations',
            'approve' => 'can_approve_corrections',
            'reject' => 'can_approve_corrections',
        ];
        
        return $permissionMap[$action] ?? null;
    }
}