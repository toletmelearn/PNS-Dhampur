<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class DatabaseQueryOptimization
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
        // Only monitor in development or when explicitly enabled
        if (!config('app.debug') && !config('database.query_monitoring', false)) {
            return $next($request);
        }

        $startTime = microtime(true);
        $startQueries = $this->getQueryCount();
        
        // Enable query logging
        DB::enableQueryLog();
        
        $response = $next($request);
        
        $endTime = microtime(true);
        $endQueries = $this->getQueryCount();
        
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $queryCount = $endQueries - $startQueries;
        $queries = DB::getQueryLog();
        
        // Analyze queries for optimization opportunities
        $this->analyzeQueries($request, $queries, $queryCount, $executionTime);
        
        // Disable query logging to prevent memory issues
        DB::disableQueryLog();
        
        return $response;
    }

    /**
     * Get current query count
     *
     * @return int
     */
    protected function getQueryCount(): int
    {
        return count(DB::getQueryLog());
    }

    /**
     * Analyze queries for optimization opportunities
     *
     * @param Request $request
     * @param array $queries
     * @param int $queryCount
     * @param float $executionTime
     * @return void
     */
    protected function analyzeQueries(Request $request, array $queries, int $queryCount, float $executionTime): void
    {
        $issues = [];
        $slowQueries = [];
        $duplicateQueries = [];
        $nPlusOnePatterns = [];

        // Analyze each query
        foreach ($queries as $index => $query) {
            $queryTime = $query['time'] ?? 0;
            $sql = $query['query'] ?? '';
            
            // Detect slow queries (> 100ms)
            if ($queryTime > 100) {
                $slowQueries[] = [
                    'sql' => $sql,
                    'time' => $queryTime,
                    'bindings' => $query['bindings'] ?? []
                ];
            }
            
            // Detect potential N+1 queries
            if ($this->isPotentialNPlusOne($sql, $queries, $index)) {
                $nPlusOnePatterns[] = [
                    'sql' => $sql,
                    'time' => $queryTime,
                    'index' => $index
                ];
            }
        }

        // Detect duplicate queries
        $duplicateQueries = $this->findDuplicateQueries($queries);

        // Check for excessive query count
        if ($queryCount > 50) {
            $issues[] = "High query count: {$queryCount} queries executed";
        }

        // Check for slow page load
        if ($executionTime > 1000) {
            $issues[] = "Slow page load: {$executionTime}ms execution time";
        }

        // Log optimization opportunities
        if (!empty($issues) || !empty($slowQueries) || !empty($duplicateQueries) || !empty($nPlusOnePatterns)) {
            $this->logOptimizationOpportunities($request, [
                'issues' => $issues,
                'slow_queries' => $slowQueries,
                'duplicate_queries' => $duplicateQueries,
                'n_plus_one_patterns' => $nPlusOnePatterns,
                'total_queries' => $queryCount,
                'execution_time' => $executionTime
            ]);
        }

        // Cache query statistics for performance monitoring
        $this->cacheQueryStatistics($request, $queryCount, $executionTime);
    }

    /**
     * Check if a query is potentially part of an N+1 problem
     *
     * @param string $sql
     * @param array $allQueries
     * @param int $currentIndex
     * @return bool
     */
    protected function isPotentialNPlusOne(string $sql, array $allQueries, int $currentIndex): bool
    {
        // Look for SELECT queries with WHERE clauses that might be repeated
        if (!preg_match('/^select.*where.*=\s*\?/i', $sql)) {
            return false;
        }

        // Count similar queries in the same request
        $similarCount = 0;
        $basePattern = preg_replace('/\s+/', ' ', trim($sql));
        
        foreach ($allQueries as $index => $query) {
            if ($index === $currentIndex) continue;
            
            $comparePattern = preg_replace('/\s+/', ' ', trim($query['query'] ?? ''));
            if ($basePattern === $comparePattern) {
                $similarCount++;
            }
        }

        // If we find more than 3 similar queries, it's likely N+1
        return $similarCount > 3;
    }

    /**
     * Find duplicate queries in the request
     *
     * @param array $queries
     * @return array
     */
    protected function findDuplicateQueries(array $queries): array
    {
        $queryGroups = [];
        $duplicates = [];

        foreach ($queries as $query) {
            $sql = $query['query'] ?? '';
            $bindings = json_encode($query['bindings'] ?? []);
            $key = md5($sql . $bindings);

            if (!isset($queryGroups[$key])) {
                $queryGroups[$key] = [];
            }
            $queryGroups[$key][] = $query;
        }

        foreach ($queryGroups as $group) {
            if (count($group) > 1) {
                $duplicates[] = [
                    'sql' => $group[0]['query'] ?? '',
                    'count' => count($group),
                    'total_time' => array_sum(array_column($group, 'time'))
                ];
            }
        }

        return $duplicates;
    }

    /**
     * Log optimization opportunities
     *
     * @param Request $request
     * @param array $data
     * @return void
     */
    protected function logOptimizationOpportunities(Request $request, array $data): void
    {
        Log::channel('performance')->warning('Database optimization opportunities detected', [
            'url' => $request->url(),
            'method' => $request->method(),
            'route' => $request->route() ? $request->route()->getName() : 'unknown',
            'controller' => $this->getControllerName($request),
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'optimization_data' => $data
        ]);
    }

    /**
     * Cache query statistics for monitoring
     *
     * @param Request $request
     * @param int $queryCount
     * @param float $executionTime
     * @return void
     */
    protected function cacheQueryStatistics(Request $request, int $queryCount, float $executionTime): void
    {
        $route = $request->route() ? $request->route()->getName() : $request->path();
        $cacheKey = 'query_stats_' . md5($route);
        
        $stats = Cache::get($cacheKey, [
            'total_requests' => 0,
            'total_queries' => 0,
            'total_time' => 0,
            'max_queries' => 0,
            'max_time' => 0,
            'avg_queries' => 0,
            'avg_time' => 0
        ]);

        $stats['total_requests']++;
        $stats['total_queries'] += $queryCount;
        $stats['total_time'] += $executionTime;
        $stats['max_queries'] = max($stats['max_queries'], $queryCount);
        $stats['max_time'] = max($stats['max_time'], $executionTime);
        $stats['avg_queries'] = $stats['total_queries'] / $stats['total_requests'];
        $stats['avg_time'] = $stats['total_time'] / $stats['total_requests'];

        Cache::put($cacheKey, $stats, now()->addHours(24));
    }

    /**
     * Get controller name from request
     *
     * @param Request $request
     * @return string
     */
    protected function getControllerName(Request $request): string
    {
        if ($request->route() && $request->route()->getAction('controller')) {
            $controller = $request->route()->getAction('controller');
            if (is_string($controller)) {
                return class_basename(explode('@', $controller)[0]);
            }
        }
        return 'unknown';
    }
}