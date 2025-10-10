<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CacheOptimizationService;
use Illuminate\Support\Facades\Cache;

class CacheManagementCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cache:manage 
                            {action : The action to perform (clear|warm|stats|refresh)}
                            {--type= : Cache type to target (student|teacher|class|reports|all)}
                            {--id= : Specific ID to target}
                            {--interactive : Run in interactive mode}';

    /**
     * The console command description.
     */
    protected $description = 'Manage application cache for optimal performance';

    /**
     * Cache optimization service
     */
    protected $cacheService;

    /**
     * Create a new command instance.
     */
    public function __construct(CacheOptimizationService $cacheService)
    {
        parent::__construct();
        $this->cacheService = $cacheService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('interactive')) {
            return $this->runInteractiveMode();
        }

        $action = $this->argument('action');
        $type = $this->option('type');
        $id = $this->option('id');

        switch ($action) {
            case 'clear':
                return $this->clearCache($type, $id);
            case 'warm':
                return $this->warmUpCache();
            case 'stats':
                return $this->showCacheStatistics();
            case 'refresh':
                return $this->refreshCache($type, $id);
            default:
                $this->error("Invalid action: {$action}");
                $this->info('Available actions: clear, warm, stats, refresh');
                return 1;
        }
    }

    /**
     * Run interactive mode
     */
    protected function runInteractiveMode()
    {
        $this->info('=== Cache Management Interactive Mode ===');
        $this->newLine();

        while (true) {
            $action = $this->choice(
                'What would you like to do?',
                [
                    'clear' => 'Clear cache',
                    'warm' => 'Warm up cache',
                    'stats' => 'Show cache statistics',
                    'refresh' => 'Refresh specific cache',
                    'monitor' => 'Monitor cache performance',
                    'exit' => 'Exit'
                ],
                'stats'
            );

            if ($action === 'exit') {
                $this->info('Goodbye!');
                break;
            }

            switch ($action) {
                case 'clear':
                    $this->interactiveClearCache();
                    break;
                case 'warm':
                    $this->warmUpCache();
                    break;
                case 'stats':
                    $this->showCacheStatistics();
                    break;
                case 'refresh':
                    $this->interactiveRefreshCache();
                    break;
                case 'monitor':
                    $this->monitorCachePerformance();
                    break;
            }

            $this->newLine();
            if (!$this->confirm('Continue with another operation?', true)) {
                break;
            }
        }

        return 0;
    }

    /**
     * Clear cache
     */
    protected function clearCache($type = null, $id = null)
    {
        $this->info('Clearing cache...');

        if (!$type) {
            $type = 'all';
        }

        try {
            $this->cacheService->clearCache($type, $id);
            
            $message = "Cache cleared successfully";
            if ($type !== 'all') {
                $message .= " for type: {$type}";
                if ($id) {
                    $message .= " (ID: {$id})";
                }
            }
            
            $this->info($message);
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to clear cache: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Interactive cache clearing
     */
    protected function interactiveClearCache()
    {
        $type = $this->choice(
            'Which cache type would you like to clear?',
            ['all', 'student', 'teacher', 'class', 'reports'],
            'all'
        );

        $id = null;
        if (in_array($type, ['student', 'teacher', 'class'])) {
            if ($this->confirm('Clear cache for a specific ID?', false)) {
                $id = $this->ask('Enter the ID:');
            }
        }

        $this->clearCache($type, $id);
    }

    /**
     * Warm up cache
     */
    protected function warmUpCache()
    {
        $this->info('Warming up cache...');
        
        $progressBar = $this->output->createProgressBar(4);
        $progressBar->start();

        try {
            // Warm up different cache types
            $this->cacheService->warmUpCache();
            $progressBar->advance();

            $progressBar->finish();
            $this->newLine();
            $this->info('Cache warm-up completed successfully!');
            return 0;
        } catch (\Exception $e) {
            $progressBar->finish();
            $this->newLine();
            $this->error("Cache warm-up failed: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Show cache statistics
     */
    protected function showCacheStatistics()
    {
        $this->info('=== Cache Statistics ===');
        
        try {
            $stats = $this->cacheService->getCacheStatistics();
            
            if (isset($stats['error'])) {
                $this->error($stats['error']);
                if (isset($stats['message'])) {
                    $this->line($stats['message']);
                }
                return 1;
            }

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Memory Usage', $stats['memory_usage']],
                    ['Memory Peak', $stats['memory_peak']],
                    ['Total Keys', number_format($stats['total_keys'])],
                    ['Cache Hits', number_format($stats['cache_hits'])],
                    ['Cache Misses', number_format($stats['cache_misses'])],
                    ['Hit Rate', $stats['hit_rate'] . '%'],
                    ['Uptime', $this->formatUptime($stats['uptime'])],
                ]
            );

            // Performance indicators
            $this->newLine();
            $this->info('=== Performance Indicators ===');
            
            if ($stats['hit_rate'] >= 90) {
                $this->info('✅ Excellent cache performance (Hit rate: ' . $stats['hit_rate'] . '%)');
            } elseif ($stats['hit_rate'] >= 75) {
                $this->comment('⚠️  Good cache performance (Hit rate: ' . $stats['hit_rate'] . '%)');
            } else {
                $this->error('❌ Poor cache performance (Hit rate: ' . $stats['hit_rate'] . '%)');
                $this->line('Consider warming up cache or reviewing cache strategy.');
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to retrieve cache statistics: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Refresh specific cache
     */
    protected function refreshCache($type = null, $id = null)
    {
        if (!$type) {
            $this->error('Cache type is required for refresh operation');
            return 1;
        }

        $this->info("Refreshing {$type} cache" . ($id ? " for ID: {$id}" : ''));

        try {
            $this->cacheService->scheduleRefresh($type, $id);
            $this->info('Cache refreshed successfully!');
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to refresh cache: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Interactive cache refresh
     */
    protected function interactiveRefreshCache()
    {
        $type = $this->choice(
            'Which cache type would you like to refresh?',
            ['student', 'teacher', 'class', 'reports'],
            'reports'
        );

        $id = null;
        if (in_array($type, ['student', 'teacher', 'class'])) {
            if ($this->confirm('Refresh cache for a specific ID?', false)) {
                $id = $this->ask('Enter the ID:');
            }
        }

        $this->refreshCache($type, $id);
    }

    /**
     * Monitor cache performance
     */
    protected function monitorCachePerformance()
    {
        $this->info('=== Cache Performance Monitor ===');
        $this->info('Press Ctrl+C to stop monitoring');
        $this->newLine();

        $iterations = 0;
        while ($iterations < 10) { // Limit to 10 iterations for demo
            $stats = $this->cacheService->getCacheStatistics();
            
            if (isset($stats['error'])) {
                $this->error('Unable to retrieve cache statistics');
                break;
            }

            $this->line(sprintf(
                '[%s] Memory: %s | Keys: %s | Hit Rate: %s%% | Hits: %s | Misses: %s',
                now()->format('H:i:s'),
                $stats['memory_usage'],
                number_format($stats['total_keys']),
                $stats['hit_rate'],
                number_format($stats['cache_hits']),
                number_format($stats['cache_misses'])
            ));

            sleep(5); // Wait 5 seconds between updates
            $iterations++;
        }

        $this->newLine();
        $this->info('Monitoring stopped.');
    }

    /**
     * Format uptime in human readable format
     */
    protected function formatUptime($seconds)
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        $parts = [];
        if ($days > 0) $parts[] = "{$days}d";
        if ($hours > 0) $parts[] = "{$hours}h";
        if ($minutes > 0) $parts[] = "{$minutes}m";

        return implode(' ', $parts) ?: '< 1m';
    }
}