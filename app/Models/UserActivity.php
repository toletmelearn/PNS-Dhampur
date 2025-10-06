<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UserActivity extends Model
{
    use HasFactory;

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
        return $this->belongsTo(User::class);
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
        ];

        return $colors[$this->activity_type] ?? 'secondary';
    }
}
