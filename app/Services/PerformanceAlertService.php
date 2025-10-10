<?php

namespace App\Services;

use App\Models\SystemHealth;
use App\Models\PerformanceMetric;
use App\Models\ErrorLog;
use App\Models\User;
use App\Notifications\PerformanceAlert;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

class PerformanceAlertService
{
    protected $thresholds;
    protected $alertCooldowns;

    public function __construct()
    {
        $this->thresholds = config('performance.alert_thresholds', [
            'response_time' => Constants::RESPONSE_TIME_THRESHOLD, // milliseconds
            'memory_usage' => Constants::MEMORY_USAGE_THRESHOLD, // percentage
            'cpu_usage' => Constants::CPU_USAGE_THRESHOLD, // percentage
            'disk_space' => Constants::DISK_SPACE_THRESHOLD, // GB remaining
            'error_rate' => Constants::ERROR_RATE_THRESHOLD, // percentage
            'system_load' => Constants::SYSTEM_LOAD_THRESHOLD, // load average
            'database_query_time' => Constants::DATABASE_QUERY_TIME_THRESHOLD, // milliseconds
        ]);

        $this->alertCooldowns = config('performance.alert_cooldowns', [
            'default' => Constants::ALERT_COOLDOWN_DEFAULT, // 5 minutes
            'critical' => Constants::ALERT_COOLDOWN_CRITICAL, // 10 minutes
            'emergency' => Constants::ALERT_COOLDOWN_EMERGENCY, // 30 minutes
        ]);
    }

    /**
     * Check all performance metrics and trigger alerts if necessary
     */
    public function checkPerformanceMetrics()
    {
        try {
            $this->checkResponseTime();
            $this->checkMemoryUsage();
            $this->checkCpuUsage();
            $this->checkDiskSpace();
            $this->checkErrorRate();
            $this->checkSystemLoad();
            $this->checkDatabasePerformance();
            $this->checkServiceAvailability();
            
            Log::info('Performance metrics check completed successfully');
        } catch (\Exception $e) {
            Log::error('Error checking performance metrics: ' . $e->getMessage());
        }
    }

    /**
     * Check response time metrics
     */
    protected function checkResponseTime()
    {
        $avgResponseTime = PerformanceMetric::where('recorded_at', '>=', Carbon::now()->subMinutes(5))
            ->where('metric_type', 'system')
            ->where('metric_name', 'response_time')
            ->avg('value');

        if ($avgResponseTime && $avgResponseTime > $this->thresholds['response_time']) {
            $this->triggerAlert(
                'high_response_time',
                PerformanceAlert::highResponseTime($avgResponseTime, $this->thresholds['response_time']),
                'warning'
            );
        }
    }

    /**
     * Check memory usage
     */
    protected function checkMemoryUsage()
    {
        $memoryUsage = $this->getCurrentMemoryUsage();
        
        if ($memoryUsage > $this->thresholds['memory_usage']) {
            $severity = $memoryUsage > Constants::CRITICAL_MEMORY_THRESHOLD ? 'critical' : 'warning';
            
            $this->triggerAlert(
                'high_memory_usage',
                PerformanceAlert::highMemoryUsage($memoryUsage, $this->thresholds['memory_usage']),
                $severity
            );
        }
    }

    /**
     * Check CPU usage
     */
    protected function checkCpuUsage()
    {
        $cpuUsage = $this->getCurrentCpuUsage();
        
        if ($cpuUsage > $this->thresholds['cpu_usage']) {
            $severity = $cpuUsage > Constants::CRITICAL_CPU_THRESHOLD ? 'critical' : 'warning';
            
            $this->triggerAlert(
                'high_cpu_usage',
                PerformanceAlert::highCpuUsage($cpuUsage, $this->thresholds['cpu_usage']),
                $severity
            );
        }
    }

    /**
     * Check disk space
     */
    protected function checkDiskSpace()
    {
        $availableSpace = $this->getAvailableDiskSpace();
        
        if ($availableSpace < $this->thresholds['disk_space']) {
            $severity = $availableSpace < Constants::CRITICAL_DISK_SPACE ? 'critical' : 'warning';
            
            $this->triggerAlert(
                'disk_space_low',
                PerformanceAlert::diskSpaceLow($availableSpace, $this->thresholds['disk_space']),
                $severity
            );
        }
    }

