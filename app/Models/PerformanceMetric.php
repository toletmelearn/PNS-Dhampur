<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PerformanceMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'metric_type',
        'metric_name',
        'value',
        'unit',
        'metadata',
        'recorded_at',
        // Legacy fields for backward compatibility
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
    ];

    protected $casts = [
        'value' => 'decimal:4',
        'metadata' => 'array',
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

    // New scopes for enhanced monitoring
    public function scopeOfType($query, $type)
    {
        return $query->where('metric_type', $type);
    }

    public function scopeOfName($query, $name)
    {
        return $query->where('metric_name', $name);
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('recorded_at', [$startDate, $endDate]);
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

    /**
     * Get the average value for a specific metric over a time period
     */
    public static function getAverageValue($metricType, $metricName, $minutes = 60)
    {
        return static::ofType($metricType)
            ->ofName($metricName)
            ->recent($minutes)
            ->avg('value');
    }

    /**
     * Get the latest value for a specific metric
     */
    public static function getLatestValue($metricType, $metricName)
    {
        $metric = static::ofType($metricType)
            ->ofName($metricName)
            ->latest('recorded_at')
            ->first();

        return $metric ? $metric->value : null;
    }

    /**
     * Record a new metric
     */
    public static function record($type, $name, $value, $unit = null, $metadata = null)
    {
        return static::create([
            'metric_type' => $type,
            'metric_name' => $name,
            'value' => $value,
            'unit' => $unit,
            'metadata' => $metadata,
            'recorded_at' => Carbon::now(),
        ]);
    }

    /**
     * Get metrics for dashboard charts
     */
    public static function getChartData($metricType, $metricName, $hours = 24, $interval = 'hour')
    {
        $startDate = Carbon::now()->subHours($hours);
        
        $query = static::ofType($metricType)
            ->ofName($metricName)
            ->where('recorded_at', '>=', $startDate)
            ->orderBy('recorded_at');

        if ($interval === 'hour') {
            return $query->selectRaw('
                DATE_FORMAT(recorded_at, "%Y-%m-%d %H:00:00") as period,
                AVG(value) as avg_value,
                MAX(value) as max_value,
                MIN(value) as min_value,
                COUNT(*) as count
            ')
            ->groupBy('period')
            ->get();
        } elseif ($interval === 'minute') {
            return $query->selectRaw('
                DATE_FORMAT(recorded_at, "%Y-%m-%d %H:%i:00") as period,
                AVG(value) as avg_value,
                MAX(value) as max_value,
                MIN(value) as min_value,
                COUNT(*) as count
            ')
            ->groupBy('period')
            ->get();
        }

        return $query->get();
    }

    /**
     * Clean up old metrics
     */
    public static function cleanup($days = 30)
    {
        $cutoffDate = Carbon::now()->subDays($days);
        
        return static::where('recorded_at', '<', $cutoffDate)->delete();
    }

    /**
     * Get system health summary
     */
    public static function getSystemHealthSummary()
    {
        $recentMetrics = static::recent(5)->get()->groupBy('metric_name');
        
        $summary = [];
        
        foreach ($recentMetrics as $metricName => $metrics) {
            $latestMetric = $metrics->first();
            $summary[$metricName] = [
                'current_value' => $latestMetric->value,
                'unit' => $latestMetric->unit,
                'recorded_at' => $latestMetric->recorded_at,
                'avg_last_hour' => static::getAverageValue($latestMetric->metric_type, $metricName, 60),
                'trend' => static::getTrend($latestMetric->metric_type, $metricName),
            ];
        }
        
        return $summary;
    }

    /**
     * Get trend for a metric (up, down, stable)
     */
    public static function getTrend($metricType, $metricName, $minutes = 30)
    {
        $recent = static::ofType($metricType)
            ->ofName($metricName)
            ->recent($minutes)
            ->orderBy('recorded_at')
            ->pluck('value')
            ->toArray();

        if (count($recent) < 2) {
            return 'stable';
        }

        $firstHalf = array_slice($recent, 0, ceil(count($recent) / 2));
        $secondHalf = array_slice($recent, floor(count($recent) / 2));

        $firstAvg = array_sum($firstHalf) / count($firstHalf);
        $secondAvg = array_sum($secondHalf) / count($secondHalf);

        $threshold = 0.05; // 5% change threshold

        if ($secondAvg > $firstAvg * (1 + $threshold)) {
            return 'up';
        } elseif ($secondAvg < $firstAvg * (1 - $threshold)) {
            return 'down';
        }

        return 'stable';
    }
}
