<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

class SessionController extends Controller
{
    /**
     * Get current session information
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSessionInfo(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        $user = Auth::user();
        $lastActivity = Session::get('last_activity');
        $sessionTimeout = Session::get('session_timeout', 30);
        
        $timeRemaining = 0;
        if ($lastActivity) {
            $timeSinceLastActivity = Carbon::now()->diffInMinutes(Carbon::parse($lastActivity));
            $timeRemaining = max(0, $sessionTimeout - $timeSinceLastActivity);
        }

        return response()->json([
            'user_id' => $user->id,
            'role' => $user->role,
            'session_timeout_minutes' => $sessionTimeout,
            'last_activity' => $lastActivity,
            'time_remaining_minutes' => $timeRemaining,
            'expires_on_close' => config('session.expire_on_close'),
            'secure_cookie' => config('session.secure'),
            'http_only' => config('session.http_only'),
            'same_site' => config('session.same_site')
        ]);
    }

    /**
     * Extend current session (refresh activity)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function extendSession(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        // Update last activity timestamp
        Session::put('last_activity', Carbon::now()->toDateTimeString());
        
        $user = Auth::user();
        $sessionTimeout = Session::get('session_timeout', 30);

        return response()->json([
            'message' => 'Session extended successfully',
            'last_activity' => Session::get('last_activity'),
            'timeout_minutes' => $sessionTimeout,
            'user_role' => $user->role
        ]);
    }

    /**
     * Get session timeout warning (called via AJAX)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTimeoutWarning(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        $lastActivity = Session::get('last_activity');
        $sessionTimeout = Session::get('session_timeout', 30);
        $warningThreshold = max(5, $sessionTimeout * 0.2); // Warning at 20% of timeout or 5 minutes minimum
        
        if ($lastActivity) {
            $timeSinceLastActivity = Carbon::now()->diffInMinutes(Carbon::parse($lastActivity));
            $timeRemaining = max(0, $sessionTimeout - $timeSinceLastActivity);
            
            if ($timeRemaining <= $warningThreshold && $timeRemaining > 0) {
                return response()->json([
                    'warning' => true,
                    'time_remaining' => $timeRemaining,
                    'message' => "Your session will expire in {$timeRemaining} minutes due to inactivity."
                ]);
            }
        }

        return response()->json(['warning' => false]);
    }

    /**
     * Force logout (admin function)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function forceLogout(Request $request)
    {
        $user = Auth::user();
        
        // Log the forced logout
        \Log::info('Forced logout initiated', [
            'user_id' => $user->id,
            'role' => $user->role,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        Auth::logout();
        Session::flush();
        Session::regenerate();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Logged out successfully']);
        }

        return redirect()->route('login')->with('success', 'You have been logged out successfully.');
    }

    /**
     * Get role-based session policies
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSessionPolicies(Request $request)
    {
        $policies = [
            'super_admin' => [
                'timeout_minutes' => 15,
                'expire_on_close' => true,
                'security_level' => 'maximum'
            ],
            'admin' => [
                'timeout_minutes' => 20,
                'expire_on_close' => true,
                'security_level' => 'high'
            ],
            'principal' => [
                'timeout_minutes' => 25,
                'expire_on_close' => true,
                'security_level' => 'high'
            ],
            'vice_principal' => [
                'timeout_minutes' => 30,
                'expire_on_close' => true,
                'security_level' => 'medium'
            ],
            'accountant' => [
                'timeout_minutes' => 30,
                'expire_on_close' => true,
                'security_level' => 'high'
            ],
            'teacher' => [
                'timeout_minutes' => 45,
                'expire_on_close' => true,
                'security_level' => 'medium'
            ],
            'class_teacher' => [
                'timeout_minutes' => 45,
                'expire_on_close' => true,
                'security_level' => 'medium'
            ],
            'librarian' => [
                'timeout_minutes' => 60,
                'expire_on_close' => false,
                'security_level' => 'standard'
            ],
            'receptionist' => [
                'timeout_minutes' => 60,
                'expire_on_close' => false,
                'security_level' => 'standard'
            ],
            'student' => [
                'timeout_minutes' => 120,
                'expire_on_close' => false,
                'security_level' => 'standard'
            ]
        ];

        return response()->json([
            'policies' => $policies,
            'description' => 'Role-based session timeout and security policies'
        ]);
    }
}