    /**
     * Check error rate
     */
    protected function checkErrorRate()
    {
        $totalRequests = PerformanceMetric::where('recorded_at', '>=', Carbon::now()->subHour())
            ->where('metric_type', 'request')
            ->count();
        $errorRequests = PerformanceMetric::where('recorded_at', '>=', Carbon::now()->subHour())
            ->where('metric_type', 'request')
            ->where('metric_name', 'status_code')
            ->where('value', '>=', 400)
            ->count();

        if ($totalRequests > 0) {
            $errorRate = ($errorRequests / $totalRequests) * 100;
            
            if ($errorRate > $this->thresholds['error_rate']) {
                $severity = $errorRate > Constants::CRITICAL_ERROR_RATE ? 'critical' : 'warning';
                
                $this->triggerAlert(
                    'high_error_rate',
                    PerformanceAlert::highErrorRate($errorRate, $this->thresholds['error_rate']),
                    $severity
                );
            }
        }
    }

    /**
     * Get error rate percentage
     */
    protected function getErrorRate()
    {
        try {
            $totalRequests = PerformanceMetric::where('recorded_at', '>=', Carbon::now()->subHour(Constants::ONE_COUNT))
                ->where('metric_type', 'request')
                ->count();

            $errorRequests = PerformanceMetric::where('recorded_at', '>=', Carbon::now()->subHour(Constants::ONE_COUNT))
                ->where('metric_type', 'request')
                ->where('value', '>=', Constants::HTTP_BAD_REQUEST) // HTTP 400+ status codes
                ->count();

            if ($totalRequests > Constants::ZERO_COUNT) {
                return ($errorRequests / $totalRequests) * Constants::PERCENTAGE_DIVISOR;
            }

            return Constants::ZERO_COUNT;
        } catch (\Exception $e) {
            Log::error('Error calculating error rate: ' . $e->getMessage());
            return Constants::ZERO_COUNT;
        }
    }

    /**
     * Check system load
     */
    protected function checkSystemLoad()
    {
        $systemLoad = $this->getCurrentSystemLoad();
        
        if ($systemLoad > $this->thresholds['system_load']) {
            $severity = $systemLoad > Constants::CRITICAL_SYSTEM_LOAD ? 'critical' : 'warning';
            
            $this->triggerAlert(
                'system_overload',
                PerformanceAlert::systemOverload($systemLoad, $this->thresholds['system_load']),
                $severity
            );
        }
    }

    /**
     * Check database performance
     */
    protected function checkDatabasePerformance()
    {
        $avgQueryTime = $this->getAverageDatabaseQueryTime();
        
        if ($avgQueryTime > $this->thresholds['database_query_time']) {
            $this->triggerAlert(
                'database_slow_queries',
                PerformanceAlert::databaseSlowQueries($avgQueryTime),
                'warning'
            );
        }
    }

    /**
     * Check service availability
     */
    protected function checkServiceAvailability()
    {
        $criticalServices = [
            'database' => $this->isDatabaseAvailable(),
            'cache' => $this->isCacheAvailable(),
            'storage' => $this->isStorageAvailable(),
            'mail' => $this->isMailServiceAvailable(),
        ];

        foreach ($criticalServices as $service => $isAvailable) {
            if (!$isAvailable) {
                $this->triggerAlert(
                    'service_unavailable',
                    PerformanceAlert::serviceUnavailable($service),
                    'emergency'
                );
            }
        }
    }

    /**
     * Trigger an alert if cooldown period has passed
     */
    protected function triggerAlert($alertType, $notification, $severity = 'warning')
    {
        $cacheKey = "performance_alert_{$alertType}";
        $cooldownPeriod = $this->alertCooldowns[$severity] ?? $this->alertCooldowns['default'];
        
        if (!Cache::has($cacheKey)) {
            // Send notification to admin users
            $adminUsers = User::role('admin')->get();
            
            Notification::send($adminUsers, $notification);
            
            // Set cooldown
            Cache::put($cacheKey, true, $cooldownPeriod);
            
            Log::info("Performance alert triggered: {$alertType}", [
                'severity' => $severity,
                'cooldown' => $cooldownPeriod
            ]);
        }
    }

    /**
     * Get current memory usage percentage
     */
    protected function getCurrentMemoryUsage()
    {
        try {
            $memoryLimit = ini_get('memory_limit');
            $memoryUsage = memory_get_usage(true);
            
            if ($memoryLimit === '-1') {
                return Constants::ZERO_COUNT; // No limit set
            }
            
            $memoryLimitBytes = $this->convertToBytes($memoryLimit);
            return ($memoryUsage / $memoryLimitBytes) * Constants::PERCENTAGE_DIVISOR;
        } catch (\Exception $e) {
            return Constants::ZERO_COUNT;
        }
    }

    /**
     * Get current CPU usage percentage
     */
    protected function getCurrentCpuUsage()
    {
        try {
            // This is a simplified CPU usage calculation
            // In production, you might want to use a more sophisticated method
            $load = sys_getloadavg();
            if ($load && isset($load[Constants::ZERO_COUNT])) {
                return ($load[Constants::ZERO_COUNT] / Constants::CPU_CORES_DEFAULT) * Constants::PERCENTAGE_DIVISOR;
            }
        } catch (\Exception $e) {
            // Fallback for systems that don't support sys_getloadavg
        }
        
        return Constants::ZERO_COUNT;
    }

