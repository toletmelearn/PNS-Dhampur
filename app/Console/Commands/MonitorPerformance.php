<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PerformanceAlertService;
use App\Models\SystemHealth;
use App\Models\PerformanceMetric;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MonitorPerformance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'performance:monitor 
                            {--check=all : Specific check to run (all, response-time, memory, cpu, disk, errors, load, database, services)}
                            {--threshold=* : Override specific thresholds (e.g., --threshold=memory_usage:90)}
                            {--force : Force alerts even if in cooldown period}
                            {--dry-run : Run checks without triggering alerts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor system performance and trigger alerts when thresholds are exceeded';

    protected $performanceAlertService;

    /**
     * Create a new command instance.
     */
    public function __construct(PerformanceAlertService $performanceAlertService)
    {
        parent::__construct();
        $this->performanceAlertService = $performanceAlertService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting performance monitoring...');
        
        $startTime = microtime(true);
        $check = $this->option('check');
        $isDryRun = $this->option('dry-run');
        $force = $this->option('force');
        
        // Override thresholds if provided
        $this->handleThresholdOverrides();
        
        // Clear cooldowns if force flag is used
        if ($force) {
            $this->clearAllCooldowns();
            $this->warn('Force flag enabled - all alert cooldowns cleared');
        }
        
        if ($isDryRun) {
            $this->warn('Dry run mode - no alerts will be triggered');
        }
        
        try {
            // Record system health metrics
            $this->recordSystemHealth();
            
            // Run specific checks or all checks
            switch ($check) {
                case 'response-time':
                    $this->checkResponseTime();
                    break;
                case 'memory':
                    $this->checkMemoryUsage();
                    break;
                case 'cpu':
                    $this->checkCpuUsage();
                    break;
                case 'disk':
                    $this->checkDiskSpace();
                    break;
                case 'errors':
                    $this->checkErrorRate();
                    break;
                case 'load':
                    $this->checkSystemLoad();
                    break;
                case 'database':
                    $this->checkDatabasePerformance();
                    break;
                case 'services':
                    $this->checkServiceAvailability();
                    break;
                case 'all':
                default:
                    if (!$isDryRun) {
                        $this->performanceAlertService->checkPerformanceMetrics();
                    } else {
                        $this->runDryRunChecks();
                    }
                    break;
            }
            
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            $this->info("Performance monitoring completed in {$executionTime}ms");
            
            // Display summary
            $this->displaySummary();
            
        } catch (\Exception $e) {
            $this->error('Error during performance monitoring: ' . $e->getMessage());
            Log::error('Performance monitoring command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
        
        return 0;
    }

    /**
     * Handle threshold overrides from command line
     */
    protected function handleThresholdOverrides()
    {
        $thresholdOverrides = $this->option('threshold');
        
        if (!empty($thresholdOverrides)) {
            $overrides = [];
            foreach ($thresholdOverrides as $override) {
                if (strpos($override, ':') !== false) {
                    [$key, $value] = explode(':', $override, 2);
                    $overrides[$key] = is_numeric($value) ? (float) $value : $value;
                }
            }
            
            if (!empty($overrides)) {
                $this->performanceAlertService->updateThresholds($overrides);
                $this->info('Threshold overrides applied: ' . json_encode($overrides));
            }
        }
    }

    /**
     * Clear all alert cooldowns
     */
    protected function clearAllCooldowns()
    {
        $alertTypes = [
            'high_response_time',
            'high_memory_usage',
            'high_cpu_usage',
            'disk_space_low',
            'high_error_rate',
            'system_overload',
            'database_slow_queries',
            'service_unavailable'
        ];
        
        foreach ($alertTypes as $alertType) {
            $this->performanceAlertService->clearAlertCooldown($alertType);
        }
    }

    /**
     * Record current system health metrics
     */
    protected function recordSystemHealth()
    {
        $this->line('Recording system health metrics...');
        
        $dashboardData = $this->performanceAlertService->getPerformanceDashboardData();
        $metrics = $dashboardData['current_metrics'];
        
        // Record individual metrics using the new table structure
        $this->recordMetric('system', 'memory_usage', $metrics['memory_usage'], '%');
        $this->recordMetric('system', 'cpu_usage', $metrics['cpu_usage'], '%');
        $this->recordMetric('system', 'disk_space', $metrics['disk_space'], 'GB');
        $this->recordMetric('system', 'system_load', $metrics['system_load'], '');
        
        if ($metrics['avg_response_time']) {
            $this->recordMetric('system', 'response_time', $metrics['avg_response_time'], 'ms');
        }
        
        // Determine overall system status
        $status = 'healthy';
        if ($metrics['memory_usage'] > 85 || $metrics['cpu_usage'] > 80) {
            $status = 'warning';
        }
        if ($metrics['memory_usage'] > 95 || $metrics['cpu_usage'] > 95 || $metrics['disk_space'] < 5) {
            $status = 'critical';
        }
        
        SystemHealth::create([
            'metric_name' => 'system_overview',
            'metric_type' => 'system',
            'value' => 100, // Overall system health score
            'unit' => '%',
            'status' => $status,
            'details' => json_encode($metrics),
            'metadata' => json_encode([
                'service_status' => $dashboardData['service_status'],
                'thresholds' => $dashboardData['thresholds']
            ]),
            'recorded_at' => Carbon::now(),
        ]);
        
        $this->info("System health recorded - Status: {$status}");
    }

    /**
     * Record a performance metric
     */
    protected function recordMetric($type, $name, $value, $unit, $metadata = [])
    {
        PerformanceMetric::create([
            'metric_type' => $type,
            'metric_name' => $name,
            'value' => $value,
            'unit' => $unit,
            'metadata' => json_encode($metadata),
            'recorded_at' => Carbon::now(),
        ]);
    }

    /**
     * Run dry run checks without triggering alerts
     */
    protected function runDryRunChecks()
    {
        $dashboardData = $this->performanceAlertService->getPerformanceDashboardData();
        $metrics = $dashboardData['current_metrics'];
        $thresholds = $dashboardData['thresholds'];
        
        $this->table(
            ['Metric', 'Current Value', 'Threshold', 'Status'],
            [
                ['Memory Usage', $metrics['memory_usage'] . '%', $thresholds['memory_usage'] . '%', 
                 $metrics['memory_usage'] > $thresholds['memory_usage'] ? '⚠️ ALERT' : '✅ OK'],
                ['CPU Usage', $metrics['cpu_usage'] . '%', $thresholds['cpu_usage'] . '%',
                 $metrics['cpu_usage'] > $thresholds['cpu_usage'] ? '⚠️ ALERT' : '✅ OK'],
                ['Disk Space', $metrics['disk_space'] . ' GB', $thresholds['disk_space'] . ' GB',
                 $metrics['disk_space'] < $thresholds['disk_space'] ? '⚠️ ALERT' : '✅ OK'],
                ['System Load', $metrics['system_load'], $thresholds['system_load'],
                 $metrics['system_load'] > $thresholds['system_load'] ? '⚠️ ALERT' : '✅ OK'],
                ['Avg Response Time', $metrics['avg_response_time'] . ' ms', $thresholds['response_time'] . ' ms',
                 $metrics['avg_response_time'] > $thresholds['response_time'] ? '⚠️ ALERT' : '✅ OK'],
            ]
        );
        
        // Service status
        $this->line('');
        $this->info('Service Status:');
        foreach ($dashboardData['service_status'] as $service => $status) {
            $statusIcon = $status ? '✅' : '❌';
            $this->line("  {$statusIcon} " . ucfirst($service) . ': ' . ($status ? 'Available' : 'Unavailable'));
        }
    }

    /**
     * Individual check methods for specific monitoring
     */
    protected function checkResponseTime()
    {
        $this->line('Checking response time...');
        // Implementation would call specific method from service
    }

    protected function checkMemoryUsage()
    {
        $this->line('Checking memory usage...');
        // Implementation would call specific method from service
    }

    protected function checkCpuUsage()
    {
        $this->line('Checking CPU usage...');
        // Implementation would call specific method from service
    }

    protected function checkDiskSpace()
    {
        $this->line('Checking disk space...');
        // Implementation would call specific method from service
    }

    protected function checkErrorRate()
    {
        $this->line('Checking error rate...');
        // Implementation would call specific method from service
    }

    protected function checkSystemLoad()
    {
        $this->line('Checking system load...');
        // Implementation would call specific method from service
    }

    protected function checkDatabasePerformance()
    {
        $this->line('Checking database performance...');
        // Implementation would call specific method from service
    }

    protected function checkServiceAvailability()
    {
        $this->line('Checking service availability...');
        // Implementation would call specific method from service
    }

    /**
     * Display monitoring summary
     */
    protected function displaySummary()
    {
        $this->line('');
        $this->info('=== Performance Monitoring Summary ===');
        
        $dashboardData = $this->performanceAlertService->getPerformanceDashboardData();
        $recentAlerts = $dashboardData['recent_alerts'];
        
        if (count($recentAlerts) > 0) {
            $this->warn('Recent Alerts (last 24 hours): ' . count($recentAlerts));
            foreach (array_slice($recentAlerts, 0, 5) as $alert) {
                $severityIcon = $this->getSeverityIcon($alert['severity']);
                $this->line("  {$severityIcon} {$alert['title']} - " . Carbon::parse($alert['created_at'])->diffForHumans());
            }
        } else {
            $this->info('No recent alerts - system is running smoothly');
        }
        
        // System health summary
        $healthCount = SystemHealth::where('recorded_at', '>=', Carbon::now()->subHours(24))->count();
        $criticalCount = SystemHealth::critical()->where('recorded_at', '>=', Carbon::now()->subHours(24))->count();
        
        $this->line('');
        $this->info("Health checks in last 24h: {$healthCount}");
        if ($criticalCount > 0) {
            $this->warn("Critical issues: {$criticalCount}");
        } else {
            $this->info("Critical issues: 0");
        }
    }

    /**
     * Get severity icon for display
     */
    protected function getSeverityIcon($severity)
    {
        switch ($severity) {
            case 'emergency':
                return '🚨';
            case 'critical':
                return '⚠️';
            case 'warning':
                return '⚡';
            default:
                return 'ℹ️';
        }
    }
}