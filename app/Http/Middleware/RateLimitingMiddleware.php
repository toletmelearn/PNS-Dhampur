<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitingMiddleware
{
    /**
     * Rate limiting configurations for different types of requests
     */
    protected array $rateLimits = [
        // Authentication related
        'login' => ['attempts' => 5, 'decay' => 900], // 5 attempts per 15 minutes
        'register' => ['attempts' => 3, 'decay' => 3600], // 3 attempts per hour
        'password_reset' => ['attempts' => 3, 'decay' => 3600], // 3 attempts per hour
        
        // Form submissions
        'student_create' => ['attempts' => 10, 'decay' => 3600], // 10 per hour
        'student_update' => ['attempts' => 20, 'decay' => 3600], // 20 per hour
        'teacher_create' => ['attempts' => 5, 'decay' => 3600], // 5 per hour
        'teacher_update' => ['attempts' => 15, 'decay' => 3600], // 15 per hour
        'fee_create' => ['attempts' => 50, 'decay' => 3600], // 50 per hour
        'attendance_create' => ['attempts' => 100, 'decay' => 3600], // 100 per hour
        
        // File uploads
        'file_upload' => ['attempts' => 20, 'decay' => 3600], // 20 per hour
        'bulk_upload' => ['attempts' => 5, 'decay' => 3600], // 5 per hour
        
        // API endpoints
        'api_general' => ['attempts' => 60, 'decay' => 60], // 60 per minute
        'api_search' => ['attempts' => 30, 'decay' => 60], // 30 per minute
        
        // Default fallback
        'default' => ['attempts' => 30, 'decay' => 3600] // 30 per hour
    ];

    /**
     * Routes and their corresponding rate limit types
     */
    protected array $routeMapping = [
        'login' => 'login',
        'register' => 'register',
        'password/email' => 'password_reset',
        'password/reset' => 'password_reset',
        
        'students/store' => 'student_create',
        'students/*/update' => 'student_update',
        'students/update' => 'student_update',
        
        'teachers/store' => 'teacher_create',
        'teachers/*/update' => 'teacher_update',
        'teachers/update' => 'teacher_update',
        
        'fees/store' => 'fee_create',
        'fees/*/update' => 'fee_create',
        
        'attendance/store' => 'attendance_create',
        'attendance/bulk' => 'attendance_create',
        
        'upload' => 'file_upload',
        'import' => 'bulk_upload',
        
        'api/' => 'api_general',
        'api/search' => 'api_search'
    ];

    /**
     * Suspicious activity thresholds
     */
    protected array $suspiciousThresholds = [
        'rapid_requests' => 10, // 10 requests in 10 seconds
        'failed_attempts' => 20, // 20 failed attempts in 1 hour
        'different_ips' => 5 // Same user from 5 different IPs in 1 hour
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Determine rate limit type for this request
        $limitType = $this->determineLimitType($request);
        
        // Apply rate limiting
        $this->applyRateLimit($request, $limitType);
        
        // Check for suspicious activity
        $this->checkSuspiciousActivity($request);
        
        $response = $next($request);
        
        // Log successful request for monitoring
        $this->logRequestActivity($request, $limitType, true);
        
        return $response;
    }

    /**
     * Determine the appropriate rate limit type for the request
     */
    protected function determineLimitType(Request $request): string
    {
        $path = $request->path();
        $method = $request->method();

        // Skip rate limiting for static asset requests
        if ($this->isStaticAssetRequest($request)) {
            return 'none';
        }

        // Check for exact matches first
        foreach ($this->routeMapping as $route => $limitType) {
            if ($this->matchesRoute($path, $route)) {
                // Throttle auth endpoints only on POST to avoid 429 on GET pages
                if (in_array($limitType, ['login', 'register', 'password_reset']) && $method !== 'POST') {
                    // Skip special auth rate limit for non-POST methods
                    continue;
                }
                return $limitType;
            }
        }

        // Special handling for different HTTP methods
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return 'default';
        }

        // API requests
        if (str_starts_with($path, 'api/')) {
            return 'api_general';
        }

        // For normal GET web page requests (non-AJAX, non-API), disable rate limiting
        if ($method === 'GET' && !$request->expectsJson() && strtolower($request->header('X-Requested-With', '')) !== 'xmlhttprequest' && !str_starts_with($path, 'api/')) {
            return 'none';
        }

        return 'default';
    }

    /**
     * Check if path matches route pattern
     */
    protected function matchesRoute(string $path, string $route): bool
    {
        // Handle wildcard patterns
        if (str_contains($route, '*')) {
            $pattern = str_replace('*', '[^/]+', $route);
            return preg_match('#^' . $pattern . '$#', $path);
        }

        // Handle prefix matching
        if (str_ends_with($route, '/')) {
            return str_starts_with($path, $route);
        }

        // Exact match
        return $path === $route;
    }

    /**
     * Apply rate limiting to the request
     */
    protected function applyRateLimit(Request $request, string $limitType): void
    {
        // No-op for requests explicitly marked as not rate limited
        if ($limitType === 'none') {
            return;
        }

        $config = $this->rateLimits[$limitType] ?? $this->rateLimits['default'];
        
        // Create rate limiter key
        $key = $this->getRateLimiterKey($request, $limitType);
        
        // Check if rate limit is exceeded
        if (RateLimiter::tooManyAttempts($key, $config['attempts'])) {
            $this->handleRateLimitExceeded($request, $limitType, $key);
        }

        // Increment attempt counter
        RateLimiter::hit($key, $config['decay']);
    }

    /**
     * Generate rate limiter key
     */
    protected function getRateLimiterKey(Request $request, string $limitType): string
    {
        $ip = $request->ip();
        $userId = auth()->id() ?? 'guest';
        $userAgent = md5($request->userAgent() ?? '');
        
        // For authentication attempts, use IP + user agent
        if (in_array($limitType, ['login', 'register', 'password_reset'])) {
            return "rate_limit:{$limitType}:{$ip}:{$userAgent}";
        }
        
        // For authenticated users, use user ID + IP
        if (auth()->check()) {
            return "rate_limit:{$limitType}:{$userId}:{$ip}";
        }
        
        // For guests, use IP + user agent
        return "rate_limit:{$limitType}:{$ip}:{$userAgent}";
    }

    /**
     * Handle rate limit exceeded
     */
    protected function handleRateLimitExceeded(Request $request, string $limitType, string $key): void
    {
        $config = $this->rateLimits[$limitType] ?? $this->rateLimits['default'];
        $retryAfter = RateLimiter::availableIn($key);
        
        // Log rate limit violation
        $this->logRateLimitViolation($request, $limitType, $retryAfter);
        
        // Check if this is a severe violation (multiple rate limit types exceeded)
        $this->checkSevereViolation($request);
        
        // Return rate limit response
        abort(429, 'Too many requests. Please try again in ' . $this->formatRetryAfter($retryAfter) . '.', [
            'Retry-After' => $retryAfter,
            'X-RateLimit-Limit' => $config['attempts'],
            'X-RateLimit-Remaining' => 0,
            'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->timestamp
        ]);
    }

    /**
     * Check for suspicious activity patterns
     */
    protected function checkSuspiciousActivity(Request $request): void
    {
        // Skip monitoring for static assets and normal GET page loads
        if ($this->shouldSkipMonitoring($request)) {
            return;
        }

        $ip = $request->ip();
        $userId = auth()->id();
        
        // Check for rapid requests from same IP
        $this->checkRapidRequests($ip, $request);
        
        // Check for failed attempts pattern
        $this->checkFailedAttempts($ip, $request);
        
        // Check for same user from multiple IPs (if authenticated)
        if ($userId) {
            $this->checkMultipleIPs($userId, $ip, $request);
        }
    }

    /**
     * Determine if monitoring (rapid/suspicious checks) should be skipped.
     * Mirrors the same logic used to skip rate limiting for safe requests.
     */
    protected function shouldSkipMonitoring(Request $request): bool
    {
        if ($this->isStaticAssetRequest($request)) {
            return true;
        }

        $path = $request->path();
        $method = $request->method();

        // For normal GET web page requests (non-AJAX, non-API), skip monitoring
        if ($method === 'GET'
            && !$request->expectsJson()
            && strtolower($request->header('X-Requested-With', '')) !== 'xmlhttprequest'
            && !str_starts_with($path, 'api/')
        ) {
            return true;
        }

        return false;
    }

    /**
     * Check for rapid requests pattern
     */
    protected function checkRapidRequests(string $ip, Request $request): void
    {
        // Do not count static assets toward rapid requests to avoid false positives
        if ($this->isStaticAssetRequest($request)) {
            return;
        }

        $key = "rapid_requests:{$ip}";
        $count = Cache::get($key, 0);
        
        if ($count >= $this->suspiciousThresholds['rapid_requests']) {
            $this->logSuspiciousActivity($request, 'rapid_requests', [
                'count' => $count,
                'threshold' => $this->suspiciousThresholds['rapid_requests']
            ]);
            
            // Temporarily block IP for severe rapid requests
            if ($count >= $this->suspiciousThresholds['rapid_requests'] * 2) {
                $this->temporaryBlock($ip, 'rapid_requests');
            }
        }
        
        Cache::put($key, $count + 1, 10); // 10 seconds window
    }

    /**
     * Check for failed attempts pattern
     */
    protected function checkFailedAttempts(string $ip, Request $request): void
    {
        $key = "failed_attempts:{$ip}";
        $count = Cache::get($key, 0);
        
        if ($count >= $this->suspiciousThresholds['failed_attempts']) {
            $this->logSuspiciousActivity($request, 'excessive_failed_attempts', [
                'count' => $count,
                'threshold' => $this->suspiciousThresholds['failed_attempts']
            ]);
        }
    }

    /**
     * Check for same user from multiple IPs
     */
    protected function checkMultipleIPs(int $userId, string $ip, Request $request): void
    {
        $key = "user_ips:{$userId}";
        $ips = Cache::get($key, []);
        
        if (!in_array($ip, $ips)) {
            $ips[] = $ip;
            Cache::put($key, $ips, 3600); // 1 hour
            
            if (count($ips) >= $this->suspiciousThresholds['different_ips']) {
                $this->logSuspiciousActivity($request, 'multiple_ips', [
                    'user_id' => $userId,
                    'ip_count' => count($ips),
                    'ips' => $ips,
                    'threshold' => $this->suspiciousThresholds['different_ips']
                ]);
            }
        }
    }

    /**
     * Check for severe violations (multiple rate limits exceeded)
     */
    protected function checkSevereViolation(Request $request): void
    {
        $ip = $request->ip();
        $key = "severe_violations:{$ip}";
        $count = Cache::get($key, 0);
        
        Cache::put($key, $count + 1, 3600); // 1 hour
        
        if ($count >= 3) { // 3 different rate limits exceeded
            $this->logSuspiciousActivity($request, 'severe_rate_limit_violation', [
                'violation_count' => $count + 1
            ]);
            
            // Temporary block for severe violations
            $this->temporaryBlock($ip, 'severe_violations');
        }
    }

    /**
     * Temporarily block an IP address
     */
    protected function temporaryBlock(string $ip, string $reason): void
    {
        $blockKey = "blocked_ip:{$ip}";
        $blockDuration = 3600; // 1 hour
        
        Cache::put($blockKey, [
            'reason' => $reason,
            'blocked_at' => now()->toISOString(),
            'expires_at' => now()->addSeconds($blockDuration)->toISOString()
        ], $blockDuration);
        
        Log::warning('IP temporarily blocked', [
            'ip' => $ip,
            'reason' => $reason,
            'duration' => $blockDuration
        ]);
        
        abort(429, 'Access temporarily restricted due to suspicious activity.');
    }

    /**
     * Log rate limit violation
     */
    protected function logRateLimitViolation(Request $request, string $limitType, int $retryAfter): void
    {
        Log::warning('Rate limit exceeded', [
            'ip' => $request->ip(),
            'user_id' => auth()->id(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'limit_type' => $limitType,
            'retry_after' => $retryAfter,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Log suspicious activity
     */
    protected function logSuspiciousActivity(Request $request, string $activityType, array $details): void
    {
        Log::warning('Suspicious activity detected', [
            'activity_type' => $activityType,
            'ip' => $request->ip(),
            'user_id' => auth()->id(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'details' => $details,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Log request activity for monitoring
     */
    protected function logRequestActivity(Request $request, string $limitType, bool $success): void
    {
        // Only log for monitoring purposes, not as warnings
        if (config('app.debug')) {
            Log::info('Request activity', [
                'ip' => $request->ip(),
                'user_id' => auth()->id(),
                'limit_type' => $limitType,
                'success' => $success,
                'timestamp' => now()->toISOString()
            ]);
        }
    }

    /**
     * Format retry after seconds into human readable format
     */
    protected function formatRetryAfter(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . ' seconds';
        } elseif ($seconds < 3600) {
            return ceil($seconds / 60) . ' minutes';
        } else {
            return ceil($seconds / 3600) . ' hours';
        }
    }

    /**
     * Configure rate limits for specific routes
     */
    public function configureRateLimit(string $route, int $attempts, int $decay): self
    {
        $this->rateLimits[$route] = ['attempts' => $attempts, 'decay' => $decay];
        return $this;
    }

    /**
     * Add route mapping
     */
    public function addRouteMapping(string $route, string $limitType): self
    {
        $this->routeMapping[$route] = $limitType;
        return $this;
    }

    /**
     * Detect if request targets static assets to bypass rate limiting
     */
    protected function isStaticAssetRequest(Request $request): bool
    {
        $path = ltrim($request->path(), '/');
        $lower = strtolower($path);

        // Common asset directories
        $assetDirs = ['assets/', 'css/', 'js/', 'images/', 'img/', 'vendor/', 'fonts/', 'storage/', 'build/', 'dist/'];
        foreach ($assetDirs as $dir) {
            if (str_starts_with($lower, $dir)) {
                return true;
            }
        }

        // File extension check
        $exts = ['.css', '.js', '.mjs', '.map', '.png', '.jpg', '.jpeg', '.gif', '.webp', '.svg', '.ico', '.bmp', '.tif', '.tiff', '.woff', '.woff2', '.ttf', '.eot'];
        foreach ($exts as $ext) {
            if (str_ends_with($lower, $ext)) {
                return true;
            }
        }

        return false;
    }
}