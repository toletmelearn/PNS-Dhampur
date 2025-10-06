<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Carbon\Carbon;

class LoginRateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();
        $email = $request->input('email', '');
        
        // Create unique keys for different rate limiting scenarios
        $ipKey = 'login_attempts_ip:' . $ip;
        $emailKey = 'login_attempts_email:' . $email;
        $globalKey = 'login_attempts_global';
        
        // Check for IP-based rate limiting (5 attempts per 15 minutes)
        if (RateLimiter::tooManyAttempts($ipKey, 5)) {
            $this->logRateLimitExceeded('ip', $ip, $request);
            return $this->rateLimitResponse($request, 'IP address', RateLimiter::availableIn($ipKey));
        }
        
        // Check for email-based rate limiting (3 attempts per 10 minutes)
        if ($email && RateLimiter::tooManyAttempts($emailKey, 3)) {
            $this->logRateLimitExceeded('email', $email, $request);
            return $this->rateLimitResponse($request, 'email address', RateLimiter::availableIn($emailKey));
        }
        
        // Check for global rate limiting (100 attempts per minute across all IPs)
        if (RateLimiter::tooManyAttempts($globalKey, 100)) {
            $this->logRateLimitExceeded('global', 'system', $request);
            return $this->rateLimitResponse($request, 'system', RateLimiter::availableIn($globalKey));
        }
        
        // Check for suspicious patterns (rapid requests from same IP)
        $rapidKey = 'rapid_login:' . $ip;
        if (RateLimiter::tooManyAttempts($rapidKey, 10)) {
            $this->logSuspiciousActivity($ip, $request);
            return $this->suspiciousActivityResponse($request);
        }
        
        // Increment counters
        RateLimiter::hit($ipKey, 900); // 15 minutes
        if ($email) {
            RateLimiter::hit($emailKey, 600); // 10 minutes
        }
        RateLimiter::hit($globalKey, 60); // 1 minute
        RateLimiter::hit($rapidKey, 30); // 30 seconds for rapid detection
        
        $response = $next($request);
        
        // If login was successful, clear the rate limiting for this IP/email
        if ($response->getStatusCode() === 200 || $response->isRedirection()) {
            $this->clearSuccessfulLoginAttempts($ip, $email);
        }
        
        return $response;
    }
    
    /**
     * Generate rate limit response
     */
    private function rateLimitResponse(Request $request, string $type, int $retryAfter)
    {
        $message = "Too many login attempts from this {$type}. Please try again in " . 
                  $this->formatRetryAfter($retryAfter) . ".";
        
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Rate Limit Exceeded',
                'message' => $message,
                'code' => 'LOGIN_RATE_LIMIT_EXCEEDED',
                'retry_after' => $retryAfter,
                'type' => $type,
                'timestamp' => now()->toISOString()
            ], 429);
        }
        
        return redirect()->back()
            ->withErrors(['email' => $message])
            ->withInput($request->except('password'));
    }
    
    /**
     * Generate suspicious activity response
     */
    private function suspiciousActivityResponse(Request $request)
    {
        $message = "Suspicious login activity detected. Please wait before trying again.";
        
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Suspicious Activity',
                'message' => $message,
                'code' => 'SUSPICIOUS_LOGIN_ACTIVITY',
                'retry_after' => 300, // 5 minutes
                'timestamp' => now()->toISOString()
            ], 429);
        }
        
        return redirect()->back()
            ->withErrors(['email' => $message])
            ->withInput($request->except('password'));
    }
    
    /**
     * Log rate limit exceeded events
     */
    private function logRateLimitExceeded(string $type, string $identifier, Request $request)
    {
        Log::warning('Login rate limit exceeded', [
            'type' => $type,
            'identifier' => $identifier,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'email_attempted' => $request->input('email'),
            'timestamp' => now()->toISOString(),
            'headers' => $request->headers->all()
        ]);
    }
    
    /**
     * Log suspicious activity
     */
    private function logSuspiciousActivity(string $ip, Request $request)
    {
        Log::alert('Suspicious login activity detected', [
            'ip' => $ip,
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'email_attempted' => $request->input('email'),
            'timestamp' => now()->toISOString(),
            'headers' => $request->headers->all(),
            'severity' => 'high'
        ]);
    }
    
    /**
     * Clear rate limiting after successful login
     */
    private function clearSuccessfulLoginAttempts(string $ip, ?string $email)
    {
        RateLimiter::clear('login_attempts_ip:' . $ip);
        if ($email) {
            RateLimiter::clear('login_attempts_email:' . $email);
        }
        RateLimiter::clear('rapid_login:' . $ip);
        
        Log::info('Login rate limits cleared after successful authentication', [
            'ip' => $ip,
            'email' => $email,
            'timestamp' => now()->toISOString()
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