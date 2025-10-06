<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class CheckPasswordExpiration
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

        // Skip password expiration check for certain routes
        $exemptRoutes = [
            'password.change',
            'password.update',
            'logout',
            'password.expired',
        ];

        if (in_array($request->route()->getName(), $exemptRoutes)) {
            return $next($request);
        }

        // Check if password reset is required
        if ($user->password_reset_required) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Password reset required',
                    'redirect' => route('password.change')
                ], 403);
            }
            
            return redirect()->route('password.change')
                ->with('warning', 'You must change your password to continue.');
        }

        // Check if password has expired
        if ($user->isPasswordExpired()) {
            $gracePeriod = config('password_policy.expiration.grace_period_days', 3);
            $expiredDays = abs($user->getDaysUntilPasswordExpires());
            
            if ($expiredDays > $gracePeriod) {
                // Force password change after grace period
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Password has expired and must be changed',
                        'redirect' => route('password.change')
                    ], 403);
                }
                
                return redirect()->route('password.change')
                    ->with('error', 'Your password has expired and must be changed.');
            } else {
                // Show warning during grace period
                session()->flash('warning', "Your password expired {$expiredDays} days ago. You have " . ($gracePeriod - $expiredDays) . " days remaining to change it.");
            }
        }

        // Check if password is expiring soon
        if ($user->isPasswordExpiringSoon()) {
            $daysLeft = $user->getDaysUntilPasswordExpires();
            session()->flash('info', "Your password will expire in {$daysLeft} days. Please change it soon.");
        }

        return $next($request);
    }
}