<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Budget;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\BudgetVarianceAlert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class BudgetVarianceMonitor implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $budgetId;
    protected $department;
    protected $forceCheck;

    /**
     * Create a new job instance.
     */
    public function __construct($budgetId = null, $department = null, $forceCheck = false)
    {
        $this->budgetId = $budgetId;
        $this->department = $department;
        $this->forceCheck = $forceCheck;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            Log::info('Budget variance monitoring started', [
                'budget_id' => $this->budgetId,
                'department' => $this->department,
                'force_check' => $this->forceCheck
            ]);

            if ($this->budgetId) {
                $this->checkSpecificBudget($this->budgetId);
            } else {
                $this->checkAllBudgets();
            }

            Log::info('Budget variance monitoring completed successfully');
        } catch (\Exception $e) {
            Log::error('Budget variance monitoring failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Check specific budget
     */
    private function checkSpecificBudget($budgetId)
    {
        $budget = Budget::find($budgetId);
        
        if (!$budget) {
            Log::warning('Budget not found for variance check', ['budget_id' => $budgetId]);
            return;
        }

        $this->analyzeBudgetVariance($budget);
    }

    /**
     * Check all active budgets
     */
    private function checkAllBudgets()
    {
        $currentYear = Carbon::now()->year;
        
        $budgets = Budget::where('year', $currentYear)
            ->where('total_budget', '>', 0)
            ->get();

        foreach ($budgets as $budget) {
            $this->analyzeBudgetVariance($budget);
        }

        // Check department-wise budgets if department filter is specified
        if ($this->department) {
            $this->checkDepartmentBudgets($this->department);
        }
    }

    /**
     * Analyze budget variance for a specific budget
     */
    private function analyzeBudgetVariance(Budget $budget)
    {
        // Calculate current spending
        $currentSpent = $this->calculateCurrentSpending($budget);
        
        // Update budget spent amount
        $budget->update(['spent_amount' => $currentSpent]);

        // Calculate variance percentage
        $variancePercentage = ($currentSpent / $budget->total_budget) * 100;

        // Check if we need to send alerts
        $this->checkAndSendAlerts($budget, $variancePercentage);

        Log::info('Budget variance analyzed', [
            'budget_id' => $budget->id,
            'budget_year' => $budget->year,
            'total_budget' => $budget->total_budget,
            'current_spent' => $currentSpent,
            'variance_percentage' => $variancePercentage
        ]);
    }

    /**
     * Calculate current spending for a budget
     */
    private function calculateCurrentSpending(Budget $budget)
    {
        $startOfYear = Carbon::create($budget->year, 1, 1)->startOfDay();
        $endOfYear = Carbon::create($budget->year, 12, 31)->endOfDay();

        return Transaction::where('type', 'expense')
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [$startOfYear, $endOfYear])
            ->sum('amount');
    }

    /**
     * Check department-specific budgets
     */
    private function checkDepartmentBudgets($department)
    {
        $currentYear = Carbon::now()->year;
        $startOfYear = Carbon::create($currentYear, 1, 1)->startOfDay();
        $endOfYear = Carbon::create($currentYear, 12, 31)->endOfDay();

        // Calculate department spending
        $departmentSpending = Transaction::where('type', 'expense')
            ->where('status', 'completed')
            ->where('department', $department)
            ->whereBetween('transaction_date', [$startOfYear, $endOfYear])
            ->sum('amount');

        // Get department budget allocation (assuming it's stored in a settings table or calculated)
        $departmentBudget = $this->getDepartmentBudgetAllocation($department, $currentYear);

        if ($departmentBudget > 0) {
            $variancePercentage = ($departmentSpending / $departmentBudget) * 100;
            
            // Create a virtual budget object for department
            $virtualBudget = new Budget([
                'year' => $currentYear,
                'total_budget' => $departmentBudget,
                'spent_amount' => $departmentSpending
            ]);
            $virtualBudget->id = "dept_{$department}_{$currentYear}";

            $this->checkAndSendAlerts($virtualBudget, $variancePercentage, $department);
        }
    }

    /**
     * Get department budget allocation
     */
    private function getDepartmentBudgetAllocation($department, $year)
    {
        // This could be retrieved from a department_budgets table or calculated
        // For now, we'll use a simple calculation based on total budget
        $totalBudget = Budget::where('year', $year)->sum('total_budget');
        
        // Default department allocation percentages
        $departmentAllocations = [
            'IT' => 0.15,
            'HR' => 0.10,
            'Finance' => 0.08,
            'Operations' => 0.25,
            'Marketing' => 0.12,
            'Sales' => 0.20,
            'Administration' => 0.10
        ];

        $allocationPercentage = $departmentAllocations[$department] ?? 0.10;
        return $totalBudget * $allocationPercentage;
    }

    /**
     * Check and send alerts based on variance thresholds
     */
    private function checkAndSendAlerts(Budget $budget, $variancePercentage, $department = null)
    {
        $alertThresholds = [
            'warning' => 75,    // 75% of budget used
            'critical' => 90,   // 90% of budget used
            'exceeded' => 100   // Budget exceeded
        ];

        $alertType = null;
        $shouldSendAlert = false;

        // Determine alert type
        if ($variancePercentage >= $alertThresholds['exceeded']) {
            $alertType = 'exceeded';
        } elseif ($variancePercentage >= $alertThresholds['critical']) {
            $alertType = 'critical';
        } elseif ($variancePercentage >= $alertThresholds['warning']) {
            $alertType = 'warning';
        }

        if ($alertType) {
            // Check if we've already sent this type of alert recently
            $cacheKey = "budget_alert_{$budget->id}_{$alertType}" . ($department ? "_{$department}" : '');
            
            if ($this->forceCheck || !Cache::has($cacheKey)) {
                $shouldSendAlert = true;
                
                // Cache the alert to prevent spam (cache for different durations based on alert type)
                $cacheDuration = match($alertType) {
                    'exceeded' => 60,      // 1 hour for exceeded alerts
                    'critical' => 240,     // 4 hours for critical alerts
                    'warning' => 1440,     // 24 hours for warning alerts
                    default => 1440
                };
                
                Cache::put($cacheKey, true, $cacheDuration);
            }
        }

        if ($shouldSendAlert) {
            $this->sendBudgetAlert($budget, $variancePercentage, $alertType, $department);
        }
    }

    /**
     * Send budget variance alert to relevant users
     */
    private function sendBudgetAlert(Budget $budget, $variancePercentage, $alertType, $department = null)
    {
        // Get users who should receive budget alerts
        $recipients = $this->getBudgetAlertRecipients($department);

        foreach ($recipients as $user) {
            try {
                $user->notify(new BudgetVarianceAlert($budget, $variancePercentage, $alertType, $department));
                
                Log::info('Budget variance alert sent', [
                    'user_id' => $user->id,
                    'budget_id' => $budget->id,
                    'alert_type' => $alertType,
                    'variance_percentage' => $variancePercentage,
                    'department' => $department
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send budget variance alert', [
                    'user_id' => $user->id,
                    'budget_id' => $budget->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Get users who should receive budget alerts
     */
    private function getBudgetAlertRecipients($department = null)
    {
        $query = User::where('is_active', true);

        // If department is specified, include department heads and managers
        if ($department) {
            $query->where(function($q) use ($department) {
                $q->where('department', $department)
                  ->whereIn('role', ['manager', 'head', 'admin'])
                  ->orWhere('role', 'admin')
                  ->orWhere('role', 'finance_manager');
            });
        } else {
            // For general budget alerts, notify admins and finance managers
            $query->whereIn('role', ['admin', 'finance_manager', 'manager']);
        }

        return $query->get();
    }

    /**
     * Get budget variance summary
     */
    public static function getBudgetVarianceSummary($year = null)
    {
        $year = $year ?? Carbon::now()->year;
        
        $budgets = Budget::where('year', $year)->get();
        $summary = [
            'total_budgets' => $budgets->count(),
            'total_allocated' => $budgets->sum('total_budget'),
            'total_spent' => $budgets->sum('spent_amount'),
            'overall_variance' => 0,
            'budgets_at_risk' => 0,
            'budgets_exceeded' => 0,
            'departments' => []
        ];

        if ($summary['total_allocated'] > 0) {
            $summary['overall_variance'] = ($summary['total_spent'] / $summary['total_allocated']) * 100;
        }

        foreach ($budgets as $budget) {
            $variance = ($budget->spent_amount / $budget->total_budget) * 100;
            
            if ($variance >= 100) {
                $summary['budgets_exceeded']++;
            } elseif ($variance >= 75) {
                $summary['budgets_at_risk']++;
            }
        }

        return $summary;
    }
}