<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class SecureSessionConfig
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
        if (Auth::check()) {
            $user = Auth::user();
            $securitySettings = $this->getSecuritySettingsForRole($user->role);
            
            // Apply dynamic session configuration based on user role
            Config::set('session.expire_on_close', $securitySettings['expire_on_close']);
            Config::set('session.secure', $securitySettings['secure_cookie']);
            Config::set('session.http_only', $securitySettings['http_only']);
            Config::set('session.same_site', $securitySettings['same_site']);
            
            // Log security configuration for audit purposes
            if (in_array($user->role, ['super_admin', 'admin'])) {
                \Log::info('Session security configuration applied', [
                    'user_id' => $user->id,
                    'role' => $user->role,
                    'settings' => $securitySettings,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);
            }
        }
        
        return $next($request);
    }
    
    /**
     * Get session security settings based on user role
     *
     * @param string $role
     * @return array
     */
    private function getSecuritySettingsForRole($role)
    {
        $highSecurityRoles = ['super_admin', 'admin', 'principal', 'accountant'];
        $mediumSecurityRoles = ['vice_principal', 'teacher', 'class_teacher'];
        $lowSecurityRoles = ['librarian', 'receptionist', 'student'];
        
        if (in_array($role, $highSecurityRoles)) {
            // Maximum security for administrative roles
            return [
                'expire_on_close' => true,
                'secure_cookie' => true, // Always secure for admin roles
                'http_only' => true,
                'same_site' => 'strict'
            ];
        } elseif (in_array($role, $mediumSecurityRoles)) {
            // Medium security for teaching staff
            return [
                'expire_on_close' => true,
                'secure_cookie' => env('APP_ENV') === 'production',
                'http_only' => true,
                'same_site' => 'strict'
            ];
        } elseif (in_array($role, $lowSecurityRoles)) {
            // Standard security for support staff and students
            return [
                'expire_on_close' => false,
                'secure_cookie' => env('APP_ENV') === 'production',
                'http_only' => true,
                'same_site' => 'lax'
            ];
        } else {
            // Default security settings for unknown roles
            return [
                'expire_on_close' => true,
                'secure_cookie' => env('APP_ENV') === 'production',
                'http_only' => true,
                'same_site' => 'strict'
            ];
        }
    }
    
    /**
     * Get session timeout in minutes based on user role
     *
     * @param string $role
     * @return int
     */
    private function getSessionTimeoutForRole($role)
    {
        $timeouts = [
            'super_admin' => 15,    // 15 minutes for super admin
            'admin' => 20,          // 20 minutes for admin
            'principal' => 25,      // 25 minutes for principal
            'vice_principal' => 30, // 30 minutes for vice principal
            'accountant' => 30,     // 30 minutes for accountant
            'teacher' => 45,        // 45 minutes for teachers
            'class_teacher' => 45,  // 45 minutes for class teachers
            'librarian' => 60,      // 60 minutes for librarian
            'receptionist' => 60,   // 60 minutes for receptionist
            'student' => 120,       // 120 minutes for students
        ];
        
        return $timeouts[$role] ?? 30; // Default to 30 minutes
    }
}