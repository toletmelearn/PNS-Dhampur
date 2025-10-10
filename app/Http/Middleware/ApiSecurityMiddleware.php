<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use App\Services\ComprehensiveErrorHandlingService;
use Carbon\Carbon;

class ApiSecurityMiddleware
{
    protected $errorHandlingService;

    /**
     * Get the error handling service instance (lazy loading)
     */
    protected function getErrorHandlingService()
    {
        if (!$this->errorHandlingService) {
            $this->errorHandlingService = app(ComprehensiveErrorHandlingService::class);
        }
        return $this->errorHandlingService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // 1. Validate API request format and headers
        if (!$this->validateApiRequest($request)) {
            return $this->unauthorizedResponse('Invalid API request format');
        }

        // 2. Check for suspicious patterns
        if ($this->detectSuspiciousActivity($request)) {
            $this->logSecurityEvent('suspicious_api_activity', $request);
            return $this->unauthorizedResponse('Suspicious activity detected');
        }

        // 3. Validate token format and integrity
        if ($request->bearerToken() && !$this->validateTokenFormat($request->bearerToken())) {
            $this->logSecurityEvent('invalid_token_format', $request);
            return $this->unauthorizedResponse('Invalid token format');
        }

        // 4. Check for token reuse/replay attacks
        if ($this->detectTokenReuse($request)) {
            $this->logSecurityEvent('token_reuse_detected', $request);
            return $this->unauthorizedResponse('Token reuse detected');
        }

        // 5. Implement API-specific rate limiting
        if (!$this->checkApiRateLimit($request)) {
            $this->logSecurityEvent('api_rate_limit_exceeded', $request);
            return $this->rateLimitResponse($request);
        }

        // 6. Validate request size and content
        if (!$this->validateRequestSize($request)) {
            $this->logSecurityEvent('oversized_request', $request);
            return $this->unauthorizedResponse('Request too large');
        }

        // 7. Check for SQL injection patterns in API parameters
        if ($this->detectSqlInjection($request)) {
            $this->logSecurityEvent('sql_injection_attempt', $request);
            return $this->unauthorizedResponse('Invalid request parameters');
        }

        // 8. Add security headers to response
        $response = $next($request);
        return $this->addSecurityHeaders($response, $request);
    }

