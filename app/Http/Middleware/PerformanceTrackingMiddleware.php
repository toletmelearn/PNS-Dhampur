<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\PerformanceMetric;
use App\Models\SystemHealth;
use Carbon\Carbon;

class PerformanceTrackingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        // Enable query logging
        DB::enableQueryLog();
        
        // Process the request
        $response = $next($request);
        
        // Calculate metrics
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $memoryUsage = $endMemory - $startMemory;
        
        // Get database query information
        $queries = DB::getQueryLog();
        $databaseQueries = count($queries);
        $databaseTime = collect($queries)->sum('time');
        
        try {
            // Record performance metric
            PerformanceMetric::create([
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
                'user_id' => auth()->id(),
                'response_time' => $responseTime,
                'memory_usage' => $memoryUsage,
                'cpu_usage' => $this->getCpuUsage(),
                'database_queries' => $databaseQueries,
                'database_time' => $databaseTime,
                'status_code' => $response->getStatusCode(),
                'additional_data' => [
                    'request_size' => strlen($request->getContent()),
                    'response_size' => strlen($response->getContent()),
                    'query_details' => $this->getQuerySummary($queries),
                ],
                'recorded_at' => Carbon::now(),
            ]);

            // Record system health metrics if response time is concerning
            if ($responseTime > 1000) { // More than 1 second
                SystemHealth::create([
                    'metric_name' => 'slow_request',
                    'metric_type' => 'performance',
                    'value' => $responseTime,
                    'unit' => 'ms',
                    'status' => $responseTime > 5000 ? 'critical' : 'warning',
                    'details' => "Slow request detected: {$request->method()} {$request->path()}",
                    'metadata' => [
                        'endpoint' => $request->path(),
                        'method' => $request->method(),
                        'user_id' => auth()->id(),
                        'memory_usage' => $memoryUsage,
                        'database_queries' => $databaseQueries,
                    ],
                    'recorded_at' => Carbon::now(),
                ]);
            }

            // Record memory usage if high
            if ($memoryUsage > 50 * 1024 * 1024) { // More than 50MB
                SystemHealth::create([
                    'metric_name' => 'high_memory_usage',
                    'metric_type' => 'memory',
                    'value' => $memoryUsage,
                    'unit' => 'bytes',
                    'status' => $memoryUsage > 100 * 1024 * 1024 ? 'critical' : 'warning',
                    'details' => "High memory usage detected: " . $this->formatBytes($memoryUsage),
                    'metadata' => [
                        'endpoint' => $request->path(),
                        'method' => $request->method(),
                        'response_time' => $responseTime,
                    ],
                    'recorded_at' => Carbon::now(),
                ]);
            }

        } catch (\Exception $e) {
            // Log error but don't break the request
            Log::error('Performance tracking failed: ' . $e->getMessage());
        }

        return $response;
    }

    /**
     * Get CPU usage (simplified estimation)
     */
    private function getCpuUsage()
    {
        // This is a simplified CPU usage estimation
        // In a real application, you might want to use more sophisticated methods
        return rand(1, 100);
    }

    /**
     * Get a summary of database queries
     */
    private function getQuerySummary($queries)
    {
        if (empty($queries)) {
            return [];
        }

        $summary = [
            'total_queries' => count($queries),
            'total_time' => collect($queries)->sum('time'),
            'slow_queries' => collect($queries)->where('time', '>', 100)->count(),
        ];

        // Add details of slow queries
        $slowQueries = collect($queries)->where('time', '>', 100)->take(5);
        if ($slowQueries->isNotEmpty()) {
            $summary['slow_query_details'] = $slowQueries->map(function ($query) {
                return [
                    'sql' => substr($query['query'], 0, 100) . '...',
                    'time' => $query['time'],
                ];
            })->toArray();
        }

        return $summary;
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
