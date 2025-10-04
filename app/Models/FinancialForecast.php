<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class FinancialForecast extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'forecast_type',
        'department',
        'forecast_year',
        'forecast_month',
        'model_type',
        'scenario_type',
        'forecasted_revenue',
        'forecasted_expenses',
        'forecasted_net_income',
        'confidence_level',
        'accuracy_score',
        'variance_from_actual',
        'risk_score',
        'seasonal_adjustment',
        'trend_factor',
        'growth_rate',
        'assumptions',
        'risk_factors',
        'methodology',
        'data_sources',
        'created_by',
        'approved_by',
        'approved_at',
        'status',
        'notes',
        'is_active'
    ];

    protected $casts = [
        'forecasted_revenue' => 'decimal:2',
        'forecasted_expenses' => 'decimal:2',
        'forecasted_net_income' => 'decimal:2',
        'confidence_level' => 'decimal:2',
        'accuracy_score' => 'decimal:2',
        'variance_from_actual' => 'decimal:2',
        'risk_score' => 'decimal:2',
        'seasonal_adjustment' => 'decimal:4',
        'trend_factor' => 'decimal:4',
        'growth_rate' => 'decimal:2',
        'assumptions' => 'array',
        'risk_factors' => 'array',
        'data_sources' => 'array',
        'approved_at' => 'datetime',
        'is_active' => 'boolean'
    ];

    protected $dates = [
        'approved_at',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function forecastDetails()
    {
        return $this->hasMany(ForecastDetail::class);
    }

    public function actualResults()
    {
        return $this->hasMany(ForecastActual::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByYear($query, $year)
    {
        return $query->where('forecast_year', $year);
    }

    public function scopeByMonth($query, $month)
    {
        return $query->where('forecast_month', $month);
    }

    public function scopeByDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('forecast_type', $type);
    }

    public function scopeByModel($query, $model)
    {
        return $query->where('model_type', $model);
    }

    public function scopeByScenario($query, $scenario)
    {
        return $query->where('scenario_type', $scenario);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeHighConfidence($query, $threshold = 80)
    {
        return $query->where('confidence_level', '>=', $threshold);
    }

    public function scopeLowRisk($query, $threshold = 30)
    {
        return $query->where('risk_score', '<=', $threshold);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeCurrentYear($query)
    {
        return $query->where('forecast_year', now()->year);
    }

    public function scopeCurrentMonth($query)
    {
        return $query->where('forecast_month', now()->month);
    }

    // Accessors
    public function getConfidenceLevelBadgeAttribute()
    {
        if ($this->confidence_level >= 90) return 'success';
        if ($this->confidence_level >= 70) return 'warning';
        return 'danger';
    }

    public function getRiskLevelAttribute()
    {
        if ($this->risk_score <= 30) return 'low';
        if ($this->risk_score <= 60) return 'medium';
        return 'high';
    }

    public function getRiskLevelBadgeAttribute()
    {
        switch ($this->risk_level) {
            case 'low': return 'success';
            case 'medium': return 'warning';
            case 'high': return 'danger';
            default: return 'secondary';
        }
    }

    public function getStatusBadgeAttribute()
    {
        switch ($this->status) {
            case 'approved': return 'success';
            case 'pending': return 'warning';
            case 'rejected': return 'danger';
            case 'draft': return 'secondary';
            default: return 'secondary';
        }
    }

    public function getAccuracyGradeAttribute()
    {
        if ($this->accuracy_score >= 95) return 'A+';
        if ($this->accuracy_score >= 90) return 'A';
        if ($this->accuracy_score >= 85) return 'B+';
        if ($this->accuracy_score >= 80) return 'B';
        if ($this->accuracy_score >= 75) return 'C+';
        if ($this->accuracy_score >= 70) return 'C';
        return 'D';
    }

    public function getVarianceStatusAttribute()
    {
        if (abs($this->variance_from_actual) <= 5) return 'excellent';
        if (abs($this->variance_from_actual) <= 10) return 'good';
        if (abs($this->variance_from_actual) <= 20) return 'acceptable';
        return 'poor';
    }

    public function getForecastPeriodAttribute()
    {
        return Carbon::create($this->forecast_year, $this->forecast_month, 1)->format('F Y');
    }

    public function getIsOverdueAttribute()
    {
        $forecastDate = Carbon::create($this->forecast_year, $this->forecast_month, 1);
        return $forecastDate->isPast() && $this->status === 'pending';
    }

    public function getModelTypeDisplayAttribute()
    {
        $types = [
            'linear' => 'Linear Regression',
            'seasonal' => 'Seasonal Decomposition',
            'trend' => 'Trend Analysis',
            'arima' => 'ARIMA Model',
            'exponential' => 'Exponential Smoothing',
            'composite' => 'Composite Model',
            'monte_carlo' => 'Monte Carlo Simulation'
        ];

        return $types[$this->model_type] ?? ucfirst($this->model_type);
    }

    public function getScenarioTypeDisplayAttribute()
    {
        $scenarios = [
            'optimistic' => 'Optimistic Scenario',
            'realistic' => 'Realistic Scenario',
            'pessimistic' => 'Pessimistic Scenario',
            'best_case' => 'Best Case',
            'worst_case' => 'Worst Case',
            'base_case' => 'Base Case'
        ];

        return $scenarios[$this->scenario_type] ?? ucfirst($this->scenario_type);
    }

    // Helper methods
    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    public function isDraft()
    {
        return $this->status === 'draft';
    }

    public function isHighConfidence($threshold = 80)
    {
        return $this->confidence_level >= $threshold;
    }

    public function isLowRisk($threshold = 30)
    {
        return $this->risk_score <= $threshold;
    }

    public function isAccurate($threshold = 80)
    {
        return $this->accuracy_score >= $threshold;
    }

    public function hasLowVariance($threshold = 10)
    {
        return abs($this->variance_from_actual) <= $threshold;
    }

    public function canBeApproved()
    {
        return $this->status === 'pending' && $this->confidence_level >= 70;
    }

    public function canBeRejected()
    {
        return in_array($this->status, ['pending', 'draft']);
    }

    public function canBeEdited()
    {
        return in_array($this->status, ['draft', 'rejected']);
    }

    public function approve($userId)
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now()
        ]);
    }

    public function reject($userId, $reason = null)
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $userId,
            'approved_at' => now(),
            'notes' => $reason
        ]);
    }

    public function submit()
    {
        $this->update(['status' => 'pending']);
    }

    public function calculateAccuracy($actualRevenue, $actualExpenses)
    {
        $revenueAccuracy = $this->forecasted_revenue > 0 
            ? (1 - abs($actualRevenue - $this->forecasted_revenue) / $this->forecasted_revenue) * 100
            : 0;
            
        $expenseAccuracy = $this->forecasted_expenses > 0 
            ? (1 - abs($actualExpenses - $this->forecasted_expenses) / $this->forecasted_expenses) * 100
            : 0;

        $overallAccuracy = ($revenueAccuracy + $expenseAccuracy) / 2;
        
        $this->update([
            'accuracy_score' => round($overallAccuracy, 2),
            'variance_from_actual' => round((($actualRevenue - $actualExpenses) - $this->forecasted_net_income) / $this->forecasted_net_income * 100, 2)
        ]);

        return $overallAccuracy;
    }

    public function updateRiskScore($factors = [])
    {
        $baseRisk = 20; // Base risk score
        
        // Add risk based on confidence level
        if ($this->confidence_level < 70) {
            $baseRisk += 20;
        } elseif ($this->confidence_level < 80) {
            $baseRisk += 10;
        }
        
        // Add risk based on variance
        if (abs($this->variance_from_actual) > 20) {
            $baseRisk += 15;
        } elseif (abs($this->variance_from_actual) > 10) {
            $baseRisk += 10;
        }
        
        // Add external risk factors
        foreach ($factors as $factor => $impact) {
            $baseRisk += $impact;
        }
        
        $this->update(['risk_score' => min(100, max(0, $baseRisk))]);
    }

    public function generateInsights()
    {
        $insights = [];
        
        if ($this->confidence_level >= 90) {
            $insights[] = 'High confidence forecast - reliable for planning';
        }
        
        if ($this->risk_score <= 30) {
            $insights[] = 'Low risk scenario - stable financial outlook';
        }
        
        if ($this->forecasted_net_income > 0) {
            $insights[] = 'Positive net income projected';
        } else {
            $insights[] = 'Deficit projected - review expenses';
        }
        
        if ($this->growth_rate > 10) {
            $insights[] = 'Strong growth rate projected';
        } elseif ($this->growth_rate < 0) {
            $insights[] = 'Negative growth - requires attention';
        }
        
        return $insights;
    }

    // Static methods
    public static function createForecast($data)
    {
        return self::create(array_merge($data, [
            'status' => 'draft',
            'is_active' => true,
            'created_by' => auth()->id()
        ]));
    }

    public static function getLatestForecast($department = null, $year = null)
    {
        $query = self::active()->approved();
        
        if ($department) {
            $query->where('department', $department);
        }
        
        if ($year) {
            $query->where('forecast_year', $year);
        }
        
        return $query->latest()->first();
    }

    public static function getForecastAccuracy($department = null, $period = 12)
    {
        $query = self::approved()
                    ->where('accuracy_score', '>', 0)
                    ->where('created_at', '>=', now()->subMonths($period));
        
        if ($department) {
            $query->where('department', $department);
        }
        
        return $query->avg('accuracy_score') ?? 0;
    }

    public static function getAverageRiskScore($department = null)
    {
        $query = self::active()->approved();
        
        if ($department) {
            $query->where('department', $department);
        }
        
        return $query->avg('risk_score') ?? 0;
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($forecast) {
            if (!$forecast->created_by) {
                $forecast->created_by = auth()->id();
            }
            
            // Calculate initial risk score if not provided
            if (!$forecast->risk_score) {
                $forecast->risk_score = 50; // Default medium risk
            }
            
            // Set default confidence level if not provided
            if (!$forecast->confidence_level) {
                $forecast->confidence_level = 75; // Default 75% confidence
            }
        });

        static::updating(function ($forecast) {
            // Recalculate net income when revenue or expenses change
            if ($forecast->isDirty(['forecasted_revenue', 'forecasted_expenses'])) {
                $forecast->forecasted_net_income = $forecast->forecasted_revenue - $forecast->forecasted_expenses;
            }
        });
    }
}