    /**
     * Validate API request format and required headers
     */
    protected function validateApiRequest(Request $request): bool
    {
        // Check for required headers
        $requiredHeaders = ['Accept', 'User-Agent'];
        foreach ($requiredHeaders as $header) {
            if (!$request->hasHeader($header)) {
                return false;
            }
        }

        // Validate Accept header for API requests
        $acceptHeader = $request->header('Accept');
        if (!str_contains($acceptHeader, 'application/json') && !str_contains($acceptHeader, '*/*')) {
            return false;
        }

        // Check User-Agent for suspicious patterns
        $userAgent = $request->header('User-Agent');
        $suspiciousPatterns = ['bot', 'crawler', 'scanner', 'curl', 'wget'];
        foreach ($suspiciousPatterns as $pattern) {
            if (stripos($userAgent, $pattern) !== false && !$this->isAllowedBot($userAgent)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the bot/crawler is allowed
     */
    protected function isAllowedBot(string $userAgent): bool
    {
        $allowedBots = [
            'Googlebot',
            'Bingbot',
            'Slackbot',
            'facebookexternalhit',
            'Twitterbot'
        ];

        foreach ($allowedBots as $bot) {
            if (stripos($userAgent, $bot) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect suspicious activity patterns
     */
    protected function detectSuspiciousActivity(Request $request): bool
    {
        $ip = $request->ip();
        $userAgent = $request->header('User-Agent');

        // Check for rapid requests from same IP
        $requestCount = Cache::get("api_requests_{$ip}", 0);
        if ($requestCount > 100) { // More than 100 requests per minute
            return true;
        }

        // Check for suspicious user agents
        $suspiciousAgents = [
            'sqlmap',
            'nikto',
            'nmap',
            'masscan',
            'burp',
            'owasp',
            'hack',
            'exploit'
        ];

        foreach ($suspiciousAgents as $agent) {
            if (stripos($userAgent, $agent) !== false) {
                return true;
            }
        }

        // Check for suspicious request patterns
        $path = $request->path();
        $suspiciousPaths = [
            'admin',
            'phpmyadmin',
            'wp-admin',
            'config',
            'backup',
            '.env',
            'database'
        ];

        foreach ($suspiciousPaths as $suspiciousPath) {
            if (stripos($path, $suspiciousPath) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate token format
     */
    protected function validateTokenFormat(string $token): bool
    {
        // Sanctum tokens should be at least 40 characters
        if (strlen($token) < 40) {
            return false;
        }

        // Check for valid characters (alphanumeric and some special chars)
        if (!preg_match('/^[a-zA-Z0-9|_\-\.]+$/', $token)) {
            return false;
        }

        return true;
    }

    /**
     * Detect token reuse/replay attacks
     */
    protected function detectTokenReuse(Request $request): bool
    {
        $token = $request->bearerToken();
        if (!$token) {
            return false;
        }

        $tokenHash = hash('sha256', $token);
        $cacheKey = "token_usage_{$tokenHash}";
        
        // Get last usage info
        $lastUsage = Cache::get($cacheKey);
        
        if ($lastUsage) {
            $timeDiff = now()->diffInSeconds($lastUsage['timestamp']);
            
            // If same token used from different IP within 5 seconds
            if ($timeDiff < 5 && $lastUsage['ip'] !== $request->ip()) {
                return true;
            }
            
            // If same token used more than 10 times per minute
            if ($lastUsage['count'] > 10 && $timeDiff < 60) {
                return true;
            }
        }

        // Update usage tracking
        Cache::put($cacheKey, [
            'ip' => $request->ip(),
            'timestamp' => now(),
            'count' => ($lastUsage['count'] ?? 0) + 1
        ], 300); // 5 minutes

        return false;
    }

    /**
     * Check API-specific rate limiting
     */
    protected function checkApiRateLimit(Request $request): bool
    {
        $key = 'api_rate_limit:' . $request->ip();
        
        // Different limits based on authentication
        $limit = $request->user() ? 120 : 60; // Authenticated users get higher limit
        
        return RateLimiter::attempt($key, $limit, function() {
            // This callback is executed if the rate limit is not exceeded
        }, 60); // 60 seconds window
    }

    /**
     * Validate request size
     */
    protected function validateRequestSize(Request $request): bool
    {
        $maxSize = 10 * 1024 * 1024; // 10MB
        
        if ($request->header('Content-Length') > $maxSize) {
            return false;
        }

        // Check JSON payload size
        $content = $request->getContent();
        if (strlen($content) > $maxSize) {
            return false;
        }

        return true;
    }

    /**
     * Detect SQL injection patterns
     */
    protected function detectSqlInjection(Request $request): bool
    {
        $sqlPatterns = [
            '/(\bUNION\b.*\bSELECT\b)/i',
            '/(\bSELECT\b.*\bFROM\b.*\bWHERE\b)/i',
            '/(\bINSERT\b.*\bINTO\b)/i',
            '/(\bUPDATE\b.*\bSET\b)/i',
            '/(\bDELETE\b.*\bFROM\b)/i',
            '/(\bDROP\b.*\bTABLE\b)/i',
            '/(\bALTER\b.*\bTABLE\b)/i',
            '/(\'.*OR.*\'.*=.*\')/i',
            '/(\".*OR.*\".*=.*\")/i',
            '/(\bOR\b.*\b1\b.*=.*\b1\b)/i',
            '/(\bAND\b.*\b1\b.*=.*\b1\b)/i'
        ];

        // Check all request parameters
        $allInput = array_merge(
            $request->query(),
            $request->all(),
            [$request->getContent()]
        );

        foreach ($allInput as $value) {
            if (is_string($value)) {
                foreach ($sqlPatterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Add security headers to response
     */
    protected function addSecurityHeaders($response, Request $request)
    {
        $response->headers->set('X-API-Version', '1.0');
        $response->headers->set('X-Request-ID', $request->header('X-Request-ID', uniqid()));
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        
        // Add rate limit headers
        $ip = $request->ip();
        $remaining = RateLimiter::remaining('api_rate_limit:' . $ip, 60);
        $response->headers->set('X-RateLimit-Limit', '60');
        $response->headers->set('X-RateLimit-Remaining', $remaining);
        $response->headers->set('X-RateLimit-Reset', now()->addMinute()->timestamp);

        return $response;
    }

    /**
     * Return unauthorized response
     */
    protected function unauthorizedResponse(string $message)
    {
        return response()->json([
            'error' => 'Unauthorized',
            'message' => $message,
            'timestamp' => now()->toISOString()
        ], 401);
    }

    /**
     * Return rate limit response
     */
    protected function rateLimitResponse(Request $request)
    {
        $retryAfter = RateLimiter::availableIn('api_rate_limit:' . $request->ip());
        
        return response()->json([
            'error' => 'Too Many Requests',
            'message' => 'Rate limit exceeded. Please try again later.',
            'retry_after' => $retryAfter,
            'timestamp' => now()->toISOString()
        ], 429)->header('Retry-After', $retryAfter);
    }

    /**
     * Log security events
     */
    protected function logSecurityEvent(string $eventType, Request $request): void
    {
        $context = [
            'event_type' => $eventType,
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'path' => $request->path(),
            'method' => $request->method(),
            'headers' => $request->headers->all(),
            'timestamp' => now()->toISOString()
        ];

        Log::channel('security')->warning("API Security Event: {$eventType}", $context);

        // Use comprehensive error handling service for critical events
        $criticalEvents = [
            'sql_injection_attempt',
            'token_reuse_detected',
            'suspicious_api_activity'
        ];

        if (in_array($eventType, $criticalEvents)) {
            $this->getErrorHandlingService()->handleSecurityEvent($eventType, $context);
        }
    }
}