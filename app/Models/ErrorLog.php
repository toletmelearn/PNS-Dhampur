<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ErrorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'level',
        'message',
        'context',
        'file',
        'line',
        'trace',
        'url',
        'method',
        'ip_address',
        'user_agent',
        'user_id',
        'session_id',
        'request_data',
        'exception_class',
        'is_resolved',
        'resolution_notes',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'request_data' => 'array',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    protected $dates = [
        'resolved_at',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // Scopes
    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    public function scopeResolved($query)
    {
        return $query->where('is_resolved', true);
    }

    public function scopeCritical($query)
    {
        return $query->whereIn('level', ['emergency', 'alert', 'critical', 'error']);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('created_at', '>=', Carbon::now()->subHours($hours));
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByException($query, $exceptionClass)
    {
        return $query->where('exception_class', $exceptionClass);
    }

    // Helper methods
    public function isCritical()
    {
        return in_array($this->level, ['emergency', 'alert', 'critical', 'error']);
    }

    public function isResolved()
    {
        return $this->is_resolved;
    }

    public function markAsResolved($userId, $notes = null)
    {
        $this->update([
            'is_resolved' => true,
            'resolved_by' => $userId,
            'resolved_at' => Carbon::now(),
            'resolution_notes' => $notes,
        ]);
    }

    public function getShortMessageAttribute()
    {
        return strlen($this->message) > 100 
            ? substr($this->message, 0, 100) . '...' 
            : $this->message;
    }

    public function getLevelColorAttribute()
    {
        $colors = [
            'emergency' => 'danger',
            'alert' => 'danger',
            'critical' => 'danger',
            'error' => 'danger',
            'warning' => 'warning',
            'notice' => 'info',
            'info' => 'info',
            'debug' => 'secondary',
        ];

        return $colors[$this->level] ?? 'secondary';
    }

    public function getContextDataAttribute()
    {
        return $this->context ? json_decode($this->context, true) : null;
    }
}
