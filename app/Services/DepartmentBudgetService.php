<?php

namespace App\Services;

use App\Models\DepartmentBudget;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\BudgetVarianceAlert;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DepartmentBudgetService
{
    /**
     * Get department budget dashboard data
     */
    public function getDashboardData($year = null)
    {
        $year = $year ?? Carbon::now()->year;
        
        return Cache::remember("department_budget_dashboard_{$year}", 300, function () use ($year) {
            $budgets = DepartmentBudget::where('budget_year', $year)
                ->with(['budgetManager', 'approvedBy'])
                ->get();
            
            $summary = DepartmentBudget::getBudgetSummary($year);
            
            $monthlyTrends = $this->getMonthlyTrends($year);
            $quarterlyAnalysis = $this->getQuarterlyAnalysis($year);
            $departmentComparison = $this->getDepartmentComparison($year);
            $budgetAlerts = $this->getBudgetAlerts($year);
            
            return [
                'summary' => $summary,
                'budgets' => $budgets,
                'monthly_trends' => $monthlyTrends,
                'quarterly_analysis' => $quarterlyAnalysis,
                'department_comparison' => $departmentComparison,
                'budget_alerts' => $budgetAlerts,
                'year' => $year
            ];
        });
    }

    /**
     * Create or update department budget allocation
     */
    public function createOrUpdateBudget($data)
    {
        try {
            DB::beginTransaction();
            
            $budget = DepartmentBudget::updateOrCreate(
                [
                    'department' => $data['department'],
                    'budget_year' => $data['budget_year']
                ],
                [
                    'allocated_budget' => $data['allocated_budget'],
                    'budget_manager_id' => $data['budget_manager_id'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'approval_status' => 'pending',
                    'is_active' => true
                ]
            );
            
            // Allocate quarterly and monthly budgets
            if (isset($data['auto_allocate']) && $data['auto_allocate']) {
                $budget->allocateQuarterlyBudgets();
                $budget->allocateMonthlyBudgets();
            }
            
            // Update available amount
            $budget->update([
                'available_amount' => $budget->allocated_budget - $budget->spent_amount - $budget->committed_amount
            ]);
            
            DB::commit();
            
            // Clear cache
            $this->clearBudgetCache($data['budget_year']);
            
            Log::info('Department budget created/updated', [
                'department' => $data['department'],
                'budget_year' => $data['budget_year'],
                'allocated_budget' => $data['allocated_budget']
            ]);
            
            return $budget;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create/update department budget', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Update budget spending from transactions
     */
    public function updateBudgetSpending($department, $year = null)
    {
        $year = $year ?? Carbon::now()->year;
        
        $budget = DepartmentBudget::where('department', $department)
            ->where('budget_year', $year)
            ->first();
        
        if (!$budget) {
            return null;
        }
        
        // Update spent amounts
        $budget->updateSpentAmount();
        $budget->updateQuarterlySpent();
        $budget->updateMonthlySpent();
        
        // Check for budget alerts
        $this->checkBudgetAlerts($budget);
        
        // Clear cache
        $this->clearBudgetCache($year);
        
        return $budget;
    }

    /**
     * Get monthly spending trends
     */
    public function getMonthlyTrends($year)
    {
        $budgets = DepartmentBudget::where('budget_year', $year)->get();
        
        $trends = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthName = Carbon::create($year, $month, 1)->format('M');
            $totalBudgeted = 0;
            $totalSpent = 0;
            
            foreach ($budgets as $budget) {
                $monthlyBudgets = $budget->monthly_budgets ?? [];
                $monthlySpent = $budget->monthly_spent ?? [];
                
                $totalBudgeted += $monthlyBudgets[$month] ?? 0;
                $totalSpent += $monthlySpent[$month] ?? 0;
            }
            
            $trends[] = [
                'month' => $monthName,
                'budgeted' => $totalBudgeted,
                'spent' => $totalSpent,
                'variance' => $totalSpent - $totalBudgeted,
                'utilization' => $totalBudgeted > 0 ? round(($totalSpent / $totalBudgeted) * 100, 2) : 0
            ];
        }
        
        return $trends;
    }

    /**
     * Get quarterly analysis
     */
    public function getQuarterlyAnalysis($year)
    {
        $budgets = DepartmentBudget::where('budget_year', $year)->get();
        
        $analysis = [];
        for ($quarter = 1; $quarter <= 4; $quarter++) {
            $totalBudgeted = $budgets->sum("quarterly_q{$quarter}_budget");
            $totalSpent = $budgets->sum("quarterly_q{$quarter}_spent");
            
            $analysis[] = [
                'quarter' => "Q{$quarter}",
                'budgeted' => $totalBudgeted,
                'spent' => $totalSpent,
                'variance' => $totalSpent - $totalBudgeted,
                'utilization' => $totalBudgeted > 0 ? round(($totalSpent / $totalBudgeted) * 100, 2) : 0
            ];
        }
        
        return $analysis;
    }

    /**
     * Get department comparison
     */
    public function getDepartmentComparison($year)
    {
        return DepartmentBudget::where('budget_year', $year)
            ->select([
                'department',
                'allocated_budget',
                'spent_amount',
                'available_amount',
                DB::raw('ROUND((spent_amount / allocated_budget) * 100, 2) as utilization_percentage'),
                DB::raw('spent_amount - allocated_budget as variance')
            ])
            ->orderBy('utilization_percentage', 'desc')
            ->get();
    }

    /**
     * Get budget alerts
     */
    public function getBudgetAlerts($year)
    {
        $budgets = DepartmentBudget::where('budget_year', $year)->get();
        
        $alerts = [];
        foreach ($budgets as $budget) {
            $utilization = $budget->utilization_percentage;
            
            if ($utilization >= 100) {
                $alerts[] = [
                    'type' => 'exceeded',
                    'department' => $budget->department,
                    'message' => "Budget exceeded by " . number_format($budget->variance, 2),
                    'utilization' => $utilization,
                    'severity' => 'critical'
                ];
            } elseif ($utilization >= 90) {
                $alerts[] = [
                    'type' => 'critical',
                    'department' => $budget->department,
                    'message' => "Budget utilization at {$utilization}%",
                    'utilization' => $utilization,
                    'severity' => 'warning'
                ];
            } elseif ($utilization >= 75) {
                $alerts[] = [
                    'type' => 'warning',
                    'department' => $budget->department,
                    'message' => "Budget utilization at {$utilization}%",
                    'utilization' => $utilization,
                    'severity' => 'info'
                ];
            }
        }
        
        return collect($alerts)->sortByDesc('utilization')->values()->all();
    }

    /**
     * Check and send budget alerts
     */
    public function checkBudgetAlerts($budget)
    {
        $utilization = $budget->utilization_percentage;
        $alertType = null;
        
        if ($utilization >= 100) {
            $alertType = 'exceeded';
        } elseif ($utilization >= 90) {
            $alertType = 'critical';
        } elseif ($utilization >= 75) {
            $alertType = 'warning';
        }
        
        if ($alertType) {
            // Get users to notify (budget manager, finance team, admins)
            $usersToNotify = User::where(function ($query) use ($budget) {
                $query->where('id', $budget->budget_manager_id)
                      ->orWhere('role', 'admin')
                      ->orWhere('department', 'Finance');
            })->get();
            
            foreach ($usersToNotify as $user) {
                $user->notify(new BudgetVarianceAlert($budget, $alertType));
            }
            
            Log::info('Budget alert sent', [
                'department' => $budget->department,
                'alert_type' => $alertType,
                'utilization' => $utilization
            ]);
        }
    }

    /**
     * Generate budget forecast
     */
    public function generateForecast($department, $months = 6)
    {
        $budget = DepartmentBudget::where('department', $department)
            ->where('budget_year', Carbon::now()->year)
            ->first();
        
        if (!$budget) {
            return null;
        }
        
        $forecast = $budget->getBudgetForecast($months);
        $currentSpending = $budget->spent_amount;
        $projectedTotal = $currentSpending + array_sum($forecast);
        
        return [
            'department' => $department,
            'current_spending' => $currentSpending,
            'allocated_budget' => $budget->allocated_budget,
            'monthly_forecast' => $forecast,
            'projected_total' => $projectedTotal,
            'projected_variance' => $projectedTotal - $budget->allocated_budget,
            'risk_level' => $this->calculateRiskLevel($projectedTotal, $budget->allocated_budget)
        ];
    }

    /**
     * Calculate risk level based on projected spending
     */
    private function calculateRiskLevel($projectedTotal, $allocatedBudget)
    {
        if ($allocatedBudget <= 0) {
            return 'unknown';
        }
        
        $projectedUtilization = ($projectedTotal / $allocatedBudget) * 100;
        
        if ($projectedUtilization >= 110) {
            return 'high';
        } elseif ($projectedUtilization >= 100) {
            return 'medium';
        } elseif ($projectedUtilization >= 90) {
            return 'low';
        } else {
            return 'minimal';
        }
    }

    /**
     * Transfer budget between departments
     */
    public function transferBudget($fromDepartment, $toDepartment, $amount, $reason, $approvedBy)
    {
        try {
            DB::beginTransaction();
            
            $year = Carbon::now()->year;
            
            $fromBudget = DepartmentBudget::where('department', $fromDepartment)
                ->where('budget_year', $year)
                ->first();
            
            $toBudget = DepartmentBudget::where('department', $toDepartment)
                ->where('budget_year', $year)
                ->first();
            
            if (!$fromBudget || !$toBudget) {
                throw new \Exception('One or both departments not found');
            }
            
            if ($fromBudget->available_amount < $amount) {
                throw new \Exception('Insufficient available budget for transfer');
            }
            
            // Update budgets
            $fromBudget->decrement('allocated_budget', $amount);
            $fromBudget->decrement('available_amount', $amount);
            
            $toBudget->increment('allocated_budget', $amount);
            $toBudget->increment('available_amount', $amount);
            
            // Log the transfer
            Log::info('Budget transfer completed', [
                'from_department' => $fromDepartment,
                'to_department' => $toDepartment,
                'amount' => $amount,
                'reason' => $reason,
                'approved_by' => $approvedBy
            ]);
            
            DB::commit();
            
            // Clear cache
            $this->clearBudgetCache($year);
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Budget transfer failed', [
                'error' => $e->getMessage(),
                'from_department' => $fromDepartment,
                'to_department' => $toDepartment,
                'amount' => $amount
            ]);
            throw $e;
        }
    }

    /**
     * Approve budget allocation
     */
    public function approveBudget($budgetId, $approvedBy)
    {
        $budget = DepartmentBudget::findOrFail($budgetId);
        
        if (!$budget->canBeModified()) {
            throw new \Exception('Budget cannot be approved in current status');
        }
        
        $budget->approve($approvedBy);
        
        // Clear cache
        $this->clearBudgetCache($budget->budget_year);
        
        Log::info('Budget approved', [
            'budget_id' => $budgetId,
            'department' => $budget->department,
            'approved_by' => $approvedBy
        ]);
        
        return $budget;
    }

    /**
     * Get budget utilization report
     */
    public function getUtilizationReport($year = null, $department = null)
    {
        $year = $year ?? Carbon::now()->year;
        
        $query = DepartmentBudget::where('budget_year', $year);
        
        if ($department) {
            $query->where('department', $department);
        }
        
        $budgets = $query->get();
        
        $report = [];
        foreach ($budgets as $budget) {
            $report[] = [
                'department' => $budget->department,
                'allocated_budget' => $budget->allocated_budget,
                'spent_amount' => $budget->spent_amount,
                'available_amount' => $budget->available_amount,
                'utilization_percentage' => $budget->utilization_percentage,
                'variance' => $budget->variance,
                'variance_percentage' => $budget->variance_percentage,
                'status' => $budget->budget_status,
                'quarterly_breakdown' => [
                    'Q1' => [
                        'budgeted' => $budget->quarterly_q1_budget,
                        'spent' => $budget->quarterly_q1_spent,
                        'variance' => $budget->getQuarterlyVariance(1)
                    ],
                    'Q2' => [
                        'budgeted' => $budget->quarterly_q2_budget,
                        'spent' => $budget->quarterly_q2_spent,
                        'variance' => $budget->getQuarterlyVariance(2)
                    ],
                    'Q3' => [
                        'budgeted' => $budget->quarterly_q3_budget,
                        'spent' => $budget->quarterly_q3_spent,
                        'variance' => $budget->getQuarterlyVariance(3)
                    ],
                    'Q4' => [
                        'budgeted' => $budget->quarterly_q4_budget,
                        'spent' => $budget->quarterly_q4_spent,
                        'variance' => $budget->getQuarterlyVariance(4)
                    ]
                ]
            ];
        }
        
        return $report;
    }

    /**
     * Clear budget cache
     */
    private function clearBudgetCache($year)
    {
        Cache::forget("department_budget_dashboard_{$year}");
        Cache::forget("budget_summary_{$year}");
        Cache::forget("monthly_trends_{$year}");
        Cache::forget("quarterly_analysis_{$year}");
    }
}