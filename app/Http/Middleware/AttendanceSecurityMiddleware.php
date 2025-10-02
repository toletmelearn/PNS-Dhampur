<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\Role;

class AttendanceSecurityMiddleware
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
        // Check if user is authenticated
        if (!Auth::check()) {
            $this->logSecurityEvent('unauthenticated_access', $request);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'Authentication required for attendance access'
                ], 401);
            }
            
            return redirect()->route('login')->with('error', 'Please login to access the attendance system.');
        }

        $user = Auth::user();

        // Rate limiting for attendance operations
        $rateLimitKey = 'attendance_access:' . $user->id;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 100)) { // 100 requests per minute
            $this->logSecurityEvent('rate_limit_exceeded', $request, $user);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Too Many Requests',
                    'message' => 'Rate limit exceeded. Please try again later.'
                ], 429);
            }
            
            return redirect()->back()->with('error', 'Too many requests. Please wait before trying again.');
        }

        RateLimiter::hit($rateLimitKey, 60); // 1 minute window

        // Check if user can access attendance module
        if (!$user->canAccessAttendance()) {
            $this->logSecurityEvent('attendance_access_denied', $request, $user);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'Access denied to attendance module'
                ], 403);
            }
            
            return redirect()->route('dashboard')->with('error', 'You do not have access to the attendance module.');
        }

        // Validate CSRF token for state-changing operations
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            if (!$request->hasValidSignature() && !$request->session()->token() === $request->input('_token')) {
                $this->logSecurityEvent('csrf_token_mismatch', $request, $user);
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'error' => 'Invalid Token',
                        'message' => 'CSRF token mismatch'
                    ], 419);
                }
                
                return redirect()->back()->with('error', 'Security token mismatch. Please try again.');
            }
        }

        // Check for suspicious activity patterns
        if ($this->detectSuspiciousActivity($request, $user)) {
            $this->logSecurityEvent('suspicious_activity', $request, $user);
            
            // Temporarily block user for 5 minutes
            RateLimiter::hit('suspicious_activity:' . $user->id, 300);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Suspicious Activity',
                    'message' => 'Suspicious activity detected. Access temporarily restricted.'
                ], 403);
            }
            
            return redirect()->route('dashboard')->with('error', 'Suspicious activity detected. Please contact administrator.');
        }

        // Log successful access for audit trail
        $this->logSecurityEvent('attendance_access_granted', $request, $user);

        return $next($request);
    }

    /**
     * Log security events for audit trail
     */
    private function logSecurityEvent($event, Request $request, $user = null)
    {
        $logData = [
            'event' => $event,
            'timestamp' => now()->toISOString(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'route' => $request->route() ? $request->route()->getName() : null,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'session_id' => $request->session()->getId(),
        ];

        if ($user) {
            $logData['user_id'] = $user->id;
            $logData['user_email'] = $user->email;
            $logData['user_role'] = $user->role;
        }

        // Add request data for certain events (excluding sensitive information)
        if (in_array($event, ['suspicious_activity', 'csrf_token_mismatch'])) {
            $logData['request_data'] = $this->sanitizeRequestData($request->all());
        }

        Log::channel('security')->info("Attendance Security Event: {$event}", $logData);
    }

    /**
     * Detect suspicious activity patterns
     */
    private function detectSuspiciousActivity(Request $request, $user)
    {
        // Check for rapid successive requests
        $rapidRequestKey = 'rapid_requests:' . $user->id;
        if (RateLimiter::tooManyAttempts($rapidRequestKey, 20)) { // 20 requests in 10 seconds
            return true;
        }
        RateLimiter::hit($rapidRequestKey, 10);

        // Check for unusual request patterns
        if ($this->hasUnusualRequestPattern($request)) {
            return true;
        }

        // Check for role escalation attempts
        if ($this->hasRoleEscalationAttempt($request, $user)) {
            return true;
        }

        return false;
    }

    /**
     * Check for unusual request patterns
     */
    private function hasUnusualRequestPattern(Request $request)
    {
        // Check for SQL injection patterns
        $sqlPatterns = [
            '/(\bUNION\b|\bSELECT\b|\bINSERT\b|\bUPDATE\b|\bDELETE\b|\bDROP\b)/i',
            '/(\bOR\b|\bAND\b)\s+\d+\s*=\s*\d+/i',
            '/[\'";].*(\bOR\b|\bAND\b)/i'
        ];

        $requestString = json_encode($request->all());
        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $requestString)) {
                return true;
            }
        }

        // Check for XSS patterns
        $xssPatterns = [
            '/<script[^>]*>.*?<\/script>/i',
            '/javascript:/i',
            '/on\w+\s*=/i'
        ];

        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $requestString)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for role escalation attempts
     */
    private function hasRoleEscalationAttempt(Request $request, $user)
    {
        // Check if user is trying to access admin-only endpoints
        $adminOnlyRoutes = [
            'attendance.manage_users',
            'attendance.system_settings',
            'attendance.audit_logs'
        ];

        $currentRoute = $request->route() ? $request->route()->getName() : '';
        
        if (in_array($currentRoute, $adminOnlyRoutes) && !$user->isAdmin()) {
            return true;
        }

        // Check for role parameter manipulation
        if ($request->has('role') && $request->input('role') !== $user->role) {
            return true;
        }

        return false;
    }

    /**
     * Sanitize request data for logging (remove sensitive information)
     */
    private function sanitizeRequestData($data)
    {
        $sensitiveFields = ['password', 'password_confirmation', '_token', 'api_key', 'secret'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }

        return $data;
    }
}