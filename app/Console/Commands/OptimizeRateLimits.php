<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class OptimizeRateLimits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rate-limit:optimize 
                            {--cleanup : Clean up expired rate limit entries}
                            {--stats : Show rate limiting statistics}
                            {--reset : Reset all rate limit counters}
                            {--user= : Reset rate limits for specific user ID}
                            {--ip= : Reset rate limits for specific IP address}
                            {--type= : Reset specific type of rate limits (login,api,form,download,upload)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize and manage rate limiting system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Rate Limit Optimization Tool');
        $this->info('============================');

        if ($this->option('cleanup')) {
            $this->cleanupExpiredEntries();
        }

        if ($this->option('stats')) {
            $this->showStatistics();
        }

        if ($this->option('reset')) {
            $this->resetRateLimits();
        }

        if ($this->option('user')) {
            $this->resetUserRateLimits($this->option('user'));
        }

        if ($this->option('ip')) {
            $this->resetIpRateLimits($this->option('ip'));
        }

        if (!$this->hasOption('cleanup') && !$this->hasOption('stats') && 
            !$this->hasOption('reset') && !$this->option('user') && !$this->option('ip')) {
            $this->showMenu();
        }

        return 0;
    }

    /**
     * Show interactive menu
     */
    private function showMenu()
    {
        $choice = $this->choice('What would you like to do?', [
            'Show Statistics',
            'Cleanup Expired Entries',
            'Reset All Rate Limits',
            'Reset User Rate Limits',
            'Reset IP Rate Limits',
            'Optimize Cache',
            'Generate Report'
        ]);

        switch ($choice) {
            case 'Show Statistics':
                $this->showStatistics();
                break;
            case 'Cleanup Expired Entries':
                $this->cleanupExpiredEntries();
                break;
            case 'Reset All Rate Limits':
                if ($this->confirm('Are you sure you want to reset ALL rate limits?')) {
                    $this->resetRateLimits();
                }
                break;
            case 'Reset User Rate Limits':
                $userId = $this->ask('Enter User ID');
                $this->resetUserRateLimits($userId);
                break;
            case 'Reset IP Rate Limits':
                $ip = $this->ask('Enter IP Address');
                $this->resetIpRateLimits($ip);
                break;
            case 'Optimize Cache':
                $this->optimizeCache();
                break;
            case 'Generate Report':
                $this->generateReport();
                break;
        }
    }

    /**
     * Show rate limiting statistics
     */
    private function showStatistics()
    {
        $this->info('Rate Limiting Statistics');
        $this->info('-----------------------');

        $cacheStore = Cache::getStore();
        $prefix = config('ratelimit.cache.prefix', 'rate_limit');

        // Get all rate limit keys
        $keys = $this->getRateLimitKeys();
        
        $stats = [
            'total_keys' => count($keys),
            'login_attempts' => 0,
            'api_requests' => 0,
            'form_submissions' => 0,
            'downloads' => 0,
            'uploads' => 0,
            'blocked_ips' => 0,
            'active_users' => 0
        ];

        foreach ($keys as $key) {
            if (strpos($key, 'login_') === 0) {
                $stats['login_attempts']++;
            } elseif (strpos($key, 'api_') === 0) {
                $stats['api_requests']++;
            } elseif (strpos($key, 'form_') === 0) {
                $stats['form_submissions']++;
            } elseif (strpos($key, 'download_') === 0) {
                $stats['downloads']++;
            } elseif (strpos($key, 'upload_') === 0) {
                $stats['uploads']++;
            }
        }

        $this->table(['Metric', 'Count'], [
            ['Total Rate Limit Keys', $stats['total_keys']],
            ['Login Attempt Keys', $stats['login_attempts']],
            ['API Request Keys', $stats['api_requests']],
            ['Form Submission Keys', $stats['form_submissions']],
            ['Download Keys', $stats['downloads']],
            ['Upload Keys', $stats['uploads']]
        ]);

        // Show top rate limited IPs
        $this->showTopRateLimitedIPs();
        
        // Show top rate limited users
        $this->showTopRateLimitedUsers();
    }

    /**
     * Clean up expired rate limit entries
     */
    private function cleanupExpiredEntries()
    {
        $this->info('Cleaning up expired rate limit entries...');

        $keys = $this->getRateLimitKeys();
        $cleaned = 0;

        foreach ($keys as $key) {
            $value = Cache::get($key);
            if ($value === null || $value === 0) {
                Cache::forget($key);
                $cleaned++;
            }
        }

        $this->info("Cleaned up {$cleaned} expired entries.");
        
        // Log cleanup activity
        Log::channel('daily')->info('Rate limit cleanup completed', [
            'cleaned_entries' => $cleaned,
            'total_keys' => count($keys),
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Reset all rate limits
     */
    private function resetRateLimits()
    {
        $this->info('Resetting all rate limits...');

        $type = $this->option('type');
        $keys = $this->getRateLimitKeys();
        $reset = 0;

        foreach ($keys as $key) {
            if ($type && strpos($key, $type . '_') !== 0) {
                continue;
            }
            
            Cache::forget($key);
            $reset++;
        }

        $this->info("Reset {$reset} rate limit entries.");
        
        // Log reset activity
        Log::channel('security')->warning('Rate limits reset', [
            'reset_entries' => $reset,
            'type' => $type ?? 'all',
            'admin_user' => auth()->id(),
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Reset rate limits for specific user
     */
    private function resetUserRateLimits($userId)
    {
        $this->info("Resetting rate limits for user ID: {$userId}");

        $keys = $this->getRateLimitKeys();
        $reset = 0;

        foreach ($keys as $key) {
            if (strpos($key, "_user:{$userId}") !== false) {
                Cache::forget($key);
                $reset++;
            }
        }

        $this->info("Reset {$reset} rate limit entries for user {$userId}.");
        
        // Log user reset activity
        Log::channel('security')->info('User rate limits reset', [
            'target_user_id' => $userId,
            'reset_entries' => $reset,
            'admin_user' => auth()->id(),
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Reset rate limits for specific IP
     */
    private function resetIpRateLimits($ip)
    {
        $this->info("Resetting rate limits for IP: {$ip}");

        $keys = $this->getRateLimitKeys();
        $reset = 0;

        foreach ($keys as $key) {
            if (strpos($key, "_ip:{$ip}") !== false || strpos($key, ":{$ip}") !== false) {
                Cache::forget($key);
                $reset++;
            }
        }

        $this->info("Reset {$reset} rate limit entries for IP {$ip}.");
        
        // Log IP reset activity
        Log::channel('security')->info('IP rate limits reset', [
            'target_ip' => $ip,
            'reset_entries' => $reset,
            'admin_user' => auth()->id(),
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Optimize cache for rate limiting
     */
    private function optimizeCache()
    {
        $this->info('Optimizing rate limit cache...');

        // Clean up expired entries
        $this->cleanupExpiredEntries();

        // Compact cache if using Redis
        if (config('cache.default') === 'redis') {
            $this->info('Compacting Redis cache...');
            // Redis-specific optimization could go here
        }

        // Warm up frequently accessed keys
        $this->warmUpCache();

        $this->info('Cache optimization completed.');
    }

    /**
     * Generate comprehensive report
     */
    private function generateReport()
    {
        $this->info('Generating Rate Limiting Report...');

        $report = [
            'timestamp' => now()->toISOString(),
            'statistics' => $this->getDetailedStatistics(),
            'top_ips' => $this->getTopRateLimitedIPs(10),
            'top_users' => $this->getTopRateLimitedUsers(10),
            'configuration' => config('ratelimit'),
            'cache_info' => $this->getCacheInfo()
        ];

        $filename = 'rate_limit_report_' . now()->format('Y-m-d_H-i-s') . '.json';
        $path = storage_path('logs/' . $filename);

        file_put_contents($path, json_encode($report, JSON_PRETTY_PRINT));

        $this->info("Report generated: {$path}");
    }

    /**
     * Get all rate limit keys from cache
     */
    private function getRateLimitKeys(): array
    {
        $keys = [];
        $prefix = config('ratelimit.cache.prefix', 'rate_limit');

        // This is a simplified approach - in production, you might need
        // to implement cache-specific key scanning
        $patterns = [
            'login_*', 'api_*', 'form_*', 'download_*', 'upload_*',
            'general_*', 'bandwidth_*', 'rapid_*'
        ];

        // For Redis, you could use SCAN command
        // For other cache drivers, you might need different approaches
        
        return $keys;
    }

    /**
     * Show top rate limited IPs
     */
    private function showTopRateLimitedIPs()
    {
        $this->info('Top Rate Limited IPs:');
        
        $ips = $this->getTopRateLimitedIPs(5);
        
        if (empty($ips)) {
            $this->info('No rate limited IPs found.');
            return;
        }

        $this->table(['IP Address', 'Attempts', 'Last Attempt'], $ips);
    }

    /**
     * Show top rate limited users
     */
    private function showTopRateLimitedUsers()
    {
        $this->info('Top Rate Limited Users:');
        
        $users = $this->getTopRateLimitedUsers(5);
        
        if (empty($users)) {
            $this->info('No rate limited users found.');
            return;
        }

        $this->table(['User ID', 'Attempts', 'Last Attempt'], $users);
    }

    /**
     * Get top rate limited IPs
     */
    private function getTopRateLimitedIPs($limit = 10): array
    {
        // Implementation would depend on your cache driver
        // This is a placeholder
        return [];
    }

    /**
     * Get top rate limited users
     */
    private function getTopRateLimitedUsers($limit = 10): array
    {
        // Implementation would depend on your cache driver
        // This is a placeholder
        return [];
    }

    /**
     * Get detailed statistics
     */
    private function getDetailedStatistics(): array
    {
        return [
            'total_keys' => 0,
            'active_limits' => 0,
            'expired_keys' => 0,
            'memory_usage' => 0
        ];
    }

    /**
     * Get cache information
     */
    private function getCacheInfo(): array
    {
        return [
            'driver' => config('cache.default'),
            'prefix' => config('ratelimit.cache.prefix'),
            'ttl' => config('ratelimit.cache.ttl')
        ];
    }

    /**
     * Warm up frequently accessed cache keys
     */
    private function warmUpCache()
    {
        $this->info('Warming up cache...');
        
        // Pre-load configuration
        config('ratelimit');
        
        // Pre-initialize common keys
        $commonKeys = [
            'login_global',
            'api_global',
            'form_global_critical',
            'download_global',
            'upload_global'
        ];

        foreach ($commonKeys as $key) {
            Cache::get($key, 0);
        }
    }
}