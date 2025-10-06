<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PerformanceMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'endpoint',
        'method',
        'user_agent',
        'ip_address',
        'user_id',
        'response_time',
        'memory_usage',
        'cpu_usage',
        'database_queries',
        'database_time',
        'status_code',
        'additional_data',
        'recorded_at',
    ];

    protected $casts = [
        'additional_data' => 'array',
        'recorded_at' => 'datetime',
        'response_time' => 'decimal:2',
        'database_time' => 'decimal:2',
    ];

    protected $dates = [
        'recorded_at',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByEndpoint($query, $endpoint)
    {
        return $query->where('endpoint', $endpoint);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeSlowRequests($query, $threshold = 1000)
    {
        return $query->where('response_time', '>', $threshold);
    }

    public function scopeByStatusCode($query, $code)
    {
        return $query->where('status_code', $code);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('recorded_at', '>=', Carbon::now()->subHours($hours));
    }

    public function scopeToday($query)
    {
        return $query->whereDate('recorded_at', Carbon::today());
    }

    // Helper methods
    public function isSlowRequest($threshold = 1000)
    {
        return $this->response_time > $threshold;
    }

    public function isSuccessful()
    {
        return $this->status_code >= 200 && $this->status_code < 300;
    }

    public function isError()
    {
        return $this->status_code >= 400;
    }

    public function getFormattedResponseTimeAttribute()
    {
        return number_format($this->response_time, 2) . ' ms';
    }

    public function getFormattedMemoryUsageAttribute()
    {
        if (!$this->memory_usage) return 'N/A';
        
        $bytes = $this->memory_usage;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
