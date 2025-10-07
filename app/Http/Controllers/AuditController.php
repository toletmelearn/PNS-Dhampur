<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\AuditTrail;
use App\Models\UserSession;
use App\Models\User;
use Carbon\Carbon;
use App\Http\Traits\DateRangeValidationTrait;

class AuditController extends Controller
{
    use DateRangeValidationTrait;
    /**
     * Display audit logs dashboard
     */
    public function index(Request $request)
    {
        $this->authorize('view_audit_trails');

        $request->validate([
            ...$this->getFilterDateRangeValidationRules(),
            'user_id' => ['nullable', 'exists:users,id'],
            'event' => ['nullable', 'string'],
            'model_type' => ['nullable', 'string'],
            'search' => ['nullable', 'string', 'max:255']
        ], $this->getDateRangeValidationMessages());

        $filters = $request->only(['user_id', 'event', 'model_type', 'date_from', 'date_to', 'search']);
        
        $auditLogs = AuditTrail::with(['user', 'auditable'])
            ->when($filters['user_id'] ?? null, function ($query, $userId) {
                return $query->where('user_id', $userId);
            })
            ->when($filters['event'] ?? null, function ($query, $event) {
                return $query->where('event', $event);
            })
            ->when($filters['model_type'] ?? null, function ($query, $modelType) {
                return $query->where('auditable_type', $modelType);
            })
            ->when($filters['date_from'] ?? null, function ($query, $dateFrom) {
                return $query->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($filters['date_to'] ?? null, function ($query, $dateTo) {
                return $query->whereDate('created_at', '<=', $dateTo);
            })
            ->when($filters['search'] ?? null, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('url', 'like', "%{$search}%")
                      ->orWhere('ip_address', 'like', "%{$search}%")
                      ->orWhereHas('user', function ($userQuery) use ($search) {
                          $userQuery->where('name', 'like', "%{$search}%")
                                   ->orWhere('email', 'like', "%{$search}%");
                      });
                });
            })
            ->latest()
            ->paginate(50);

        $users = User::select('id', 'name', 'email')->get();
        $events = AuditTrail::distinct()->pluck('event');
        $modelTypes = AuditTrail::distinct()->pluck('auditable_type');

