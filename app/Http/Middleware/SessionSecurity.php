<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\UserSession;
use App\Models\UserActivity;
use Carbon\Carbon;

class SessionSecurity
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
        // Skip for unauthenticated users
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        $sessionId = session()->getId();

        // Find current session record
        $currentSession = UserSession::where('session_id', $sessionId)
                                   ->where('user_id', $user->id)
                                   ->where('is_active', true)
                                   ->first();

        // If no session record exists, create one
        if (!$currentSession) {
            $currentSession = $this->createSessionRecord($request, $user, $sessionId);
        }

        // Validate session security
        if (!$this->validateSessionSecurity($request, $user, $currentSession)) {
            return $this->handleSecurityViolation($request, $user, $currentSession);
        }

        // Update session activity
        $this->updateSessionActivity($currentSession, $request);

        // Check for concurrent sessions if enabled
        if ($this->shouldCheckConcurrentSessions($user)) {
            $this->manageConcurrentSessions($user, $currentSession);
        }

        // Check session timeout
        if ($this->isSessionExpired($currentSession)) {
            return $this->handleSessionTimeout($request, $user, $currentSession);
        }

        return $next($request);
    }

    /**
     * Create session record
     */
    protected function createSessionRecord(Request $request, $user, string $sessionId): UserSession
    {
        return UserSession::createSession([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'login_method' => UserSession::LOGIN_METHOD_PASSWORD,
        ]);
    }

    /**
     * Validate session security
     */
    protected function validateSessionSecurity(Request $request, $user, UserSession $session): bool
    {
        // Check IP address consistency (if enabled)
        if ($this->shouldValidateIpAddress($user) && $session->ip_address !== $request->ip()) {
            $this->logSecurityEvent($user, 'ip_address_mismatch', [
                'original_ip' => $session->ip_address,
                'current_ip' => $request->ip(),
                'session_id' => $session->session_id,
            ]);
            return false;
        }

        // Check user agent consistency (basic check)
        if ($this->shouldValidateUserAgent($user) && $this->hasSignificantUserAgentChange($session, $request)) {
            $this->logSecurityEvent($user, 'user_agent_change', [
                'original_user_agent' => $session->user_agent,
                'current_user_agent' => $request->userAgent(),
                'session_id' => $session->session_id,
            ]);
            return false;
        }

        // Check if session is marked as compromised (stored in additional_data)
        $sessionData = $session->additional_data ?? [];
        if (!empty($sessionData['is_compromised'])) {
            $this->logSecurityEvent($user, 'compromised_session_access', [
                'session_id' => $session->session_id,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Update session activity
     */
    protected function updateSessionActivity(UserSession $session, Request $request)
    {
        $additional = $session->additional_data ?? [];
        $additional['page_views'] = ($additional['page_views'] ?? 0) + 1;
        $additional['last_route'] = $request->route()->getName();
        $additional['last_url'] = $request->fullUrl();
        $additional['last_method'] = $request->method();

        $session->update([
            'last_activity' => now(),
            'additional_data' => $additional,
        ]);
    }

    /**
     * Check if should validate IP address
     */
    protected function shouldValidateIpAddress($user): bool
    {
        // Check user preference or system setting
        return $user->getPreference('strict_ip_validation', false) || 
               config('auth.strict_ip_validation', false);
    }

    /**
     * Check if should validate user agent
     */
    protected function shouldValidateUserAgent($user): bool
    {
        return $user->getPreference('validate_user_agent', true) || 
               config('auth.validate_user_agent', true);
    }

    /**
     * Check for significant user agent changes
     */
    protected function hasSignificantUserAgentChange(UserSession $session, Request $request): bool
    {
        $originalAgent = $session->user_agent;
        $currentAgent = $request->userAgent();

        if (!$originalAgent || !$currentAgent) {
            return true;
        }

        // Extract browser and platform info
        $originalInfo = $this->parseUserAgent($originalAgent);
        $currentInfo = $this->parseUserAgent($currentAgent);

        // Check for significant changes (different browser or platform)
        return $originalInfo['browser'] !== $currentInfo['browser'] || 
               $originalInfo['platform'] !== $currentInfo['platform'];
    }

    /**
     * Parse user agent for basic info
     */
    protected function parseUserAgent(string $userAgent): array
    {
        $browser = 'unknown';
        $platform = 'unknown';

        // Basic browser detection
        if (str_contains($userAgent, 'Chrome')) {
            $browser = 'Chrome';
        } elseif (str_contains($userAgent, 'Firefox')) {
            $browser = 'Firefox';
        } elseif (str_contains($userAgent, 'Safari')) {
            $browser = 'Safari';
        } elseif (str_contains($userAgent, 'Edge')) {
            $browser = 'Edge';
        }

        // Basic platform detection
        if (str_contains($userAgent, 'Windows')) {
            $platform = 'Windows';
        } elseif (str_contains($userAgent, 'Mac')) {
            $platform = 'Mac';
        } elseif (str_contains($userAgent, 'Linux')) {
            $platform = 'Linux';
        } elseif (str_contains($userAgent, 'Android')) {
            $platform = 'Android';
        } elseif (str_contains($userAgent, 'iOS')) {
            $platform = 'iOS';
        }

        return compact('browser', 'platform');
    }

    /**
     * Check if should manage concurrent sessions
     */
    protected function shouldCheckConcurrentSessions($user): bool
    {
        return $user->getPreference('limit_concurrent_sessions', true) || 
               config('auth.limit_concurrent_sessions', true);
    }

    /**
     * Manage concurrent sessions
     */
    protected function manageConcurrentSessions($user, UserSession $currentSession)
    {
        $maxSessions = $user->getPreference('max_concurrent_sessions', 3);
        
        $activeSessions = UserSession::where('user_id', $user->id)
                                   ->where('is_active', true)
                                   ->where('id', '!=', $currentSession->id)
                                   ->orderBy('last_activity', 'desc')
                                   ->get();

        if ($activeSessions->count() >= $maxSessions) {
            // End oldest sessions
            $sessionsToEnd = $activeSessions->skip($maxSessions - 1);
            
            foreach ($sessionsToEnd as $session) {
                UserSession::endSession($session->session_id, UserSession::LOGOUT_REASON_CONCURRENT_LIMIT);
                
                $this->logSecurityEvent($user, 'session_ended_concurrent_limit', [
                    'ended_session_id' => $session->session_id,
                    'current_session_id' => $currentSession->session_id,
                ]);
            }
        }
    }

    /**
     * Check if session is expired
     */
    protected function isSessionExpired(UserSession $session): bool
    {
        $sessionTimeout = config('session.lifetime', 120); // minutes
        $lastActivity = $session->last_activity ?? $session->login_at;
        
        return $lastActivity->addMinutes($sessionTimeout)->isPast();
    }

    /**
     * Handle session timeout
     */
    protected function handleSessionTimeout(Request $request, $user, UserSession $session)
    {
        UserSession::endSession($session->session_id, UserSession::LOGOUT_REASON_TIMEOUT);
        
        $this->logSecurityEvent($user, 'session_timeout', [
            'session_id' => $session->session_id,
            'last_activity' => $session->last_activity,
        ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Session expired',
                'redirect' => route('login')
            ], 401);
        }

        return redirect()->route('login')
                        ->with('warning', 'Your session has expired. Please log in again.');
    }

    /**
     * Handle security violation
     */
    protected function handleSecurityViolation(Request $request, $user, UserSession $session)
    {
        // Mark session as compromised via additional_data
        $session->update([
            'logout_at' => now(),
            'logout_reason' => UserSession::LOGOUT_REASON_SECURITY_VIOLATION,
            'is_active' => false,
            'additional_data' => array_merge($session->additional_data ?? [], [
                'is_compromised' => true,
            ]),
        ]);

        // End all user sessions for security
        UserSession::where('user_id', $user->id)
                  ->where('is_active', true)
                  ->update([
                      'is_active' => false,
                      'logout_at' => now(),
                      'logout_reason' => UserSession::LOGOUT_REASON_SECURITY_VIOLATION,
                  ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Security violation detected',
                'redirect' => route('login')
            ], 401);
        }

        return redirect()->route('login')
                        ->with('error', 'Security violation detected. Please log in again.');
    }

    /**
     * Log security event
     */
    protected function logSecurityEvent($user, string $eventType, array $data = [])
    {
        UserActivity::logActivity([
            'user_id' => $user->id,
            'activity_type' => UserActivity::TYPE_SECURITY_EVENT,
            'activity_description' => "Security event: {$eventType}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'additional_data' => array_merge([
                'event_type' => $eventType,
                'timestamp' => now()->toISOString(),
            ], $data)
        ]);
    }
}