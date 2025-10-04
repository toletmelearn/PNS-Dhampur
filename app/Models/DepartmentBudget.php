<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class DepartmentBudget extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'department',
        'budget_year',
        'allocated_budget',
        'spent_amount',
        'committed_amount',
        'available_amount',
        'quarterly_q1_budget',
        'quarterly_q2_budget',
        'quarterly_q3_budget',
        'quarterly_q4_budget',
        'quarterly_q1_spent',
        'quarterly_q2_spent',
        'quarterly_q3_spent',
        'quarterly_q4_spent',
        'monthly_budgets',
        'monthly_spent',
        'budget_manager_id',
        'approval_status',
        'approved_by',
        'approved_at',
        'notes',
        'is_active'
    ];

    protected $casts = [
        'allocated_budget' => 'decimal:2',
        'spent_amount' => 'decimal:2',
        'committed_amount' => 'decimal:2',
        'available_amount' => 'decimal:2',
        'quarterly_q1_budget' => 'decimal:2',
        'quarterly_q2_budget' => 'decimal:2',
        'quarterly_q3_budget' => 'decimal:2',
        'quarterly_q4_budget' => 'decimal:2',
        'quarterly_q1_spent' => 'decimal:2',
        'quarterly_q2_spent' => 'decimal:2',
        'quarterly_q3_spent' => 'decimal:2',
        'quarterly_q4_spent' => 'decimal:2',
        'monthly_budgets' => 'array',
        'monthly_spent' => 'array',
        'approved_at' => 'datetime',
        'is_active' => 'boolean'
    ];

    protected $dates = [
        'approved_at',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    /**
     * Relationships
     */
    public function budgetManager()
    {
        return $this->belongsTo(User::class, 'budget_manager_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'department', 'department')
            ->whereYear('transaction_date', $this->budget_year);
    }

    public function expenses()
    {
        return $this->transactions()->where('type', 'expense');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrentYear($query)
    {
        return $query->where('budget_year', Carbon::now()->year);
    }

    public function scopeByDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    public function scopeByYear($query, $year)
    {
        return $query->where('budget_year', $year);
    }

    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('approval_status', 'pending');
    }

    public function scopeOverBudget($query)
    {
        return $query->whereRaw('spent_amount > allocated_budget');
    }

    public function scopeNearBudgetLimit($query, $threshold = 90)
    {
        return $query->whereRaw("(spent_amount / allocated_budget) * 100 >= ?", [$threshold]);
    }

    /**
     * Accessors
     */
    public function getUtilizationPercentageAttribute()
    {
        if ($this->allocated_budget <= 0) {
            return 0;
        }
        return round(($this->spent_amount / $this->allocated_budget) * 100, 2);
    }

    public function getRemainingBudgetAttribute()
    {
        return $this->allocated_budget - $this->spent_amount;
    }

    public function getVarianceAttribute()
    {
        return $this->spent_amount - $this->allocated_budget;
    }

    public function getVariancePercentageAttribute()
    {
        if ($this->allocated_budget <= 0) {
            return 0;
        }
        return round((($this->spent_amount - $this->allocated_budget) / $this->allocated_budget) * 100, 2);
    }

    public function getBudgetStatusAttribute()
    {
        $utilization = $this->utilization_percentage;
        
        if ($utilization >= 100) {
            return 'exceeded';
        } elseif ($utilization >= 90) {
            return 'critical';
        } elseif ($utilization >= 75) {
            return 'warning';
        } else {
            return 'normal';
        }
    }

    public function getBudgetStatusBadgeAttribute()
    {
        return match($this->budget_status) {
            'exceeded' => '<span class="badge bg-danger">Exceeded</span>',
            'critical' => '<span class="badge bg-warning">Critical</span>',
            'warning' => '<span class="badge bg-info">Warning</span>',
            'normal' => '<span class="badge bg-success">Normal</span>',
            default => '<span class="badge bg-secondary">Unknown</span>'
        };
    }

    public function getCurrentQuarterBudgetAttribute()
    {
        $currentQuarter = $this->getCurrentQuarter();
        return $this->{"quarterly_q{$currentQuarter}_budget"} ?? 0;
    }

    public function getCurrentQuarterSpentAttribute()
    {
        $currentQuarter = $this->getCurrentQuarter();
        return $this->{"quarterly_q{$currentQuarter}_spent"} ?? 0;
    }

    public function getCurrentMonthBudgetAttribute()
    {
        $currentMonth = Carbon::now()->month;
        $monthlyBudgets = $this->monthly_budgets ?? [];
        return $monthlyBudgets[$currentMonth] ?? 0;
    }

    public function getCurrentMonthSpentAttribute()
    {
        $currentMonth = Carbon::now()->month;
        $monthlySpent = $this->monthly_spent ?? [];
        return $monthlySpent[$currentMonth] ?? 0;
    }

    /**
     * Helper Methods
     */
    public function getCurrentQuarter()
    {
        $month = Carbon::now()->month;
        return ceil($month / 3);
    }

    public function updateSpentAmount()
    {
        $totalSpent = $this->expenses()->sum('amount');
        $this->update(['spent_amount' => $totalSpent]);
        
        // Update available amount
        $this->update(['available_amount' => $this->allocated_budget - $totalSpent - $this->committed_amount]);
        
        return $totalSpent;
    }

    public function updateQuarterlySpent()
    {
        for ($quarter = 1; $quarter <= 4; $quarter++) {
            $startMonth = ($quarter - 1) * 3 + 1;
            $endMonth = $quarter * 3;
            
            $quarterlySpent = $this->expenses()
                ->whereMonth('transaction_date', '>=', $startMonth)
                ->whereMonth('transaction_date', '<=', $endMonth)
                ->sum('amount');
            
            $this->{"quarterly_q{$quarter}_spent"} = $quarterlySpent;
        }
        
        $this->save();
    }

    public function updateMonthlySpent()
    {
        $monthlySpent = [];
        
        for ($month = 1; $month <= 12; $month++) {
            $monthlySpent[$month] = $this->expenses()
                ->whereMonth('transaction_date', $month)
                ->sum('amount');
        }
        
        $this->update(['monthly_spent' => $monthlySpent]);
    }

    public function allocateQuarterlyBudgets()
    {
        $quarterlyAmount = $this->allocated_budget / 4;
        
        $this->update([
            'quarterly_q1_budget' => $quarterlyAmount,
            'quarterly_q2_budget' => $quarterlyAmount,
            'quarterly_q3_budget' => $quarterlyAmount,
            'quarterly_q4_budget' => $quarterlyAmount
        ]);
    }

    public function allocateMonthlyBudgets()
    {
        $monthlyAmount = $this->allocated_budget / 12;
        $monthlyBudgets = [];
        
        for ($month = 1; $month <= 12; $month++) {
            $monthlyBudgets[$month] = $monthlyAmount;
        }
        
        $this->update(['monthly_budgets' => $monthlyBudgets]);
    }

    public function approve($approvedBy)
    {
        $this->update([
            'approval_status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now()
        ]);
    }

    public function reject($rejectedBy, $reason = null)
    {
        $this->update([
            'approval_status' => 'rejected',
            'approved_by' => $rejectedBy,
            'approved_at' => now(),
            'notes' => $reason
        ]);
    }

    public function canBeModified()
    {
        return in_array($this->approval_status, ['draft', 'pending', 'rejected']);
    }

    public function getMonthlyVariance($month)
    {
        $monthlyBudgets = $this->monthly_budgets ?? [];
        $monthlySpent = $this->monthly_spent ?? [];
        
        $budgeted = $monthlyBudgets[$month] ?? 0;
        $spent = $monthlySpent[$month] ?? 0;
        
        return $spent - $budgeted;
    }

    public function getQuarterlyVariance($quarter)
    {
        $budgeted = $this->{"quarterly_q{$quarter}_budget"} ?? 0;
        $spent = $this->{"quarterly_q{$quarter}_spent"} ?? 0;
        
        return $spent - $budgeted;
    }

    public function getBudgetForecast($months = 3)
    {
        $monthlySpent = $this->monthly_spent ?? [];
        $currentMonth = Carbon::now()->month;
        
        // Calculate average monthly spending for the last 3 months
        $recentSpending = [];
        for ($i = 1; $i <= 3; $i++) {
            $month = $currentMonth - $i;
            if ($month <= 0) {
                $month += 12;
            }
            $recentSpending[] = $monthlySpent[$month] ?? 0;
        }
        
        $averageMonthlySpending = array_sum($recentSpending) / count($recentSpending);
        
        // Forecast for next months
        $forecast = [];
        for ($i = 1; $i <= $months; $i++) {
            $forecastMonth = $currentMonth + $i;
            if ($forecastMonth > 12) {
                $forecastMonth -= 12;
            }
            $forecast[$forecastMonth] = $averageMonthlySpending;
        }
        
        return $forecast;
    }

    /**
     * Static Methods
     */
    public static function getDepartmentList()
    {
        return [
            'IT' => 'Information Technology',
            'HR' => 'Human Resources',
            'Finance' => 'Finance',
            'Operations' => 'Operations',
            'Marketing' => 'Marketing',
            'Sales' => 'Sales',
            'Administration' => 'Administration',
            'Maintenance' => 'Maintenance',
            'Security' => 'Security',
            'Legal' => 'Legal'
        ];
    }

    public static function createYearlyBudgets($year, $totalBudget)
    {
        $departments = self::getDepartmentList();
        $defaultAllocations = [
            'IT' => 0.15,
            'HR' => 0.10,
            'Finance' => 0.08,
            'Operations' => 0.25,
            'Marketing' => 0.12,
            'Sales' => 0.20,
            'Administration' => 0.10
        ];
        
        foreach ($departments as $code => $name) {
            $allocation = $defaultAllocations[$code] ?? 0.05;
            $allocatedAmount = $totalBudget * $allocation;
            
            self::create([
                'department' => $code,
                'budget_year' => $year,
                'allocated_budget' => $allocatedAmount,
                'spent_amount' => 0,
                'committed_amount' => 0,
                'available_amount' => $allocatedAmount,
                'approval_status' => 'draft',
                'is_active' => true
            ]);
        }
    }

    public static function getBudgetSummary($year = null)
    {
        $year = $year ?? Carbon::now()->year;
        
        $budgets = self::where('budget_year', $year)->get();
        
        return [
            'total_allocated' => $budgets->sum('allocated_budget'),
            'total_spent' => $budgets->sum('spent_amount'),
            'total_committed' => $budgets->sum('committed_amount'),
            'total_available' => $budgets->sum('available_amount'),
            'departments_count' => $budgets->count(),
            'over_budget_count' => $budgets->where('budget_status', 'exceeded')->count(),
            'critical_count' => $budgets->where('budget_status', 'critical')->count(),
            'warning_count' => $budgets->where('budget_status', 'warning')->count(),
            'normal_count' => $budgets->where('budget_status', 'normal')->count()
        ];
    }
}