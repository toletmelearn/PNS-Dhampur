<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class SecurityMonitoringController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:super_admin,admin']);
        $this->middleware('throttle:60,1');
    }

    /**
     * Display security monitoring dashboard
     */
    public function dashboard()
    {
        $securityStats = $this->getSecurityStats();
        $recentEvents = $this->getRecentSecurityEvents();
        $rateLimitStats = $this->getRateLimitStats();
        $suspiciousActivities = $this->getSuspiciousActivities();
        $systemHealth = $this->getSystemHealth();
        $blockedIPs = $this->getBlockedIPs();

        return view('admin.security.dashboard', compact(
            'securityStats', 'recentEvents', 'rateLimitStats', 'suspiciousActivities', 'systemHealth', 'blockedIPs'
        ));
    }

    /**
     * Get security statistics for API
     */
    public function getSecurityStats()
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $lastWeek = Carbon::now()->subWeek();

        return response()->json([
            'events_24h' => $this->getSecurityEventsCount($today),
            'blocked_ips' => $this->getBlockedIPsCount(),
            'failed_logins_24h' => $this->getFailedLoginsCount($today),
            'active_sessions' => $this->getActiveSessionsCount(),
            'suspicious_uploads_today' => $this->getSuspiciousUploadsCount($today),
            'rate_limit_violations_today' => $this->getRateLimitViolationsCount($today),
            'virus_detections_today' => $this->getVirusDetectionsCount($today)
        ]);
    }

    /**
     * Get recent security events for API
     */
    public function getRecentSecurityEvents($limit = 50)
    {
        $events = [];

        // Parse security logs
        $securityLogPath = storage_path('logs/security.log');
        if (file_exists($securityLogPath)) {
            $lines = array_slice(file($securityLogPath), -$limit);
            foreach ($lines as $line) {
                $event = $this->parseLogLine($line);
                if ($event) {
                    $events[] = $event;
                }
            }
        }

        return response()->json(['events' => array_reverse($events)]);
    }

    /**
     * Get suspicious activities for API
     */
    public function getSuspiciousActivities()
    {
        $activities = [];

        // Multiple failed logins
        $multipleFailedLogins = $this->detectMultipleFailedLogins();
        foreach ($multipleFailedLogins as $activity) {
            $activities[] = [
                'id' => uniqid(),
                'timestamp' => $activity['timestamp'],
                'type' => 'Multiple Failed Logins',
                'risk_level' => 'HIGH',
                'risk_color' => 'danger',
                'ip_address' => $activity['ip'],
                'user_agent' => $activity['user_agent'] ?? 'Unknown',
                'details' => "Failed login attempts: {$activity['count']}"
            ];
        }

        // Unusual file uploads
        $unusualUploads = $this->detectUnusualFileUploads();
        foreach ($unusualUploads as $activity) {
            $activities[] = [
                'id' => uniqid(),
                'timestamp' => $activity['timestamp'],
                'type' => 'Suspicious File Upload',
                'risk_level' => 'MEDIUM',
                'risk_color' => 'warning',
                'ip_address' => $activity['ip'],
                'user_agent' => $activity['user_agent'] ?? 'Unknown',
                'details' => "Suspicious file: {$activity['filename']}"
            ];
        }

        // Rapid requests
        $rapidRequests = $this->detectRapidRequests();
        foreach ($rapidRequests as $activity) {
            $activities[] = [
                'id' => uniqid(),
                'timestamp' => $activity['timestamp'],
                'type' => 'Rapid Requests',
                'risk_level' => 'MEDIUM',
                'risk_color' => 'warning',
                'ip_address' => $activity['ip'],
                'user_agent' => $activity['user_agent'] ?? 'Unknown',
                'details' => "Requests per minute: {$activity['count']}"
            ];
        }

        return response()->json(['activities' => $activities]);
    }

    /**
     * Block an IP address
     */
    public function blockIP(Request $request)
    {
        $request->validate([
            'ip_address' => 'required|ip',
            'reason' => 'required|string',
            'duration' => 'required|string'
        ]);

        $ipAddress = $request->ip_address;
        $reason = $request->reason;
        $duration = $request->duration;

        // Calculate expiry time
        $expiresAt = null;
        if ($duration !== 'permanent') {
            $hours = (int) $duration;
            $expiresAt = Carbon::now()->addHours($hours);
        }

        // Store blocked IP in cache and database
        $blockData = [
            'ip_address' => $ipAddress,
            'reason' => $reason,
            'blocked_at' => Carbon::now(),
            'expires_at' => $expiresAt,
            'blocked_by' => auth()->id()
        ];

        // Store in cache for quick access
        $cacheKey = "blocked_ip_{$ipAddress}";
        if ($expiresAt) {
            Cache::put($cacheKey, $blockData, $expiresAt);
        } else {
            Cache::forever($cacheKey, $blockData);
        }

        // Store in database for persistence
        DB::table('blocked_ips')->updateOrInsert(
            ['ip_address' => $ipAddress],
            $blockData
        );

        // Log the action
        Log::channel('security')->info("IP {$ipAddress} blocked", [
            'reason' => $reason,
            'duration' => $duration,
            'blocked_by' => auth()->user()->name
        ]);

        return response()->json(['success' => true, 'message' => 'IP address blocked successfully']);
    }

    /**
     * Unblock an IP address
     */
    public function unblockIP(Request $request)
    {
        $request->validate([
            'ip_address' => 'required|ip'
        ]);

        $ipAddress = $request->ip_address;

        // Remove from cache
        Cache::forget("blocked_ip_{$ipAddress}");

        // Remove from database
        DB::table('blocked_ips')->where('ip_address', $ipAddress)->delete();

        // Log the action
        Log::channel('security')->info("IP {$ipAddress} unblocked", [
            'unblocked_by' => auth()->user()->name
        ]);

        return response()->json(['success' => true, 'message' => 'IP address unblocked successfully']);
    }

    /**
     * Update threat detection settings
     */
    public function updateDetectionSettings(Request $request)
    {
        $settings = $request->validate([
            'brute_force' => 'boolean',
            'sql_injection' => 'boolean',
            'xss' => 'boolean',
            'suspicious_activity' => 'boolean',
            'rate_limit' => 'boolean'
        ]);

        // Store settings in cache
        Cache::forever('security_detection_settings', $settings);

        // Log the change
        Log::channel('security')->info('Security detection settings updated', [
            'settings' => $settings,
            'updated_by' => auth()->user()->name
        ]);

        return response()->json(['success' => true, 'message' => 'Detection settings updated successfully']);
    }

    /**
     * Get security statistics (private method)
     */
    private function getSecurityStatsData()
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $lastWeek = Carbon::now()->subWeek();

        return [
            'events_24h' => $this->getSecurityEventsCount($today),
            'blocked_ips' => $this->getBlockedIPsCount(),
            'failed_logins_24h' => $this->getFailedLoginsCount($today),
            'active_sessions' => $this->getActiveSessionsCount(),
            'suspicious_uploads_today' => $this->getSuspiciousUploadsCount($today),
            'rate_limit_violations_today' => $this->getRateLimitViolationsCount($today),
            'virus_detections_today' => $this->getVirusDetectionsCount($today)
        ];
    }

    /**
     * Get blocked IPs list
     */
    private function getBlockedIPs()
    {
        $blockedIPs = DB::table('blocked_ips')
            ->where(function($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', Carbon::now());
            })
            ->orderBy('blocked_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function($ip) {
                return [
                    'address' => $ip->ip_address,
                    'reason' => $ip->reason,
                    'blocked_at' => $ip->blocked_at,
                    'expires_at' => $ip->expires_at
                ];
            });

        return $blockedIPs;
     }

    /**
     * Get rate limiting statistics
     */
    private function getRateLimitStats()
    {
        $cachePrefix = config('ratelimit.cache.prefix', 'rate_limit');
        
        return [
            'login_attempts_blocked' => Cache::get($cachePrefix . '_login_blocked_today', 0),
            'api_requests_blocked' => Cache::get($cachePrefix . '_api_blocked_today', 0),
            'form_submissions_blocked' => Cache::get($cachePrefix . '_form_blocked_today', 0),
            'downloads_blocked' => Cache::get($cachePrefix . '_download_blocked_today', 0),
            'uploads_blocked' => Cache::get($cachePrefix . '_upload_blocked_today', 0),
            'top_blocked_ips' => $this->getTopBlockedIPs(),
            'rate_limit_efficiency' => $this->calculateRateLimitEfficiency()
        ];
    }

    /**
     * Get suspicious activities (private method)
     */
    private function getSuspiciousActivitiesData()
    {
        return [
            'multiple_failed_logins' => $this->getMultipleFailedLogins(),
            'unusual_file_uploads' => $this->getUnusualFileUploads(),
            'rapid_requests' => $this->getRapidRequests(),
            'suspicious_user_agents' => $this->getSuspiciousUserAgents(),
            'geo_anomalies' => $this->getGeoAnomalies(),
            'privilege_escalation_attempts' => $this->getPrivilegeEscalationAttempts()
        ];
    }

    /**
     * Display detailed security logs
     */
    public function logs(Request $request)
    {
        $type = $request->get('type', 'all');
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $search = $request->get('search');

        $logs = $this->getFilteredLogs($type, $date, $search);

        return view('admin.security.logs', compact('logs', 'type', 'date', 'search'));
    }

    /**
     * Export security report
     */
    public function exportReport(Request $request)
    {
        $format = $request->get('format', 'json');
        $dateFrom = $request->get('date_from', Carbon::now()->subWeek()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::today()->format('Y-m-d'));

        $report = $this->generateSecurityReport($dateFrom, $dateTo);

        $filename = "security_report_{$dateFrom}_to_{$dateTo}";

        switch ($format) {
            case 'csv':
                return $this->exportToCsv($report, $filename);
            case 'pdf':
                return $this->exportToPdf($report, $filename);
            default:
                return response()->json($report)
                    ->header('Content-Disposition', "attachment; filename=\"{$filename}.json\"");
        }
    }

    /**
     * Block IP address
     */
    public function blockIp(Request $request)
    {
        $request->validate([
            'ip' => 'required|ip',
            'reason' => 'required|string|max:255',
            'duration' => 'required|integer|min:1|max:525600' // Max 1 year in minutes
        ]);

        $ip = $request->ip;
        $reason = $request->reason;
        $duration = $request->duration;

        // Add to blocked IPs cache
        $blockedIps = Cache::get('blocked_ips', []);
        $blockedIps[$ip] = [
            'reason' => $reason,
            'blocked_at' => now()->toISOString(),
            'blocked_by' => auth()->id(),
            'expires_at' => now()->addMinutes($duration)->toISOString()
        ];
        
        Cache::put('blocked_ips', $blockedIps, now()->addMinutes($duration));

        // Log the action
        Log::channel('security')->warning('IP address blocked manually', [
            'ip' => $ip,
            'reason' => $reason,
            'duration_minutes' => $duration,
            'blocked_by' => auth()->id(),
            'timestamp' => now()->toISOString()
        ]);

        return response()->json([
            'success' => true,
            'message' => "IP {$ip} has been blocked for {$duration} minutes."
        ]);
    }

    /**
     * Unblock IP address
     */
    public function unblockIp(Request $request)
    {
        $request->validate([
            'ip' => 'required|ip'
        ]);

        $ip = $request->ip;

        // Remove from blocked IPs cache
        $blockedIps = Cache::get('blocked_ips', []);
        if (isset($blockedIps[$ip])) {
            unset($blockedIps[$ip]);
            Cache::put('blocked_ips', $blockedIps, now()->addYear());

            // Log the action
            Log::channel('security')->info('IP address unblocked manually', [
                'ip' => $ip,
                'unblocked_by' => auth()->id(),
                'timestamp' => now()->toISOString()
            ]);

            return response()->json([
                'success' => true,
                'message' => "IP {$ip} has been unblocked."
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => "IP {$ip} is not currently blocked."
        ], 404);
    }

    /**
     * Get system health status
     */
    public function systemHealth()
    {
        $health = [
            'timestamp' => now()->toISOString(),
            'status' => 'healthy',
            'checks' => [
                'database' => $this->checkDatabaseHealth(),
                'cache' => $this->checkCacheHealth(),
                'storage' => $this->checkStorageHealth(),
                'queue' => $this->checkQueueHealth(),
                'rate_limiting' => $this->checkRateLimitingHealth(),
                'security_middleware' => $this->checkSecurityMiddlewareHealth(),
                'log_files' => $this->checkLogFilesHealth()
            ]
        ];

        // Determine overall status
        $failedChecks = array_filter($health['checks'], function($check) {
            return $check['status'] !== 'healthy';
        });

        if (!empty($failedChecks)) {
            $health['status'] = count($failedChecks) > 2 ? 'critical' : 'warning';
        }

        return response()->json($health);
    }

    // Helper methods for statistics

    private function getFailedLoginsCount($date)
    {
        // Implementation would parse security logs or query database
        return 0;
    }

    private function getBlockedIPsCount()
    {
        $blockedIps = Cache::get('blocked_ips', []);
        return count($blockedIps);
    }

    private function getSuspiciousUploadsCount($date)
    {
        // Implementation would parse file upload logs
        return 0;
    }

    private function getRateLimitViolationsCount($date)
    {
        // Implementation would parse rate limit logs
        return 0;
    }

    private function getSecurityEventsCount($since)
    {
        // Implementation would count security events since date
        return 0;
    }

    private function getActiveSessionsCount()
    {
        // Implementation would count active user sessions
        return DB::table('sessions')->count();
    }

    private function getVirusDetectionsCount($date)
    {
        // Implementation would parse virus scan logs
        return 0;
    }

    private function parseLogLine($line)
    {
        // Parse log line and extract relevant information
        if (preg_match('/\[(.*?)\].*?(ERROR|WARNING|INFO).*?({.*})/', $line, $matches)) {
            return [
                'timestamp' => $matches[1],
                'level' => $matches[2],
                'data' => json_decode($matches[3], true)
            ];
        }
        return null;
    }

    private function getTopBlockedIPs($limit = 10)
    {
        // Implementation would analyze blocked IPs
        return [];
    }

    private function calculateRateLimitEfficiency()
    {
        // Calculate rate limiting effectiveness
        return 95.5; // Placeholder
    }

    private function getMultipleFailedLogins()
    {
        // Implementation would identify IPs with multiple failed logins
        return [];
    }

    private function getUnusualFileUploads()
    {
        // Implementation would identify unusual file upload patterns
        return [];
    }

    private function getRapidRequests()
    {
        // Implementation would identify rapid request patterns
        return [];
    }

    private function getSuspiciousUserAgents()
    {
        // Implementation would identify suspicious user agents
        return [];
    }

    private function getGeoAnomalies()
    {
        // Implementation would identify geographical anomalies
        return [];
    }

    private function getPrivilegeEscalationAttempts()
    {
        // Implementation would identify privilege escalation attempts
        return [];
    }

    private function getFilteredLogs($type, $date, $search)
    {
        // Implementation would filter and return logs
        return [];
    }

    private function generateSecurityReport($dateFrom, $dateTo)
    {
        return [
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'summary' => $this->getSecurityStats(),
            'events' => $this->getRecentSecurityEvents(1000),
            'rate_limits' => $this->getRateLimitStats(),
            'suspicious_activities' => $this->getSuspiciousActivities(),
            'recommendations' => $this->getSecurityRecommendations()
        ];
    }

    private function getSecurityRecommendations()
    {
        return [
            'Update rate limiting thresholds based on usage patterns',
            'Review and update blocked IP list',
            'Implement additional monitoring for suspicious activities',
            'Consider implementing CAPTCHA for repeated failed logins',
            'Review and update security headers configuration'
        ];
    }

    // Health check methods

    private function checkDatabaseHealth()
    {
        try {
            DB::connection()->getPdo();
            return ['status' => 'healthy', 'message' => 'Database connection successful'];
        } catch (\Exception $e) {
            return ['status' => 'critical', 'message' => 'Database connection failed: ' . $e->getMessage()];
        }
    }

    private function checkCacheHealth()
    {
        try {
            Cache::put('health_check', 'test', 60);
            $value = Cache::get('health_check');
            Cache::forget('health_check');
            
            if ($value === 'test') {
                return ['status' => 'healthy', 'message' => 'Cache is working properly'];
            } else {
                return ['status' => 'warning', 'message' => 'Cache read/write issue'];
            }
        } catch (\Exception $e) {
            return ['status' => 'critical', 'message' => 'Cache error: ' . $e->getMessage()];
        }
    }

    private function checkStorageHealth()
    {
        try {
            $testFile = 'health_check_' . time() . '.txt';
            Storage::put($testFile, 'test');
            $content = Storage::get($testFile);
            Storage::delete($testFile);
            
            if ($content === 'test') {
                return ['status' => 'healthy', 'message' => 'Storage is working properly'];
            } else {
                return ['status' => 'warning', 'message' => 'Storage read/write issue'];
            }
        } catch (\Exception $e) {
            return ['status' => 'critical', 'message' => 'Storage error: ' . $e->getMessage()];
        }
    }

    private function checkQueueHealth()
    {
        try {
            // Check if queue workers are running
            $queueSize = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')->count();
            
            if ($failedJobs > 100) {
                return ['status' => 'warning', 'message' => "High number of failed jobs: {$failedJobs}"];
            } elseif ($queueSize > 1000) {
                return ['status' => 'warning', 'message' => "High queue size: {$queueSize}"];
            } else {
                return ['status' => 'healthy', 'message' => "Queue healthy (Size: {$queueSize}, Failed: {$failedJobs})"];
            }
        } catch (\Exception $e) {
            return ['status' => 'critical', 'message' => 'Queue check error: ' . $e->getMessage()];
        }
    }

    private function checkRateLimitingHealth()
    {
        try {
            $rateLimitConfig = config('ratelimit');
            if (empty($rateLimitConfig)) {
                return ['status' => 'warning', 'message' => 'Rate limiting configuration not found'];
            }
            
            return ['status' => 'healthy', 'message' => 'Rate limiting is configured and active'];
        } catch (\Exception $e) {
            return ['status' => 'critical', 'message' => 'Rate limiting check error: ' . $e->getMessage()];
        }
    }

    private function checkSecurityMiddlewareHealth()
    {
        try {
            // Check if security middleware is registered
            $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
            $middleware = $kernel->getMiddleware();
            
            $securityMiddleware = array_filter($middleware, function($mw) {
                return strpos($mw, 'ProductionSecurityMiddleware') !== false;
            });
            
            if (!empty($securityMiddleware)) {
                return ['status' => 'healthy', 'message' => 'Security middleware is active'];
            } else {
                return ['status' => 'warning', 'message' => 'Security middleware not found in global middleware'];
            }
        } catch (\Exception $e) {
            return ['status' => 'critical', 'message' => 'Security middleware check error: ' . $e->getMessage()];
        }
    }

    private function checkLogFilesHealth()
    {
        try {
            $logPath = storage_path('logs');
            $logFiles = ['laravel.log', 'security.log', 'file_uploads.log'];
            $issues = [];
            
            foreach ($logFiles as $logFile) {
                $filePath = $logPath . '/' . $logFile;
                if (!file_exists($filePath)) {
                    $issues[] = "Missing log file: {$logFile}";
                } elseif (!is_writable($filePath)) {
                    $issues[] = "Log file not writable: {$logFile}";
                } elseif (filesize($filePath) > 100 * 1024 * 1024) { // 100MB
                    $issues[] = "Log file too large: {$logFile}";
                }
            }
            
            if (empty($issues)) {
                return ['status' => 'healthy', 'message' => 'All log files are healthy'];
            } else {
                return ['status' => 'warning', 'message' => implode(', ', $issues)];
            }
        } catch (\Exception $e) {
            return ['status' => 'critical', 'message' => 'Log files check error: ' . $e->getMessage()];
        }
    }

    private function exportToCsv($report, $filename)
    {
        // Implementation for CSV export
        return response()->json(['message' => 'CSV export not implemented yet']);
    }

    private function exportToPdf($report, $filename)
    {
        // Implementation for PDF export
        return response()->json(['message' => 'PDF export not implemented yet']);
    }
}