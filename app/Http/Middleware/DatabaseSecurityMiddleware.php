<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class DatabaseSecurityMiddleware
{
    /**
     * SQL injection patterns to detect
     */
    private $sqlInjectionPatterns = [
        // Union-based injection
        '/(\bUNION\b.*\bSELECT\b)/i',
        
        // Boolean-based blind injection
        '/(\bOR\b|\bAND\b)\s+\d+\s*=\s*\d+/i',
        '/(\bOR\b|\bAND\b)\s+[\'"]?\w+[\'"]?\s*=\s*[\'"]?\w+[\'"]?/i',
        
        // Time-based blind injection
        '/\b(SLEEP|BENCHMARK|WAITFOR|DELAY)\s*\(/i',
        
        // Error-based injection
        '/\b(EXTRACTVALUE|UPDATEXML|EXP|FLOOR|RAND)\s*\(/i',
        
        // Stacked queries
        '/;\s*(DROP|DELETE|UPDATE|INSERT|CREATE|ALTER|TRUNCATE)\b/i',
        
        // Comment-based injection
        '/\/\*.*\*\/|--|\#/',
        
        // Function-based injection
        '/\b(LOAD_FILE|INTO\s+OUTFILE|INTO\s+DUMPFILE)\b/i',
        
        // Information schema queries
        '/\b(information_schema|mysql\.user|sys\.)\b/i',
        
        // Hex and char-based injection
        '/\b(0x[0-9a-f]+|CHAR\s*\(|ASCII\s*\()/i',
        
        // Conditional statements
        '/\b(IF\s*\(|CASE\s+WHEN|IIF\s*\()/i'
    ];

    /**
     * Dangerous SQL keywords
     */
    private $dangerousKeywords = [
        'DROP', 'DELETE', 'TRUNCATE', 'ALTER', 'CREATE', 'GRANT', 'REVOKE',
        'LOAD_FILE', 'INTO OUTFILE', 'INTO DUMPFILE', 'LOAD DATA',
        'SHOW DATABASES', 'SHOW TABLES', 'DESCRIBE', 'EXPLAIN'
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip for certain routes (like health checks)
        if ($this->shouldSkipSecurity($request)) {
            return $next($request);
        }

        // Check for SQL injection in request data
        if ($this->detectSqlInjection($request)) {
            $this->logSecurityThreat($request, 'SQL Injection Attempt');
            return $this->blockRequest('Malicious request detected');
        }

        // Monitor database queries during request
        $this->enableQueryMonitoring();

        $response = $next($request);

        // Check for suspicious query patterns
        $this->checkQuerySecurity();

        return $response;
    }

    /**
     * Check if security should be skipped for this request
     */
    private function shouldSkipSecurity(Request $request): bool
    {
        $skipRoutes = [
            'health-check',
            'status',
            'ping'
        ];

        return in_array($request->route()?->getName(), $skipRoutes);
    }

    /**
     * Detect SQL injection attempts in request data
     */
    private function detectSqlInjection(Request $request): bool
    {
        $requestData = $this->getAllRequestData($request);
        
        foreach ($requestData as $key => $value) {
            if (is_string($value) && $this->containsSqlInjection($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all request data including query parameters, form data, and JSON
     */
    private function getAllRequestData(Request $request): array
    {
        $data = array_merge(
            $request->query->all(),
            $request->request->all(),
            $request->json()->all() ?? []
        );

        // Also check headers for injection attempts
        foreach ($request->headers->all() as $key => $values) {
            $data["header_{$key}"] = implode(' ', $values);
        }

        return $data;
    }

    /**
     * Check if a string contains SQL injection patterns
     */
    private function containsSqlInjection(string $input): bool
    {
        // Normalize input for better detection
        $normalizedInput = strtoupper(trim($input));
        
        // Check against SQL injection patterns
        foreach ($this->sqlInjectionPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        // Check for dangerous keywords in suspicious contexts
        foreach ($this->dangerousKeywords as $keyword) {
            if (strpos($normalizedInput, $keyword) !== false) {
                // Additional context checks to reduce false positives
                if ($this->isInSuspiciousContext($normalizedInput, $keyword)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if a keyword appears in a suspicious context
     */
    private function isInSuspiciousContext(string $input, string $keyword): bool
    {
        // Look for SQL-like syntax around the keyword
        $suspiciousPatterns = [
            "/\b{$keyword}\s+(TABLE|DATABASE|USER|FROM|INTO)/i",
            "/;\s*{$keyword}\b/i",
            "/\'\s*;\s*{$keyword}\b/i",
            "/\"\s*;\s*{$keyword}\b/i"
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Enable query monitoring for this request
     */
    private function enableQueryMonitoring(): void
    {
        DB::listen(function ($query) {
            $this->analyzeQuery($query);
        });
    }

    /**
     * Analyze executed queries for security issues
     */
    private function analyzeQuery($query): void
    {
        $sql = $query->sql;
        $bindings = $query->bindings;
        $time = $query->time;

        // Check for suspicious query patterns
        if ($this->isSuspiciousQuery($sql)) {
            $this->logSuspiciousQuery($sql, $bindings, $time);
        }

        // Check for slow queries that might indicate injection attempts
        if ($time > 5000) { // 5 seconds
            $this->logSlowQuery($sql, $bindings, $time);
        }

        // Check for queries without proper parameter binding
        if ($this->hasUnboundParameters($sql)) {
            $this->logUnboundQuery($sql, $bindings);
        }
    }

    /**
     * Check if a query is suspicious
     */
    private function isSuspiciousQuery(string $sql): bool
    {
        $suspiciousPatterns = [
            // Information disclosure
            '/SELECT.*FROM\s+(information_schema|mysql\.user|sys\.)/i',
            
            // Multiple table operations
            '/;\s*(SELECT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER)/i',
            
            // Union-based queries (might be legitimate, but worth monitoring)
            '/UNION\s+SELECT/i',
            
            // Queries with many OR conditions (possible brute force)
            '/(OR\s+\w+\s*=\s*[\'"]?\w+[\'"]?\s*){5,}/i',
            
            // Queries accessing system tables
            '/FROM\s+(mysql\.|information_schema\.|sys\.)/i'
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $sql)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if query has unbound parameters (potential injection)
     */
    private function hasUnboundParameters(string $sql): bool
    {
        // Look for direct string concatenation patterns
        $unboundPatterns = [
            '/[\'"][^\'\"]*\$\w+[^\'\"]*[\'"]/',  // Variables in quotes
            '/[\'"][^\'\"]*\{[^}]+\}[^\'\"]*[\'"]/', // Array access in quotes
            '/LIKE\s+[\'"][^\'\"]*%[^\'\"]*[\'"]/', // LIKE with % not parameterized
        ];

        foreach ($unboundPatterns as $pattern) {
            if (preg_match($pattern, $sql)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check query security after request processing
     */
    private function checkQuerySecurity(): void
    {
        // Check for rate limiting on database queries
        $queryCount = Cache::get('db_queries_' . request()->ip(), 0);
        
        if ($queryCount > 100) { // More than 100 queries per minute
            $this->logExcessiveQueries(request()->ip(), $queryCount);
        }
        
        Cache::put('db_queries_' . request()->ip(), $queryCount + 1, 60);
    }

    /**
     * Log security threat
     */
    private function logSecurityThreat(Request $request, string $threatType): void
    {
        Log::warning('Database Security Threat Detected', [
            'threat_type' => $threatType,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'data' => $request->all(),
            'timestamp' => now()
        ]);

        // Store in cache for rate limiting
        $key = 'security_threats_' . $request->ip();
        $count = Cache::get($key, 0) + 1;
        Cache::put($key, $count, 3600); // 1 hour

        // Block IP if too many threats
        if ($count > 5) {
            Cache::put('blocked_ip_' . $request->ip(), true, 86400); // 24 hours
        }
    }

    /**
     * Log suspicious query
     */
    private function logSuspiciousQuery(string $sql, array $bindings, float $time): void
    {
        Log::warning('Suspicious Database Query Detected', [
            'sql' => $sql,
            'bindings' => $bindings,
            'execution_time' => $time,
            'ip' => request()->ip(),
            'user_id' => auth()->id(),
            'timestamp' => now()
        ]);
    }

    /**
     * Log slow query
     */
    private function logSlowQuery(string $sql, array $bindings, float $time): void
    {
        Log::info('Slow Database Query Detected', [
            'sql' => $sql,
            'bindings' => $bindings,
            'execution_time' => $time,
            'ip' => request()->ip(),
            'user_id' => auth()->id(),
            'timestamp' => now()
        ]);
    }

    /**
     * Log unbound query
     */
    private function logUnboundQuery(string $sql, array $bindings): void
    {
        Log::warning('Potentially Unbound Database Query', [
            'sql' => $sql,
            'bindings' => $bindings,
            'ip' => request()->ip(),
            'user_id' => auth()->id(),
            'timestamp' => now()
        ]);
    }

    /**
     * Log excessive queries
     */
    private function logExcessiveQueries(string $ip, int $queryCount): void
    {
        Log::warning('Excessive Database Queries Detected', [
            'ip' => $ip,
            'query_count' => $queryCount,
            'user_id' => auth()->id(),
            'timestamp' => now()
        ]);
    }

    /**
     * Block malicious request
     */
    private function blockRequest(string $message): Response
    {
        return response()->json([
            'error' => 'Access Denied',
            'message' => $message,
            'code' => 'SECURITY_VIOLATION'
        ], 403);
    }
}