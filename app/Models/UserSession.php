<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use Jenssegers\Agent\Agent;

class UserSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'login_at',
        'logout_at',
        'last_activity',
        'login_method',
        'device_type',
        'browser',
        'platform',
        'location',
        'is_active',
        'logout_reason',
        'additional_data'
    ];

    protected $casts = [
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
        'last_activity' => 'datetime',
        'is_active' => 'boolean',
        'additional_data' => 'array'
    ];

    // Login method constants
    const LOGIN_METHOD_PASSWORD = 'password';
    const LOGIN_METHOD_TWO_FACTOR = 'two_factor';
    const LOGIN_METHOD_REMEMBER_TOKEN = 'remember_token';
    const LOGIN_METHOD_SSO = 'sso';
    const LOGIN_METHOD_API_TOKEN = 'api_token';

    // Device type constants
    const DEVICE_TYPE_DESKTOP = 'desktop';
    const DEVICE_TYPE_MOBILE = 'mobile';
    const DEVICE_TYPE_TABLET = 'tablet';
    const DEVICE_TYPE_UNKNOWN = 'unknown';

    // Logout reason constants
    const LOGOUT_REASON_USER = 'user_logout';
    const LOGOUT_REASON_TIMEOUT = 'session_timeout';
    const LOGOUT_REASON_ADMIN = 'admin_logout';
    const LOGOUT_REASON_SECURITY = 'security_logout';
    const LOGOUT_REASON_REVOKED = 'revoked_by_system';
    const LOGOUT_REASON_EXPIRED = 'session_expired';
    // Added for middleware references
    const LOGOUT_REASON_CONCURRENT_LIMIT = 'concurrent_limit';
    const LOGOUT_REASON_SECURITY_VIOLATION = 'security_violation';

    // Relationships
    public function user()
    {
        return $this->belongsTo(NewUser::class, 'user_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('login_at', '>=', now()->subDays($days));
    }

    public function scopeByLoginMethod($query, $method)
    {
        return $query->where('login_method', $method);
    }

    // Static methods for session management
    public static function startSession($user, $sessionId = null, $loginMethod = 'web')
    {
        $agent = new Agent();
        $sessionId = $sessionId ?: session()->getId();

        // Ensure $user is a User model instance, not just an ID
        if (is_numeric($user)) {
            $user = User::find($user);
        }

        // End any existing active sessions for this user if needed
        // self::where('user_id', $user->id)->where('is_active', true)->update(['is_active' => false, 'logout_reason' => 'new_session']);

        return self::create([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'login_at' => now(),
            'last_activity' => now(),
            'login_method' => $loginMethod,
            'device_type' => $agent->isMobile() ? 'mobile' : ($agent->isTablet() ? 'tablet' : 'desktop'),
            'browser' => $agent->browser(),
            'platform' => $agent->platform(),
            'is_active' => true,
            'additional_data' => [
                'device' => $agent->device(),
                'robot' => $agent->robot(),
                'languages' => $agent->languages(),
            ]
        ]);
    }

    /**
     * Create session from provided data (used by middleware/controllers)
     */
    public static function createSession(array $data): self
    {
        $agent = new Agent();
        $ua = $data['user_agent'] ?? Request::userAgent();
        if (method_exists($agent, 'setUserAgent')) {
            $agent->setUserAgent($ua);
        }

        return self::create([
            'user_id' => $data['user_id'],
            'session_id' => $data['session_id'] ?? session()->getId(),
            'ip_address' => $data['ip_address'] ?? Request::ip(),
            'user_agent' => $ua,
            'login_at' => now(),
            'last_activity' => now(),
            'login_method' => $data['login_method'] ?? self::LOGIN_METHOD_PASSWORD,
            'device_type' => $agent->isMobile() ? self::DEVICE_TYPE_MOBILE : ($agent->isTablet() ? self::DEVICE_TYPE_TABLET : self::DEVICE_TYPE_DESKTOP),
            'browser' => $agent->browser(),
            'platform' => $agent->platform(),
            'is_active' => true,
            'additional_data' => array_merge([
                'device' => $agent->device(),
                'robot' => $agent->robot(),
                'languages' => $agent->languages(),
            ], $data['additional_data'] ?? []),
        ]);
    }

    public static function endSession($sessionId, $reason = 'manual')
    {
        return self::where('session_id', $sessionId)
                  ->where('is_active', true)
                  ->update([
                      'logout_at' => now(),
                      'is_active' => false,
                      'logout_reason' => $reason
                  ]);
    }

    public static function updateActivity($sessionId)
    {
        return self::where('session_id', $sessionId)
                  ->where('is_active', true)
                  ->update(['last_activity' => now()]);
    }

    public static function endUserSessions($userId, $reason = 'forced')
    {
        return self::where('user_id', $userId)
                  ->where('is_active', true)
                  ->update([
                      'logout_at' => now(),
                      'is_active' => false,
                      'logout_reason' => $reason
                  ]);
    }

    public static function getActiveSessionsCount($userId = null)
    {
        $query = self::where('is_active', true);
        
        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->count();
    }

    public static function getSessionStats($days = 30)
    {
        $sessions = self::recent($days)->get();

        return [
            'total_sessions' => $sessions->count(),
            'active_sessions' => $sessions->where('is_active', true)->count(),
            'unique_users' => $sessions->unique('user_id')->count(),
            'by_method' => $sessions->groupBy('login_method')->map->count(),
            'by_device' => $sessions->groupBy('device_type')->map->count(),
            'by_browser' => $sessions->groupBy('browser')->map->count(),
            'average_session_duration' => self::getAverageSessionDuration($days)
        ];
    }

    public static function getAverageSessionDuration($days = 30)
    {
        $sessions = self::recent($days)
                       ->whereNotNull('logout_at')
                       ->get();

        if ($sessions->isEmpty()) {
            return 0;
        }

        $totalDuration = $sessions->sum(function ($session) {
            return $session->logout_at->diffInMinutes($session->login_at);
        });

        return round($totalDuration / $sessions->count(), 2);
    }

    // Accessors
    public function getSessionDurationAttribute()
    {
        if (!$this->logout_at) {
            return $this->login_at->diffForHumans(now(), true);
        }

        return $this->login_at->diffForHumans($this->logout_at, true);
    }

    public function getStatusBadgeAttribute()
    {
        return $this->is_active 
            ? '<span class="badge bg-success">Active</span>'
            : '<span class="badge bg-secondary">Ended</span>';
    }

    public function getDeviceIconAttribute()
    {
        return match($this->device_type) {
            'mobile' => 'fas fa-mobile-alt',
            'tablet' => 'fas fa-tablet-alt',
            'desktop' => 'fas fa-desktop',
            default => 'fas fa-question-circle'
        };
    }
}
