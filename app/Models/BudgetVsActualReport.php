<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BudgetVsActualReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_name',
        'report_type',
        'period_type',
        'year',
        'month',
        'quarter',
        'department_id',
        'category',
        'budgeted_amount',
        'actual_amount',
        'variance_amount',
        'variance_percentage',
        'variance_type',
        'budget_utilization',
        'performance_rating',
        'trend_direction',
        'previous_period_variance',
        'ytd_budgeted',
        'ytd_actual',
        'ytd_variance',
        'forecast_accuracy',
        'risk_level',
        'action_required',
        'notes',
        'generated_by',
        'generated_at',
        'approved_by',
        'approved_at',
        'status',
        'is_published'
    ];

    protected $casts = [
        'budgeted_amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'variance_amount' => 'decimal:2',
        'variance_percentage' => 'decimal:2',
        'budget_utilization' => 'decimal:2',
        'previous_period_variance' => 'decimal:2',
        'ytd_budgeted' => 'decimal:2',
        'ytd_actual' => 'decimal:2',
        'ytd_variance' => 'decimal:2',
        'forecast_accuracy' => 'decimal:2',
        'generated_at' => 'datetime',
        'approved_at' => 'datetime',
        'is_published' => 'boolean'
    ];

    // Relationships
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeByYear($query, $year)
    {
        return $query->where('year', $year);
    }

    public function scopeByMonth($query, $month)
    {
        return $query->where('month', $month);
    }

    public function scopeByQuarter($query, $quarter)
    {
        return $query->where('quarter', $quarter);
    }

    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByPeriodType($query, $periodType)
    {
        return $query->where('period_type', $periodType);
    }

    public function scopeByReportType($query, $reportType)
    {
        return $query->where('report_type', $reportType);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeHighVariance($query, $threshold = 20)
    {
        return $query->where('variance_percentage', '>', $threshold)
                    ->orWhere('variance_percentage', '<', -$threshold);
    }

    public function scopeOverBudget($query)
    {
        return $query->where('variance_amount', '>', 0);
    }

    public function scopeUnderBudget($query)
    {
        return $query->where('variance_amount', '<', 0);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('generated_at', '>=', Carbon::now()->subDays($days));
    }

    // Accessors
    public function getPeriodDisplayAttribute()
    {
        switch ($this->period_type) {
            case 'monthly':
                return Carbon::createFromDate($this->year, $this->month, 1)->format('M Y');
            case 'quarterly':
                return "Q{$this->quarter} {$this->year}";
            case 'yearly':
                return $this->year;
            default:
                return "{$this->period_type} {$this->year}";
        }
    }

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

    public function getPerformanceRatingBadgeAttribute()
    {
        switch ($this->performance_rating) {
            case 'excellent': return 'success';
            case 'good': return 'info';
            case 'average': return 'warning';
            case 'poor': return 'danger';
            default: return 'secondary';
        }
    }

    public function getRiskLevelBadgeAttribute()
    {
        switch ($this->risk_level) {
            case 'low': return 'success';
            case 'medium': return 'warning';
            case 'high': return 'danger';
            case 'critical': return 'dark';
            default: return 'secondary';
        }
    }

    public function getTrendDirectionIconAttribute()
    {
        switch ($this->trend_direction) {
            case 'improving': return '📈';
            case 'declining': return '📉';
            case 'stable': return '➡️';
            default: return '❓';
        }
    }

    // Helper methods
    public function calculateVariance()
    {
        if ($this->budgeted_amount > 0) {
            $this->variance_amount = $this->actual_amount - $this->budgeted_amount;
            $this->variance_percentage = ($this->variance_amount / $this->budgeted_amount) * 100;
            $this->budget_utilization = ($this->actual_amount / $this->budgeted_amount) * 100;
        }
    }

    public function determineVarianceType()
    {
        if ($this->variance_amount > 0) {
            $this->variance_type = 'over_budget';
        } elseif ($this->variance_amount < 0) {
            $this->variance_type = 'under_budget';
        } else {
            $this->variance_type = 'on_budget';
        }
    }

    public function calculatePerformanceRating()
    {
        $absVariance = abs($this->variance_percentage);
        
        if ($absVariance <= 5) {
            $this->performance_rating = 'excellent';
        } elseif ($absVariance <= 10) {
            $this->performance_rating = 'good';
        } elseif ($absVariance <= 20) {
            $this->performance_rating = 'average';
        } else {
            $this->performance_rating = 'poor';
        }
    }

    public function assessRiskLevel()
    {
        $absVariance = abs($this->variance_percentage);
        $utilization = $this->budget_utilization;
        
        if ($absVariance > 30 || $utilization > 120) {
            $this->risk_level = 'critical';
        } elseif ($absVariance > 20 || $utilization > 100) {
            $this->risk_level = 'high';
        } elseif ($absVariance > 10 || $utilization > 85) {
            $this->risk_level = 'medium';
        } else {
            $this->risk_level = 'low';
        }
    }

    public function determineActionRequired()
    {
        switch ($this->risk_level) {
            case 'critical':
                $this->action_required = 'immediate_intervention';
                break;
            case 'high':
                $this->action_required = 'review_required';
                break;
            case 'medium':
                $this->action_required = 'monitoring_needed';
                break;
            default:
                $this->action_required = 'none';
        }
    }

    public function approve($userId = null)
    {
        $this->status = 'approved';
        $this->approved_by = $userId ?? auth()->id();
        $this->approved_at = now();
        $this->save();
    }

    public function publish()
    {
        $this->is_published = true;
        $this->save();
    }

    public function isOverBudget()
    {
        return $this->variance_amount > 0;
    }

    public function isUnderBudget()
    {
        return $this->variance_amount < 0;
    }

    public function isOnBudget($tolerance = 5)
    {
        return abs($this->variance_percentage) <= $tolerance;
    }

    public function hasHighVariance($threshold = 20)
    {
        return abs($this->variance_percentage) > $threshold;
    }

    public function requiresAction()
    {
        return in_array($this->action_required, ['immediate_intervention', 'review_required']);
    }

    // Static methods
    public static function generateMonthlyReport($year, $month, $departmentId = null)
    {
        // Implementation for generating monthly reports
        return self::generateReport('monthly', $year, $month, null, $departmentId);
    }

    public static function generateQuarterlyReport($year, $quarter, $departmentId = null)
    {
        // Implementation for generating quarterly reports
        return self::generateReport('quarterly', $year, null, $quarter, $departmentId);
    }

    public static function generateYearlyReport($year, $departmentId = null)
    {
        // Implementation for generating yearly reports
        return self::generateReport('yearly', $year, null, null, $departmentId);
    }

    private static function generateReport($periodType, $year, $month = null, $quarter = null, $departmentId = null)
    {
        // This would contain the logic to generate reports
        // For now, returning a placeholder
        return [
            'period_type' => $periodType,
            'year' => $year,
            'month' => $month,
            'quarter' => $quarter,
            'department_id' => $departmentId,
            'generated_at' => now()
        ];
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->generated_at) {
                $model->generated_at = now();
            }
            if (!$model->generated_by) {
                $model->generated_by = auth()->id();
            }
            if (!$model->status) {
                $model->status = 'draft';
            }
        });

        static::saving(function ($model) {
            // Auto-calculate variance and related metrics
            $model->calculateVariance();
            $model->determineVarianceType();
            $model->calculatePerformanceRating();
            $model->assessRiskLevel();
            $model->determineActionRequired();
        });
    }
}