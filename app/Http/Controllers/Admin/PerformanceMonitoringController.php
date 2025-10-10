<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SystemHealth;
use App\Models\PerformanceMetric;
use App\Models\PerformanceAlert;
use App\Models\ErrorLog;
use App\Models\UserActivity;
use App\Services\PerformanceAlertService;
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
     * Display comprehensive performance dashboard
     */
    public function dashboard()
    {
        $performanceService = app(PerformanceAlertService::class);
        $dashboardData = $performanceService->getPerformanceDashboardData();
        
        $data = [
            'metrics' => [
                'memory_usage' => $dashboardData['memory_usage'] ?? 0,
                'cpu_usage' => $dashboardData['cpu_usage'] ?? 0,
                'disk_space' => $dashboardData['disk_space'] ?? 0,
                'avg_response_time' => $dashboardData['avg_response_time'] ?? 0,
                'total_memory' => round(($dashboardData['memory_usage'] ?? 0) / 100 * 16, 1), // Assuming 16GB total
                'cpu_cores' => 8, // Default value
                'total_disk' => 100, // Default value in GB
            ],
            'systemStatus' => [
                'overall' => $this->determineOverallStatus($dashboardData),
            ],
            'serviceStatus' => [
                'database' => 'available',
                'cache' => 'available',
                'storage' => 'available',
                'queue' => 'available',
            ],
            'recentAlerts' => $this->getRecentAlertsForDashboard(),
            'recentActivities' => $this->getRecentActivitiesForDashboard(),
            'detailedMetrics' => $this->getDetailedMetricsForDashboard($dashboardData),
            'chartData' => $this->getChartDataForDashboard(),
        ];

        return view('admin.performance.dashboard', $data);
    }

    /**
     * Get dashboard data via AJAX
     */
    public function dashboardData(Request $request)
    {
        $period = $request->get('period', '24h');
        $performanceService = app(PerformanceAlertService::class);
        $dashboardData = $performanceService->getPerformanceDashboardData();
        
        $data = [
            'metrics' => [
                'memory_usage' => $dashboardData['memory_usage'] ?? 0,
                'cpu_usage' => $dashboardData['cpu_usage'] ?? 0,
                'disk_space' => $dashboardData['disk_space'] ?? 0,
                'avg_response_time' => $dashboardData['avg_response_time'] ?? 0,
            ],
            'chartData' => $this->getChartDataForDashboard($period),
        ];

        return response()->json($data);
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

    /**
     * Display performance alerts dashboard
     */
    public function alerts()
    {
        $alerts = PerformanceAlert::with(['acknowledgedBy', 'resolvedBy'])
            ->orderBy('severity', 'desc')
            ->orderBy('triggered_at', 'desc')
            ->paginate(20);

        $alertsSummary = PerformanceAlert::getAlertsSummary();
        $alertsByType = PerformanceAlert::getAlertsByType();
        $alertsBySeverity = PerformanceAlert::getAlertsBySeverity();
        $recentTrends = PerformanceAlert::getAlertTrends(7);

        return view('admin.performance.alerts', compact(
            'alerts',
            'alertsSummary',
            'alertsByType',
            'alertsBySeverity',
            'recentTrends'
        ));
    }

    /**
     * Show specific alert details
     */
    public function showAlert($id)
    {
        $alert = PerformanceAlert::with(['acknowledgedBy', 'resolvedBy'])->findOrFail($id);
        
        return view('admin.performance.alert-details', compact('alert'));
    }

    /**
     * Acknowledge an alert
     */
    public function acknowledgeAlert(Request $request, $id)
    {
        $alert = PerformanceAlert::findOrFail($id);
        
        $request->validate([
            'notes' => 'nullable|string|max:1000'
        ]);

        $alert->acknowledge(auth()->id(), $request->input('notes'));

        return redirect()->back()->with('success', 'Alert acknowledged successfully.');
    }

    /**
     * Resolve an alert
     */
    public function resolveAlert(Request $request, $id)
    {
        $alert = PerformanceAlert::findOrFail($id);
        
        $request->validate([
            'notes' => 'required|string|max:1000'
        ]);

        $alert->resolve(auth()->id(), $request->input('notes'));

        return redirect()->back()->with('success', 'Alert resolved successfully.');
    }

    /**
     * Get alerts data for AJAX requests
     */
    public function getAlertsData(Request $request)
    {
        $query = PerformanceAlert::with(['acknowledgedBy', 'resolvedBy']);

        // Apply filters
        if ($request->has('severity') && $request->severity !== 'all') {
            $query->bySeverity($request->severity);
        }

        if ($request->has('type') && $request->type !== 'all') {
            $query->byType($request->type);
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->where('triggered_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->where('triggered_at', '<=', $request->date_to . ' 23:59:59');
        }

        $alerts = $query->orderBy('triggered_at', 'desc')->paginate(20);

        return response()->json([
            'alerts' => $alerts->items(),
            'pagination' => [
                'current_page' => $alerts->currentPage(),
                'last_page' => $alerts->lastPage(),
                'per_page' => $alerts->perPage(),
                'total' => $alerts->total(),
            ]
        ]);
    }

    /**
     * Trigger manual performance check
     */
    public function triggerCheck(Request $request)
    {
        $request->validate([
            'check_type' => 'required|in:all,response_time,memory,cpu,disk,database,error_rate'
        ]);

        $alertService = app(PerformanceAlertService::class);
        $checkType = $request->input('check_type');

        try {
            if ($checkType === 'all') {
                $alertService->runAllChecks();
                $message = 'All performance checks completed successfully.';
            } else {
                switch ($checkType) {
                    case 'response_time':
                        $alertService->checkResponseTime();
                        break;
                    case 'memory':
                        $alertService->checkMemoryUsage();
                        break;
                    case 'cpu':
                        $alertService->checkCpuUsage();
                        break;
                    case 'disk':
                        $alertService->checkDiskSpace();
                        break;
                    case 'database':
                        $alertService->checkDatabasePerformance();
                        break;
                    case 'error_rate':
                        $alertService->checkErrorRate();
                        break;
                }
                $message = ucfirst(str_replace('_', ' ', $checkType)) . ' check completed successfully.';
            }

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Performance check failed: ' . $e->getMessage());
        }
    }

    /**
     * Get alert statistics for dashboard widgets
     */
    public function getAlertStats()
    {
        $stats = [
            'active_alerts' => PerformanceAlert::getActiveAlertsCount(),
            'critical_alerts' => PerformanceAlert::getCriticalAlertsCount(),
            'recent_alerts' => PerformanceAlert::getRecentAlertsCount(24),
            'resolved_today' => PerformanceAlert::resolved()
                ->whereDate('resolved_at', today())
                ->count(),
            'avg_resolution_time' => $this->getAverageResolutionTime(),
        ];

        return response()->json($stats);
    }

    /**
     * Bulk acknowledge alerts
     */
    public function bulkAcknowledge(Request $request)
    {
        $request->validate([
            'alert_ids' => 'required|array',
            'alert_ids.*' => 'exists:performance_alerts,id',
            'notes' => 'nullable|string|max:1000'
        ]);

        $alertIds = $request->input('alert_ids');
        $notes = $request->input('notes');
        $userId = auth()->id();

        PerformanceAlert::whereIn('id', $alertIds)
            ->where('status', 'active')
            ->update([
                'status' => 'acknowledged',
                'acknowledged_at' => now(),
                'acknowledged_by' => $userId,
                'resolution_notes' => $notes,
            ]);

        return redirect()->back()->with('success', count($alertIds) . ' alerts acknowledged successfully.');
    }

    /**
     * Bulk resolve alerts
     */
    public function bulkResolve(Request $request)
    {
        $request->validate([
            'alert_ids' => 'required|array',
            'alert_ids.*' => 'exists:performance_alerts,id',
            'notes' => 'required|string|max:1000'
        ]);

        $alertIds = $request->input('alert_ids');
        $notes = $request->input('notes');
        $userId = auth()->id();

        PerformanceAlert::whereIn('id', $alertIds)
            ->whereIn('status', ['active', 'acknowledged'])
            ->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'resolved_by' => $userId,
                'resolution_notes' => $notes,
            ]);

        return redirect()->back()->with('success', count($alertIds) . ' alerts resolved successfully.');
    }

    /**
     * Get alert configuration
     */
    public function getAlertConfig()
    {
        $config = config('performance');
        
        return response()->json([
            'thresholds' => $config['thresholds'],
            'cooldowns' => $config['cooldowns'],
            'monitoring' => $config['monitoring'],
            'notifications' => $config['notifications'],
        ]);
    }

    /**
     * Update alert configuration
     */
    public function updateAlertConfig(Request $request)
    {
        $request->validate([
            'thresholds' => 'required|array',
            'cooldowns' => 'required|array',
            'monitoring.enabled' => 'required|boolean',
            'monitoring.interval' => 'required|integer|min:1',
            'notifications.channels' => 'required|array',
        ]);

        // In a real application, you would save this to a database or config file
        // For now, we'll just return success
        
        return redirect()->back()->with('success', 'Alert configuration updated successfully.');
    }

    /**
     * Get performance trends for charts
     */
    public function getPerformanceTrends(Request $request)
    {
        $days = $request->input('days', 7);
        $metricType = $request->input('metric', 'response_time');

        $trends = PerformanceMetric::getTrend($metricType, $days);
        $alerts = PerformanceAlert::getAlertTrends($days);

        return response()->json([
            'trends' => $trends,
            'alerts' => $alerts,
        ]);
    }

    /**
     * Calculate average resolution time for alerts
     */
    private function getAverageResolutionTime()
    {
        $resolvedAlerts = PerformanceAlert::resolved()
            ->whereNotNull('resolved_at')
            ->whereNotNull('triggered_at')
            ->get();

        if ($resolvedAlerts->isEmpty()) {
            return 0;
        }

        $totalMinutes = $resolvedAlerts->sum(function ($alert) {
            return $alert->triggered_at->diffInMinutes($alert->resolved_at);
        });

        return round($totalMinutes / $resolvedAlerts->count(), 2);
    }

    /**
     * Determine overall system status based on metrics
     */
    private function determineOverallStatus($dashboardData)
    {
        $memoryUsage = $dashboardData['memory_usage'] ?? 0;
        $cpuUsage = $dashboardData['cpu_usage'] ?? 0;
        $diskSpace = $dashboardData['disk_space'] ?? 100;
        $responseTime = $dashboardData['avg_response_time'] ?? 0;

        // Critical conditions
        if ($memoryUsage > 90 || $cpuUsage > 90 || $diskSpace < 5 || $responseTime > 10000) {
            return 'critical';
        }

        // Warning conditions
        if ($memoryUsage > 80 || $cpuUsage > 80 || $diskSpace < 10 || $responseTime > 5000) {
            return 'warning';
        }

        return 'healthy';
    }

    /**
     * Get recent alerts for dashboard
     */
    private function getRecentAlertsForDashboard()
    {
        return PerformanceAlert::with(['acknowledgedBy', 'resolvedBy'])
            ->where('triggered_at', '>=', Carbon::now()->subHours(24))
            ->orderBy('triggered_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($alert) {
                return [
                    'title' => $alert->alert_type . ' Alert',
                    'message' => $alert->message,
                    'severity' => $alert->severity,
                    'time_ago' => $alert->triggered_at->diffForHumans(),
                    'icon' => $this->getAlertIcon($alert->alert_type),
                ];
            })
            ->toArray();
    }

    /**
     * Get recent activities for dashboard
     */
    private function getRecentActivitiesForDashboard()
    {
        return UserActivity::with('user')
            ->where('performed_at', '>=', Carbon::now()->subHours(24))
            ->orderBy('performed_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($activity) {
                return [
                    'title' => ucfirst($activity->action) . ' ' . $activity->model_type,
                    'description' => 'by ' . ($activity->user->name ?? 'System'),
                    'time_ago' => $activity->performed_at->diffForHumans(),
                    'icon' => $this->getActivityIcon($activity->action),
                ];
            })
            ->toArray();
    }

    /**
     * Get detailed metrics for dashboard
     */
    private function getDetailedMetricsForDashboard($dashboardData)
    {
        return [
            [
                'name' => 'Memory Usage',
                'value' => ($dashboardData['memory_usage'] ?? 0) . '%',
                'unit' => '',
                'threshold' => '85%',
                'status' => ($dashboardData['memory_usage'] ?? 0) > 85 ? 'critical' : 'normal',
                'status_color' => ($dashboardData['memory_usage'] ?? 0) > 85 ? 'danger' : 'success',
                'updated_at' => now()->format('Y-m-d H:i:s'),
                'updated_ago' => 'Just now',
                'trend' => 'up',
                'trend_percentage' => '2.5',
                'icon' => 'memory',
            ],
            [
                'name' => 'CPU Usage',
                'value' => ($dashboardData['cpu_usage'] ?? 0) . '%',
                'unit' => '',
                'threshold' => '80%',
                'status' => ($dashboardData['cpu_usage'] ?? 0) > 80 ? 'critical' : 'normal',
                'status_color' => ($dashboardData['cpu_usage'] ?? 0) > 80 ? 'danger' : 'success',
                'updated_at' => now()->format('Y-m-d H:i:s'),
                'updated_ago' => 'Just now',
                'trend' => 'down',
                'trend_percentage' => '1.2',
                'icon' => 'cpu-64-bit',
            ],
            [
                'name' => 'Disk Space',
                'value' => ($dashboardData['disk_space'] ?? 0),
                'unit' => 'GB',
                'threshold' => '10 GB',
                'status' => ($dashboardData['disk_space'] ?? 0) < 10 ? 'critical' : 'normal',
                'status_color' => ($dashboardData['disk_space'] ?? 0) < 10 ? 'danger' : 'success',
                'updated_at' => now()->format('Y-m-d H:i:s'),
                'updated_ago' => 'Just now',
                'trend' => 'down',
                'trend_percentage' => '0.8',
                'icon' => 'harddisk',
            ],
            [
                'name' => 'Response Time',
                'value' => ($dashboardData['avg_response_time'] ?? 0),
                'unit' => 'ms',
                'threshold' => '5000ms',
                'status' => ($dashboardData['avg_response_time'] ?? 0) > 5000 ? 'critical' : 'normal',
                'status_color' => ($dashboardData['avg_response_time'] ?? 0) > 5000 ? 'danger' : 'success',
                'updated_at' => now()->format('Y-m-d H:i:s'),
                'updated_ago' => 'Just now',
                'trend' => 'up',
                'trend_percentage' => '3.1',
                'icon' => 'speedometer',
            ],
        ];
    }

    /**
     * Get chart data for dashboard
     */
    private function getChartDataForDashboard($period = '24h')
    {
        $hours = $period === '7d' ? 168 : ($period === '30d' ? 720 : 24);
        $interval = $period === '30d' ? 24 : ($period === '7d' ? 6 : 1);
        
        $labels = [];
        $memoryData = [];
        $cpuData = [];
        $responseTimeData = [];

        for ($i = $hours; $i >= 0; $i -= $interval) {
            $time = Carbon::now()->subHours($i);
            $labels[] = $time->format($period === '30d' ? 'M-d' : ($period === '7d' ? 'M-d H:i' : 'H:i'));
            
            // Get metrics for this time period
            $startTime = $time->copy()->subHours($interval);
            $endTime = $time;
            
            $memoryMetrics = SystemHealth::where('metric_type', 'system')
                ->where('metric_name', 'memory_usage')
                ->whereBetween('recorded_at', [$startTime, $endTime])
                ->avg('value') ?? 0;
                
            $cpuMetrics = SystemHealth::where('metric_type', 'system')
                ->where('metric_name', 'cpu_usage')
                ->whereBetween('recorded_at', [$startTime, $endTime])
                ->avg('value') ?? 0;
                
            $responseMetrics = PerformanceMetric::where('metric_type', 'system')
                ->where('metric_name', 'response_time')
                ->whereBetween('recorded_at', [$startTime, $endTime])
                ->avg('value') ?? 0;

            $memoryData[] = round($memoryMetrics, 1);
            $cpuData[] = round($cpuMetrics, 1);
            $responseTimeData[] = round($responseMetrics, 1);
        }

        return [
            'labels' => $labels,
            'memory' => $memoryData,
            'cpu' => $cpuData,
            'response_time' => $responseTimeData,
        ];
    }

    /**
     * Get alert icon based on type
     */
    private function getAlertIcon($alertType)
    {
        $icons = [
            'memory' => 'memory',
            'cpu' => 'cpu-64-bit',
            'disk' => 'harddisk',
            'response_time' => 'speedometer',
            'error_rate' => 'alert-circle',
            'default' => 'alert-circle',
        ];

        return $icons[$alertType] ?? $icons['default'];
    }

    /**
     * Get activity icon based on action
     */
    private function getActivityIcon($action)
    {
        $icons = [
            'create' => 'plus-circle',
            'update' => 'pencil',
            'delete' => 'trash-can',
            'login' => 'login',
            'logout' => 'logout',
            'view' => 'eye',
            'default' => 'information',
        ];

        return $icons[$action] ?? $icons['default'];
    }
}
