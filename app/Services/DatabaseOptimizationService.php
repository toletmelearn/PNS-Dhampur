<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DatabaseOptimizationService
{
    private $slowQueryThreshold = 100; // milliseconds
    private $cachePrefix = 'db_optimization:';
    private $cacheTtl = 3600; // 1 hour

    /**
     * Monitor and log slow queries
     */
    public function monitorSlowQueries()
    {
        $slowQueries = $this->getSlowQueries();
        
        foreach ($slowQueries as $query) {
            if ($query['time'] > $this->slowQueryThreshold) {
                Log::warning('Slow query detected', [
                    'query' => $query['sql'],
                    'time' => $query['time'],
                    'bindings' => $query['bindings'],
                    'connection' => $query['connection']
                ]);
                
                $this->recordSlowQuery($query);
            }
        }
        
        return $slowQueries;
    }

    /**
     * Get slow queries from database logs
     */
    public function getSlowQueries($limit = 50)
    {
        $cacheKey = $this->cachePrefix . 'slow_queries';
        
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($limit) {
            try {
                // For MySQL, get slow queries from performance schema
                $slowQueries = DB::select("
                    SELECT 
                        DIGEST_TEXT as sql,
                        AVG_TIMER_WAIT / 1000000000 as avg_time_seconds,
                        COUNT_STAR as execution_count,
                        SUM_TIMER_WAIT / 1000000000 as total_time_seconds,
                        MAX_TIMER_WAIT / 1000000000 as max_time_seconds,
                        FIRST_SEEN,
                        LAST_SEEN
                    FROM performance_schema.events_statements_summary_by_digest 
                    WHERE DIGEST_TEXT IS NOT NULL 
                        AND AVG_TIMER_WAIT > ? 
                    ORDER BY AVG_TIMER_WAIT DESC 
                    LIMIT ?
                ", [$this->slowQueryThreshold * 1000000, $limit]);

                return collect($slowQueries)->map(function ($query) {
                    return [
                        'sql' => $query->sql,
                        'avg_time' => round($query->avg_time_seconds * 1000, 2), // Convert to ms
                        'execution_count' => $query->execution_count,
                        'total_time' => round($query->total_time_seconds * 1000, 2),
                        'max_time' => round($query->max_time_seconds * 1000, 2),
                        'first_seen' => $query->FIRST_SEEN,
                        'last_seen' => $query->LAST_SEEN,
                    ];
                })->toArray();
            } catch (\Exception $e) {
                Log::error('Failed to get slow queries from performance schema', [
                    'error' => $e->getMessage()
                ]);
                return [];
            }
        });
    }

    /**
     * Analyze table performance and suggest optimizations
     */
    public function analyzeTablePerformance()
    {
        $tables = $this->getAllTables();
        $analysis = [];

        foreach ($tables as $table) {
            $tableAnalysis = $this->analyzeTable($table);
            if (!empty($tableAnalysis['issues'])) {
                $analysis[$table] = $tableAnalysis;
            }
        }

        return $analysis;
    }

    /**
     * Analyze individual table
     */
    private function analyzeTable($tableName)
    {
        $issues = [];
        $suggestions = [];

        try {
            // Get table status
            $status = DB::select("SHOW TABLE STATUS LIKE ?", [$tableName])[0] ?? null;
            
            if (!$status) {
                return ['issues' => [], 'suggestions' => []];
            }

            // Check table size
            $dataLength = $status->Data_length ?? 0;
            $indexLength = $status->Index_length ?? 0;
            $totalSize = $dataLength + $indexLength;

            // Large table without proper indexing
            if ($totalSize > 100 * 1024 * 1024 && $indexLength < $dataLength * 0.1) {
                $issues[] = 'Large table with insufficient indexing';
                $suggestions[] = 'Consider adding indexes on frequently queried columns';
            }

            // Check for unused indexes
            $indexes = $this->getTableIndexes($tableName);
            $unusedIndexes = $this->findUnusedIndexes($tableName, $indexes);
            
            if (!empty($unusedIndexes)) {
                $issues[] = 'Unused indexes detected';
                $suggestions[] = 'Consider dropping unused indexes: ' . implode(', ', $unusedIndexes);
            }

            // Check for missing indexes on foreign keys
            $missingFkIndexes = $this->findMissingForeignKeyIndexes($tableName);
            if (!empty($missingFkIndexes)) {
                $issues[] = 'Missing indexes on foreign keys';
                $suggestions[] = 'Add indexes on foreign key columns: ' . implode(', ', $missingFkIndexes);
            }

            // Check for fragmentation
            $fragmentation = $this->calculateFragmentation($status);
            if ($fragmentation > 20) {
                $issues[] = "High fragmentation ({$fragmentation}%)";
                $suggestions[] = 'Consider running OPTIMIZE TABLE';
            }

            return [
                'issues' => $issues,
                'suggestions' => $suggestions,
                'size_mb' => round($totalSize / (1024 * 1024), 2),
                'fragmentation' => $fragmentation,
                'row_count' => $status->Rows ?? 0
            ];

        } catch (\Exception $e) {
            Log::error("Failed to analyze table {$tableName}", [
                'error' => $e->getMessage()
            ]);
            return ['issues' => [], 'suggestions' => []];
        }
    }

    /**
     * Get all tables in the database
     */
    private function getAllTables()
    {
        try {
            $tables = DB::select('SHOW TABLES');
            $databaseName = DB::getDatabaseName();
            $tableKey = "Tables_in_{$databaseName}";
            
            return collect($tables)->pluck($tableKey)->toArray();
        } catch (\Exception $e) {
            Log::error('Failed to get database tables', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get indexes for a table
     */
    private function getTableIndexes($tableName)
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM `{$tableName}`");
            return collect($indexes)->groupBy('Key_name')->toArray();
        } catch (\Exception $e) {
            Log::error("Failed to get indexes for table {$tableName}", [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Find unused indexes
     */
    private function findUnusedIndexes($tableName, $indexes)
    {
        $unusedIndexes = [];
        
        try {
            // Get index usage statistics
            $indexStats = DB::select("
                SELECT DISTINCT INDEX_NAME
                FROM information_schema.STATISTICS 
                WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = ?
                    AND INDEX_NAME NOT IN (
                        SELECT INDEX_NAME 
                        FROM performance_schema.table_io_waits_summary_by_index_usage 
                        WHERE OBJECT_SCHEMA = DATABASE() 
                            AND OBJECT_NAME = ?
                            AND COUNT_STAR > 0
                    )
                    AND INDEX_NAME != 'PRIMARY'
            ", [$tableName, $tableName]);

            foreach ($indexStats as $stat) {
                $unusedIndexes[] = $stat->INDEX_NAME;
            }
        } catch (\Exception $e) {
            // Fallback: assume all non-primary indexes might be unused
            foreach ($indexes as $indexName => $indexData) {
                if ($indexName !== 'PRIMARY') {
                    $unusedIndexes[] = $indexName;
                }
            }
        }

        return $unusedIndexes;
    }

    /**
     * Find missing foreign key indexes
     */
    private function findMissingForeignKeyIndexes($tableName)
    {
        $missingIndexes = [];
        
        try {
            $foreignKeys = DB::select("
                SELECT COLUMN_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = ?
                    AND REFERENCED_TABLE_NAME IS NOT NULL
            ", [$tableName]);

            $existingIndexes = DB::select("
                SELECT COLUMN_NAME
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = ?
                    AND SEQ_IN_INDEX = 1
            ", [$tableName]);

            $indexedColumns = collect($existingIndexes)->pluck('COLUMN_NAME')->toArray();

            foreach ($foreignKeys as $fk) {
                if (!in_array($fk->COLUMN_NAME, $indexedColumns)) {
                    $missingIndexes[] = $fk->COLUMN_NAME;
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to check foreign key indexes for {$tableName}", [
                'error' => $e->getMessage()
            ]);
        }

        return $missingIndexes;
    }

    /**
     * Calculate table fragmentation
     */
    private function calculateFragmentation($status)
    {
        $dataLength = $status->Data_length ?? 0;
        $dataFree = $status->Data_free ?? 0;
        
        if ($dataLength == 0) {
            return 0;
        }
        
        return round(($dataFree / ($dataLength + $dataFree)) * 100, 2);
    }

    /**
     * Optimize database tables
     */
    public function optimizeTables($tables = null)
    {
        if ($tables === null) {
            $tables = $this->getAllTables();
        }

        $results = [];
        
        foreach ($tables as $table) {
            try {
                $result = DB::select("OPTIMIZE TABLE `{$table}`")[0];
                $results[$table] = [
                    'status' => $result->Msg_type ?? 'unknown',
                    'message' => $result->Msg_text ?? 'No message'
                ];
                
                Log::info("Optimized table {$table}", $results[$table]);
            } catch (\Exception $e) {
                $results[$table] = [
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
                
                Log::error("Failed to optimize table {$table}", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Analyze and repair tables
     */
    public function repairTables($tables = null)
    {
        if ($tables === null) {
            $tables = $this->getAllTables();
        }

        $results = [];
        
        foreach ($tables as $table) {
            try {
                $result = DB::select("REPAIR TABLE `{$table}`")[0];
                $results[$table] = [
                    'status' => $result->Msg_type ?? 'unknown',
                    'message' => $result->Msg_text ?? 'No message'
                ];
            } catch (\Exception $e) {
                $results[$table] = [
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Get database performance metrics
     */
    public function getPerformanceMetrics()
    {
        $cacheKey = $this->cachePrefix . 'performance_metrics';
        
        return Cache::remember($cacheKey, 300, function () { // 5 minutes cache
            try {
                $metrics = [];

                // Get connection statistics
                $connectionStats = DB::select("SHOW STATUS LIKE 'Connections'")[0] ?? null;
                $metrics['total_connections'] = $connectionStats->Value ?? 0;

                // Get query statistics
                $queryStats = DB::select("SHOW STATUS LIKE 'Questions'")[0] ?? null;
                $metrics['total_queries'] = $queryStats->Value ?? 0;

                // Get slow query count
                $slowQueryStats = DB::select("SHOW STATUS LIKE 'Slow_queries'")[0] ?? null;
                $metrics['slow_queries'] = $slowQueryStats->Value ?? 0;

                // Get buffer pool statistics
                $bufferPoolStats = DB::select("SHOW STATUS LIKE 'Innodb_buffer_pool_read_requests'")[0] ?? null;
                $metrics['buffer_pool_reads'] = $bufferPoolStats->Value ?? 0;

                // Calculate query cache hit ratio
                $qcHits = DB::select("SHOW STATUS LIKE 'Qcache_hits'")[0]->Value ?? 0;
                $qcInserts = DB::select("SHOW STATUS LIKE 'Qcache_inserts'")[0]->Value ?? 0;
                $qcNotCached = DB::select("SHOW STATUS LIKE 'Qcache_not_cached'")[0]->Value ?? 0;
                
                $totalQueries = $qcHits + $qcInserts + $qcNotCached;
                $metrics['query_cache_hit_ratio'] = $totalQueries > 0 ? round(($qcHits / $totalQueries) * 100, 2) : 0;

                // Get table lock statistics
                $tableLockWaits = DB::select("SHOW STATUS LIKE 'Table_locks_waited'")[0]->Value ?? 0;
                $tableLockImmediate = DB::select("SHOW STATUS LIKE 'Table_locks_immediate'")[0]->Value ?? 0;
                $totalLocks = $tableLockWaits + $tableLockImmediate;
                $metrics['table_lock_contention'] = $totalLocks > 0 ? round(($tableLockWaits / $totalLocks) * 100, 2) : 0;

                return $metrics;
            } catch (\Exception $e) {
                Log::error('Failed to get performance metrics', [
                    'error' => $e->getMessage()
                ]);
                return [];
            }
        });
    }

    /**
     * Record slow query for analysis
     */
    private function recordSlowQuery($query)
    {
        $cacheKey = $this->cachePrefix . 'recorded_slow_queries';
        $slowQueries = Cache::get($cacheKey, []);
        
        $slowQueries[] = [
            'sql' => $query['sql'],
            'time' => $query['time'],
            'bindings' => $query['bindings'],
            'timestamp' => now()->toISOString(),
        ];
        
        // Keep only last 100 slow queries
        if (count($slowQueries) > 100) {
            $slowQueries = array_slice($slowQueries, -100);
        }
        
        Cache::put($cacheKey, $slowQueries, $this->cacheTtl);
    }

    /**
     * Get recorded slow queries
     */
    public function getRecordedSlowQueries()
    {
        $cacheKey = $this->cachePrefix . 'recorded_slow_queries';
        return Cache::get($cacheKey, []);
    }

    /**
     * Generate optimization recommendations
     */
    public function generateOptimizationRecommendations()
    {
        $recommendations = [];
        
        // Analyze slow queries
        $slowQueries = $this->getSlowQueries(10);
        if (!empty($slowQueries)) {
            $recommendations[] = [
                'type' => 'slow_queries',
                'priority' => 'high',
                'title' => 'Slow Queries Detected',
                'description' => count($slowQueries) . ' slow queries found. Consider optimizing these queries or adding appropriate indexes.',
                'action' => 'Review and optimize slow queries'
            ];
        }

        // Analyze table performance
        $tableAnalysis = $this->analyzeTablePerformance();
        foreach ($tableAnalysis as $table => $analysis) {
            if (!empty($analysis['issues'])) {
                $recommendations[] = [
                    'type' => 'table_optimization',
                    'priority' => 'medium',
                    'title' => "Table Optimization: {$table}",
                    'description' => implode(', ', $analysis['issues']),
                    'action' => implode('; ', $analysis['suggestions'])
                ];
            }
        }

        // Check performance metrics
        $metrics = $this->getPerformanceMetrics();
        
        if (isset($metrics['query_cache_hit_ratio']) && $metrics['query_cache_hit_ratio'] < 80) {
            $recommendations[] = [
                'type' => 'query_cache',
                'priority' => 'medium',
                'title' => 'Low Query Cache Hit Ratio',
                'description' => "Query cache hit ratio is {$metrics['query_cache_hit_ratio']}%",
                'action' => 'Consider increasing query cache size or reviewing query patterns'
            ];
        }

        if (isset($metrics['table_lock_contention']) && $metrics['table_lock_contention'] > 5) {
            $recommendations[] = [
                'type' => 'table_locks',
                'priority' => 'high',
                'title' => 'High Table Lock Contention',
                'description' => "Table lock contention is {$metrics['table_lock_contention']}%",
                'action' => 'Consider using InnoDB tables or optimizing queries to reduce lock time'
            ];
        }

        return $recommendations;
    }

    /**
     * Clear optimization cache
     */
    public function clearCache()
    {
        $keys = [
            'slow_queries',
            'performance_metrics',
            'recorded_slow_queries'
        ];

        foreach ($keys as $key) {
            Cache::forget($this->cachePrefix . $key);
        }
    }

    /**
     * Set slow query threshold
     */
    public function setSlowQueryThreshold($milliseconds)
    {
        $this->slowQueryThreshold = $milliseconds;
    }
}