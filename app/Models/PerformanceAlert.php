<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PerformanceAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'alert_type',
        'severity',
        'title',
        'message',
        'data',
        'threshold_value',
        'current_value',
        'unit',
        'status',
        'triggered_at',
        'acknowledged_at',
        'resolved_at',
        'acknowledged_by',
        'resolved_by',
        'resolution_notes',
    ];

    protected $casts = [
        'data' => 'array',
        'threshold_value' => 'decimal:4',
        'current_value' => 'decimal:4',
        'triggered_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    // Relationships
    public function acknowledgedBy()
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeAcknowledged($query)
    {
        return $query->where('status', 'acknowledged');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('alert_type', $type);
    }

    public function scopeCritical($query)
    {
        return $query->whereIn('severity', ['critical', 'emergency']);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('triggered_at', '>=', Carbon::now()->subHours($hours));
    }

    public function scopeUnresolved($query)
    {
        return $query->whereIn('status', ['active', 'acknowledged']);
    }

    // Helper methods
    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isAcknowledged()
    {
        return $this->status === 'acknowledged';
    }

    public function isResolved()
    {
        return $this->status === 'resolved';
    }

    public function isCritical()
    {
        return in_array($this->severity, ['critical', 'emergency']);
    }

    public function acknowledge($userId, $notes = null)
    {
        $this->update([
            'status' => 'acknowledged',
            'acknowledged_at' => Carbon::now(),
            'acknowledged_by' => $userId,
            'resolution_notes' => $notes,
        ]);

        return $this;
    }

    public function resolve($userId, $notes = null)
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => Carbon::now(),
            'resolved_by' => $userId,
            'resolution_notes' => $notes,
        ]);

        return $this;
    }

    public function getDurationAttribute()
    {
        $start = $this->triggered_at;
        $end = $this->resolved_at ?? Carbon::now();
        
        return $start->diffForHumans($end, true);
    }

    public function getFormattedThresholdAttribute()
    {
        if (!$this->threshold_value) return 'N/A';
        
        return number_format($this->threshold_value, 2) . ($this->unit ? ' ' . $this->unit : '');
    }

    public function getFormattedCurrentValueAttribute()
    {
        if (!$this->current_value) return 'N/A';
        
        return number_format($this->current_value, 2) . ($this->unit ? ' ' . $this->unit : '');
    }

    public function getSeverityColorAttribute()
    {
        return match($this->severity) {
            'warning' => 'warning',
            'critical' => 'danger',
            'emergency' => 'dark',
            default => 'info'
        };
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'active' => 'danger',
            'acknowledged' => 'warning',
            'resolved' => 'success',
            default => 'secondary'
        };
    }

    // Static methods
    public static function createAlert($type, $severity, $title, $message, $data = null, $thresholdValue = null, $currentValue = null, $unit = null)
    {
        return static::create([
            'alert_type' => $type,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'threshold_value' => $thresholdValue,
            'current_value' => $currentValue,
            'unit' => $unit,
            'status' => 'active',
            'triggered_at' => Carbon::now(),
        ]);
    }

    public static function getActiveAlertsCount()
    {
        return static::active()->count();
    }

    public static function getCriticalAlertsCount()
    {
        return static::critical()->unresolved()->count();
    }

    public static function getRecentAlertsCount($hours = 24)
    {
        return static::recent($hours)->count();
    }

    public static function getAlertsSummary()
    {
        return [
            'total' => static::count(),
            'active' => static::active()->count(),
            'acknowledged' => static::acknowledged()->count(),
            'resolved' => static::resolved()->count(),
            'critical' => static::critical()->unresolved()->count(),
            'recent_24h' => static::recent(24)->count(),
        ];
    }

    public static function getAlertsByType()
    {
        return static::selectRaw('alert_type, COUNT(*) as count, MAX(triggered_at) as last_triggered')
            ->groupBy('alert_type')
            ->orderBy('count', 'desc')
            ->get();
    }

    public static function getAlertsBySeverity()
    {
        return static::selectRaw('severity, COUNT(*) as count')
            ->groupBy('severity')
            ->orderByRaw("FIELD(severity, 'emergency', 'critical', 'warning')")
            ->get();
    }

    public static function cleanupOldAlerts($days = 90)
    {
        $cutoffDate = Carbon::now()->subDays($days);
        
        return static::where('triggered_at', '<', $cutoffDate)
            ->where('status', 'resolved')
            ->delete();
    }

    public static function getAlertsForDashboard($limit = 10)
    {
        return static::with(['acknowledgedBy', 'resolvedBy'])
            ->unresolved()
            ->orderBy('severity', 'desc')
            ->orderBy('triggered_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function getAlertTrends($days = 7)
    {
        $startDate = Carbon::now()->subDays($days);
        
        return static::selectRaw('DATE(triggered_at) as date, COUNT(*) as count, severity')
            ->where('triggered_at', '>=', $startDate)
            ->groupBy('date', 'severity')
            ->orderBy('date')
            ->get()
            ->groupBy('date');
    }
}