<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ForecastDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'financial_forecast_id',
        'category',
        'subcategory',
        'line_item',
        'forecasted_amount',
        'actual_amount',
        'variance_amount',
        'variance_percentage',
        'forecast_method',
        'assumptions',
        'confidence_level',
        'risk_factors',
        'seasonal_factor',
        'trend_factor',
        'growth_rate',
        'base_amount',
        'adjustment_amount',
        'notes'
    ];

    protected $casts = [
        'forecasted_amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'variance_amount' => 'decimal:2',
        'variance_percentage' => 'decimal:2',
        'confidence_level' => 'decimal:2',
        'seasonal_factor' => 'decimal:4',
        'trend_factor' => 'decimal:4',
        'growth_rate' => 'decimal:2',
        'base_amount' => 'decimal:2',
        'adjustment_amount' => 'decimal:2',
        'assumptions' => 'array',
        'risk_factors' => 'array'
    ];

    // Relationships
    public function forecast()
    {
        return $this->belongsTo(FinancialForecast::class, 'financial_forecast_id');
    }

    // Scopes
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeBySubcategory($query, $subcategory)
    {
        return $query->where('subcategory', $subcategory);
    }

    public function scopeRevenue($query)
    {
        return $query->where('category', 'revenue');
    }

    public function scopeExpense($query)
    {
        return $query->where('category', 'expense');
    }

    public function scopeHighVariance($query, $threshold = 10)
    {
        return $query->where('variance_percentage', '>', $threshold);
    }

    public function scopeLowConfidence($query, $threshold = 70)
    {
        return $query->where('confidence_level', '<', $threshold);
    }

    // Accessors
    public function getVarianceStatusAttribute()
    {
        if (abs($this->variance_percentage) <= 5) return 'excellent';
        if (abs($this->variance_percentage) <= 10) return 'good';
        if (abs($this->variance_percentage) <= 20) return 'acceptable';
        return 'poor';
    }

    public function getVarianceStatusBadgeAttribute()
    {
        switch ($this->variance_status) {
            case 'excellent': return 'success';
            case 'good': return 'info';
            case 'acceptable': return 'warning';
            case 'poor': return 'danger';
            default: return 'secondary';
        }
    }

    public function getConfidenceLevelBadgeAttribute()
    {
        if ($this->confidence_level >= 90) return 'success';
        if ($this->confidence_level >= 70) return 'warning';
        return 'danger';
    }

    // Helper methods
    public function calculateVariance()
    {
        if ($this->actual_amount !== null && $this->forecasted_amount > 0) {
            $this->variance_amount = $this->actual_amount - $this->forecasted_amount;
            $this->variance_percentage = ($this->variance_amount / $this->forecasted_amount) * 100;
            $this->save();
        }
    }

    public function updateActual($actualAmount)
    {
        $this->actual_amount = $actualAmount;
        $this->calculateVariance();
    }

    public function isAccurate($threshold = 10)
    {
        return abs($this->variance_percentage) <= $threshold;
    }

    public function hasHighVariance($threshold = 20)
    {
        return abs($this->variance_percentage) > $threshold;
    }
}