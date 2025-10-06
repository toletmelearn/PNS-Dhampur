<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckAccountLockout
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
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Check if account is locked
        if ($user->isLocked()) {
            $lockoutDuration = config('password_policy.lockout.lockout_duration', 30);
            $remainingMinutes = now()->diffInMinutes($user->locked_until);
            
            Auth::logout();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "Account is locked due to too many failed login attempts. Try again in {$remainingMinutes} minutes.",
                    'locked_until' => $user->locked_until,
                    'remaining_minutes' => $remainingMinutes
                ], 423); // 423 Locked
            }
            
            return redirect()->route('login')
                ->with('error', "Account is locked due to too many failed login attempts. Try again in {$remainingMinutes} minutes.");
        }

        return $next($request);
    }
}