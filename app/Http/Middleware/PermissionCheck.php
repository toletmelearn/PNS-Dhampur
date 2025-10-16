<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Permission;
use App\Models\UserActivity;

class PermissionCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  ...$permissions
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$permissions)
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return $this->handleUnauthenticated($request);
        }

        $user = Auth::user();

        // Check if user account is still valid
        if (!$this->isUserAccountValid($user)) {
            Auth::logout();
            return $this->handleInvalidAccount($request);
        }

        // If no specific permissions required, just check if authenticated
        if (empty($permissions)) {
            return $next($request);
        }

        // Check permissions
        if (!$this->hasRequiredPermissions($user, $permissions)) {
            return $this->handleUnauthorized($request, $user, $permissions);
        }

        // Log access for sensitive permissions
        if ($this->isSensitivePermission($permissions)) {
            $this->logPermissionAccess($request, $user, $permissions);
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
     * Check if user has required permissions
     */
    protected function hasRequiredPermissions($user, array $permissions): bool
    {
        // Check if user has all required permissions (AND logic)
        foreach ($permissions as $permission) {
            if (!$user->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if any of the permissions are sensitive
     */
    protected function isSensitivePermission(array $permissions): bool
    {
        $sensitivePermissions = [
            'users.create',
            'users.edit',
            'users.delete',
            'users.view_all',
            'roles.create',
            'roles.edit',
            'roles.delete',
            'permissions.manage',
            'system.settings.manage',
            'system.backup',
            'system.restore',
            'financial.view_all',
            'financial.manage',
            'reports.financial',
            'database.access',
            'audit.view',
        ];

        foreach ($permissions as $permission) {
            if (in_array($permission, $sensitivePermissions)) {
                return true;
            }

            // Check for patterns
            if (str_contains($permission, 'delete') || 
                str_contains($permission, 'manage') || 
                str_contains($permission, 'admin') ||
                str_contains($permission, 'system')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Log permission-based access
     */
    protected function logPermissionAccess(Request $request, $user, array $permissions)
    {
        UserActivity::logActivity([
            'user_id' => $user->id,
            'activity_type' => UserActivity::TYPE_PERMISSION_ACCESS,
            'activity_description' => "Accessed route with permissions: " . implode(', ', $permissions),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'additional_data' => [
                'required_permissions' => $permissions,
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
    protected function handleInvalidAccount(Request $request)
    {
        $message = 'Your account access has been restricted. Please contact administrator.';

        if ($request->expectsJson()) {
            return response()->json(['error' => $message], 403);
        }

        return redirect()->route('login')->with('error', $message);
    }

    /**
     * Handle unauthorized access
     */
    protected function handleUnauthorized(Request $request, $user, array $permissions)
    {
        // Log unauthorized permission access attempt
        UserActivity::logActivity([
            'user_id' => $user->id,
            'activity_type' => UserActivity::TYPE_UNAUTHORIZED_ACCESS,
            'activity_description' => "Attempted to access route requiring permissions: " . implode(', ', $permissions),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'additional_data' => [
                'required_permissions' => $permissions,
                'user_permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                'route' => $request->route()->getName(),
                'url' => $request->fullUrl(),
            ]
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Insufficient permissions',
                'required_permissions' => $permissions,
                'user_permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            ], 403);
        }

        // Redirect to appropriate dashboard or show error
        return redirect()->back()
                        ->with('error', 'You do not have permission to perform this action.');
    }
}

/**
 * Permission-based route helper middleware
 */
class RequirePermission
{
    /**
     * Handle permission check for specific actions
     */
    public static function check(string $permission): \Closure
    {
        return function (Request $request, Closure $next) use ($permission) {
            if (!Auth::check()) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Unauthenticated'], 401);
                }
                return redirect()->guest(route('login'));
            }

            $user = Auth::user();

            if (!$user->hasPermission($permission)) {
                UserActivity::logActivity([
                    'user_id' => $user->id,
                    'activity_type' => UserActivity::TYPE_UNAUTHORIZED_ACCESS,
                    'activity_description' => "Permission denied for: {$permission}",
                    'ip_address' => $request->ip(),
                    'additional_data' => [
                        'required_permission' => $permission,
                        'route' => $request->route()->getName(),
                    ]
                ]);

                if ($request->expectsJson()) {
                    return response()->json([
                        'error' => 'Permission denied',
                        'required_permission' => $permission,
                    ], 403);
                }

                return redirect()->back()
                               ->with('error', 'You do not have permission to perform this action.');
            }

            return $next($request);
        };
    }

    /**
     * Check multiple permissions (user must have ALL)
     */
    public static function checkAll(array $permissions): \Closure
    {
        return function (Request $request, Closure $next) use ($permissions) {
            if (!Auth::check()) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Unauthenticated'], 401);
                }
                return redirect()->guest(route('login'));
            }

            $user = Auth::user();

            foreach ($permissions as $permission) {
                if (!$user->hasPermission($permission)) {
                    UserActivity::logActivity([
                        'user_id' => $user->id,
                        'activity_type' => UserActivity::TYPE_UNAUTHORIZED_ACCESS,
                        'activity_description' => "Permission denied for: " . implode(', ', $permissions),
                        'ip_address' => $request->ip(),
                        'additional_data' => [
                            'required_permissions' => $permissions,
                            'missing_permission' => $permission,
                            'route' => $request->route()->getName(),
                        ]
                    ]);

                    if ($request->expectsJson()) {
                        return response()->json([
                            'error' => 'Permission denied',
                            'required_permissions' => $permissions,
                            'missing_permission' => $permission,
                        ], 403);
                    }

                    return redirect()->back()
                                   ->with('error', 'You do not have permission to perform this action.');
                }
            }

            return $next($request);
        };
    }

    /**
     * Check multiple permissions (user must have ANY)
     */
    public static function checkAny(array $permissions): \Closure
    {
        return function (Request $request, Closure $next) use ($permissions) {
            if (!Auth::check()) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Unauthenticated'], 401);
                }
                return redirect()->guest(route('login'));
            }

            $user = Auth::user();
            $hasPermission = false;

            foreach ($permissions as $permission) {
                if ($user->hasPermission($permission)) {
                    $hasPermission = true;
                    break;
                }
            }

            if (!$hasPermission) {
                UserActivity::logActivity([
                    'user_id' => $user->id,
                    'activity_type' => UserActivity::TYPE_UNAUTHORIZED_ACCESS,
                    'activity_description' => "Permission denied for any of: " . implode(', ', $permissions),
                    'ip_address' => $request->ip(),
                    'additional_data' => [
                        'required_permissions_any' => $permissions,
                        'route' => $request->route()->getName(),
                    ]
                ]);

                if ($request->expectsJson()) {
                    return response()->json([
                        'error' => 'Permission denied',
                        'required_permissions_any' => $permissions,
                    ], 403);
                }

                return redirect()->back()
                               ->with('error', 'You do not have permission to perform this action.');
            }

            return $next($request);
        };
    }
}