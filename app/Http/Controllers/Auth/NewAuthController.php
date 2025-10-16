<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\NewUser;
use App\Models\NewRole;
use App\Models\UserSession;
use App\Models\UserActivity;
use App\Models\UserRoleAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class NewAuthController extends Controller
{
    /**
     * Show the login form
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return $this->redirectToDashboard();
        }

        return view('auth.login');
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        // Check rate limiting
        if (RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            $seconds = RateLimiter::availableIn($this->throttleKey($request));
            
            UserActivity::logSecurityEvent(
                'rate_limit_exceeded',
                "Too many login attempts from IP: {$request->ip()}",
                ['ip_address' => $request->ip(), 'lockout_seconds' => $seconds]
            );

            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }

        $credentials = $this->credentials($request);
        $user = $this->findUser($credentials);

        // Log failed attempt if user not found
        if (!$user) {
            $this->recordFailedAttempt($request, null, 'user_not_found');
            RateLimiter::hit($this->throttleKey($request));
            
            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        // Check if user account is active and not locked
        if (!$this->isUserAccountValid($user)) {
            $this->recordFailedAttempt($request, $user->id, 'account_locked_or_inactive');
            RateLimiter::hit($this->throttleKey($request));
            
            throw ValidationException::withMessages([
                'email' => $this->getAccountStatusMessage($user),
            ]);
        }

        // Verify password
        if (!Hash::check($credentials['password'], $user->password)) {
            $this->recordFailedAttempt($request, $user->id, 'invalid_password');
            $user->recordLoginAttempt(false, $request->ip(), $request->userAgent());
            RateLimiter::hit($this->throttleKey($request));
            
            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        // Check if password needs to be changed
        if ($user->mustChangePassword()) {
            session(['user_id_for_password_change' => $user->id]);
            return redirect()->route('password.change.form')
                           ->with('warning', 'You must change your password before continuing.');
        }

        // Successful login
        $this->performLogin($request, $user);
        
        RateLimiter::clear($this->throttleKey($request));

        return $this->redirectToDashboard();
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        $user = Auth::user();
        
        if ($user) {
            // End current session
            $this->endCurrentSession($request, 'user_logout');
            
            // Log logout activity
            UserActivity::logLogout($user->id, 'user_logout');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('success', 'You have been logged out successfully.');
    }

    /**
     * Show password change form
     */
    public function showPasswordChangeForm()
    {
        $userId = session('user_id_for_password_change') ?? auth()->id();
        
        if (!$userId) {
            return redirect()->route('login');
        }

        $user = NewUser::find($userId);
        
        if (!$user) {
            return redirect()->route('login');
        }

        return view('auth.change-password', compact('user'));
    }

    /**
     * Handle password change request
     */
    public function changePassword(Request $request)
    {
        $userId = session('user_id_for_password_change') ?? auth()->id();
        $user = NewUser::find($userId);

        if (!$user) {
            return redirect()->route('login');
        }

        $validator = Validator::make($request->all(), [
            'current_password' => auth()->check() ? 'required|string' : 'nullable',
            'password' => [
                'required',
                'string',
                'min:' . NewUser::MIN_PASSWORD_LENGTH,
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
            ],
        ], [
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Verify current password if user is logged in
        if (auth()->check() && !Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        // Change password
        $user->changePassword($request->password);
        
        // Log password change
        UserActivity::logPasswordChange($user->id, !auth()->check());

        // Clear session flag
        session()->forget('user_id_for_password_change');

        // If user wasn't logged in, log them in now and regenerate session
        if (!auth()->check()) {
            Auth::login($user);
            $request->session()->regenerate();
            $this->createUserSession($request, $user);
        }

        return $this->redirectToDashboard()
                   ->with('success', 'Password changed successfully.');
    }

    /**
     * Show password reset request form
     */
    public function showPasswordResetForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle password reset request
     */
    public function sendPasswordResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = NewUser::where('email', $request->email)->first();

        if ($user) {
            // Generate reset token
            $token = Str::random(64);
            
            // Store reset token (you might want to create a password_reset_tokens table)
            $user->setPreference('password_reset_token', $token);
            $user->setPreference('password_reset_expires', now()->addHours(1)->timestamp);
            
            // Send reset email (implement your email sending logic here)
            // Mail::to($user->email)->send(new PasswordResetMail($user, $token));
            
            UserActivity::logActivity([
                'user_id' => $user->id,
                'activity_type' => UserActivity::TYPE_PASSWORD_RESET,
                'activity_description' => 'Password reset link requested',
            ]);
        }

        // Always return success message for security
        return back()->with('success', 'If an account with that email exists, a password reset link has been sent.');
    }

    /**
     * Show password reset form
     */
    public function showPasswordResetFormWithToken($token)
    {
        $user = NewUser::whereJsonContains('preferences->password_reset_token', $token)->first();

        if (!$user || !$this->isValidResetToken($user, $token)) {
            return redirect()->route('login')->with('error', 'Invalid or expired reset token.');
        }

        return view('auth.reset-password', compact('token', 'user'));
    }

    /**
     * Handle password reset
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'password' => [
                'required',
                'string',
                'min:' . NewUser::MIN_PASSWORD_LENGTH,
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
            ],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = NewUser::whereJsonContains('preferences->password_reset_token', $request->token)->first();

        if (!$user || !$this->isValidResetToken($user, $request->token)) {
            return redirect()->route('login')->with('error', 'Invalid or expired reset token.');
        }

        // Reset password
        $user->changePassword($request->password, true);
        
        // Clear reset token
        $user->setPreference('password_reset_token', null);
        $user->setPreference('password_reset_expires', null);

        // Log password reset
        UserActivity::logActivity([
            'user_id' => $user->id,
            'activity_type' => UserActivity::TYPE_PASSWORD_RESET,
            'activity_description' => 'Password reset completed',
        ]);

        return redirect()->route('login')->with('success', 'Password reset successfully. Please log in with your new password.');
    }

    /**
     * Validate login request
     */
    protected function validateLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);
    }

    /**
     * Get login credentials
     */
    protected function credentials(Request $request): array
    {
        $field = filter_var($request->email, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        
        return [
            $field => $request->email,
            'password' => $request->password,
        ];
    }

    /**
     * Find user by credentials
     */
    protected function findUser(array $credentials): ?NewUser
    {
        $field = array_key_first($credentials);
        
        return NewUser::where($field, $credentials[$field])->first();
    }

    /**
     * Check if user account is valid for login
     */
    protected function isUserAccountValid(NewUser $user): bool
    {
        // Block if locked or suspended
        if ($user->isLocked() || $user->isSuspended()) {
            return false;
        }

        // Allow if already active
        if ($user->isActive()) {
            return true;
        }

        // If pending verification, allow login if account is active.
        // The user will be redirected to the verification notice until they verify.
        if ($user->status === NewUser::STATUS_PENDING_VERIFICATION) {
            return (bool) $user->is_active;
        }

        return false;
    }

    /**
     * Get account status message
     */
    protected function getAccountStatusMessage(NewUser $user): string
    {
        if ($user->isLocked()) {
            if ($user->locked_until && $user->locked_until->isFuture()) {
                $minutes = $user->locked_until->diffInMinutes(now());
                return "Account is temporarily locked. Try again in {$minutes} minutes.";
            }
            return 'Account is locked. Please contact administrator.';
        }

        if ($user->isSuspended()) {
            return 'Account is suspended. Please contact administrator.';
        }

        if ($user->status === NewUser::STATUS_PENDING_VERIFICATION) {
            if (!$user->hasVerifiedEmail()) {
                return 'Please verify your email to activate your account';
            }
            // Email verified but not activated or inactive
            if (!$user->is_active) {
                return 'Account is inactive. Please contact administrator.';
            }
        }

        if (!$user->isActive()) {
            return 'Account is inactive. Please contact administrator.';
        }

        return 'Account access denied.';
    }

    /**
     * Record failed login attempt
     */
    protected function recordFailedAttempt(Request $request, ?int $userId, string $reason)
    {
        UserActivity::logActivity([
            'user_id' => $userId,
            'activity_type' => UserActivity::TYPE_LOGIN_FAILED,
            'activity_description' => "Failed login attempt: {$reason}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'additional_data' => [
                'reason' => $reason,
                'attempted_email' => $request->email,
            ]
        ]);
    }

    /**
     * Perform successful login
     */
    protected function performLogin(Request $request, NewUser $user)
    {
        // Log successful login
        $user->recordLoginAttempt(true, $request->ip(), $request->userAgent());
        UserActivity::logLogin($user->id, true);

        // If user was pending verification and email is verified, activate now
        if ($user->status === NewUser::STATUS_PENDING_VERIFICATION && $user->hasVerifiedEmail() && $user->is_active) {
            $user->activateAccount();
        }

        // Login user and prevent session fixation
        Auth::login($user, $request->filled('remember'));
        $request->session()->regenerate();

        // Create user session record with the regenerated session ID
        $this->createUserSession($request, $user);
    }

    /**
     * Create user session record
     */
    protected function createUserSession(Request $request, NewUser $user)
    {
        UserSession::createSession([
            'user_id' => $user->id,
            'session_id' => session()->getId(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'login_method' => UserSession::LOGIN_METHOD_PASSWORD,
        ]);
    }

    /**
     * End current user session
     */
    protected function endCurrentSession(Request $request, string $reason)
    {
        $session = UserSession::where('session_id', session()->getId())
                             ->where('is_active', true)
                             ->first();

        if ($session) {
            UserSession::endSession($session->session_id, $reason);
        }
    }

    /**
     * Redirect to appropriate dashboard based on user role
     */
    protected function redirectToDashboard()
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        // Normalize to NewUser model if legacy User is returned by the guard
        if (!($user instanceof NewUser)) {
            $normalized = NewUser::find($user->id);
            if ($normalized) {
                $user = $normalized;
            }
        }

        // If user is pending and hasn't verified email, send them to the verification notice page
        if ($user instanceof NewUser && $user->status === NewUser::STATUS_PENDING_VERIFICATION && !$user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        $primaryRole = ($user instanceof NewUser) ? $user->getPrimaryRole() : null;
        
        if (!$primaryRole) {
            return redirect()->route('dashboard.default');
        }

        // Redirect based on role hierarchy
        switch ($primaryRole->name) {
            case NewRole::SUPER_ADMIN:
                return redirect()->route('dashboard.super-admin');
            
            case NewRole::ADMIN:
                return redirect()->route('dashboard.admin');
            
            case NewRole::PRINCIPAL:
                return redirect()->route('dashboard.principal');
            
            case NewRole::TEACHER:
                return redirect()->route('dashboard.teacher');
            
            case NewRole::STUDENT:
                return redirect()->route('dashboard.student');
            
            case NewRole::PARENT:
                return redirect()->route('dashboard.parent');
            
            default:
                return redirect()->route('dashboard.default');
        }
    }

    /**
     * Get throttle key for rate limiting
     */
    protected function throttleKey(Request $request): string
    {
        return Str::lower($request->input('email')) . '|' . $request->ip();
    }

    /**
     * Check if reset token is valid
     */
    protected function isValidResetToken(NewUser $user, string $token): bool
    {
        $storedToken = $user->getPreference('password_reset_token');
        $expiresAt = $user->getPreference('password_reset_expires');

        if (!$storedToken || !$expiresAt) {
            return false;
        }

        if ($storedToken !== $token) {
            return false;
        }

        if (now()->timestamp > $expiresAt) {
            return false;
        }

        return true;
    }

    /**
     * Get user info for API
     */
    public function user(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'roles' => $user->getActiveRoles(),
                'permissions' => $user->getAllPermissions(),
                'primary_role' => $user->getPrimaryRole(),
                'must_change_password' => $user->mustChangePassword(),
                'two_factor_enabled' => $user->hasTwoFactorEnabled(),
                'last_login_at' => $user->last_login_at,
            ]
        ]);
    }

    /**
     * Check authentication status
     */
    public function check()
    {
        return response()->json([
            'authenticated' => Auth::check(),
            'user' => Auth::check() ? Auth::user()->only(['id', 'name', 'email']) : null,
        ]);
    }
}