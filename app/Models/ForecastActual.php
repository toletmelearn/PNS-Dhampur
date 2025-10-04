<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ForecastActual extends Model
{
    use HasFactory;

    protected $fillable = [
        'financial_forecast_id',
        'period_type',
        'year',
        'month',
        'quarter',
        'week',
        'actual_revenue',
        'actual_expenses',
        'actual_net_income',
        'actual_cash_flow',
        'recorded_date',
        'data_source',
        'verified_by',
        'verification_date',
        'notes',
        'is_verified',
        'is_final'
    ];

    protected $casts = [
        'actual_revenue' => 'decimal:2',
        'actual_expenses' => 'decimal:2',
        'actual_net_income' => 'decimal:2',
        'actual_cash_flow' => 'decimal:2',
        'recorded_date' => 'date',
        'verification_date' => 'datetime',
        'is_verified' => 'boolean',
        'is_final' => 'boolean'
    ];

    // Relationships
    public function forecast()
    {
        return $this->belongsTo(FinancialForecast::class, 'financial_forecast_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
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

    public function scopeByPeriod($query, $periodType)
    {
        return $query->where('period_type', $periodType);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeFinal($query)
    {
        return $query->where('is_final', true);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('recorded_date', '>=', Carbon::now()->subDays($days));
    }

    // Accessors
    public function getPeriodDisplayAttribute()
    {
        switch ($this->period_type) {
            case 'monthly':
                return Carbon::createFromDate($this->year, $this->month, 1)->format('M Y');
            case 'quarterly':
                return "Q{$this->quarter} {$this->year}";
            case 'weekly':
                return "Week {$this->week}, {$this->year}";
            case 'yearly':
                return $this->year;
            default:
                return "{$this->period_type} {$this->year}";
        }
    }

    public function getVerificationStatusAttribute()
    {
        if ($this->is_final) return 'final';
        if ($this->is_verified) return 'verified';
        return 'pending';
    }

    public function getVerificationBadgeAttribute()
    {
        switch ($this->verification_status) {
            case 'final': return 'success';
            case 'verified': return 'info';
            case 'pending': return 'warning';
            default: return 'secondary';
        }
    }

    // Helper methods
    public function verify($userId = null)
    {
        $this->is_verified = true;
        $this->verified_by = $userId ?? auth()->id();
        $this->verification_date = now();
        $this->save();
    }

    public function finalize()
    {
        $this->is_final = true;
        $this->save();
    }

    public function calculateVarianceWith($forecast)
    {
        $revenueVariance = $this->actual_revenue - $forecast->forecasted_revenue;
        $expenseVariance = $this->actual_expenses - $forecast->forecasted_expenses;
        $netIncomeVariance = $this->actual_net_income - $forecast->forecasted_net_income;

        return [
            'revenue_variance' => $revenueVariance,
            'revenue_variance_percentage' => $forecast->forecasted_revenue > 0 ? 
                ($revenueVariance / $forecast->forecasted_revenue) * 100 : 0,
            'expense_variance' => $expenseVariance,
            'expense_variance_percentage' => $forecast->forecasted_expenses > 0 ? 
                ($expenseVariance / $forecast->forecasted_expenses) * 100 : 0,
            'net_income_variance' => $netIncomeVariance,
            'net_income_variance_percentage' => $forecast->forecasted_net_income > 0 ? 
                ($netIncomeVariance / $forecast->forecasted_net_income) * 100 : 0
        ];
    }

    public function isPending()
    {
        return !$this->is_verified && !$this->is_final;
    }

    public function isOverdue($days = 7)
    {
        return $this->isPending() && $this->recorded_date < Carbon::now()->subDays($days);
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->recorded_date) {
                $model->recorded_date = now();
            }
        });

        static::updating(function ($model) {
            // Recalculate net income if revenue or expenses change
            if ($model->isDirty(['actual_revenue', 'actual_expenses'])) {
                $model->actual_net_income = $model->actual_revenue - $model->actual_expenses;
            }
        });
    }
}