    /**
     * Get available disk space in GB
     */
    protected function getAvailableDiskSpace()
    {
        try {
            $bytes = disk_free_space(storage_path());
            return $bytes ? round($bytes / Constants::BYTES_TO_GB_DIVISOR, 2) : Constants::ZERO_COUNT;
        } catch (\Exception $e) {
            return Constants::ZERO_COUNT;
        }
    }

    /**
     * Get current system load
     */
    protected function getCurrentSystemLoad()
    {
        try {
            $load = sys_getloadavg();
            return $load ? $load[Constants::ZERO_COUNT] : Constants::ZERO_COUNT;
        } catch (\Exception $e) {
            return Constants::ZERO_COUNT;
        }
    }

    /**
     * Get average database query time
     */
    protected function getAverageDatabaseQueryTime()
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $end = microtime(true);
            
            return ($end - $start) * Constants::MILLISECONDS_MULTIPLIER; // Convert to milliseconds
        } catch (\Exception $e) {
            return Constants::ZERO_COUNT;
        }
    }

    /**
     * Check if database is available
     */
    protected function isDatabaseAvailable()
    {
        try {
            DB::select('SELECT 1');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if cache is available
     */
    protected function isCacheAvailable()
    {
        try {
            Cache::put('health_check', true, Constants::CACHE_HEALTH_CHECK_SECONDS);
            return Cache::get('health_check') === true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if storage is available
     */
    protected function isStorageAvailable()
    {
        try {
            $testFile = storage_path('app/health_check.txt');
            file_put_contents($testFile, 'test');
            $result = file_exists($testFile);
            if ($result) {
                unlink($testFile);
            }
            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if mail service is available
     */
    protected function isMailServiceAvailable()
    {
        try {
            // This is a basic check - you might want to implement a more thorough test
            return config('mail.default') !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Convert memory limit string to bytes
     */
    protected function convertToBytes($val)
    {
        $val = trim($val);
        $last = strtolower($val[strlen($val) - 1]);
        $val = (int) $val;
        
        switch ($last) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }
        
        return $val;
    }

    /**
     * Get performance dashboard data with alerts
     */
    public function getPerformanceDashboardData()
    {
        return [
            'current_metrics' => [
                'memory_usage' => $this->getCurrentMemoryUsage(),
                'cpu_usage' => $this->getCurrentCpuUsage(),
                'disk_space' => $this->getAvailableDiskSpace(),
                'system_load' => $this->getCurrentSystemLoad(),
                'avg_response_time' => PerformanceMetric::where('recorded_at', '>=', Carbon::now()->subMinutes(Constants::PERFORMANCE_CHECK_MINUTES))
                    ->where('metric_type', 'system')
                    ->where('metric_name', 'response_time')
                    ->avg('value'),
            ],
            'thresholds' => $this->thresholds,
            'recent_alerts' => $this->getRecentAlerts(),
            'service_status' => [
                'database' => $this->isDatabaseAvailable(),
                'cache' => $this->isCacheAvailable(),
                'storage' => $this->isStorageAvailable(),
                'mail' => $this->isMailServiceAvailable(),
            ]
        ];
    }

    /**
     * Get recent performance alerts
     */
    protected function getRecentAlerts()
    {
        return DB::table('notifications')
            ->where('type', 'App\Notifications\PerformanceAlert')
            ->where('created_at', '>=', Carbon::now()->subHours(Constants::RECENT_ALERTS_HOURS))
            ->orderBy('created_at', 'desc')
            ->limit(Constants::RECENT_ALERTS_LIMIT)
            ->get()
            ->map(function ($notification) {
                $data = json_decode($notification->data, true);
                return [
                    'id' => $notification->id,
                    'title' => $data['title'] ?? 'Performance Alert',
                    'message' => $data['message'] ?? '',
                    'severity' => $data['severity'] ?? 'warning',
                    'created_at' => $notification->created_at,
                    'read_at' => $notification->read_at,
                ];
            });
    }

    /**
     * Update alert thresholds
     */
    public function updateThresholds(array $newThresholds)
    {
        $this->thresholds = array_merge($this->thresholds, $newThresholds);
        
        // You might want to store these in database or config file
        Cache::put('performance_alert_thresholds', $this->thresholds, Constants::PERFORMANCE_CACHE_HOURS * 3600); // 24 hours
        
        Log::info('Performance alert thresholds updated', $newThresholds);
    }

    /**
     * Clear alert cooldown for testing
     */
    public function clearAlertCooldown($alertType)
    {
        $cacheKey = "performance_alert_{$alertType}";
        Cache::forget($cacheKey);
    }
}