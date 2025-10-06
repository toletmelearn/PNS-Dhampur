<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Role;

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
        // Check if user is authenticated
        if (!Auth::check()) {
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
        $parsedRoles = [];
        foreach ($roles as $role) {
            if (strpos($role, ',') !== false) {
                // Split comma-separated role string
                $parsedRoles = array_merge($parsedRoles, array_map('trim', explode(',', $role)));
            } else {
                $parsedRoles[] = $role;
            }
        }

        // Check if user has any of the required roles
        if (!$user->hasAnyRole($parsedRoles)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'Insufficient privileges. Required roles: ' . implode(', ', $parsedRoles),
                    'user_role' => $user->role,
                    'required_roles' => $parsedRoles
                ], 403);
            }
            
            return redirect()->route('dashboard')->with('error', 
                'Access denied. You need one of the following roles: ' . implode(', ', array_map(function($role) {
                    return Role::getRoleName($role);
                }, $parsedRoles))
            );
        }

        return $next($request);
    }
}