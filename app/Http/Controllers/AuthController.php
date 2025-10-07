<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use App\Models\User;
use App\Models\UserSession;
use App\Models\AuditTrail;
use App\Http\Traits\EmailValidationTrait;

class AuthController extends Controller
{
    use EmailValidationTrait;
    // -----------------------------
    // LOGIN
    // -----------------------------
    public function login(Request $request)
    {
        $request->validate(array_merge(
            $this->getSimpleEmailValidationRules(),
            ['password' => 'required|string']
        ), $this->getEmailValidationMessages());

        $user = User::where('email', $request->email)->first();

        // Check if user exists and account is not locked
        if ($user && $user->isLocked()) {
            $lockoutDuration = config('password_policy.lockout.lockout_duration', 30);
            $remainingMinutes = now()->diffInMinutes($user->locked_until);
            
            return response()->json([
                'message' => "Account is locked due to too many failed login attempts. Try again in {$remainingMinutes} minutes.",
                'locked_until' => $user->locked_until
            ], 423); // 423 Locked
        }

        if (!$user || !Hash::check($request->password, $user->password)) {
            // If user exists, increment failed attempts
            if ($user) {
                $user->incrementFailedAttempts();
            }
            
            // Log failed login attempt - create a dummy user object for logging
            $dummyUser = new User(['email' => $request->email]);
            $dummyUser->id = 0; // Set a dummy ID
            
            AuditTrail::logActivity(
                $dummyUser,
                'login_failed',
                [],
                ['email' => $request->email],
                ['tags' => ['authentication', 'security', 'failed_login']]
            );

            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Reset failed attempts on successful login
        $user->resetFailedAttempts();

        // Set session security markers for RoleMiddleware validation
        Session::put('login_time', Carbon::now()->toDateTimeString());
        Session::put('user_agent_hash', hash('sha256', $request->userAgent() ?? ''));

        // End any existing active sessions for this user
        UserSession::endUserSessions($user->id, 'new_login');

        // Revoke all old tokens
        $user->tokens()->delete();

        // Create new Sanctum token
        $token = $user->createToken('api-token')->plainTextToken;

        // Start new user session
        $sessionData = UserSession::startSession(
            $user->id,
            $request->session()->getId(),
            $request->ip(),
            $request->userAgent(),
            'api_token'
        );

        // Log successful login
        AuditTrail::logActivity(
            $user,
            'login_success',
            [],
            [
                'login_method' => 'api_token',
                'session_id' => $sessionData->session_id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ],
            ['tags' => ['authentication', 'login', 'api']]
        );

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
            'session_id' => $sessionData->id
        ]);
    }

    // -----------------------------
    // LOGOUT
    // -----------------------------
    public function logout(Request $request)
    {
        $user = $request->user();
        
        // End user session
        if ($user) {
            UserSession::endSession($request->session()->getId(), 'manual_logout');
            
            // Log logout
            AuditTrail::logActivity(
                $user,
                'logout',
                [],
                [
                    'logout_method' => 'manual',
                    'session_id' => $request->session()->getId()
                ],
                ['tags' => ['authentication', 'logout', 'api']]
            );
        }

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    // -----------------------------
    // CURRENT USER
    // -----------------------------
    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user()
        ]);
    }
}
