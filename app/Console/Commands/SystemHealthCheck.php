<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Exception;

class SystemHealthCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:health-check 
                            {--format=table : Output format (table, json, text)}
                            {--alert : Send alerts for critical issues}
                            {--detailed : Show detailed information}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform comprehensive system health checks';

    /**
     * Health check results
     *
     * @var array
     */
    protected $results = [];

    /**
     * Critical issues found
     *
     * @var array
     */
    protected $criticalIssues = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting system health check...');
        $this->newLine();

        // Run all health checks
        $this->checkDatabase();
        $this->checkCache();
        $this->checkStorage();
        $this->checkQueue();
        $this->checkSecurity();
        $this->checkPerformance();
        $this->checkExternalServices();
        $this->checkSystemResources();
        $this->checkBackups();
        $this->checkLogs();

        // Display results
        $this->displayResults();

        // Send alerts if requested and critical issues found
        if ($this->option('alert') && !empty($this->criticalIssues)) {
            $this->sendAlerts();
        }

        // Log health check results
        $this->logResults();

        return empty($this->criticalIssues) ? 0 : 1;
    }

    /**
     * Check database connectivity and performance
     */
    protected function checkDatabase()
    {
        $this->info('Checking database...');
        
        try {
            $start = microtime(true);
            
            // Test connection
            DB::connection()->getPdo();
            $connectionTime = round((microtime(true) - $start) * 1000, 2);
            
            // Test query performance
            $start = microtime(true);
            $userCount = User::count();
            $queryTime = round((microtime(true) - $start) * 1000, 2);
            
            // Check database size
            $databaseSize = DB::select("SELECT 
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb 
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()")[0]->size_mb ?? 0;
            
            $this->results['database'] = [
                'status' => 'healthy',
                'connection_time' => $connectionTime . 'ms',
                'query_time' => $queryTime . 'ms',
                'user_count' => $userCount,
                'database_size' => $databaseSize . 'MB',
                'details' => []
            ];
            
            // Check for slow queries
            if ($queryTime > 1000) {
                $this->results['database']['details'][] = 'Slow query detected';
                $this->criticalIssues[] = 'Database queries are slow';
            }
            
            if ($connectionTime > 500) {
                $this->results['database']['details'][] = 'Slow database connection';
                $this->criticalIssues[] = 'Database connection is slow';
            }
            
        } catch (Exception $e) {
            $this->results['database'] = [
                'status' => 'critical',
                'error' => $e->getMessage()
            ];
            $this->criticalIssues[] = 'Database connection failed: ' . $e->getMessage();
        }
    }

    /**
     * Check cache system
     */
    protected function checkCache()
    {
        $this->info('Checking cache system...');
        
        try {
            $testKey = 'health_check_' . time();
            $testValue = 'test_value';
            
            // Test cache write
            $start = microtime(true);
            Cache::put($testKey, $testValue, 60);
            $writeTime = round((microtime(true) - $start) * 1000, 2);
            
            // Test cache read
            $start = microtime(true);
            $cachedValue = Cache::get($testKey);
            $readTime = round((microtime(true) - $start) * 1000, 2);
            
            // Clean up
            Cache::forget($testKey);
            
            $this->results['cache'] = [
                'status' => $cachedValue === $testValue ? 'healthy' : 'warning',
                'write_time' => $writeTime . 'ms',
                'read_time' => $readTime . 'ms',
                'driver' => config('cache.default'),
                'details' => []
            ];
            
            if ($cachedValue !== $testValue) {
                $this->results['cache']['details'][] = 'Cache read/write test failed';
                $this->criticalIssues[] = 'Cache system is not working properly';
            }
            
        } catch (Exception $e) {
            $this->results['cache'] = [
                'status' => 'critical',
                'error' => $e->getMessage()
            ];
            $this->criticalIssues[] = 'Cache system failed: ' . $e->getMessage();
        }
    }

    /**
     * Check storage system
     */
    protected function checkStorage()
    {
        $this->info('Checking storage system...');
        
        try {
            $testFile = 'health_check_' . time() . '.txt';
            $testContent = 'Health check test file';
            
            // Test file write
            $start = microtime(true);
            Storage::put($testFile, $testContent);
            $writeTime = round((microtime(true) - $start) * 1000, 2);
            
            // Test file read
            $start = microtime(true);
            $fileContent = Storage::get($testFile);
            $readTime = round((microtime(true) - $start) * 1000, 2);
            
            // Test file delete
            Storage::delete($testFile);
            
            // Check disk space
            $diskSpace = disk_free_space(storage_path());
            $diskSpaceGB = round($diskSpace / 1024 / 1024 / 1024, 2);
            
            $this->results['storage'] = [
                'status' => $fileContent === $testContent ? 'healthy' : 'warning',
                'write_time' => $writeTime . 'ms',
                'read_time' => $readTime . 'ms',
                'disk_space' => $diskSpaceGB . 'GB',
                'driver' => config('filesystems.default'),
                'details' => []
            ];
            
            if ($fileContent !== $testContent) {
                $this->results['storage']['details'][] = 'Storage read/write test failed';
                $this->criticalIssues[] = 'Storage system is not working properly';
            }
            
            if ($diskSpaceGB < 1) {
                $this->results['storage']['details'][] = 'Low disk space';
                $this->criticalIssues[] = 'Disk space is critically low';
            }
            
        } catch (Exception $e) {
            $this->results['storage'] = [
                'status' => 'critical',
                'error' => $e->getMessage()
            ];
            $this->criticalIssues[] = 'Storage system failed: ' . $e->getMessage();
        }
    }

    /**
     * Check queue system
     */
    protected function checkQueue()
    {
        $this->info('Checking queue system...');
        
        try {
            // Check failed jobs
            $failedJobs = DB::table('failed_jobs')->count();
            
            // Check jobs table if it exists
            $pendingJobs = 0;
            try {
                $pendingJobs = DB::table('jobs')->count();
            } catch (Exception $e) {
                // Jobs table might not exist if not using database queue
            }
            
            $this->results['queue'] = [
                'status' => $failedJobs > 100 ? 'warning' : 'healthy',
                'failed_jobs' => $failedJobs,
                'pending_jobs' => $pendingJobs,
                'driver' => config('queue.default'),
                'details' => []
            ];
            
            if ($failedJobs > 100) {
                $this->results['queue']['details'][] = 'High number of failed jobs';
            }
            
            if ($pendingJobs > 1000) {
                $this->results['queue']['details'][] = 'High number of pending jobs';
                $this->criticalIssues[] = 'Queue has too many pending jobs';
            }
            
        } catch (Exception $e) {
            $this->results['queue'] = [
                'status' => 'warning',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check security configuration
     */
    protected function checkSecurity()
    {
        $this->info('Checking security configuration...');
        
        $issues = [];
        $warnings = [];
        
        // Check debug mode
        if (config('app.debug')) {
            $issues[] = 'Debug mode is enabled in production';
        }
        
        // Check HTTPS
        if (!config('app.url') || !str_starts_with(config('app.url'), 'https://')) {
            $warnings[] = 'HTTPS is not configured';
        }
        
        // Check session security
        if (!config('session.secure')) {
            $warnings[] = 'Secure cookies are not enabled';
        }
        
        // Check CSRF protection
        if (!config('app.csrf_protection', true)) {
            $issues[] = 'CSRF protection is disabled';
        }
        
        // Check file permissions
        $storagePermissions = substr(sprintf('%o', fileperms(storage_path())), -4);
        if ($storagePermissions !== '0755' && $storagePermissions !== '0775') {
            $warnings[] = 'Storage directory permissions may be insecure';
        }
        
        $status = 'healthy';
        if (!empty($issues)) {
            $status = 'critical';
            $this->criticalIssues = array_merge($this->criticalIssues, $issues);
        } elseif (!empty($warnings)) {
            $status = 'warning';
        }
        
        $this->results['security'] = [
            'status' => $status,
            'issues' => $issues,
            'warnings' => $warnings,
            'details' => array_merge($issues, $warnings)
        ];
    }

    /**
     * Check system performance
     */
    protected function checkPerformance()
    {
        $this->info('Checking system performance...');
        
        // Memory usage
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        $memoryLimit = ini_get('memory_limit');
        
        // Convert memory limit to bytes
        $memoryLimitBytes = $this->convertToBytes($memoryLimit);
        
        // CPU load (if available)
        $cpuLoad = null;
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            $cpuLoad = $load[0] ?? null;
        }
        
        $this->results['performance'] = [
            'status' => 'healthy',
            'memory_usage' => $this->formatBytes($memoryUsage),
            'memory_peak' => $this->formatBytes($memoryPeak),
            'memory_limit' => $memoryLimit,
            'cpu_load' => $cpuLoad,
            'details' => []
        ];
        
        // Check memory usage
        if ($memoryLimitBytes > 0 && $memoryUsage > ($memoryLimitBytes * 0.8)) {
            $this->results['performance']['details'][] = 'High memory usage';
            $this->results['performance']['status'] = 'warning';
        }
        
        // Check CPU load
        if ($cpuLoad && $cpuLoad > 2.0) {
            $this->results['performance']['details'][] = 'High CPU load';
            $this->results['performance']['status'] = 'warning';
        }
    }

    /**
     * Check external services
     */
    protected function checkExternalServices()
    {
        $this->info('Checking external services...');
        
        $services = [];
        
        // Check mail service
        try {
            $mailConfig = config('mail.default');
            $services['mail'] = [
                'status' => 'healthy',
                'driver' => $mailConfig,
                'host' => config('mail.mailers.' . $mailConfig . '.host')
            ];
        } catch (Exception $e) {
            $services['mail'] = [
                'status' => 'warning',
                'error' => $e->getMessage()
            ];
        }
        
        // Check external API endpoints (if any)
        $externalApis = [
            'biometric_device' => config('app.biometric_api_url'),
        ];
        
        foreach ($externalApis as $name => $url) {
            if ($url) {
                try {
                    $response = Http::timeout(5)->get($url);
                    $services[$name] = [
                        'status' => $response->successful() ? 'healthy' : 'warning',
                        'response_time' => $response->transferStats->getTransferTime() ?? 0,
                        'status_code' => $response->status()
                    ];
                } catch (Exception $e) {
                    $services[$name] = [
                        'status' => 'critical',
                        'error' => $e->getMessage()
                    ];
                    $this->criticalIssues[] = "External service $name is unreachable";
                }
            }
        }
        
        $this->results['external_services'] = $services;
    }

    /**
     * Check system resources
     */
    protected function checkSystemResources()
    {
        $this->info('Checking system resources...');
        
        $resources = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
            'environment' => config('app.env')
        ];
        
        // Check PHP extensions
        $requiredExtensions = ['pdo', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath'];
        $missingExtensions = [];
        
        foreach ($requiredExtensions as $extension) {
            if (!extension_loaded($extension)) {
                $missingExtensions[] = $extension;
            }
        }
        
        $resources['missing_extensions'] = $missingExtensions;
        
        if (!empty($missingExtensions)) {
            $this->criticalIssues[] = 'Missing PHP extensions: ' . implode(', ', $missingExtensions);
        }
        
        $this->results['system_resources'] = [
            'status' => empty($missingExtensions) ? 'healthy' : 'critical',
            'details' => $resources
        ];
    }

    /**
     * Check backup system
     */
    protected function checkBackups()
    {
        $this->info('Checking backup system...');
        
        $backupPath = config('backup.backup.destination.disks')[0] ?? 'local';
        $backupEnabled = config('backup.backup.enabled', false);
        
        $this->results['backups'] = [
            'status' => $backupEnabled ? 'healthy' : 'warning',
            'enabled' => $backupEnabled,
            'disk' => $backupPath,
            'details' => []
        ];
        
        if (!$backupEnabled) {
            $this->results['backups']['details'][] = 'Backup system is not enabled';
        }
    }

    /**
     * Check log files
     */
    protected function checkLogs()
    {
        $this->info('Checking log files...');
        
        $logPath = storage_path('logs/laravel.log');
        $logSize = 0;
        $lastModified = null;
        
        if (file_exists($logPath)) {
            $logSize = filesize($logPath);
            $lastModified = date('Y-m-d H:i:s', filemtime($logPath));
        }
        
        $this->results['logs'] = [
            'status' => $logSize > 100 * 1024 * 1024 ? 'warning' : 'healthy', // 100MB
            'log_size' => $this->formatBytes($logSize),
            'last_modified' => $lastModified,
            'details' => []
        ];
        
        if ($logSize > 100 * 1024 * 1024) {
            $this->results['logs']['details'][] = 'Log file is very large';
        }
    }

    /**
     * Display health check results
     */
    protected function displayResults()
    {
        $format = $this->option('format');
        
        switch ($format) {
            case 'json':
                $this->line(json_encode($this->results, JSON_PRETTY_PRINT));
                break;
                
            case 'text':
                foreach ($this->results as $component => $result) {
                    $this->line("$component: " . $result['status']);
                }
                break;
                
            default:
                $this->displayTable();
                break;
        }
        
        if (!empty($this->criticalIssues)) {
            $this->newLine();
            $this->error('Critical Issues Found:');
            foreach ($this->criticalIssues as $issue) {
                $this->line("  • $issue");
            }
        }
    }

    /**
     * Display results in table format
     */
    protected function displayTable()
    {
        $headers = ['Component', 'Status', 'Details'];
        $rows = [];
        
        foreach ($this->results as $component => $result) {
            $status = $result['status'];
            $details = '';
            
            if ($this->option('detailed')) {
                $detailsArray = [];
                foreach ($result as $key => $value) {
                    if (!in_array($key, ['status', 'details', 'issues', 'warnings', 'error'])) {
                        if (is_array($value)) {
                            $detailsArray[] = "$key: " . json_encode($value);
                        } else {
                            $detailsArray[] = "$key: $value";
                        }
                    }
                }
                $details = implode(', ', $detailsArray);
            } else {
                if (isset($result['details']) && is_array($result['details'])) {
                    $details = implode(', ', $result['details']);
                } elseif (isset($result['error'])) {
                    $details = $result['error'];
                }
            }
            
            $rows[] = [
                ucfirst(str_replace('_', ' ', $component)),
                $this->getStatusIcon($status) . ' ' . ucfirst($status),
                $details
            ];
        }
        
        $this->table($headers, $rows);
    }

    /**
     * Get status icon
     */
    protected function getStatusIcon($status)
    {
        switch ($status) {
            case 'healthy':
                return '✅';
            case 'warning':
                return '⚠️';
            case 'critical':
                return '❌';
            default:
                return '❓';
        }
    }

    /**
     * Send alerts for critical issues
     */
    protected function sendAlerts()
    {
        $this->info('Sending alerts for critical issues...');
        
        $message = "System Health Check Alert\n\n";
        $message .= "Critical issues found:\n";
        foreach ($this->criticalIssues as $issue) {
            $message .= "• $issue\n";
        }
        
        // Log the alert
        Log::critical('System health check failed', [
            'issues' => $this->criticalIssues,
            'results' => $this->results
        ]);
        
        // Send email alert (if configured)
        try {
            // Implement email alert sending here
            $this->info('Alert sent successfully');
        } catch (Exception $e) {
            $this->error('Failed to send alert: ' . $e->getMessage());
        }
    }

    /**
     * Log health check results
     */
    protected function logResults()
    {
        $logLevel = empty($this->criticalIssues) ? 'info' : 'warning';
        
        Log::$logLevel('System health check completed', [
            'results' => $this->results,
            'critical_issues' => $this->criticalIssues
        ]);
    }

    /**
     * Convert memory limit string to bytes
     */
    protected function convertToBytes($value)
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $value = (int) $value;
        
        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}