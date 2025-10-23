<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ModuleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $module, string $permission = null): Response
    {
        // Log module access attempt
        Log::info('Module access attempt', [
            'module' => $module,
            'permission' => $permission,
            'user_id' => Auth::id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl()
        ]);

        // Check if user is authenticated
        if (!Auth::check()) {
            Log::warning('Unauthenticated module access attempt', [
                'module' => $module,
                'ip' => $request->ip(),
                'url' => $request->fullUrl()
            ]);
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }
            
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Check if user account is active
        if (!$user->is_active) {
            Log::warning('Inactive user module access attempt', [
                'user_id' => $user->id,
                'module' => $module,
                'ip' => $request->ip()
            ]);
            
            Auth::logout();
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Account is inactive'], 403);
            }
            
            return redirect()->route('login')->with('error', 'Your account is inactive. Please contact administrator.');
        }

        // Check module access permissions
        if (!$this->hasModuleAccess($user, $module)) {
            Log::warning('Unauthorized module access attempt', [
                'user_id' => $user->id,
                'module' => $module,
                'user_roles' => $user->roles->pluck('name')->toArray(),
                'ip' => $request->ip()
            ]);
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Insufficient permissions for this module'], 403);
            }
            
            return redirect()->route('dashboard')->with('error', 'You do not have permission to access this module.');
        }

        // Check specific permission if provided
        if ($permission && !$this->hasSpecificPermission($user, $module, $permission)) {
            Log::warning('Unauthorized permission access attempt', [
                'user_id' => $user->id,
                'module' => $module,
                'permission' => $permission,
                'ip' => $request->ip()
            ]);
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Insufficient permissions for this action'], 403);
            }
            
            return redirect()->back()->with('error', 'You do not have permission to perform this action.');
        }

        // Rate limiting per module
        $rateLimitKey = "module_access:{$user->id}:{$module}";
        $attempts = Cache::get($rateLimitKey, 0);
        
        if ($attempts >= $this->getModuleRateLimit($module)) {
            Log::warning('Module rate limit exceeded', [
                'user_id' => $user->id,
                'module' => $module,
                'attempts' => $attempts,
                'ip' => $request->ip()
            ]);
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Rate limit exceeded'], 429);
            }
            
            return redirect()->back()->with('error', 'Too many requests. Please try again later.');
        }

        // Increment rate limit counter
        Cache::put($rateLimitKey, $attempts + 1, now()->addMinutes(1));

        // Add module context to request
        $request->merge([
            'current_module' => $module,
            'user_permissions' => $this->getUserModulePermissions($user, $module)
        ]);

        // Log successful module access
        Log::info('Successful module access', [
            'user_id' => $user->id,
            'module' => $module,
            'permission' => $permission,
            'ip' => $request->ip()
        ]);

        return $next($request);
    }

    /**
     * Check if user has access to the specified module
     */
    private function hasModuleAccess($user, string $module): bool
    {
        // Cache user permissions for performance
        $cacheKey = "user_module_access:{$user->id}:{$module}";
        
        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($user, $module) {
            // Super admin has access to all modules
            if ($user->hasRole('super_admin')) {
                return true;
            }

            // Define module access rules
            $moduleAccess = [
                'student' => ['admin', 'principal', 'teacher', 'student', 'parent'],
                'teacher' => ['admin', 'principal', 'teacher'],
                'attendance' => ['admin', 'principal', 'teacher', 'student', 'parent'],
                'exam' => ['admin', 'principal', 'teacher', 'student', 'parent'],
                'fee' => ['admin', 'principal', 'accountant', 'student', 'parent'],
                'library' => ['admin', 'principal', 'librarian', 'teacher', 'student'],
                'transport' => ['admin', 'principal', 'transport_manager', 'student', 'parent'],
                'hostel' => ['admin', 'principal', 'hostel_warden', 'student', 'parent'],
                'parentportal' => ['admin', 'principal', 'teacher', 'parent'],
                'inventory' => ['admin', 'principal', 'inventory_manager'],
                'hr' => ['admin', 'principal', 'hr_manager'],
                'communication' => ['admin', 'principal', 'teacher', 'student', 'parent'],
                'report' => ['admin', 'principal', 'teacher'],
                'setting' => ['admin', 'principal'],
                'user_management' => ['admin', 'principal'],
                'system' => ['admin']
            ];

            $allowedRoles = $moduleAccess[$module] ?? [];
            
            return $user->hasAnyRole($allowedRoles);
        });
    }

    /**
     * Check if user has specific permission within a module
     */
    private function hasSpecificPermission($user, string $module, string $permission): bool
    {
        $cacheKey = "user_permission:{$user->id}:{$module}:{$permission}";
        
        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($user, $module, $permission) {
            // Super admin has all permissions
            if ($user->hasRole('super_admin')) {
                return true;
            }

            // Define permission rules
            $permissionRules = [
                'student' => [
                    'create' => ['admin', 'principal'],
                    'edit' => ['admin', 'principal'],
                    'delete' => ['admin', 'principal'],
                    'view' => ['admin', 'principal', 'teacher', 'student', 'parent'],
                    'bulk_action' => ['admin', 'principal'],
                    'export' => ['admin', 'principal', 'teacher'],
                    'import' => ['admin', 'principal']
                ],
                'teacher' => [
                    'create' => ['admin', 'principal'],
                    'edit' => ['admin', 'principal'],
                    'delete' => ['admin', 'principal'],
                    'view' => ['admin', 'principal', 'teacher'],
                    'bulk_action' => ['admin', 'principal'],
                    'export' => ['admin', 'principal'],
                    'import' => ['admin', 'principal']
                ],
                'attendance' => [
                    'mark' => ['admin', 'principal', 'teacher'],
                    'edit' => ['admin', 'principal', 'teacher'],
                    'delete' => ['admin', 'principal'],
                    'view' => ['admin', 'principal', 'teacher', 'student', 'parent'],
                    'report' => ['admin', 'principal', 'teacher'],
                    'export' => ['admin', 'principal', 'teacher']
                ],
                'fee' => [
                    'collect' => ['admin', 'principal', 'accountant'],
                    'refund' => ['admin', 'principal'],
                    'view' => ['admin', 'principal', 'accountant', 'student', 'parent'],
                    'report' => ['admin', 'principal', 'accountant'],
                    'export' => ['admin', 'principal', 'accountant']
                ]
            ];

            $modulePermissions = $permissionRules[$module] ?? [];
            $allowedRoles = $modulePermissions[$permission] ?? [];
            
            return $user->hasAnyRole($allowedRoles);
        });
    }

    /**
     * Get rate limit for specific module
     */
    private function getModuleRateLimit(string $module): int
    {
        $rateLimits = [
            'student' => 100,
            'teacher' => 100,
            'attendance' => 200,
            'exam' => 100,
            'fee' => 50,
            'library' => 150,
            'transport' => 100,
            'hostel' => 100,
            'inventory' => 80,
            'hr' => 60,
            'communication' => 300,
            'report' => 50,
            'setting' => 30,
            'user_management' => 40,
            'system' => 20
        ];

        return $rateLimits[$module] ?? 60;
    }

    /**
     * Get user's permissions for a specific module
     */
    private function getUserModulePermissions($user, string $module): array
    {
        $cacheKey = "user_module_permissions:{$user->id}:{$module}";
        
        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($user, $module) {
            if ($user->hasRole('super_admin')) {
                return ['*']; // All permissions
            }

            $permissions = [];
            $allPermissions = ['create', 'edit', 'delete', 'view', 'bulk_action', 'export', 'import', 'mark', 'collect', 'refund', 'report'];
            
            foreach ($allPermissions as $permission) {
                if ($this->hasSpecificPermission($user, $module, $permission)) {
                    $permissions[] = $permission;
                }
            }
            
            return $permissions;
        });
    }
}