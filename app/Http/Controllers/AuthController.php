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

        // Start new user session (only if session is available)
        $sessionId = null;
        try {
            $sessionId = $request->session()->getId();
        } catch (\Exception $e) {
            // Session not available in API context, use a default
            $sessionId = 'api-session-' . time();
        }
        
        $sessionData = UserSession::startSession(
            $user->id,
            $sessionId,
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

    // -----------------------------
    // REGISTER
    // -----------------------------
    public function register(Request $request)
    {
        // Basic validation for registration
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email:rfc,dns|max:255|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        // Use NewUser model to support email verification while using same users table
        try {
            $userModelClass = \App\Models\NewUser::class;
            /** @var \App\Models\NewUser $user */
            $user = $userModelClass::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'status' => defined($userModelClass.'::STATUS_PENDING_VERIFICATION') ? $userModelClass::STATUS_PENDING_VERIFICATION : 'pending_verification',
                'is_active' => true,
            ]);
        } catch (\Throwable $e) {
            // Fallback to legacy User model if NewUser fails
            Log::warning('NewUser registration failed, falling back to User model: '.$e->getMessage());
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => config('auth.default_role', 'student'),
            ]);
        }

        // Attempt to send email verification if supported
        try {
            if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && method_exists($user, 'sendEmailVerificationNotification')) {
                $user->sendEmailVerificationNotification();
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to send verification email: '.$e->getMessage());
        }

        // Log registration in audit trail (if available)
        try {
            AuditTrail::logActivity(
                $user,
                'registration_success',
                [],
                [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
                ['tags' => ['authentication', 'registration', 'api']]
            );
        } catch (\Throwable $e) {
            Log::debug('AuditTrail logActivity failed on register: '.$e->getMessage());
        }

        return response()->json([
            'message' => 'Registration successful. Please verify your email to activate your account.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ], 201);
    }
}
