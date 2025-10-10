<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use App\Models\Role;
use App\Services\ComprehensiveErrorHandlingService;

class RoleMiddleware
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
     * Handle an incoming request with enhanced role hierarchy checking
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Enhanced authentication validation
        if (!$this->isAuthenticated($request)) {
            $this->logSecurityEvent('authentication_failed', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'route' => $request->route()?->getName(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'timestamp' => now()->toISOString()
            ]);

            return $this->handleUnauthorized($request, 'Authentication required');
        }

        $user = Auth::user();

        // Check if user can access attendance module
        if (!$user->canAccessAttendance()) {
            $this->logSecurityEvent('attendance_access_denied', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'ip' => $request->ip(),
                'route' => $request->route()?->getName(),
                'timestamp' => now()->toISOString()
            ]);

            return $this->handleUnauthorized($request, 'Access to attendance module denied');
        }

        // Enhanced session integrity checks
        if (!$this->validateSessionIntegrity($request, $user)) {
            return $this->handleUnauthorized($request, 'Session integrity validation failed');
        }

        // Parse required roles and permissions
        $requiredRoles = $this->parseRequiredRoles($roles);
        $requiredPermissions = $this->extractPermissions($roles);

        // Enhanced role and permission checking with hierarchy
        if (!$this->hasRequiredAccess($user, $requiredRoles, $requiredPermissions, $request)) {
            $this->logSecurityEvent('access_denied', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'user_level' => $user->getRoleLevel(),
                'required_roles' => $requiredRoles,
                'required_permissions' => $requiredPermissions,
                'ip' => $request->ip(),
                'route' => $request->route()?->getName(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'timestamp' => now()->toISOString()
            ]);

            return $this->handleUnauthorized($request, 'Insufficient privileges for this action');
        }

        // Log successful access for audit trail
        $this->logSecurityEvent('access_granted', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'user_level' => $user->getRoleLevel(),
            'route' => $request->route()?->getName(),
            'method' => $request->method(),
            'timestamp' => now()->toISOString()
        ], 'info');

        return $next($request);
    }

    /**
     * Enhanced authentication validation
     */
    protected function isAuthenticated(Request $request): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $user = Auth::user();
        
        // Additional validation checks
        if (!$user || !$user->id) {
            return false;
        }

        // Check if user account is active (if soft deletes are used)
        if (method_exists($user, 'trashed') && $user->trashed()) {
            return false;
        }

        return true;
    }

    /**
     * Enhanced session integrity validation
     */
    protected function validateSessionIntegrity(Request $request, $user): bool
    {
        // Check for session hijacking indicators
        if ($this->detectSessionHijacking($request, $user)) {
            $this->logSecurityEvent('session_hijacking_detected', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => Session::getId(),
                'timestamp' => now()->toISOString()
            ]);
            
            Auth::logout();
            Session::invalidate();
            Session::regenerateToken();
            
            return false;
        }

        // Check session timeout based on role
        $sessionTimeout = $this->getSessionTimeoutForRole($user->role);
        $lastActivity = Session::get('last_activity', time());
        
        if (time() - $lastActivity > $sessionTimeout) {
            $this->logSecurityEvent('session_timeout', [
                'user_id' => $user->id,
                'role' => $user->role,
                'timeout_duration' => $sessionTimeout,
                'last_activity' => date('Y-m-d H:i:s', $lastActivity),
                'timestamp' => now()->toISOString()
            ]);
            
            Auth::logout();
            Session::invalidate();
            Session::regenerateToken();
            
            return false;
        }

        // Update last activity
        Session::put('last_activity', time());
        
        return true;
    }

    /**
     * Detect potential session hijacking
     */
    protected function detectSessionHijacking(Request $request, $user): bool
    {
        $currentIp = $request->ip();
        $currentUserAgent = $request->userAgent();
        
        $sessionIp = Session::get('user_ip');
        $sessionUserAgent = Session::get('user_agent');
        
        // First time setting session data
        if (!$sessionIp || !$sessionUserAgent) {
            Session::put('user_ip', $currentIp);
            Session::put('user_agent', $currentUserAgent);
            return false;
        }
        
        // Check for IP address changes (allow for reasonable IP changes)
        if ($sessionIp !== $currentIp) {
            // Allow IP changes within the same subnet for dynamic IPs
            if (!$this->isIpInSameSubnet($sessionIp, $currentIp)) {
                return true;
            }
        }
        
        // Check for user agent changes
        if ($sessionUserAgent !== $currentUserAgent) {
            return true;
        }
        
        return false;
    }

    /**
     * Check if two IPs are in the same subnet
     */
    protected function isIpInSameSubnet($ip1, $ip2): bool
    {
        // Simple subnet check for /24 networks
        $subnet1 = substr($ip1, 0, strrpos($ip1, '.'));
        $subnet2 = substr($ip2, 0, strrpos($ip2, '.'));
        
        return $subnet1 === $subnet2;
    }

    /**
     * Parse required roles from middleware parameters
     */
    protected function parseRequiredRoles(array $roles): array
    {
        $parsedRoles = [];
        
        foreach ($roles as $role) {
            // Handle role with minimum level requirement (e.g., "teacher:2")
            if (strpos($role, ':') !== false) {
                [$roleName, $minLevel] = explode(':', $role, 2);
                $parsedRoles[] = [
                    'name' => $roleName,
                    'min_level' => (int)$minLevel
                ];
            } else {
                $parsedRoles[] = [
                    'name' => $role,
                    'min_level' => null
                ];
            }
        }
        
        return $parsedRoles;
    }

    /**
     * Extract permission requirements from roles
     */
    protected function extractPermissions(array $roles): array
    {
        $permissions = [];
        
        foreach ($roles as $role) {
            // Handle permission requirements (e.g., "permission:attendance.view_all")
            if (strpos($role, 'permission:') === 0) {
                $permissions[] = substr($role, 11); // Remove "permission:" prefix
            }
        }
        
        return $permissions;
    }

    /**
     * Enhanced access checking with role hierarchy and permissions
     */
    protected function hasRequiredAccess($user, array $requiredRoles, array $requiredPermissions, Request $request): bool
    {
        $userRole = $user->role;
        $userLevel = $user->getRoleLevel();
        
        // Admin override with enhanced security checks
        if ($this->hasAdminOverride($user, $request)) {
            $this->logSecurityEvent('admin_override_used', [
                'user_id' => $user->id,
                'user_role' => $userRole,
                'route' => $request->route()?->getName(),
                'timestamp' => now()->toISOString()
            ], 'info');
            return true;
        }

        // Check specific role requirements
        if (!empty($requiredRoles)) {
            $hasRoleAccess = false;
            
            foreach ($requiredRoles as $roleRequirement) {
                $requiredRole = $roleRequirement['name'];
                $minLevel = $roleRequirement['min_level'];
                
                // Direct role match
                if ($userRole === $requiredRole) {
                    $hasRoleAccess = true;
                    break;
                }
                
                // Hierarchical access check
                if ($minLevel !== null && $userLevel >= $minLevel) {
                    $hasRoleAccess = true;
                    break;
                }
                
                // Check if user role has higher hierarchy than required role
                $requiredRoleLevel = Role::getRoleLevel($requiredRole);
                if ($userLevel >= $requiredRoleLevel) {
                    $hasRoleAccess = true;
                    break;
                }
                
                // Check role inheritance (e.g., class_teacher inherits from teacher)
                if ($this->hasRoleInheritance($userRole, $requiredRole)) {
                    $hasRoleAccess = true;
                    break;
                }
            }
            
            if (!$hasRoleAccess) {
                return false;
            }
        }

        // Check specific permission requirements
        if (!empty($requiredPermissions)) {
            foreach ($requiredPermissions as $permission) {
                if (!$user->hasPermission($permission)) {
                    $this->logSecurityEvent('permission_denied', [
                        'user_id' => $user->id,
                        'user_role' => $userRole,
                        'required_permission' => $permission,
                        'route' => $request->route()?->getName(),
                        'timestamp' => now()->toISOString()
                    ]);
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check role inheritance relationships
     */
    protected function hasRoleInheritance(string $userRole, string $requiredRole): bool
    {
        $inheritanceMap = [
            'class_teacher' => ['teacher'], // class_teacher inherits from teacher
            'exam_incharge' => ['teacher'], // exam_incharge inherits from teacher
            'principal' => ['admin'],       // principal inherits from admin
            'it' => ['admin'],              // it inherits from admin
        ];

        // Check if user role inherits from required role
        if (isset($inheritanceMap[$userRole]) && in_array($requiredRole, $inheritanceMap[$userRole])) {
            return true;
        }

        return false;
    }

    /**
     * Enhanced admin override with security restrictions
     */
    protected function hasAdminOverride($user, Request $request): bool
    {
        $adminRoles = ['admin', 'principal', 'it'];
        
        if (!in_array($user->role, $adminRoles)) {
            return false;
        }
        
        // Restrict admin access to student-only routes for security
        $studentOnlyRoutes = [
            'student.attendance.view',
            'student.profile.basic',
            'student.reports.own'
        ];
        
        $currentRoute = $request->route()?->getName();
        if (in_array($currentRoute, $studentOnlyRoutes)) {
            $this->logSecurityEvent('admin_student_route_blocked', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'blocked_route' => $currentRoute,
                'timestamp' => now()->toISOString()
            ]);
            return false;
        }
        
        // Additional security: Prevent admin override for critical operations
        $criticalOperations = [
            'user.delete',
            'system.shutdown',
            'database.drop',
            'backup.restore'
        ];
        
        if (in_array($currentRoute, $criticalOperations)) {
            $this->logSecurityEvent('admin_critical_operation_blocked', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'blocked_operation' => $currentRoute,
                'timestamp' => now()->toISOString()
            ]);
            return false;
        }
        
        return true;
    }

    /**
     * Validate role hierarchy consistency
     */
    protected function validateRoleHierarchy($user, array $requiredRoles): bool
    {
        $userLevel = $user->getRoleLevel();
        
        foreach ($requiredRoles as $roleRequirement) {
            $requiredRole = $roleRequirement['name'];
            $requiredLevel = Role::getRoleLevel($requiredRole);
            
            // Ensure user has sufficient level for the required role
            if ($userLevel < $requiredLevel) {
                return false;
            }
            
            // Additional validation: Check if role is active and valid
            if (!$this->isRoleActive($requiredRole)) {
                $this->logSecurityEvent('inactive_role_required', [
                    'user_id' => $user->id,
                    'user_role' => $user->role,
                    'required_role' => $requiredRole,
                    'timestamp' => now()->toISOString()
                ]);
                return false;
            }
        }
        
        return true;
    }

    /**
     * Check if a role is active (placeholder for future implementation)
     */
    protected function isRoleActive(string $role): bool
    {
        // In a real implementation, this would check against a database
        // For now, assume all roles are active
        $inactiveRoles = []; // Roles that might be disabled
        
        return !in_array($role, $inactiveRoles);
    }

    /**
     * Get session timeout based on role (in seconds)
     */
    protected function getSessionTimeoutForRole($role): int
    {
        $timeouts = [
            'admin' => 3600,      // 1 hour
            'principal' => 3600,   // 1 hour
            'it' => 7200,         // 2 hours
            'teacher' => 14400,    // 4 hours
            'class_teacher' => 14400, // 4 hours
            'accountant' => 7200,  // 2 hours
            'exam_incharge' => 7200, // 2 hours
            'student' => 28800,    // 8 hours
        ];

        return $timeouts[$role] ?? 3600; // Default 1 hour
    }

    /**
     * Handle unauthorized access with enhanced error response
     */
    protected function handleUnauthorized(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'error_code' => 'UNAUTHORIZED_ACCESS',
                'timestamp' => now()->toISOString(),
                'request_id' => $request->header('X-Request-ID', uniqid()),
                'security_context' => [
                    'ip_address' => $request->ip(),
                    'user_agent' => substr($request->userAgent(), 0, 100),
                    'route' => $request->route()?->getName(),
                    'method' => $request->method()
                ]
            ], 403);
        }

        return redirect()->route('login')->with('error', $message);
    }

    /**
     * Enhanced security event logging
     */
    protected function logSecurityEvent(string $event, array $context, string $level = 'warning'): void
    {
        // Log to dedicated security channel
        Log::channel('security')->{$level}("Security Event: {$event}", $context);
        
        // Also log critical events to default channel
        if (in_array($level, ['error', 'critical', 'alert', 'emergency'])) {
            Log::{$level}("Critical Security Event: {$event}", $context);
        }

        // Use comprehensive error handling service for critical security events
        if (in_array($event, ['session_hijacking_detected', 'authentication_failed', 'access_denied'])) {
            $this->getErrorHandlingService()->handleSecurityEvent($event, $context);
        }
    }
}