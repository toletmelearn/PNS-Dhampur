<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use App\Models\Role;
use Carbon\Carbon;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  ...$roles
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Enhanced authentication validation
        if (!$this->isProperlyAuthenticated($request)) {
            $this->logSecurityEvent($request, 'authentication_failed', [
                'reason' => 'User not properly authenticated',
                'session_id' => Session::getId()
            ]);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'Authentication required'
                ], 401);
            }
            
            return redirect()->route('login')->with('error', 'Please login to access this page.');
        }

        $user = Auth::user();

        // Check if user can access attendance module
        if (!$user->canAccessAttendance()) {
            $this->logSecurityEvent($request, 'attendance_access_denied', [
                'user_id' => $user->id,
                'user_role' => $user->role
            ]);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'Access denied to attendance module'
                ], 403);
            }
            
            return redirect()->route('dashboard')->with('error', 'You do not have access to the attendance module.');
        }

        // If no specific roles are required, allow access
        if (empty($roles)) {
            return $next($request);
        }

        // Parse roles - handle comma-separated strings from route definitions
        $parsedRoles = $this->parseRequiredRoles($roles);

        // Enhanced role checking with hierarchy and admin override
        if ($this->hasRequiredAccess($user, $parsedRoles, $request)) {
            return $next($request);
        }

        // Log unauthorized access attempt
        $this->logSecurityEvent($request, 'role_access_denied', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'user_role_level' => $user->getRoleLevel(),
            'required_roles' => $parsedRoles,
            'route' => $request->route() ? $request->route()->getName() : null,
            'url' => $request->fullUrl()
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Insufficient privileges. Required roles: ' . implode(', ', $parsedRoles),
                'user_role' => $user->role,
                'user_role_level' => $user->getRoleLevel(),
                'required_roles' => $parsedRoles,
                'timestamp' => now()->toISOString()
            ], 403);
        }
        
        return redirect()->route('dashboard')->with('error', 
            'Access denied. You need one of the following roles: ' . implode(', ', array_map(function($role) {
                return Role::getRoleName($role);
            }, $parsedRoles))
        );
    }

    /**
     * Enhanced authentication validation
     */
    private function isProperlyAuthenticated(Request $request): bool
    {
        // Basic authentication check
        if (!Auth::check()) {
            return false;
        }

        $user = Auth::user();

        // Validate session integrity
        if (!$this->validateSessionIntegrity($request, $user)) {
            return false;
        }

        // Check for session hijacking indicators
        if ($this->detectSessionHijacking($request, $user)) {
            return false;
        }

        return true;
    }

    /**
     * Validate session integrity
     */
    private function validateSessionIntegrity(Request $request, $user): bool
    {
        // Check if session has required authentication markers
        if (!Session::has('login_time') || !Session::has('user_agent_hash')) {
            return false;
        }

        // Validate user agent consistency
        $currentUserAgentHash = hash('sha256', $request->userAgent() ?? '');
        $sessionUserAgentHash = Session::get('user_agent_hash');
        
        if ($currentUserAgentHash !== $sessionUserAgentHash) {
            Log::warning('Session user agent mismatch detected', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'current_hash' => $currentUserAgentHash,
                'session_hash' => $sessionUserAgentHash
            ]);
            return false;
        }

        // Check session timeout based on role
        $loginTime = Session::get('login_time');
        $sessionTimeout = $this->getSessionTimeoutForRole($user->role);
        
        if (Carbon::parse($loginTime)->addMinutes($sessionTimeout)->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Detect potential session hijacking
     */
    private function detectSessionHijacking(Request $request, $user): bool
    {
        $currentIp = $request->ip();
        $sessionIp = Session::get('login_ip');

        // Allow IP changes for mobile users but log them
        if ($sessionIp && $currentIp !== $sessionIp) {
            Log::info('IP address change detected', [
                'user_id' => $user->id,
                'session_ip' => $sessionIp,
                'current_ip' => $currentIp,
                'user_agent' => $request->userAgent()
            ]);
            
            // Update session IP for legitimate IP changes
            Session::put('login_ip', $currentIp);
        }

        return false; // For now, we don't block on IP changes
    }

    /**
     * Parse required roles from middleware parameters
     */
    private function parseRequiredRoles(array $roles): array
    {
        $parsedRoles = [];
        foreach ($roles as $role) {
            if (strpos($role, ',') !== false) {
                // Split comma-separated role string
                $parsedRoles = array_merge($parsedRoles, array_map('trim', explode(',', $role)));
            } else {
                $parsedRoles[] = $role;
            }
        }
        return $parsedRoles;
    }

    /**
     * Enhanced role checking with hierarchy and admin override
     */
    private function hasRequiredAccess($user, array $requiredRoles, Request $request): bool
    {
        // Admin override: Admins can access everything except student-specific routes
        if ($this->hasAdminOverride($user, $requiredRoles, $request)) {
            $this->logSecurityEvent($request, 'admin_override_used', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'required_roles' => $requiredRoles,
                'route' => $request->route() ? $request->route()->getName() : null
            ]);
            return true;
        }

        // Check direct role match
        if ($user->hasAnyRole($requiredRoles)) {
            return true;
        }

        // Check role hierarchy (higher level roles can access lower level resources)
        if ($this->hasHierarchicalAccess($user, $requiredRoles)) {
            $this->logSecurityEvent($request, 'hierarchical_access_granted', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'user_role_level' => $user->getRoleLevel(),
                'required_roles' => $requiredRoles,
                'route' => $request->route() ? $request->route()->getName() : null
            ]);
            return true;
        }

        return false;
    }

    /**
     * Check if user has admin override privileges
     */
    private function hasAdminOverride($user, array $requiredRoles, Request $request): bool
    {
        // Only admin, principal, and IT roles have override privileges
        if (!$user->hasAnyRole(['admin', 'principal', 'it'])) {
            return false;
        }

        // Admin override restrictions: Cannot access student-only routes
        $studentOnlyRoutes = [
            'student.profile',
            'student.attendance.view',
            'student.grades.view'
        ];

        $currentRoute = $request->route() ? $request->route()->getName() : '';
        
        // Block admin override for student-specific functionality
        if (in_array($currentRoute, $studentOnlyRoutes) && in_array('student', $requiredRoles)) {
            return false;
        }

        // Block admin override if explicitly requiring student role only
        if (count($requiredRoles) === 1 && $requiredRoles[0] === 'student') {
            return false;
        }

        return true;
    }

    /**
     * Check hierarchical access based on role levels
     */
    private function hasHierarchicalAccess($user, array $requiredRoles): bool
    {
        $userLevel = $user->getRoleLevel();
        
        foreach ($requiredRoles as $requiredRole) {
            $requiredLevel = Role::getRoleLevel($requiredRole);
            
            // Users with higher or equal role level can access lower level resources
            if ($userLevel >= $requiredLevel) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get session timeout based on user role
     */
    private function getSessionTimeoutForRole(string $role): int
    {
        $timeouts = [
            'admin' => 20,
            'principal' => 25,
            'it' => 20,
            'accountant' => 30,
            'teacher' => 45,
            'class_teacher' => 45,
            'exam_incharge' => 45,
            'student' => 120
        ];

        return $timeouts[$role] ?? 60; // Default 60 minutes
    }

    /**
     * Log security events
     */
    private function logSecurityEvent(Request $request, string $event, array $context = []): void
    {
        $baseContext = [
            'event' => $event,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'session_id' => Session::getId(),
            'timestamp' => now()->toISOString()
        ];

        $fullContext = array_merge($baseContext, $context);

        // Log to security channel
        Log::channel('security')->info("Security Event: {$event}", $fullContext);
        
        // Also log to default channel for critical events
        if (in_array($event, ['authentication_failed', 'role_access_denied', 'session_hijacking_detected'])) {
            Log::warning("Critical Security Event: {$event}", $fullContext);
        }
    }
}