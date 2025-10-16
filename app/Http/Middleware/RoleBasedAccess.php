<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\NewRole;
use App\Models\UserActivity;

class RoleBasedAccess
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
        // Check if user is authenticated
        if (!Auth::check()) {
            return $this->handleUnauthenticated($request);
        }

        $user = Auth::user();

        // Check if user account is still valid
        if (!$this->isUserAccountValid($user)) {
            Auth::logout();
            return $this->handleInvalidAccount($request, $user);
        }

        // Check if user must change password
        if ($user->mustChangePassword() && !$this->isPasswordChangeRoute($request)) {
            return redirect()->route('password.change.form')
                           ->with('warning', 'You must change your password before continuing.');
        }

        // If no specific roles required, just check if authenticated
        if (empty($roles)) {
            return $next($request);
        }

        // Check role access
        if (!$this->hasRequiredRole($user, $roles)) {
            return $this->handleUnauthorized($request, $user, $roles);
        }

        // Log access for sensitive routes
        if ($this->isSensitiveRoute($request)) {
            $this->logAccess($request, $user);
        }

        return $next($request);
    }

    /**
     * Check if user account is valid
     */
    protected function isUserAccountValid($user): bool
    {
        return $user && $user->isActive() && !$user->isLocked() && !$user->isSuspended();
    }

    /**
     * Check if user has required role
     */
    protected function hasRequiredRole($user, array $roles): bool
    {
        // Convert role names to lowercase for comparison
        $roles = array_map('strtolower', $roles);

        // Check if user has any of the required roles
        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return true;
            }
        }

        // Check for hierarchical access (higher roles can access lower role routes)
        return $this->checkHierarchicalAccess($user, $roles);
    }

    /**
     * Check hierarchical access based on role levels
     */
    protected function checkHierarchicalAccess($user, array $roles): bool
    {
        $userHighestLevel = $user->getHighestRoleLevel();
        
        if ($userHighestLevel === null) {
            return false;
        }

        // Define role hierarchy levels
        $roleHierarchy = [
            'super_admin' => NewRole::HIERARCHY_SUPER_ADMIN,
            'admin' => NewRole::HIERARCHY_ADMIN,
            'principal' => NewRole::HIERARCHY_PRINCIPAL,
            'teacher' => NewRole::HIERARCHY_TEACHER,
            'student' => NewRole::HIERARCHY_STUDENT,
            'parent' => NewRole::HIERARCHY_PARENT,
        ];

        // Check if user's highest role level allows access to any of the required roles
        foreach ($roles as $role) {
            $requiredLevel = $roleHierarchy[$role] ?? null;
            
            if ($requiredLevel !== null && $userHighestLevel <= $requiredLevel) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if current route is password change route
     */
    protected function isPasswordChangeRoute(Request $request): bool
    {
        $passwordChangeRoutes = [
            'password.change.form',
            'password.change',
            'logout',
        ];

        return in_array($request->route()->getName(), $passwordChangeRoutes);
    }

    /**
     * Check if route is sensitive and requires logging
     */
    protected function isSensitiveRoute(Request $request): bool
    {
        $sensitiveRoutes = [
            'dashboard.super-admin',
            'dashboard.admin',
            'users.create',
            'users.edit',
            'users.delete',
            'roles.create',
            'roles.edit',
            'roles.delete',
            'permissions.manage',
            'system.settings',
            'reports.financial',
            'database.backup',
            'database.restore',
        ];

        $routeName = $request->route()->getName();
        
        return in_array($routeName, $sensitiveRoutes) || 
               str_contains($routeName, 'admin') || 
               str_contains($routeName, 'manage');
    }

    /**
     * Log access to sensitive routes
     */
    protected function logAccess(Request $request, $user)
    {
        UserActivity::logActivity([
            'user_id' => $user->id,
            'activity_type' => UserActivity::TYPE_PAGE_ACCESS,
            'activity_description' => "Accessed sensitive route: {$request->route()->getName()}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'additional_data' => [
                'route' => $request->route()->getName(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'parameters' => $request->route()->parameters(),
            ]
        ]);
    }

    /**
     * Handle unauthenticated request
     */
    protected function handleUnauthenticated(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        return redirect()->guest(route('login'));
    }

    /**
     * Handle invalid account
     */
    protected function handleInvalidAccount(Request $request, $user)
    {
        $message = 'Your account access has been restricted. Please contact administrator.';

        if ($user->isLocked()) {
            if ($user->locked_until && $user->locked_until->isFuture()) {
                $minutes = $user->locked_until->diffInMinutes(now());
                $message = "Your account is temporarily locked. Try again in {$minutes} minutes.";
            } else {
                $message = 'Your account is locked. Please contact administrator.';
            }
        } elseif ($user->isSuspended()) {
            $message = 'Your account is suspended. Please contact administrator.';
        } elseif (!$user->isActive()) {
            $message = 'Your account is inactive. Please contact administrator.';
        }

        if ($request->expectsJson()) {
            return response()->json(['error' => $message], 403);
        }

        return redirect()->route('login')->with('error', $message);
    }

    /**
     * Handle unauthorized access
     */
    protected function handleUnauthorized(Request $request, $user, array $roles)
    {
        // Log unauthorized access attempt
        UserActivity::logActivity([
            'user_id' => $user->id,
            'activity_type' => UserActivity::TYPE_UNAUTHORIZED_ACCESS,
            'activity_description' => "Attempted to access route requiring roles: " . implode(', ', $roles),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'additional_data' => [
                'required_roles' => $roles,
                'user_roles' => $user->getActiveRoles()->pluck('name')->toArray(),
                'route' => $request->route()->getName(),
                'url' => $request->fullUrl(),
            ]
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Insufficient permissions',
                'required_roles' => $roles,
                'user_roles' => $user->getActiveRoles()->pluck('name')->toArray(),
            ], 403);
        }

        // Redirect to appropriate dashboard or show error
        return redirect()->route('dashboard.default')
                        ->with('error', 'You do not have permission to access that page.');
    }
}