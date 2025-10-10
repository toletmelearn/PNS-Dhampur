<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Models\SecurityEvent;
use Carbon\Carbon;
use App\Support\Constants;

class MonitoringService
{
    protected $config;

    public function __construct()
    {
        $this->config = config('monitoring');
    }

    /**
     * Perform comprehensive system health check
     */
    public function performHealthCheck(): array
    {
        $results = [
            'overall_status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'checks' => [],
        ];

        // Database check
        if ($this->config['health_checks']['database']['enabled']) {
            $results['checks']['database'] = $this->checkDatabase();
        }

        // Cache check
        if ($this->config['health_checks']['cache']['enabled']) {
            $results['checks']['cache'] = $this->checkCache();
        }

        // Storage check
        if ($this->config['health_checks']['storage']['enabled']) {
            $results['checks']['storage'] = $this->checkStorage();
        }

        // Queue check
        if ($this->config['health_checks']['queue']['enabled']) {
            $results['checks']['queue'] = $this->checkQueue();
        }

        // External services check
        $results['checks']['external_services'] = $this->checkExternalServices();

        // Performance check
        $results['checks']['performance'] = $this->checkPerformance();

        // Security check
        $results['checks']['security'] = $this->checkSecurity();

        // Determine overall status
        $results['overall_status'] = $this->determineOverallStatus($results['checks']);

        // Log results
        $this->logHealthCheck($results);

        // Send alerts if necessary
        $this->processAlerts($results);

        return $results;
    }

    /**
     * Check database connectivity and performance
     */
    protected function checkDatabase(): array
    {
        $start = microtime(true);
        $status = 'healthy';
        $message = 'Database connection successful';

        try {
            DB::connection()->getPdo();
            $responseTime = (microtime(true) - $start) * Constants::get('conversion.milliseconds_per_second', 1000);

            if ($responseTime > $this->config['health_checks']['database']['critical_threshold'] * Constants::get('conversion.milliseconds_per_second', 1000)) {
                $status = 'critical';
                $message = "Database response time too high: {$responseTime}ms";
            } elseif ($responseTime > $this->config['health_checks']['database']['timeout'] * Constants::get('conversion.milliseconds_per_second', 1000)) {
                $status = 'warning';
                $message = "Database response time elevated: {$responseTime}ms";
            }

            return [
                'status' => $status,
                'message' => $message,
                'response_time_ms' => round($responseTime, 2),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'message' => 'Database connection failed: ' . $e->getMessage(),
                'response_time_ms' => null,
            ];
        }
    }

    /**
     * Check cache functionality
     */
    protected function checkCache(): array
    {
        $start = microtime(true);
        $testKey = 'health_check_' . time();
        $testValue = 'test_value';

        try {
            // Test cache write
            Cache::put($testKey, $testValue, Constants::shortCacheTtl());
            
            // Test cache read
            $retrieved = Cache::get($testKey);
            
            // Clean up
            Cache::forget($testKey);

            $responseTime = (microtime(true) - $start) * Constants::get('conversion.milliseconds_per_second', 1000);

            if ($retrieved !== $testValue) {
                return [
                    'status' => 'critical',
                    'message' => 'Cache read/write test failed',
                    'response_time_ms' => round($responseTime, 2),
                ];
            }

            $status = $responseTime > $this->config['health_checks']['cache']['critical_threshold'] * Constants::get('conversion.milliseconds_per_second', 1000)
                ? 'warning' : 'healthy';

            return [
                'status' => $status,
                'message' => 'Cache functioning normally',
                'response_time_ms' => round($responseTime, 2),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'message' => 'Cache error: ' . $e->getMessage(),
                'response_time_ms' => null,
            ];
        }
    }

