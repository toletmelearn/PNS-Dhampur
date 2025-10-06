<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SystemHealth;
use App\Models\PerformanceMetric;
use App\Models\ErrorLog;
use App\Models\UserActivity;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PerformanceApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get system health metrics
     */
    public function systemHealth(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'integer|min:1|max:100',
            'status' => 'in:healthy,warning,critical',
            'metric_type' => 'string|max:50',
            'hours' => 'integer|min:1|max:168', // Max 1 week
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = SystemHealth::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('metric_type')) {
            $query->byType($request->metric_type);
        }

        if ($request->has('hours')) {
            $query->recent($request->hours);
        }

        $metrics = $query->orderBy('recorded_at', 'desc')
            ->limit($request->get('limit', 50))
            ->get();

        return response()->json([
            'data' => $metrics,
            'summary' => [
                'total' => SystemHealth::count(),
                'healthy' => SystemHealth::healthy()->count(),
                'warning' => SystemHealth::warning()->count(),
                'critical' => SystemHealth::critical()->count(),
            ]
        ]);
    }

    /**
     * Get performance metrics
     */
    public function performanceMetrics(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'integer|min:1|max:100',
            'endpoint' => 'string|max:255',
            'user_id' => 'integer|exists:users,id',
            'status_code' => 'integer',
            'hours' => 'integer|min:1|max:168',
            'slow_only' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = PerformanceMetric::with('user:id,name,email');

        if ($request->has('endpoint')) {
            $query->byEndpoint($request->endpoint);
        }

        if ($request->has('user_id')) {
            $query->byUser($request->user_id);
        }

        if ($request->has('status_code')) {
            $query->byStatusCode($request->status_code);
        }

        if ($request->has('hours')) {
            $query->recent($request->hours);
        }

        if ($request->boolean('slow_only')) {
            $query->slowRequests();
        }

        $metrics = $query->orderBy('recorded_at', 'desc')
            ->limit($request->get('limit', 50))
            ->get();

        return response()->json([
            'data' => $metrics,
            'summary' => [
                'total_requests' => PerformanceMetric::count(),
                'avg_response_time' => round(PerformanceMetric::avg('response_time'), 2),
                'slow_requests' => PerformanceMetric::slowRequests()->count(),
                'error_requests' => PerformanceMetric::where('status_code', '>=', 400)->count(),
            ]
        ]);
    }

    /**
     * Get error logs
     */
    public function errorLogs(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'integer|min:1|max:100',
            'level' => 'in:emergency,alert,critical,error,warning,notice,info,debug',
            'resolved' => 'boolean',
            'user_id' => 'integer|exists:users,id',
            'hours' => 'integer|min:1|max:168',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = ErrorLog::with(['user:id,name,email', 'resolver:id,name,email']);

        if ($request->has('level')) {
            $query->byLevel($request->level);
        }

        if ($request->has('resolved')) {
            if ($request->boolean('resolved')) {
                $query->resolved();
            } else {
                $query->unresolved();
            }
        }

        if ($request->has('user_id')) {
            $query->byUser($request->user_id);
        }

        if ($request->has('hours')) {
            $query->recent($request->hours);
        }

        $errorLogs = $query->orderBy('created_at', 'desc')
            ->limit($request->get('limit', 50))
            ->get();

        return response()->json([
            'data' => $errorLogs,
            'summary' => [
                'total_errors' => ErrorLog::count(),
                'unresolved_errors' => ErrorLog::unresolved()->count(),
                'critical_errors' => ErrorLog::critical()->count(),
                'recent_errors' => ErrorLog::recent(24)->count(),
            ]
        ]);
    }

    /**
     * Get user activities
     */
    public function userActivities(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'integer|min:1|max:100',
            'user_id' => 'integer|exists:users,id',
            'activity_type' => 'string|max:50',
            'ip_address' => 'ip',
            'hours' => 'integer|min:1|max:168',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = UserActivity::with('user:id,name,email');

        if ($request->has('user_id')) {
            $query->byUser($request->user_id);
        }

        if ($request->has('activity_type')) {
            $query->byActivityType($request->activity_type);
        }

        if ($request->has('ip_address')) {
            $query->byIpAddress($request->ip_address);
        }

        if ($request->has('hours')) {
            $query->recent($request->hours);
        }

        $activities = $query->orderBy('performed_at', 'desc')
            ->limit($request->get('limit', 50))
            ->get();

        return response()->json([
            'data' => $activities,
            'summary' => [
                'total_activities' => UserActivity::count(),
                'unique_users_today' => UserActivity::today()->distinct('user_id')->count(),
                'logins_today' => UserActivity::logins()->today()->count(),
                'recent_activities' => UserActivity::recent(1)->count(),
            ]
        ]);
    }

    /**
     * Get dashboard statistics
     */
    public function dashboardStats()
    {
        $stats = [
            'system_health' => [
                'total' => SystemHealth::count(),
                'healthy' => SystemHealth::healthy()->count(),
                'warning' => SystemHealth::warning()->count(),
                'critical' => SystemHealth::critical()->count(),
                'recent_issues' => SystemHealth::where('status', '!=', 'healthy')->recent(24)->count(),
            ],
            'performance' => [
                'total_requests' => PerformanceMetric::count(),
                'avg_response_time' => round(PerformanceMetric::avg('response_time'), 2),
                'slow_requests' => PerformanceMetric::slowRequests()->count(),
                'today_requests' => PerformanceMetric::today()->count(),
            ],
            'errors' => [
                'total_errors' => ErrorLog::count(),
                'unresolved' => ErrorLog::unresolved()->count(),
                'critical' => ErrorLog::critical()->count(),
                'recent' => ErrorLog::recent(24)->count(),
            ],
            'user_activity' => [
                'total_activities' => UserActivity::count(),
                'unique_users_today' => UserActivity::today()->distinct('user_id')->count(),
                'logins_today' => UserActivity::logins()->today()->count(),
                'recent_activities' => UserActivity::recent(1)->count(),
            ]
        ];

        return response()->json($stats);
    }

    /**
     * Get chart data for performance metrics
     */
    public function chartData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:system_health,performance,errors,user_activity',
            'period' => 'in:24h,7d,30d',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $type = $request->type;
        $period = $request->get('period', '24h');

        switch ($type) {
            case 'system_health':
                return response()->json($this->getSystemHealthChartData($period));
            case 'performance':
                return response()->json($this->getPerformanceChartData($period));
            case 'errors':
                return response()->json($this->getErrorsChartData($period));
            case 'user_activity':
                return response()->json($this->getUserActivityChartData($period));
            default:
                return response()->json(['error' => 'Invalid chart type'], 400);
        }
    }

    /**
     * Record system health metric
     */
    public function recordSystemHealth(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'metric_name' => 'required|string|max:100',
            'metric_type' => 'required|string|max:50',
            'value' => 'required|numeric',
            'unit' => 'nullable|string|max:20',
            'status' => 'required|in:healthy,warning,critical',
            'details' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $systemHealth = SystemHealth::create([
            'metric_name' => $request->metric_name,
            'metric_type' => $request->metric_type,
            'value' => $request->value,
            'unit' => $request->unit,
            'status' => $request->status,
            'details' => $request->details,
            'metadata' => $request->metadata,
            'recorded_at' => now(),
        ]);

        return response()->json([
            'message' => 'System health metric recorded successfully',
            'data' => $systemHealth
        ], 201);
    }

    /**
     * Mark error as resolved
     */
    public function resolveError(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'resolution_notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $errorLog = ErrorLog::findOrFail($id);
        
        if ($errorLog->is_resolved) {
            return response()->json(['error' => 'Error is already resolved'], 400);
        }

        $errorLog->markAsResolved(auth()->id(), $request->resolution_notes);

        return response()->json([
            'message' => 'Error marked as resolved successfully',
            'data' => $errorLog->fresh(['resolver'])
        ]);
    }

    /**
     * Get system health chart data
     */
    private function getSystemHealthChartData($period)
    {
        $data = collect();
        
        switch ($period) {
            case '24h':
                for ($i = 23; $i >= 0; $i--) {
                    $hour = Carbon::now()->subHours($i);
                    $data->push([
                        'label' => $hour->format('H:00'),
                        'healthy' => SystemHealth::healthy()->whereBetween('recorded_at', [$hour->startOfHour(), $hour->endOfHour()])->count(),
                        'warning' => SystemHealth::warning()->whereBetween('recorded_at', [$hour->startOfHour(), $hour->endOfHour()])->count(),
                        'critical' => SystemHealth::critical()->whereBetween('recorded_at', [$hour->startOfHour(), $hour->endOfHour()])->count(),
                    ]);
                }
                break;
            case '7d':
                for ($i = 6; $i >= 0; $i--) {
                    $date = Carbon::now()->subDays($i);
                    $data->push([
                        'label' => $date->format('M j'),
                        'healthy' => SystemHealth::healthy()->whereDate('recorded_at', $date)->count(),
                        'warning' => SystemHealth::warning()->whereDate('recorded_at', $date)->count(),
                        'critical' => SystemHealth::critical()->whereDate('recorded_at', $date)->count(),
                    ]);
                }
                break;
            case '30d':
                for ($i = 29; $i >= 0; $i--) {
                    $date = Carbon::now()->subDays($i);
                    $data->push([
                        'label' => $date->format('M j'),
                        'healthy' => SystemHealth::healthy()->whereDate('recorded_at', $date)->count(),
                        'warning' => SystemHealth::warning()->whereDate('recorded_at', $date)->count(),
                        'critical' => SystemHealth::critical()->whereDate('recorded_at', $date)->count(),
                    ]);
                }
                break;
        }

        return $data;
    }

    /**
     * Get performance chart data
     */
    private function getPerformanceChartData($period)
    {
        $data = collect();
        
        switch ($period) {
            case '24h':
                for ($i = 23; $i >= 0; $i--) {
                    $hour = Carbon::now()->subHours($i);
                    $data->push([
                        'label' => $hour->format('H:00'),
                        'requests' => PerformanceMetric::whereBetween('recorded_at', [$hour->startOfHour(), $hour->endOfHour()])->count(),
                        'avg_response_time' => PerformanceMetric::whereBetween('recorded_at', [$hour->startOfHour(), $hour->endOfHour()])->avg('response_time') ?: 0,
                    ]);
                }
                break;
            case '7d':
                for ($i = 6; $i >= 0; $i--) {
                    $date = Carbon::now()->subDays($i);
                    $data->push([
                        'label' => $date->format('M j'),
                        'requests' => PerformanceMetric::whereDate('recorded_at', $date)->count(),
                        'avg_response_time' => PerformanceMetric::whereDate('recorded_at', $date)->avg('response_time') ?: 0,
                    ]);
                }
                break;
            case '30d':
                for ($i = 29; $i >= 0; $i--) {
                    $date = Carbon::now()->subDays($i);
                    $data->push([
                        'label' => $date->format('M j'),
                        'requests' => PerformanceMetric::whereDate('recorded_at', $date)->count(),
                        'avg_response_time' => PerformanceMetric::whereDate('recorded_at', $date)->avg('response_time') ?: 0,
                    ]);
                }
                break;
        }

        return $data;
    }

    /**
     * Get errors chart data
     */
    private function getErrorsChartData($period)
    {
        $data = collect();
        
        switch ($period) {
            case '24h':
                for ($i = 23; $i >= 0; $i--) {
                    $hour = Carbon::now()->subHours($i);
                    $data->push([
                        'label' => $hour->format('H:00'),
                        'error' => ErrorLog::byLevel('error')->whereBetween('created_at', [$hour->startOfHour(), $hour->endOfHour()])->count(),
                        'warning' => ErrorLog::byLevel('warning')->whereBetween('created_at', [$hour->startOfHour(), $hour->endOfHour()])->count(),
                        'critical' => ErrorLog::critical()->whereBetween('created_at', [$hour->startOfHour(), $hour->endOfHour()])->count(),
                    ]);
                }
                break;
            case '7d':
                for ($i = 6; $i >= 0; $i--) {
                    $date = Carbon::now()->subDays($i);
                    $data->push([
                        'label' => $date->format('M j'),
                        'error' => ErrorLog::byLevel('error')->whereDate('created_at', $date)->count(),
                        'warning' => ErrorLog::byLevel('warning')->whereDate('created_at', $date)->count(),
                        'critical' => ErrorLog::critical()->whereDate('created_at', $date)->count(),
                    ]);
                }
                break;
            case '30d':
                for ($i = 29; $i >= 0; $i--) {
                    $date = Carbon::now()->subDays($i);
                    $data->push([
                        'label' => $date->format('M j'),
                        'error' => ErrorLog::byLevel('error')->whereDate('created_at', $date)->count(),
                        'warning' => ErrorLog::byLevel('warning')->whereDate('created_at', $date)->count(),
                        'critical' => ErrorLog::critical()->whereDate('created_at', $date)->count(),
                    ]);
                }
                break;
        }

        return $data;
    }

    /**
     * Get user activity chart data
     */
    private function getUserActivityChartData($period)
    {
        $data = collect();
        
        switch ($period) {
            case '24h':
                for ($i = 23; $i >= 0; $i--) {
                    $hour = Carbon::now()->subHours($i);
                    $data->push([
                        'label' => $hour->format('H:00'),
                        'logins' => UserActivity::logins()->whereBetween('performed_at', [$hour->startOfHour(), $hour->endOfHour()])->count(),
                        'activities' => UserActivity::whereBetween('performed_at', [$hour->startOfHour(), $hour->endOfHour()])->count(),
                    ]);
                }
                break;
            case '7d':
                for ($i = 6; $i >= 0; $i--) {
                    $date = Carbon::now()->subDays($i);
                    $data->push([
                        'label' => $date->format('M j'),
                        'logins' => UserActivity::logins()->whereDate('performed_at', $date)->count(),
                        'activities' => UserActivity::whereDate('performed_at', $date)->count(),
                    ]);
                }
                break;
            case '30d':
                for ($i = 29; $i >= 0; $i--) {
                    $date = Carbon::now()->subDays($i);
                    $data->push([
                        'label' => $date->format('M j'),
                        'logins' => UserActivity::logins()->whereDate('performed_at', $date)->count(),
                        'activities' => UserActivity::whereDate('performed_at', $date)->count(),
                    ]);
                }
                break;
        }

        return $data;
    }
}
