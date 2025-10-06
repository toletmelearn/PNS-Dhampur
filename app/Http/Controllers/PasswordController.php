<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Rules\PasswordComplexity;
use App\Rules\PasswordHistory;
use App\Models\AuditTrail;

class PasswordController extends Controller
{
    /**
     * Show the password change form.
     */
    public function showChangeForm()
    {
        $user = Auth::user();
        
        return view('auth.change-password', [
            'user' => $user,
            'isExpired' => $user->isPasswordExpired(),
            'isExpiringSoon' => $user->isPasswordExpiringSoon(),
            'daysUntilExpiration' => $user->getDaysUntilPasswordExpires(),
            'passwordResetRequired' => $user->password_reset_required
        ]);
    }

    /**
     * Handle password change request.
     */
    public function changePassword(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'current_password' => 'required|string',
            'password' => [
                'required',
                'string',
                'confirmed',
                new PasswordComplexity($user->role),
                new PasswordHistory($user->id)
            ],
        ], [
            'current_password.required' => 'Current password is required.',
            'password.required' => 'New password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors([
                'current_password' => 'The current password is incorrect.'
            ]);
        }

        // Update password using the model method
        $user->updatePassword($request->password);
        
        // Clear password reset requirement
        $user->password_reset_required = false;
        $user->save();

        // Log password change
        AuditTrail::logActivity(
            $user,
            'password_changed',
            [],
            [
                'changed_by' => 'user',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ],
            ['tags' => ['security', 'password', 'user_action']]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Password changed successfully.',
                'password_expires_at' => $user->password_expires_at
            ]);
        }

        return redirect()->route('dashboard')
            ->with('success', 'Password changed successfully.');
    }

    /**
     * Force password reset for a user (admin only).
     */
    public function forcePasswordReset(Request $request, $userId)
    {
        $admin = Auth::user();
        
        // Check if user has permission to force password reset
        if (!$admin->hasPermission('manage_users')) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $user = \App\Models\User::findOrFail($userId);
        
        $user->forcePasswordReset();

        // Log admin action
        AuditTrail::logActivity(
            $admin,
            'force_password_reset',
            ['user_id' => $user->id],
            [
                'target_user' => $user->email,
                'admin_action' => true,
                'ip_address' => $request->ip()
            ],
            ['tags' => ['admin', 'security', 'password_reset']]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Password reset has been forced for the user.',
                'user' => $user->only(['id', 'name', 'email', 'password_reset_required'])
            ]);
        }

        return back()->with('success', 'Password reset has been forced for ' . $user->name);
    }

    /**
     * Get password policy information.
     */
    public function getPasswordPolicy()
    {
        $user = Auth::user();
        $policy = config('password_policy');
        
        // Get role-specific policy
        $rolePolicy = $policy['role_specific'][$user->role] ?? [];
        
        return response()->json([
            'complexity' => array_merge($policy['complexity'], $rolePolicy['complexity'] ?? []),
            'expiration' => array_merge($policy['expiration'], $rolePolicy['expiration'] ?? []),
            'history' => array_merge($policy['history'], $rolePolicy['history'] ?? []),
            'user_role' => $user->role,
            'password_expires_at' => $user->password_expires_at,
            'is_expired' => $user->isPasswordExpired(),
            'is_expiring_soon' => $user->isPasswordExpiringSoon(),
            'days_until_expiration' => $user->getDaysUntilPasswordExpires()
        ]);
    }
}