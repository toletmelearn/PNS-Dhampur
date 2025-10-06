<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ApiRateLimitMiddleware
{
    /**
     * API request limits per user role (requests per minute)
     */
    private const ROLE_LIMITS = [
        'super_admin' => 1000,
        'admin' => 500,
        'principal' => 300,
        'vice_principal' => 300,
        'teacher' => 200,
        'student' => 100,
        'guest' => 20
    ];
    
    /**
     * Endpoint-specific rate limits (requests per minute)
     */
    private const ENDPOINT_LIMITS = [
        // Authentication endpoints
        'auth.login' => 5,
        'auth.register' => 3,
        'auth.password.reset' => 2,
        'auth.password.update' => 3,
        'auth.logout' => 10,
        
        // User management
        'users.create' => 10,
        'users.update' => 20,
        'users.delete' => 5,
        'users.bulk' => 2,
        
        // Academic operations
        'attendance.mark' => 100,
        'attendance.bulk' => 5,
        'grades.submit' => 50,
        'grades.bulk' => 3,
        
        // Reports and exports
        'reports.generate' => 10,
        'reports.export' => 5,
        'reports.download' => 20,
        
        // File operations
        'files.upload' => 30,
        'files.download' => 100,
        'files.delete' => 20,
        
        // Search operations
        'search.students' => 200,
        'search.teachers' => 100,
        'search.general' => 150,
        
        // Notifications
        'notifications.send' => 50,
        'notifications.bulk' => 5,
        
        // System operations
        'system.backup' => 1,
        'system.maintenance' => 2,
        'system.logs' => 20
    ];
    
    /**
     * Critical endpoints that need extra monitoring
     */
    private const CRITICAL_ENDPOINTS = [
        'auth.login',
        'auth.register',
        'users.create',
        'users.delete',
        'system.backup',
        'system.maintenance',
        'grades.bulk',
        'attendance.bulk'
    ];
    
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $endpoint
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ?string $endpoint = null)
    {
        $user = Auth::user();
        $ip = $request->ip();
        $userRole = $user ? $user->role : 'guest';
        
        // Detect endpoint if not provided
        $endpoint = $endpoint ?? $this->detectEndpoint($request);
        
        // Get rate limits
        $roleLimit = $this->getRoleLimit($userRole);
        $endpointLimit = $this->getEndpointLimit($endpoint, $userRole);
        $finalLimit = min($roleLimit, $endpointLimit);
        
        // Create rate limiting keys
        $userKey = $user ? "api_user:{$user->id}:{$endpoint}" : "api_ip:{$ip}:{$endpoint}";
        $globalUserKey = $user ? "api_user_global:{$user->id}" : "api_ip_global:{$ip}";
        $endpointKey = "api_endpoint:{$endpoint}";
        $burstKey = $user ? "api_burst_user:{$user->id}" : "api_burst_ip:{$ip}";
        
        // Check endpoint-specific rate limit
        if (RateLimiter::tooManyAttempts($userKey, $finalLimit)) {
            $this->logRateLimitExceeded('endpoint', $userKey, $request, $endpoint);
            return $this->rateLimitResponse($request, 'endpoint', RateLimiter::availableIn($userKey), $finalLimit);
        }
        
        // Check global user rate limit
        if (RateLimiter::tooManyAttempts($globalUserKey, $roleLimit)) {
            $this->logRateLimitExceeded('user_global', $globalUserKey, $request, $endpoint);
            return $this->rateLimitResponse($request, 'user', RateLimiter::availableIn($globalUserKey), $roleLimit);
        }
        
        // Check global endpoint rate limit (prevent endpoint overload)
        $globalEndpointLimit = $this->getGlobalEndpointLimit($endpoint);
        if (RateLimiter::tooManyAttempts($endpointKey, $globalEndpointLimit)) {
            $this->logRateLimitExceeded('endpoint_global', $endpointKey, $request, $endpoint);
            return $this->endpointOverloadResponse($request, $endpoint);
        }
        
        // Check for burst requests (potential attack)
        $burstLimit = $this->getBurstLimit($userRole);
        if (RateLimiter::tooManyAttempts($burstKey, $burstLimit)) {
            $this->logSuspiciousActivity($request, $endpoint);
            return $this->suspiciousActivityResponse($request);
        }
        
        // Special handling for critical endpoints
        if (in_array($endpoint, self::CRITICAL_ENDPOINTS)) {
            $criticalKey = $user ? "api_critical_user:{$user->id}" : "api_critical_ip:{$ip}";
            $criticalLimit = $this->getCriticalEndpointLimit($endpoint, $userRole);
            
            if (RateLimiter::tooManyAttempts($criticalKey, $criticalLimit)) {
                $this->logCriticalEndpointExceeded($criticalKey, $request, $endpoint);
                return $this->criticalEndpointResponse($request, $endpoint);
            }
            
            RateLimiter::hit($criticalKey, 3600); // 1 hour window for critical operations
        }
        
        // Increment counters
        RateLimiter::hit($userKey, 60);        // 1 minute
        RateLimiter::hit($globalUserKey, 60);  // 1 minute
        RateLimiter::hit($endpointKey, 60);    // 1 minute
        RateLimiter::hit($burstKey, 10);       // 10 seconds for burst detection
        
        $response = $next($request);
        
        // Add comprehensive rate limit headers
        $response->headers->add([
            'X-RateLimit-Limit' => $finalLimit,
            'X-RateLimit-Remaining' => max(0, $finalLimit - RateLimiter::attempts($userKey)),
            'X-RateLimit-Reset' => now()->addSeconds(RateLimiter::availableIn($userKey))->timestamp,
            'X-RateLimit-Global-Limit' => $roleLimit,
            'X-RateLimit-Global-Remaining' => max(0, $roleLimit - RateLimiter::attempts($globalUserKey)),
            'X-RateLimit-Endpoint' => $endpoint,
            'X-RateLimit-User-Role' => $userRole,
            'X-RateLimit-Burst-Remaining' => max(0, $burstLimit - RateLimiter::attempts($burstKey))
        ]);
        
        // Track API usage statistics
        $this->trackApiUsage($user, $endpoint, $request);
        
        return $response;
    }
    
    /**
     * Detect API endpoint from request
     */
    private function detectEndpoint(Request $request): string
    {
        $path = $request->path();
        $method = $request->method();
        
        // Remove 'api/' prefix if present
        $path = preg_replace('/^api\//', '', $path);
        
        // Map common patterns to endpoint names
        $patterns = [
            // Authentication
            '/^login$/' => 'auth.login',
            '/^register$/' => 'auth.register',
            '/^logout$/' => 'auth.logout',
            '/^password\/reset$/' => 'auth.password.reset',
            '/^password\/update$/' => 'auth.password.update',
            
            // Users
            '/^users\/create$/' => 'users.create',
            '/^users\/\d+$/' => $method === 'PUT' ? 'users.update' : ($method === 'DELETE' ? 'users.delete' : 'users.show'),
            '/^users\/bulk/' => 'users.bulk',
            '/^users$/' => 'users.index',
            
            // Attendance
            '/^attendance\/mark$/' => 'attendance.mark',
            '/^attendance\/bulk/' => 'attendance.bulk',
            '/^attendance/' => 'attendance.general',
            
            // Grades
            '/^grades\/submit$/' => 'grades.submit',
            '/^grades\/bulk/' => 'grades.bulk',
            '/^grades/' => 'grades.general',
            
            // Reports
            '/^reports\/generate/' => 'reports.generate',
            '/^reports\/export/' => 'reports.export',
            '/^reports\/download/' => 'reports.download',
            '/^reports/' => 'reports.general',
            
            // Files
            '/^files\/upload$/' => 'files.upload',
            '/^files\/download/' => 'files.download',
            '/^files\/\d+$/' => $method === 'DELETE' ? 'files.delete' : 'files.show',
            '/^files/' => 'files.general',
            
            // Search
            '/^search\/students/' => 'search.students',
            '/^search\/teachers/' => 'search.teachers',
            '/^search/' => 'search.general',
            
            // Notifications
            '/^notifications\/send$/' => 'notifications.send',
            '/^notifications\/bulk/' => 'notifications.bulk',
            '/^notifications/' => 'notifications.general',
            
            // System
            '/^system\/backup/' => 'system.backup',
            '/^system\/maintenance/' => 'system.maintenance',
            '/^system\/logs/' => 'system.logs',
            '/^system/' => 'system.general'
        ];
        
        foreach ($patterns as $pattern => $endpoint) {
            if (preg_match($pattern, $path)) {
                return $endpoint;
            }
        }
        
        return 'api.general';
    }
    
    /**
     * Get role-based rate limit
     */
    private function getRoleLimit(string $userRole): int
    {
        return self::ROLE_LIMITS[$userRole] ?? self::ROLE_LIMITS['guest'];
    }
    
    /**
     * Get endpoint-specific rate limit
     */
    private function getEndpointLimit(string $endpoint, string $userRole): int
    {
        $baseLimit = self::ENDPOINT_LIMITS[$endpoint] ?? 50; // Default limit
        
        // Apply role multiplier for certain endpoints
        $roleMultipliers = [
            'super_admin' => 2.0,
            'admin' => 1.5,
            'principal' => 1.3,
            'vice_principal' => 1.3,
            'teacher' => 1.0,
            'student' => 0.8,
            'guest' => 0.5
        ];
        
        $multiplier = $roleMultipliers[$userRole] ?? 1.0;
        return max(1, intval($baseLimit * $multiplier));
    }
    
    /**
     * Get global endpoint limit to prevent system overload
     */
    private function getGlobalEndpointLimit(string $endpoint): int
    {
        $baseLimit = self::ENDPOINT_LIMITS[$endpoint] ?? 50;
        return $baseLimit * 100; // Allow 100x the individual limit globally
    }
    
    /**
     * Get burst detection limit
     */
    private function getBurstLimit(string $userRole): int
    {
        $burstLimits = [
            'super_admin' => 50,
            'admin' => 30,
            'principal' => 25,
            'vice_principal' => 25,
            'teacher' => 20,
            'student' => 15,
            'guest' => 10
        ];
        
        return $burstLimits[$userRole] ?? 10;
    }
    
    /**
     * Get critical endpoint limit
     */
    private function getCriticalEndpointLimit(string $endpoint, string $userRole): int
    {
        $criticalLimits = [
            'auth.login' => ['super_admin' => 20, 'admin' => 15, 'default' => 5],
            'auth.register' => ['super_admin' => 10, 'admin' => 8, 'default' => 3],
            'users.create' => ['super_admin' => 50, 'admin' => 30, 'default' => 10],
            'users.delete' => ['super_admin' => 20, 'admin' => 15, 'default' => 5],
            'system.backup' => ['super_admin' => 5, 'admin' => 2, 'default' => 1],
            'system.maintenance' => ['super_admin' => 10, 'admin' => 5, 'default' => 2]
        ];
        
        $limits = $criticalLimits[$endpoint] ?? ['default' => 5];
        return $limits[$userRole] ?? $limits['default'];
    }
    
    /**
     * Generate standard rate limit response
     */
    private function rateLimitResponse(Request $request, string $type, int $retryAfter, int $limit)
    {
        return response()->json([
            'error' => 'API Rate Limit Exceeded',
            'message' => "Too many API requests for {$type}. Limit: {$limit} requests per minute.",
            'code' => 'API_RATE_LIMIT_EXCEEDED',
            'type' => $type,
            'limit' => $limit,
            'retry_after' => $retryAfter,
            'timestamp' => now()->toISOString()
        ], 429)->header('Retry-After', $retryAfter);
    }
    
    /**
     * Generate endpoint overload response
     */
    private function endpointOverloadResponse(Request $request, string $endpoint)
    {
        return response()->json([
            'error' => 'Endpoint Overloaded',
            'message' => "The {$endpoint} endpoint is currently experiencing high traffic. Please try again later.",
            'code' => 'ENDPOINT_OVERLOADED',
            'endpoint' => $endpoint,
            'retry_after' => 300,
            'timestamp' => now()->toISOString()
        ], 503)->header('Retry-After', 300);
    }
    
    /**
     * Generate suspicious activity response
     */
    private function suspiciousActivityResponse(Request $request)
    {
        return response()->json([
            'error' => 'Suspicious Activity Detected',
            'message' => 'Unusual API usage pattern detected. Access temporarily restricted.',
            'code' => 'SUSPICIOUS_API_ACTIVITY',
            'retry_after' => 600,
            'timestamp' => now()->toISOString()
        ], 429)->header('Retry-After', 600);
    }
    
    /**
     * Generate critical endpoint response
     */
    private function criticalEndpointResponse(Request $request, string $endpoint)
    {
        return response()->json([
            'error' => 'Critical Endpoint Rate Limit',
            'message' => "Rate limit exceeded for critical endpoint: {$endpoint}. Enhanced security restrictions applied.",
            'code' => 'CRITICAL_ENDPOINT_RATE_LIMIT',
            'endpoint' => $endpoint,
            'retry_after' => 3600,
            'timestamp' => now()->toISOString()
        ], 429)->header('Retry-After', 3600);
    }
    
    /**
     * Log rate limit exceeded events
     */
    private function logRateLimitExceeded(string $type, string $key, Request $request, string $endpoint)
    {
        Log::warning('API rate limit exceeded', [
            'limit_type' => $type,
            'key' => $key,
            'endpoint' => $endpoint,
            'ip' => $request->ip(),
            'user_id' => Auth::id(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'timestamp' => now()->toISOString()
        ]);
    }
    
    /**
     * Log suspicious activity
     */
    private function logSuspiciousActivity(Request $request, string $endpoint)
    {
        Log::alert('Suspicious API activity detected', [
            'endpoint' => $endpoint,
            'ip' => $request->ip(),
            'user_id' => Auth::id(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'timestamp' => now()->toISOString(),
            'severity' => 'high'
        ]);
    }
    
    /**
     * Log critical endpoint exceeded
     */
    private function logCriticalEndpointExceeded(string $key, Request $request, string $endpoint)
    {
        Log::critical('Critical API endpoint rate limit exceeded', [
            'key' => $key,
            'endpoint' => $endpoint,
            'ip' => $request->ip(),
            'user_id' => Auth::id(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'timestamp' => now()->toISOString(),
            'severity' => 'critical'
        ]);
    }
    
    /**
     * Track API usage statistics
     */
    private function trackApiUsage($user, string $endpoint, Request $request)
    {
        $key = 'api_stats:' . now()->format('Y-m-d-H');
        $stats = Cache::get($key, []);
        
        $stats['total'] = ($stats['total'] ?? 0) + 1;
        $stats['endpoints'][$endpoint] = ($stats['endpoints'][$endpoint] ?? 0) + 1;
        
        if ($user) {
            $stats['users'][$user->id] = ($stats['users'][$user->id] ?? 0) + 1;
            $stats['roles'][$user->role] = ($stats['roles'][$user->role] ?? 0) + 1;
        } else {
            $stats['anonymous'] = ($stats['anonymous'] ?? 0) + 1;
        }
        
        Cache::put($key, $stats, 7200); // Store for 2 hours
    }
}