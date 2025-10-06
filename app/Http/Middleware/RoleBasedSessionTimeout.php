<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

class RoleBasedSessionTimeout
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
            $sessionTimeout = $this->getSessionTimeoutForRole($user->role);
            
            // Check if session has timed out based on role
            $lastActivity = Session::get('last_activity');
            
            if ($lastActivity) {
                $timeSinceLastActivity = Carbon::now()->diffInMinutes(Carbon::parse($lastActivity));
                
                if ($timeSinceLastActivity > $sessionTimeout) {
                    // Log the session timeout
                    \Log::info('Session timeout for user', [
                        'user_id' => $user->id,
                        'role' => $user->role,
                        'timeout_minutes' => $sessionTimeout,
                        'last_activity' => $lastActivity
                    ]);
                    
                    Auth::logout();
                    Session::flush();
                    Session::regenerate();
                    
                    if ($request->expectsJson()) {
                        return response()->json([
                            'message' => 'Session expired due to inactivity',
                            'timeout_minutes' => $sessionTimeout
                        ], 401);
                    }
                    
                    return redirect()->route('login')
                        ->with('error', "Your session has expired after {$sessionTimeout} minutes of inactivity. Please log in again.");
                }
            }
            
            // Update last activity timestamp
            Session::put('last_activity', Carbon::now()->toDateTimeString());
            Session::put('session_timeout', $sessionTimeout);
            
            // Set dynamic session lifetime based on role
            config(['session.lifetime' => $sessionTimeout]);
        }
        
        return $next($request);
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
            'super_admin' => 15,    // 15 minutes for super admin (highest security)
            'admin' => 20,          // 20 minutes for admin
            'principal' => 25,      // 25 minutes for principal
            'vice_principal' => 30, // 30 minutes for vice principal
            'teacher' => 45,        // 45 minutes for teachers
            'class_teacher' => 45,  // 45 minutes for class teachers
            'accountant' => 30,     // 30 minutes for accountant (financial access)
            'librarian' => 60,      // 60 minutes for librarian
            'receptionist' => 60,   // 60 minutes for receptionist
            'student' => 120,       // 120 minutes for students (lowest privilege)
        ];
        
        return $timeouts[$role] ?? 30; // Default to 30 minutes if role not found
    }
    
    /**
     * Get session security level based on role
     *
     * @param string $role
     * @return array
     */
    private function getSecuritySettingsForRole($role)
    {
        $highSecurityRoles = ['super_admin', 'admin', 'principal', 'accountant'];
        $mediumSecurityRoles = ['vice_principal', 'teacher', 'class_teacher'];
        
        if (in_array($role, $highSecurityRoles)) {
            return [
                'expire_on_close' => true,
                'secure_cookie' => true,
                'http_only' => true,
                'same_site' => 'strict'
            ];
        } elseif (in_array($role, $mediumSecurityRoles)) {
            return [
                'expire_on_close' => true,
                'secure_cookie' => env('APP_ENV') === 'production',
                'http_only' => true,
                'same_site' => 'strict'
            ];
        } else {
            return [
                'expire_on_close' => false,
                'secure_cookie' => env('APP_ENV') === 'production',
                'http_only' => true,
                'same_site' => 'lax'
            ];
        }
    }
}