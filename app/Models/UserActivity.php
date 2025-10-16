<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UserActivity extends Model
{
    use HasFactory;

    // Activity type constants used across the application
    const TYPE_LOGIN_FAILED = 'login_failed';
    const TYPE_PASSWORD_RESET = 'password_reset';
    const TYPE_PASSWORD_CHANGE = 'password_change';
    const TYPE_PAGE_ACCESS = 'page_access';
    const TYPE_PERMISSION_ACCESS = 'permission_access';
    const TYPE_UNAUTHORIZED_ACCESS = 'unauthorized_access';
    const TYPE_SECURITY_EVENT = 'security_event';
    const TYPE_LOGIN = 'login';
    const TYPE_LOGOUT = 'logout';

    protected $fillable = [
        'user_id',
        'activity_type',
        'description',
        'subject_type',
        'subject_id',
        'properties',
        'url',
        'method',
        'ip_address',
        'user_agent',
        'session_id',
        'request_data',
        'response_time',
        'status_code',
        'performed_at',
    ];

    protected $casts = [
        'properties' => 'array',
        'request_data' => 'array',
        'performed_at' => 'datetime',
        'response_time' => 'decimal:2',
    ];

    protected $dates = [
        'performed_at',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(NewUser::class, 'user_id');
    }

    public function subject()
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByActivityType($query, $type)
    {
        return $query->where('activity_type', $type);
    }

    public function scopeBySubject($query, $subjectType, $subjectId = null)
    {
        $query = $query->where('subject_type', $subjectType);
        
        if ($subjectId) {
            $query->where('subject_id', $subjectId);
        }
        
        return $query;
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('performed_at', '>=', Carbon::now()->subHours($hours));
    }

    public function scopeToday($query)
    {
        return $query->whereDate('performed_at', Carbon::today());
    }

    public function scopeByIpAddress($query, $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    public function scopeLogins($query)
    {
        return $query->where('activity_type', 'login');
    }

    public function scopeLogouts($query)
    {
        return $query->where('activity_type', 'logout');
    }

    public function scopeCreates($query)
    {
        return $query->where('activity_type', 'create');
    }

    public function scopeUpdates($query)
    {
        return $query->where('activity_type', 'update');
    }

    public function scopeDeletes($query)
    {
        return $query->where('activity_type', 'delete');
    }

    // Helper methods
    public function isLogin()
    {
        return $this->activity_type === 'login';
    }

    public function isLogout()
    {
        return $this->activity_type === 'logout';
    }

    public function isCreate()
    {
        return $this->activity_type === 'create';
    }

    public function isUpdate()
    {
        return $this->activity_type === 'update';
    }

    public function isDelete()
    {
        return $this->activity_type === 'delete';
    }

    public function getFormattedResponseTimeAttribute()
    {
        return $this->response_time ? number_format($this->response_time, 2) . ' ms' : 'N/A';
    }

    public function getActivityIconAttribute()
    {
        $icons = [
            'login' => 'fas fa-sign-in-alt',
            'logout' => 'fas fa-sign-out-alt',
            'create' => 'fas fa-plus',
            'update' => 'fas fa-edit',
            'delete' => 'fas fa-trash',
            'view' => 'fas fa-eye',
            'download' => 'fas fa-download',
            'upload' => 'fas fa-upload',
        ];

        return $icons[$this->activity_type] ?? 'fas fa-circle';
    }

    public function getActivityColorAttribute()
    {
        $colors = [
            'login' => 'success',
            'logout' => 'info',
            'create' => 'primary',
            'update' => 'warning',
            'delete' => 'danger',
            'view' => 'secondary',
            'download' => 'info',
            'upload' => 'primary',
            'login_failed' => 'danger',
            'password_reset' => 'warning',
            'password_change' => 'warning',
            'page_access' => 'info',
            'permission_access' => 'info',
            'unauthorized_access' => 'danger',
            'security_event' => 'danger',
        ];

        return $colors[$this->activity_type] ?? 'secondary';
    }

    /**
     * Static: Log activity with friendly keys and sensible defaults
     */
    public static function logActivity(array $data): self
    {
        $payload = [
            'user_id' => $data['user_id'] ?? auth()->id(),
            'activity_type' => $data['activity_type'] ?? ($data['type'] ?? 'unknown'),
            'description' => $data['activity_description'] ?? ($data['description'] ?? ''),
            'subject_type' => $data['subject_type'] ?? null,
            'subject_id' => $data['subject_id'] ?? null,
            'properties' => $data['additional_data'] ?? ($data['properties'] ?? null),
            'url' => $data['url'] ?? request()->fullUrl(),
            'method' => $data['method'] ?? request()->method(),
            'ip_address' => $data['ip_address'] ?? request()->ip(),
            'user_agent' => $data['user_agent'] ?? request()->userAgent(),
            'session_id' => $data['session_id'] ?? (function () { try { return session()->getId(); } catch (\Throwable $e) { return null; } })(),
            'request_data' => $data['request_data'] ?? null,
            'response_time' => $data['response_time'] ?? null,
            'status_code' => $data['status_code'] ?? null,
            'performed_at' => $data['performed_at'] ?? now(),
        ];

        return self::create($payload);
    }

    /**
     * Static: Log login event (success only; failures use TYPE_LOGIN_FAILED)
     */
    public static function logLogin(int $userId, bool $success = true, array $context = []): self
    {
        $type = $success ? self::TYPE_LOGIN : self::TYPE_LOGIN_FAILED;
        $desc = $success ? 'Successful login' : 'Failed login';

        return self::logActivity(array_merge([
            'user_id' => $userId,
            'activity_type' => $type,
            'activity_description' => $desc,
        ], $context));
    }

    /**
     * Static: Log logout event
     */
    public static function logLogout(int $userId, string $reason = 'user_logout', array $context = []): self
    {
        return self::logActivity(array_merge([
            'user_id' => $userId,
            'activity_type' => self::TYPE_LOGOUT,
            'activity_description' => "User logout: {$reason}",
            'additional_data' => array_merge(['reason' => $reason], $context['additional_data'] ?? []),
        ], $context));
    }

    /**
     * Static: Log password change event
     */
    public static function logPasswordChange(int $userId, bool $viaResetFlow = false, array $context = []): self
    {
        $desc = $viaResetFlow ? 'Password changed via reset flow' : 'Password changed';

        return self::logActivity(array_merge([
            'user_id' => $userId,
            'activity_type' => self::TYPE_PASSWORD_CHANGE,
            'activity_description' => $desc,
            'additional_data' => array_merge(['via_reset' => $viaResetFlow], $context['additional_data'] ?? []),
        ], $context));
    }

    /**
     * Static: Log security event with free-form name and context
     */
    public static function logSecurityEvent(string $event, string $description = '', array $context = []): self
    {
        $desc = $description ?: ucwords(str_replace('_', ' ', $event));

        return self::logActivity(array_merge([
            'activity_type' => self::TYPE_SECURITY_EVENT,
            'activity_description' => $desc,
            'additional_data' => array_merge(['event' => $event], $context['additional_data'] ?? []),
        ], $context));
    }
}
