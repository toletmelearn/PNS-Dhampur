<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\User;
use App\Models\Role;

/**
 * SecurityController handles security-related operations and error responses
 * for the attendance system with comprehensive logging and monitoring.
 */
class SecurityController extends Controller
{
    /**
     * Handle unauthorized access attempts with detailed logging
     */
    public function unauthorized(Request $request): JsonResponse
    {
        $user = Auth::user();
        $userInfo = $user ? [
            'id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'name' => $user->name
        ] : null;

        // Log unauthorized access attempt
        Log::warning('Unauthorized access attempt', [
            'user' => $userInfo,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'timestamp' => now(),
            'session_id' => $request->session()->getId(),
            'referer' => $request->header('referer')
        ]);

        // Rate limit unauthorized attempts
        $key = 'unauthorized_attempts:' . $request->ip();
        RateLimiter::hit($key, 300); // 5 minutes

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'You do not have permission to access this resource.',
                'code' => 'INSUFFICIENT_PERMISSIONS',
                'timestamp' => now()->toISOString(),
                'request_id' => $request->header('X-Request-ID', uniqid())
            ], 403);
        }

        return response()->json([
            'error' => 'Access Denied',
            'message' => 'You do not have the required permissions to access this page.',
            'redirect' => route('dashboard')
        ], 403);
    }

    /**
     * Handle authentication required errors
     */
    public function unauthenticated(Request $request): JsonResponse
    {
        // Log unauthenticated access attempt
        Log::info('Unauthenticated access attempt', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'timestamp' => now(),
            'referer' => $request->header('referer')
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Unauthenticated',
                'message' => 'Authentication required to access this resource.',
                'code' => 'AUTHENTICATION_REQUIRED',
                'login_url' => route('login'),
                'timestamp' => now()->toISOString()
            ], 401);
        }

        return response()->json([
            'error' => 'Authentication Required',
            'message' => 'Please log in to access this page.',
            'redirect' => route('login')
        ], 401);
    }

    /**
     * Handle role-specific access denied errors
     */
    public function roleAccessDenied(Request $request, string $requiredRole = null): JsonResponse
    {
        $user = Auth::user();
        $userRole = $user ? $user->role : 'guest';

        // Log role access denied
        Log::warning('Role access denied', [
            'user_id' => $user?->id,
            'user_role' => $userRole,
            'required_role' => $requiredRole,
            'ip' => $request->ip(),
            'url' => $request->fullUrl(),
            'timestamp' => now()
        ]);

        $message = $requiredRole 
            ? "This action requires {$requiredRole} role. Your current role is {$userRole}."
            : "Your current role ({$userRole}) does not have permission to perform this action.";

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Role Access Denied',
                'message' => $message,
                'code' => 'INSUFFICIENT_ROLE',
                'user_role' => $userRole,
                'required_role' => $requiredRole,
                'timestamp' => now()->toISOString()
            ], 403);
        }

        return response()->json([
            'error' => 'Access Denied',
            'message' => $message,
            'redirect' => route('dashboard')
        ], 403);
    }

    /**
     * Handle attendance module access denied
     */
    public function attendanceAccessDenied(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Log attendance access denied
        Log::warning('Attendance module access denied', [
            'user_id' => $user?->id,
            'user_role' => $user?->role,
            'ip' => $request->ip(),
            'url' => $request->fullUrl(),
            'timestamp' => now()
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Attendance Access Denied',
                'message' => 'You do not have permission to access the attendance module.',
                'code' => 'ATTENDANCE_ACCESS_DENIED',
                'timestamp' => now()->toISOString()
            ], 403);
        }

        return response()->json([
            'error' => 'Access Denied',
            'message' => 'You do not have permission to access the attendance system.',
            'redirect' => route('dashboard')
        ], 403);
    }

    /**
     * Handle suspicious activity detection
     */
    public function suspiciousActivity(Request $request, string $activityType): JsonResponse
    {
        $user = Auth::user();

        // Log suspicious activity
        Log::alert('Suspicious activity detected', [
            'activity_type' => $activityType,
            'user_id' => $user?->id,
            'user_role' => $user?->role,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'timestamp' => now(),
            'session_id' => $request->session()->getId(),
            'request_data' => $request->except(['password', 'password_confirmation', '_token'])
        ]);

        // Temporarily block the IP for suspicious activity
        $key = 'suspicious_activity:' . $request->ip();
        RateLimiter::hit($key, 900); // 15 minutes

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Security Alert',
                'message' => 'Suspicious activity detected. Access temporarily restricted.',
                'code' => 'SUSPICIOUS_ACTIVITY',
                'activity_type' => $activityType,
                'timestamp' => now()->toISOString()
            ], 429);
        }

        return response()->json([
            'error' => 'Security Alert',
            'message' => 'Suspicious activity detected. Please contact administrator.',
            'redirect' => route('dashboard')
        ], 429);
    }

    /**
     * Get user security information
     */
    public function getUserSecurityInfo(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return $this->unauthenticated($request);
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'role_name' => $user->getRoleName(),
                'role_description' => $user->getRoleDescription(),
                'role_level' => $user->getRoleLevel(),
                'can_access_attendance' => $user->canAccessAttendance(),
                'permissions' => $user->getAttendancePermissions(),
                'allowed_navigation' => $user->getAllowedNavigation()
            ],
            'session' => [
                'expires_at' => now()->addMinutes(config('session.lifetime'))->toISOString(),
                'secure' => config('session.secure'),
                'http_only' => config('session.http_only'),
                'same_site' => config('session.same_site')
            ],
            'security' => [
                'csrf_token' => csrf_token(),
                'rate_limits' => [
                    'unauthorized_attempts' => RateLimiter::remaining('unauthorized_attempts:' . $request->ip(), 10),
                    'suspicious_activity' => RateLimiter::remaining('suspicious_activity:' . $request->ip(), 5)
                ]
            ]
        ]);
    }

    /**
     * Validate CSRF token manually
     */
    public function validateCsrfToken(Request $request): JsonResponse
    {
        $token = $request->header('X-CSRF-TOKEN') ?: $request->input('_token');
        
        if (!$token || !hash_equals(csrf_token(), $token)) {
            Log::warning('CSRF token validation failed', [
                'user_id' => Auth::id(),
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'timestamp' => now()
            ]);

            return response()->json([
                'error' => 'CSRF Token Mismatch',
                'message' => 'The provided CSRF token is invalid.',
                'code' => 'CSRF_TOKEN_MISMATCH',
                'timestamp' => now()->toISOString()
            ], 419);
        }

        return response()->json([
            'message' => 'CSRF token is valid',
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Handle rate limit exceeded
     */
    public function rateLimitExceeded(Request $request): JsonResponse
    {
        Log::warning('Rate limit exceeded', [
            'user_id' => Auth::id(),
            'ip' => $request->ip(),
            'url' => $request->fullUrl(),
            'timestamp' => now()
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Rate Limit Exceeded',
                'message' => 'Too many requests. Please try again later.',
                'code' => 'RATE_LIMIT_EXCEEDED',
                'retry_after' => 60,
                'timestamp' => now()->toISOString()
            ], 429);
        }

        return response()->json([
            'error' => 'Too Many Requests',
            'message' => 'Please wait before making another request.',
            'redirect' => route('dashboard')
        ], 429);
    }
}