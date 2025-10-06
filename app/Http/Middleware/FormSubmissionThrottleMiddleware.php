<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class FormSubmissionThrottleMiddleware
{
    /**
     * Form submission limits per user role (requests per minute)
     */
    private const ROLE_LIMITS = [
        'super_admin' => 30,
        'admin' => 25,
        'principal' => 20,
        'vice_principal' => 20,
        'teacher' => 15,
        'student' => 10,
        'guest' => 5
    ];
    
    /**
     * Critical forms that need stricter limits
     */
    private const CRITICAL_FORMS = [
        'password-reset',
        'user-creation',
        'bulk-import',
        'financial-transaction',
        'grade-submission',
        'attendance-bulk'
    ];
    
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $formType
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ?string $formType = null)
    {
        // Only apply to POST, PUT, PATCH, DELETE requests
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $next($request);
        }
        
        $user = Auth::user();
        $ip = $request->ip();
        $userRole = $user ? $user->role : 'guest';
        
        // Determine form type from parameter or URL
        $formType = $formType ?? $this->detectFormType($request);
        
        // Get rate limit based on user role and form type
        $limit = $this->getRateLimit($userRole, $formType);
        $window = $this->getRateLimitWindow($formType);
        
        // Create rate limiting keys
        $userKey = $user ? "form_submit_user:{$user->id}:{$formType}" : "form_submit_ip:{$ip}:{$formType}";
        $globalKey = "form_submit_global:{$formType}";
        
        // Check user/IP specific rate limit
        if (RateLimiter::tooManyAttempts($userKey, $limit)) {
            $this->logRateLimitExceeded('user', $userKey, $request, $formType);
            return $this->rateLimitResponse($request, 'user', RateLimiter::availableIn($userKey));
        }
        
        // Check global rate limit for critical forms
        if (in_array($formType, self::CRITICAL_FORMS)) {
            $globalLimit = $limit * 10; // Allow 10x the individual limit globally
            if (RateLimiter::tooManyAttempts($globalKey, $globalLimit)) {
                $this->logRateLimitExceeded('global', $globalKey, $request, $formType);
                return $this->rateLimitResponse($request, 'system', RateLimiter::availableIn($globalKey));
            }
        }
        
        // Check for rapid successive submissions (potential bot)
        $rapidKey = $user ? "rapid_submit_user:{$user->id}" : "rapid_submit_ip:{$ip}";
        if (RateLimiter::tooManyAttempts($rapidKey, 5)) { // 5 submissions in 10 seconds
            $this->logSuspiciousActivity($request, $formType);
            return $this->suspiciousActivityResponse($request);
        }
        
        // Increment counters
        RateLimiter::hit($userKey, $window);
        if (in_array($formType, self::CRITICAL_FORMS)) {
            RateLimiter::hit($globalKey, $window);
        }
        RateLimiter::hit($rapidKey, 10); // 10 seconds for rapid detection
        
        $response = $next($request);
        
        // Add rate limit headers
        $response->headers->add([
            'X-RateLimit-Limit' => $limit,
            'X-RateLimit-Remaining' => max(0, $limit - RateLimiter::attempts($userKey)),
            'X-RateLimit-Reset' => now()->addSeconds(RateLimiter::availableIn($userKey))->timestamp,
            'X-Form-Type' => $formType
        ]);
        
        return $response;
    }
    
    /**
     * Detect form type from request
     */
    private function detectFormType(Request $request): string
    {
        $path = $request->path();
        $action = $request->input('_action', '');
        
        // Map common patterns to form types
        $patterns = [
            '/password.*reset/' => 'password-reset',
            '/user.*create|create.*user/' => 'user-creation',
            '/bulk.*import|import.*bulk/' => 'bulk-import',
            '/payment|transaction|financial/' => 'financial-transaction',
            '/grade|result|exam/' => 'grade-submission',
            '/attendance.*bulk|bulk.*attendance/' => 'attendance-bulk',
            '/login/' => 'login',
            '/register/' => 'registration',
            '/contact|message/' => 'contact-form',
            '/upload/' => 'file-upload'
        ];
        
        foreach ($patterns as $pattern => $type) {
            if (preg_match($pattern, $path) || preg_match($pattern, $action)) {
                return $type;
            }
        }
        
        return 'general';
    }
    
    /**
     * Get rate limit based on user role and form type
     */
    private function getRateLimit(string $userRole, string $formType): int
    {
        $baseLimit = self::ROLE_LIMITS[$userRole] ?? self::ROLE_LIMITS['guest'];
        
        // Apply stricter limits for critical forms
        if (in_array($formType, self::CRITICAL_FORMS)) {
            return max(1, intval($baseLimit * 0.3)); // 30% of normal limit
        }
        
        // Apply different limits for specific form types
        $formMultipliers = [
            'login' => 0.2, // Very strict for login
            'password-reset' => 0.1, // Extremely strict
            'contact-form' => 0.5,
            'file-upload' => 0.4,
            'general' => 1.0
        ];
        
        $multiplier = $formMultipliers[$formType] ?? 1.0;
        return max(1, intval($baseLimit * $multiplier));
    }
    
    /**
     * Get rate limit window in seconds
     */
    private function getRateLimitWindow(string $formType): int
    {
        $windows = [
            'password-reset' => 3600, // 1 hour
            'user-creation' => 1800, // 30 minutes
            'bulk-import' => 1800, // 30 minutes
            'financial-transaction' => 900, // 15 minutes
            'login' => 900, // 15 minutes
            'general' => 60 // 1 minute
        ];
        
        return $windows[$formType] ?? 60;
    }
    
    /**
     * Generate rate limit response
     */
    private function rateLimitResponse(Request $request, string $type, int $retryAfter)
    {
        $message = "Too many form submissions from this {$type}. Please wait " . 
                  $this->formatRetryAfter($retryAfter) . " before submitting again.";
        
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Form Submission Rate Limit Exceeded',
                'message' => $message,
                'code' => 'FORM_RATE_LIMIT_EXCEEDED',
                'retry_after' => $retryAfter,
                'type' => $type,
                'timestamp' => now()->toISOString()
            ], 429);
        }
        
        return redirect()->back()
            ->withErrors(['form' => $message])
            ->withInput($request->except(['password', '_token']));
    }
    
    /**
     * Generate suspicious activity response
     */
    private function suspiciousActivityResponse(Request $request)
    {
        $message = "Suspicious form submission activity detected. Please wait before trying again.";
        
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Suspicious Activity',
                'message' => $message,
                'code' => 'SUSPICIOUS_FORM_ACTIVITY',
                'retry_after' => 300,
                'timestamp' => now()->toISOString()
            ], 429);
        }
        
        return redirect()->back()
            ->withErrors(['form' => $message])
            ->withInput($request->except(['password', '_token']));
    }
    
    /**
     * Log rate limit exceeded events
     */
    private function logRateLimitExceeded(string $type, string $key, Request $request, string $formType)
    {
        Log::warning('Form submission rate limit exceeded', [
            'type' => $type,
            'key' => $key,
            'form_type' => $formType,
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
    private function logSuspiciousActivity(Request $request, string $formType)
    {
        Log::alert('Suspicious form submission activity detected', [
            'form_type' => $formType,
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
     * Format retry after time in human readable format
     */
    private function formatRetryAfter(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . ' seconds';
        } elseif ($seconds < 3600) {
            return ceil($seconds / 60) . ' minutes';
        } else {
            return ceil($seconds / 3600) . ' hours';
        }
    }
}