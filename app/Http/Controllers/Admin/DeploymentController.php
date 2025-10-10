<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class DeploymentController extends Controller
{
    /**
     * Display the deployment documentation dashboard
     */
    public function index()
    {
        $systemStatus = $this->getSystemStatus();
        
        return view('admin.deployment.index', compact('systemStatus'));
    }

    /**
     * Get system status information
     */
    public function getSystemStatus(): array
    {
        try {
            return [
                'database' => $this->checkDatabaseConnection(),
                'cache' => $this->checkCacheConnection(),
                'storage' => $this->checkStorageStatus(),
                'queue' => $this->checkQueueStatus(),
                'performance' => $this->getPerformanceMetrics(),
                'security' => $this->getSecurityStatus(),
                'backups' => $this->getBackupStatus(),
            ];
        } catch (\Exception $e) {
            Log::error('Error getting system status: ' . $e->getMessage());
            return [
                'error' => 'Unable to retrieve system status',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Check database connection
     */
    private function checkDatabaseConnection(): array
    {
        try {
            DB::connection()->getPdo();
            $tableCount = DB::select("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = ?", [config('database.connections.mysql.database')]);
            
            return [
                'status' => 'connected',
                'tables' => $tableCount[0]->count ?? 0,
                'connection_time' => $this->measureExecutionTime(function() {
                    return DB::connection()->getPdo();
                })
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'disconnected',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check cache connection
     */
    private function checkCacheConnection(): array
    {
        try {
            $testKey = 'deployment_test_' . time();
            Cache::put($testKey, 'test_value', 60);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);
            
            return [
                'status' => $retrieved === 'test_value' ? 'working' : 'failed',
                'driver' => config('cache.default'),
                'response_time' => $this->measureExecutionTime(function() use ($testKey) {
                    Cache::put($testKey, 'test', 1);
                    return Cache::get($testKey);
                })
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check storage status
     */
    private function checkStorageStatus(): array
    {
        try {
            $disks = ['local', 'public'];
            $status = [];
            
            foreach ($disks as $disk) {
                try {
                    $testFile = 'deployment_test_' . time() . '.txt';
                    Storage::disk($disk)->put($testFile, 'test content');
                    $exists = Storage::disk($disk)->exists($testFile);
                    Storage::disk($disk)->delete($testFile);
                    
                    $status[$disk] = [
                        'status' => $exists ? 'working' : 'failed',
                        'path' => Storage::disk($disk)->path(''),
                        'free_space' => $this->getFreeDiskSpace(Storage::disk($disk)->path(''))
                    ];
                } catch (\Exception $e) {
                    $status[$disk] = [
                        'status' => 'failed',
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            return $status;
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check queue status
     */
    private function checkQueueStatus(): array
    {
        try {
            // Check if queue workers are running (simplified check)
            $queueConnection = config('queue.default');
            
            return [
                'status' => 'configured',
                'connection' => $queueConnection,
                'pending_jobs' => $this->getPendingJobsCount(),
                'failed_jobs' => $this->getFailedJobsCount()
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics(): array
    {
        try {
            return [
                'memory_usage' => [
                    'current' => memory_get_usage(true),
                    'peak' => memory_get_peak_usage(true),
                    'limit' => $this->getMemoryLimit()
                ],
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'server_load' => $this->getServerLoad(),
                'uptime' => $this->getSystemUptime()
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get security status
     */
    private function getSecurityStatus(): array
    {
        try {
            return [
                'https_enabled' => request()->isSecure(),
                'debug_mode' => config('app.debug'),
                'environment' => config('app.env'),
                'csrf_protection' => true, // Laravel has CSRF protection by default
                'session_secure' => config('session.secure'),
                'last_security_scan' => Cache::get('last_security_scan', 'Never')
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get backup status
     */
    private function getBackupStatus(): array
    {
        try {
            $backupPath = storage_path('app/backups');
            $backups = [];
            
            if (is_dir($backupPath)) {
                $files = glob($backupPath . '/*.zip');
                $backups = array_map(function($file) {
                    return [
                        'name' => basename($file),
                        'size' => filesize($file),
                        'created' => filemtime($file)
                    ];
                }, $files);
                
                // Sort by creation time, newest first
                usort($backups, function($a, $b) {
                    return $b['created'] - $a['created'];
                });
            }
            
            return [
                'status' => 'active',
                'total_backups' => count($backups),
                'latest_backup' => !empty($backups) ? $backups[0] : null,
                'backup_size_total' => array_sum(array_column($backups, 'size')),
                'automated_backups' => config('backup.enabled', true)
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Run system health check
     */
    public function healthCheck(): JsonResponse
    {
        try {
            $results = [
                'timestamp' => now()->toISOString(),
                'overall_status' => 'healthy',
                'checks' => []
            ];

            // Database check
            $dbCheck = $this->checkDatabaseConnection();
            $results['checks']['database'] = $dbCheck;
            if ($dbCheck['status'] !== 'connected') {
                $results['overall_status'] = 'unhealthy';
            }

            // Cache check
            $cacheCheck = $this->checkCacheConnection();
            $results['checks']['cache'] = $cacheCheck;

            // Storage check
            $storageCheck = $this->checkStorageStatus();
            $results['checks']['storage'] = $storageCheck;

            // Performance check
            $performanceCheck = $this->getPerformanceMetrics();
            $results['checks']['performance'] = $performanceCheck;

            // Memory usage warning
            $memoryUsage = $performanceCheck['memory_usage']['current'] / $performanceCheck['memory_usage']['limit'];
            if ($memoryUsage > 0.8) {
                $results['warnings'][] = 'High memory usage detected: ' . round($memoryUsage * 100, 2) . '%';
            }

            return response()->json($results);
        } catch (\Exception $e) {
            return response()->json([
                'overall_status' => 'error',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ], 500);
        }
    }

    /**
     * Run maintenance tasks
     */
    public function runMaintenance(): JsonResponse
    {
        try {
            $results = [];
            
            // Clear caches
            Artisan::call('cache:clear');
            $results[] = 'Cache cleared';
            
            Artisan::call('config:cache');
            $results[] = 'Configuration cached';
            
            Artisan::call('route:cache');
            $results[] = 'Routes cached';
            
            Artisan::call('view:cache');
            $results[] = 'Views cached';
            
            // Optimize database
            try {
                Artisan::call('db:optimize');
                $results[] = 'Database optimized';
            } catch (\Exception $e) {
                $results[] = 'Database optimization failed: ' . $e->getMessage();
            }
            
            // Clean old logs
            $this->cleanOldLogs();
            $results[] = 'Old logs cleaned';
            
            // Clean old sessions
            try {
                Artisan::call('session:gc');
                $results[] = 'Old sessions cleaned';
            } catch (\Exception $e) {
                $results[] = 'Session cleanup failed: ' . $e->getMessage();
            }
            
            Log::info('Maintenance tasks completed', ['tasks' => $results]);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Maintenance tasks completed successfully',
                'tasks_completed' => $results,
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            Log::error('Maintenance tasks failed: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Maintenance tasks failed',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ], 500);
        }
    }

    /**
     * Get system information for API
     */
    public function getSystemInfo(): JsonResponse
    {
        try {
            $info = [
                'application' => [
                    'name' => config('app.name'),
                    'version' => '1.0.0', // You can define this in config
                    'environment' => config('app.env'),
                    'debug' => config('app.debug'),
                    'url' => config('app.url'),
                    'timezone' => config('app.timezone'),
                ],
                'server' => [
                    'php_version' => PHP_VERSION,
                    'laravel_version' => app()->version(),
                    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                    'operating_system' => PHP_OS,
                    'memory_limit' => ini_get('memory_limit'),
                    'max_execution_time' => ini_get('max_execution_time'),
                    'upload_max_filesize' => ini_get('upload_max_filesize'),
                ],
                'database' => [
                    'connection' => config('database.default'),
                    'host' => config('database.connections.mysql.host'),
                    'port' => config('database.connections.mysql.port'),
                    'database' => config('database.connections.mysql.database'),
                ],
                'cache' => [
                    'driver' => config('cache.default'),
                    'prefix' => config('cache.prefix'),
                ],
                'session' => [
                    'driver' => config('session.driver'),
                    'lifetime' => config('session.lifetime'),
                ],
                'queue' => [
                    'default' => config('queue.default'),
                ],
                'mail' => [
                    'mailer' => config('mail.default'),
                    'host' => config('mail.mailers.smtp.host'),
                ],
            ];
            
            return response()->json($info);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Unable to retrieve system information',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper methods
     */
    private function measureExecutionTime(callable $callback): float
    {
        $start = microtime(true);
        $callback();
        return round((microtime(true) - $start) * 1000, 2); // Return in milliseconds
    }

    private function getFreeDiskSpace(string $path): int
    {
        return disk_free_space($path) ?: 0;
    }

    private function getMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');
        if ($limit == -1) {
            return PHP_INT_MAX;
        }
        
        $unit = strtolower(substr($limit, -1));
        $value = (int) $limit;
        
        switch ($unit) {
            case 'g':
                $value *= 1024 * 1024 * 1024;
                break;
            case 'm':
                $value *= 1024 * 1024;
                break;
            case 'k':
                $value *= 1024;
                break;
        }
        
        return $value;
    }

    private function getServerLoad(): ?float
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return $load[0] ?? null;
        }
        return null;
    }

    private function getSystemUptime(): ?string
    {
        if (PHP_OS_FAMILY === 'Linux') {
            $uptime = file_get_contents('/proc/uptime');
            if ($uptime) {
                $seconds = (float) explode(' ', $uptime)[0];
                return $this->formatUptime($seconds);
            }
        }
        return null;
    }

    private function formatUptime(float $seconds): string
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        return sprintf('%d days, %d hours, %d minutes', $days, $hours, $minutes);
    }

    private function getPendingJobsCount(): int
    {
        try {
            return DB::table('jobs')->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getFailedJobsCount(): int
    {
        try {
            return DB::table('failed_jobs')->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function cleanOldLogs(): void
    {
        $logPath = storage_path('logs');
        $files = glob($logPath . '/*.log');
        $thirtyDaysAgo = time() - (30 * 24 * 60 * 60);
        
        foreach ($files as $file) {
            if (filemtime($file) < $thirtyDaysAgo) {
                unlink($file);
            }
        }
    }
}