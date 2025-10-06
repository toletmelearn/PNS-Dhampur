<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SystemHealth extends Model
{
    use HasFactory;

    protected $table = 'system_health';

    protected $fillable = [
        'metric_name',
        'metric_type',
        'value',
        'unit',
        'status',
        'details',
        'metadata',
        'recorded_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'recorded_at' => 'datetime',
        'value' => 'decimal:2',
    ];

    protected $dates = [
        'recorded_at',
    ];

    // Scopes
    public function scopeHealthy($query)
    {
        return $query->where('status', 'healthy');
    }

    public function scopeWarning($query)
    {
        return $query->where('status', 'warning');
    }

    public function scopeCritical($query)
    {
        return $query->where('status', 'critical');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('metric_type', $type);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('recorded_at', '>=', Carbon::now()->subHours($hours));
    }

    // Helper methods
    public function isHealthy()
    {
        return $this->status === 'healthy';
    }

    public function isWarning()
    {
        return $this->status === 'warning';
    }

    public function isCritical()
    {
        return $this->status === 'critical';
    }

    public function getFormattedValueAttribute()
    {
        return $this->value . ' ' . $this->unit;
    }
}
