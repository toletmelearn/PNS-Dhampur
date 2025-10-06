<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SystemHealth;
use App\Models\PerformanceMetric;
use App\Models\ErrorLog;
use App\Models\UserActivity;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PerformanceMonitoringController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin');
    }

    /**
     * Display the main performance monitoring dashboard
     */
    public function index()
    {
        $data = [
            'systemHealth' => $this->getSystemHealthSummary(),
            'performanceMetrics' => $this->getPerformanceMetricsSummary(),
            'errorLogs' => $this->getErrorLogsSummary(),
            'userActivity' => $this->getUserActivitySummary(),
        ];

        return view('admin.performance.index', $data);
    }

    /**
     * Display system health dashboard
     */
    public function systemHealth()
    {
        $healthMetrics = SystemHealth::orderBy('recorded_at', 'desc')
            ->paginate(20);

        $healthSummary = [
            'total_metrics' => SystemHealth::count(),
            'healthy_count' => SystemHealth::healthy()->count(),
            'warning_count' => SystemHealth::warning()->count(),
            'critical_count' => SystemHealth::critical()->count(),
            'recent_critical' => SystemHealth::critical()->recent(24)->count(),
        ];

        $chartData = $this->getSystemHealthChartData();

        return view('admin.performance.system-health', compact('healthMetrics', 'healthSummary', 'chartData'));
    }

    /**
     * Display performance metrics dashboard
     */
    public function performanceMetrics()
    {
        $metrics = PerformanceMetric::with('user')
            ->orderBy('recorded_at', 'desc')
            ->paginate(20);

        $performanceSummary = [
            'total_requests' => PerformanceMetric::count(),
            'avg_response_time' => PerformanceMetric::avg('response_time'),
            'slow_requests' => PerformanceMetric::slowRequests()->count(),
            'error_requests' => PerformanceMetric::where('status_code', '>=', 400)->count(),
            'today_requests' => PerformanceMetric::today()->count(),
        ];

        $chartData = $this->getPerformanceChartData();

        return view('admin.performance.metrics', compact('metrics', 'performanceSummary', 'chartData'));
    }

    /**
     * Display error logs dashboard
     */
    public function errorLogs()
    {
        $errorLogs = ErrorLog::with(['user', 'resolver'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $errorSummary = [
            'total_errors' => ErrorLog::count(),
            'unresolved_errors' => ErrorLog::unresolved()->count(),
            'critical_errors' => ErrorLog::critical()->count(),
            'recent_errors' => ErrorLog::recent(24)->count(),
            'resolved_today' => ErrorLog::resolved()->whereDate('resolved_at', Carbon::today())->count(),
        ];

        $chartData = $this->getErrorLogsChartData();

        return view('admin.performance.error-logs', compact('errorLogs', 'errorSummary', 'chartData'));
    }

    /**
     * Display user activity dashboard
     */
    public function userActivity()
    {
        $activities = UserActivity::with('user')
            ->orderBy('performed_at', 'desc')
            ->paginate(20);

        $activitySummary = [
            'total_activities' => UserActivity::count(),
            'unique_users_today' => UserActivity::today()->distinct('user_id')->count(),
            'logins_today' => UserActivity::logins()->today()->count(),
            'creates_today' => UserActivity::creates()->today()->count(),
            'updates_today' => UserActivity::updates()->today()->count(),
            'deletes_today' => UserActivity::deletes()->today()->count(),
        ];

        $chartData = $this->getUserActivityChartData();

        return view('admin.performance.user-activity', compact('activities', 'activitySummary', 'chartData'));
    }

    /**
     * Get system health summary
     */
    private function getSystemHealthSummary()
    {
        return [
            'total' => SystemHealth::count(),
            'healthy' => SystemHealth::healthy()->count(),
            'warning' => SystemHealth::warning()->count(),
            'critical' => SystemHealth::critical()->count(),
            'recent_issues' => SystemHealth::where('status', '!=', 'healthy')->recent(24)->count(),
        ];
    }

    /**
     * Get performance metrics summary
     */
    private function getPerformanceMetricsSummary()
    {
        return [
            'total_requests' => PerformanceMetric::count(),
            'avg_response_time' => round(PerformanceMetric::avg('response_time'), 2),
            'slow_requests' => PerformanceMetric::slowRequests()->count(),
            'today_requests' => PerformanceMetric::today()->count(),
        ];
    }

    /**
     * Get error logs summary
     */
    private function getErrorLogsSummary()
    {
        return [
            'total_errors' => ErrorLog::count(),
            'unresolved' => ErrorLog::unresolved()->count(),
            'critical' => ErrorLog::critical()->count(),
            'recent' => ErrorLog::recent(24)->count(),
        ];
    }

    /**
     * Get user activity summary
     */
    private function getUserActivitySummary()
    {
        return [
            'total_activities' => UserActivity::count(),
            'unique_users_today' => UserActivity::today()->distinct('user_id')->count(),
            'logins_today' => UserActivity::logins()->today()->count(),
            'recent_activities' => UserActivity::recent(1)->count(),
        ];
    }

    /**
     * Get system health chart data
     */
    private function getSystemHealthChartData()
    {
        $last7Days = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $last7Days->push([
                'date' => $date->format('Y-m-d'),
                'healthy' => SystemHealth::healthy()->whereDate('recorded_at', $date)->count(),
                'warning' => SystemHealth::warning()->whereDate('recorded_at', $date)->count(),
                'critical' => SystemHealth::critical()->whereDate('recorded_at', $date)->count(),
            ]);
        }

        return $last7Days;
    }

    /**
     * Get performance chart data
     */
    private function getPerformanceChartData()
    {
        $last24Hours = collect();
        for ($i = 23; $i >= 0; $i--) {
            $hour = Carbon::now()->subHours($i);
            $last24Hours->push([
                'hour' => $hour->format('H:00'),
                'requests' => PerformanceMetric::whereBetween('recorded_at', [
                    $hour->startOfHour(),
                    $hour->endOfHour()
                ])->count(),
                'avg_response_time' => PerformanceMetric::whereBetween('recorded_at', [
                    $hour->startOfHour(),
                    $hour->endOfHour()
                ])->avg('response_time') ?: 0,
            ]);
        }

        return $last24Hours;
    }

    /**
     * Get error logs chart data
     */
    private function getErrorLogsChartData()
    {
        $last7Days = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $last7Days->push([
                'date' => $date->format('Y-m-d'),
                'error' => ErrorLog::byLevel('error')->whereDate('created_at', $date)->count(),
                'warning' => ErrorLog::byLevel('warning')->whereDate('created_at', $date)->count(),
                'critical' => ErrorLog::critical()->whereDate('created_at', $date)->count(),
            ]);
        }

        return $last7Days;
    }

    /**
     * Get user activity chart data
     */
    private function getUserActivityChartData()
    {
        $last7Days = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $last7Days->push([
                'date' => $date->format('Y-m-d'),
                'logins' => UserActivity::logins()->whereDate('performed_at', $date)->count(),
                'creates' => UserActivity::creates()->whereDate('performed_at', $date)->count(),
                'updates' => UserActivity::updates()->whereDate('performed_at', $date)->count(),
                'deletes' => UserActivity::deletes()->whereDate('performed_at', $date)->count(),
            ]);
        }

        return $last7Days;
    }

    /**
     * Mark error as resolved
     */
    public function resolveError(Request $request, $id)
    {
        $errorLog = ErrorLog::findOrFail($id);
        
        $errorLog->markAsResolved(auth()->id(), $request->input('resolution_notes'));
        
        return redirect()->back()->with('success', 'Error marked as resolved successfully.');
    }

    /**
     * Export performance data
     */
    public function exportData(Request $request)
    {
        $type = $request->input('type', 'performance');
        $format = $request->input('format', 'csv');
        
        // Implementation for data export would go here
        // This is a placeholder for the export functionality
        
        return response()->json(['message' => 'Export functionality will be implemented']);
    }
}