    /**
     * Check storage and disk space
     */
    protected function checkStorage(): array
    {
        try {
            $diskSpace = disk_free_space(storage_path());
            $totalSpace = disk_total_space(storage_path());
            $usedPercentage = (($totalSpace - $diskSpace) / $totalSpace) * Constants::get('conversion.percentage_multiplier', 100);

            $status = 'healthy';
            $message = "Disk usage: {$usedPercentage}%";

            if ($usedPercentage > $this->config['health_checks']['storage']['critical_threshold']) {
                $status = 'critical';
                $message = "Critical disk usage: {$usedPercentage}%";
            } elseif ($usedPercentage > $this->config['health_checks']['storage']['disk_space_threshold']) {
                $status = 'warning';
                $message = "High disk usage: {$usedPercentage}%";
            }

            return [
                'status' => $status,
                'message' => $message,
                'disk_usage_percentage' => round($usedPercentage, 2),
                'free_space_gb' => round($diskSpace / (Constants::get('conversion.bytes_per_kb', 1024) ** 3), 2),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'message' => 'Storage check failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check queue status
     */
    protected function checkQueue(): array
    {
        try {
            $failedJobs = DB::table('failed_jobs')->count();
            $pendingJobs = DB::table('jobs')->count();

            $status = 'healthy';
            $issues = [];

            if ($failedJobs > $this->config['health_checks']['queue']['failed_jobs_threshold']) {
                $status = 'warning';
                $issues[] = "High failed jobs count: {$failedJobs}";
            }

            if ($pendingJobs > $this->config['health_checks']['queue']['pending_jobs_threshold']) {
                $status = 'warning';
                $issues[] = "High pending jobs count: {$pendingJobs}";
            }

            return [
                'status' => $status,
                'message' => empty($issues) ? 'Queue functioning normally' : implode(', ', $issues),
                'failed_jobs' => $failedJobs,
                'pending_jobs' => $pendingJobs,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'message' => 'Queue check failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check external services
     */
    protected function checkExternalServices(): array
    {
        $results = [];

        // Check biometric API
        if ($this->config['health_checks']['external_services']['biometric_api']['enabled']) {
            $results['biometric_api'] = $this->checkBiometricAPI();
        }

        // Check mail service
        if ($this->config['health_checks']['external_services']['mail_service']['enabled']) {
            $results['mail_service'] = $this->checkMailService();
        }

        return $results;
    }

    /**
     * Check biometric API connectivity
     */
    protected function checkBiometricAPI(): array
    {
        $url = $this->config['health_checks']['external_services']['biometric_api']['url'];
        $timeout = $this->config['health_checks']['external_services']['biometric_api']['timeout'];

        try {
            $response = Http::timeout($timeout)->get($url . '/status');
            
            if ($response->successful()) {
                return [
                    'status' => 'healthy',
                    'message' => 'Biometric API accessible',
                    'response_time_ms' => $response->handlerStats()['total_time'] ?? null,
                ];
            } else {
                return [
                    'status' => 'warning',
                    'message' => "Biometric API returned status: {$response->status()}",
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'message' => 'Biometric API unreachable: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check mail service
     */
    protected function checkMailService(): array
    {
        try {
            // Simple mail configuration check
            $mailer = config('mail.default');
            $host = config("mail.mailers.{$mailer}.host");
            
            if (empty($host)) {
                return [
                    'status' => 'warning',
                    'message' => 'Mail service not configured',
                ];
            }

            return [
                'status' => 'healthy',
                'message' => 'Mail service configured',
                'mailer' => $mailer,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'message' => 'Mail service check failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check system performance
     */
    protected function checkPerformance(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->parseBytes(ini_get('memory_limit'));
        $memoryPercentage = ($memoryUsage / $memoryLimit) * Constants::get('conversion.percentage_multiplier', 100);

        $status = 'healthy';
        $issues = [];

        if ($memoryPercentage > $this->config['performance']['memory_threshold']) {
            $status = 'warning';
            $issues[] = "High memory usage: {$memoryPercentage}%";
        }

        return [
            'status' => $status,
            'message' => empty($issues) ? 'Performance within normal parameters' : implode(', ', $issues),
            'memory_usage_mb' => round($memoryUsage / (Constants::get('conversion.bytes_per_kb', 1024) ** 2), 2),
            'memory_percentage' => round($memoryPercentage, 2),
        ];
    }

    /**
     * Check security status
     */
    protected function checkSecurity(): array
    {
        $issues = [];

        // Check if debug mode is enabled in production
        if (config('app.debug') && config('app.env') === 'production') {
            $issues[] = 'Debug mode enabled in production';
        }

        // Check recent security events
        $recentEvents = SecurityEvent::where('created_at', '>', now()->subHour())->count();
        if ($recentEvents > $this->config['security']['suspicious_activity_threshold']) {
            $issues[] = "High security event count: {$recentEvents}";
        }

        $status = empty($issues) ? 'healthy' : 'warning';

        return [
            'status' => $status,
            'message' => empty($issues) ? 'No security issues detected' : implode(', ', $issues),
            'recent_security_events' => $recentEvents,
        ];
    }

    /**
     * Determine overall system status
     */
    protected function determineOverallStatus(array $checks): string
    {
        $hasCritical = false;
        $hasWarning = false;

        foreach ($checks as $category => $categoryChecks) {
            if (is_array($categoryChecks)) {
                foreach ($categoryChecks as $check) {
                    if (isset($check['status'])) {
                        if ($check['status'] === 'critical') {
                            $hasCritical = true;
                        } elseif ($check['status'] === 'warning') {
                            $hasWarning = true;
                        }
                    }
                }
            } elseif (isset($categoryChecks['status'])) {
                if ($categoryChecks['status'] === 'critical') {
                    $hasCritical = true;
                } elseif ($categoryChecks['status'] === 'warning') {
                    $hasWarning = true;
                }
            }
        }

        if ($hasCritical) {
            return 'critical';
        } elseif ($hasWarning) {
            return 'warning';
        }

        return 'healthy';
    }

    /**
     * Log health check results
     */
    protected function logHealthCheck(array $results): void
    {
        if ($this->config['logging']['health_checks']['enabled']) {
            Log::channel($this->config['logging']['health_checks']['channel'])
                ->log($this->config['logging']['health_checks']['level'], 'Health check completed', $results);
        }
    }

    /**
     * Process alerts based on health check results
     */
    protected function processAlerts(array $results): void
    {
        if (!$this->config['alerts']['enabled']) {
            return;
        }

        $severity = $results['overall_status'];
        
        if ($severity === 'healthy') {
            return;
        }

        $alertConfig = $this->config['alerts']['severity_levels'][$severity] ?? null;
        
        if (!$alertConfig) {
            return;
        }

        // Check throttling
        $cacheKey = "alert_throttle_{$severity}";
        if (!$alertConfig['immediate'] && Cache::has($cacheKey)) {
            return;
        }

        // Send alerts
        foreach ($alertConfig['channels'] as $channel) {
            $this->sendAlert($channel, $severity, $results);
        }

        // Set throttle
        if (!$alertConfig['immediate'] && isset($alertConfig['throttle'])) {
            Cache::put($cacheKey, true, $alertConfig['throttle']);
        }
    }

    /**
     * Send alert via specified channel
     */
    protected function sendAlert(string $channel, string $severity, array $results): void
    {
        try {
            switch ($channel) {
                case 'email':
                    $this->sendEmailAlert($severity, $results);
                    break;
                case 'slack':
                    $this->sendSlackAlert($severity, $results);
                    break;
                case 'sms':
                    $this->sendSMSAlert($severity, $results);
                    break;
            }
        } catch (\Exception $e) {
            Log::error("Failed to send {$channel} alert: " . $e->getMessage());
        }
    }

    /**
     * Send email alert
     */
    protected function sendEmailAlert(string $severity, array $results): void
    {
        $config = $this->config['alerts']['channels']['email'];
        
        if (!$config['enabled'] || empty($config['recipients'])) {
            return;
        }

        // Implementation would depend on your mail setup
        // This is a placeholder for the actual email sending logic
    }

    /**
     * Send Slack alert
     */
    protected function sendSlackAlert(string $severity, array $results): void
    {
        $config = $this->config['alerts']['channels']['slack'];
        
        if (!$config['enabled'] || empty($config['webhook_url'])) {
            return;
        }

        $message = [
            'channel' => $config['channel'],
            'text' => "System Alert: {$severity}",
            'attachments' => [
                [
                    'color' => $severity === 'critical' ? 'danger' : 'warning',
                    'fields' => [
                        [
                            'title' => 'Status',
                            'value' => ucfirst($severity),
                            'short' => true,
                        ],
                        [
                            'title' => 'Timestamp',
                            'value' => $results['timestamp'],
                            'short' => true,
                        ],
                    ],
                ],
            ],
        ];

        Http::post($config['webhook_url'], $message);
    }

    /**
     * Send SMS alert
     */
    protected function sendSMSAlert(string $severity, array $results): void
    {
        // Placeholder for SMS implementation
        // Would integrate with services like Twilio, AWS SNS, etc.
    }

    /**
     * Parse bytes from PHP ini values
     */
    protected function parseBytes(string $value): int
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $value = (int) $value;

        switch ($last) {
            case 'g':
                $value *= Constants::get('conversion.bytes_per_kb', 1024);
            case 'm':
                $value *= Constants::get('conversion.bytes_per_kb', 1024);
            case 'k':
                $value *= Constants::get('conversion.bytes_per_kb', 1024);
        }

        return $value;
    }
}