        return view('admin.audit.index', compact('auditLogs', 'users', 'events', 'modelTypes', 'filters'));
    }

    /**
     * Show detailed audit log entry
     */
    public function show(AuditTrail $auditLog)
    {
        $this->authorize('view_audit_logs');

        $auditLog->load(['user', 'auditable']);

        return view('admin.audit.show', compact('auditLog'));
    }

    /**
     * Get audit statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('view_audit_logs');

        $request->validate([
            ...$this->getFilterDateRangeValidationRules()
        ], $this->getDateRangeValidationMessages());

        $dateFrom = $request->input('date_from', now()->subDays(30));
        $dateTo = $request->input('date_to', now());

        $stats = [
            'total_activities' => AuditTrail::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'unique_users' => AuditTrail::whereBetween('created_at', [$dateFrom, $dateTo])
                ->distinct('user_id')->count('user_id'),
            'failed_logins' => AuditTrail::whereBetween('created_at', [$dateFrom, $dateTo])
                ->where('event', 'login_failed')->count(),
            'successful_logins' => AuditTrail::whereBetween('created_at', [$dateFrom, $dateTo])
                ->where('event', 'login_success')->count(),
            'data_modifications' => AuditTrail::whereBetween('created_at', [$dateFrom, $dateTo])
                ->whereIn('event', ['created', 'updated', 'deleted'])->count(),
        ];

        // Activity by day
        $activityByDay = AuditTrail::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top users by activity
        $topUsers = AuditTrail::whereBetween('created_at', [$dateFrom, $dateTo])
            ->with('user:id,name,email')
            ->selectRaw('user_id, COUNT(*) as activity_count')
            ->groupBy('user_id')
            ->orderByDesc('activity_count')
            ->limit(10)
            ->get();

        // Event distribution
        $eventDistribution = AuditTrail::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('event, COUNT(*) as count')
            ->groupBy('event')
            ->orderByDesc('count')
            ->get();

        return response()->json([
            'stats' => $stats,
            'activity_by_day' => $activityByDay,
            'top_users' => $topUsers,
            'event_distribution' => $eventDistribution
        ]);
    }

    /**
     * Get user session logs
     */
    public function sessions(Request $request)
    {
        $this->authorize('view_audit_logs');

        $request->validate([
            ...$this->getFilterDateRangeValidationRules(),
            'user_id' => ['nullable', 'exists:users,id'],
            'is_active' => ['nullable', 'boolean']
        ], $this->getDateRangeValidationMessages());

        $filters = $request->only(['user_id', 'is_active', 'date_from', 'date_to']);

        $sessions = UserSession::with('user:id,name,email')
            ->when($filters['user_id'] ?? null, function ($query, $userId) {
                return $query->where('user_id', $userId);
            })
            ->when(isset($filters['is_active']), function ($query) use ($filters) {
                return $query->where('is_active', $filters['is_active']);
            })
            ->when($filters['date_from'] ?? null, function ($query, $dateFrom) {
                return $query->whereDate('login_at', '>=', $dateFrom);
            })
            ->when($filters['date_to'] ?? null, function ($query, $dateTo) {
                return $query->whereDate('login_at', '<=', $dateTo);
            })
            ->latest('login_at')
            ->paginate(50);

        $users = User::select('id', 'name', 'email')->get();

        return view('admin.audit.sessions', compact('sessions', 'users', 'filters'));
    }

    /**
     * Get session statistics
     */
    public function sessionStatistics(Request $request): JsonResponse
    {
        $this->authorize('view_audit_logs');

        $request->validate([
            ...$this->getFilterDateRangeValidationRules()
        ], $this->getDateRangeValidationMessages());

        $dateFrom = $request->input('date_from', now()->subDays(30));
        $dateTo = $request->input('date_to', now());

        $stats = UserSession::getSessionStats($dateFrom, $dateTo);
        $averageDuration = UserSession::getAverageSessionDuration($dateFrom, $dateTo);

        // Active sessions by device type
        $deviceStats = UserSession::whereBetween('login_at', [$dateFrom, $dateTo])
            ->selectRaw('device_type, COUNT(*) as count')
            ->groupBy('device_type')
            ->get();

        // Login methods distribution
        $loginMethods = UserSession::whereBetween('login_at', [$dateFrom, $dateTo])
            ->selectRaw('login_method, COUNT(*) as count')
            ->groupBy('login_method')
            ->get();

        return response()->json([
            'stats' => $stats,
            'average_duration' => $averageDuration,
            'device_stats' => $deviceStats,
            'login_methods' => $loginMethods
        ]);
    }

    /**
     * Terminate a user session
     */
    public function terminateSession(UserSession $session)
    {
        if ($session->is_active) {
            UserSession::endSession($session->session_id, 'admin_terminated');
            
            // Log the termination
            AuditTrail::logActivity(
                null,
                $session,
                'session_terminated',
                ['terminated_by' => auth()->id()],
                null,
                ['admin_action']
            );
            
            return response()->json(['success' => true, 'message' => 'Session terminated successfully']);
        }
        
        return response()->json(['success' => false, 'message' => 'Session is already inactive']);
    }

    /**
     * Export audit logs
     */
    public function export(Request $request)
    {
        $request->validate([
            ...$this->getFilterDateRangeValidationRules(),
            'user_id' => ['nullable', 'exists:users,id'],
            'event' => ['nullable', 'string'],
            'model_type' => ['nullable', 'string']
        ], $this->getDateRangeValidationMessages());

        $filters = $request->only(['user_id', 'event', 'model_type', 'date_from', 'date_to']);
        
        $auditLogs = AuditTrail::with(['user'])
            ->when($filters['user_id'] ?? null, function ($query, $userId) {
                return $query->where('user_id', $userId);
            })
            ->when($filters['event'] ?? null, function ($query, $event) {
                return $query->where('event', $event);
            })
            ->when($filters['model_type'] ?? null, function ($query, $modelType) {
                return $query->where('auditable_type', $modelType);
            })
            ->when($filters['date_from'] ?? null, function ($query, $dateFrom) {
                return $query->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($filters['date_to'] ?? null, function ($query, $dateTo) {
                return $query->whereDate('created_at', '<=', $dateTo);
            })
            ->latest()
            ->get();

        $filename = 'audit_logs_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($auditLogs) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'ID', 'User', 'Event', 'Model Type', 'Model ID', 'URL', 
                'IP Address', 'User Agent', 'Old Values', 'New Values', 
                'Tags', 'Created At'
            ]);

            foreach ($auditLogs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->user ? $log->user->name . ' (' . $log->user->email . ')' : 'System',
                    $log->event,
                    $log->auditable_type,
                    $log->auditable_id,
                    $log->url,
                    $log->ip_address,
                    $log->user_agent,
                    json_encode($log->old_values),
                    json_encode($log->new_values),
                    implode(', ', $log->tags ?? []),
                    $log->created_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
    /**
     * Get recent activities for dashboard widget
     */
    public function recentActivities(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);

        $activities = AuditTrail::with(['user:id,name,email'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'user' => $activity->user ? $activity->user->name : 'System',
                    'event' => $activity->event,
                    'description' => $this->getActivityDescription($activity),
                    'created_at' => $activity->created_at->diffForHumans(),
                    'icon' => $activity->event_icon,
                    'badge' => $activity->status_badge
                ];
            });

        return response()->json($activities);
    }

    /**
     * Get activity description for display
     */
    private function getActivityDescription(AuditTrail $activity): string
    {
        $modelName = class_basename($activity->auditable_type ?? 'System');
        
        switch ($activity->event) {
            case 'login_success':
                return "Logged in successfully";
            case 'login_failed':
                return "Failed login attempt";
            case 'logout':
                return "Logged out";
            case 'created':
                return "Created new {$modelName}";
            case 'updated':
                return "Updated {$modelName}";
            case 'deleted':
                return "Deleted {$modelName}";
            default:
                return ucfirst(str_replace('_', ' ', $activity->event));
        }
    }
}