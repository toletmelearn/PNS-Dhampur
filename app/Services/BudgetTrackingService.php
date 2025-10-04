<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Transaction;
use App\Models\Teacher;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class BudgetTrackingService
{
    protected $cachePrefix = 'budget_tracking_';
    protected $cacheTTL = 300; // 5 minutes

    /**
     * Get real-time budget dashboard data
     */
    public function getRealTimeDashboard($year = null, $department = null)
    {
        $year = $year ?? now()->year;
        $cacheKey = $this->cachePrefix . "dashboard_{$year}_{$department}";
        
        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($year, $department) {
            return [
                'overview' => $this->getBudgetOverview($year, $department),
                'expense_monitoring' => $this->getExpenseMonitoring($year, $department),
                'variance_analysis' => $this->getVarianceAnalysis($year, $department),
                'department_breakdown' => $this->getDepartmentBreakdown($year),
                'monthly_trends' => $this->getMonthlyTrends($year, $department),
                'alerts' => $this->getBudgetAlerts($year, $department),
                'forecasting' => $this->getFinancialForecasting($year, $department),
                'top_expenses' => $this->getTopExpenses($year, $department),
                'budget_utilization' => $this->getBudgetUtilization($year, $department)
            ];
        });
    }

    /**
     * Get budget overview metrics
     */
    protected function getBudgetOverview($year, $department = null)
    {
        $query = Budget::where('year', $year);
        if ($department) {
            $query->where('department', $department);
        }

        $budgets = $query->get();
        $totalBudget = $budgets->sum('total_budget');
        $totalSpent = $budgets->sum('spent_amount');
        $remaining = $totalBudget - $totalSpent;
        $utilizationRate = $totalBudget > 0 ? ($totalSpent / $totalBudget) * 100 : 0;

        // Get current month spending
        $currentMonthSpent = $this->getCurrentMonthSpending($year, $department);
        $averageMonthlySpent = $totalSpent / max(1, now()->month);
        
        return [
            'total_budget' => $totalBudget,
            'total_spent' => $totalSpent,
            'remaining_budget' => $remaining,
            'utilization_rate' => round($utilizationRate, 2),
            'current_month_spent' => $currentMonthSpent,
            'average_monthly_spent' => round($averageMonthlySpent, 2),
            'budget_status' => $this->getBudgetStatus($utilizationRate),
            'departments_count' => $budgets->groupBy('department')->count(),
            'variance_percentage' => $this->calculateVariancePercentage($totalBudget, $totalSpent),
            'projected_year_end' => $this->projectYearEndSpending($totalSpent, $year)
        ];
    }

    /**
     * Get real-time expense monitoring data
     */
    protected function getExpenseMonitoring($year, $department = null)
    {
        $startDate = Carbon::create($year, 1, 1);
        $endDate = Carbon::create($year, 12, 31);

        $query = Transaction::whereBetween('created_at', [$startDate, $endDate])
                           ->where('type', 'expense');

        if ($department) {
            $query->where('department', $department);
        }

        $transactions = $query->get();

        // Daily spending trend (last 30 days)
        $dailySpending = $this->getDailySpendingTrend($year, $department);
        
        // Category-wise expenses
        $categoryExpenses = $transactions->groupBy('category')->map(function ($group) {
            return [
                'total' => $group->sum('amount'),
                'count' => $group->count(),
                'average' => $group->avg('amount')
            ];
        });

        // Recent transactions
        $recentTransactions = $transactions->sortByDesc('created_at')->take(10)->values();

        return [
            'daily_spending' => $dailySpending,
            'category_expenses' => $categoryExpenses,
            'recent_transactions' => $recentTransactions,
            'total_transactions' => $transactions->count(),
            'average_transaction_amount' => $transactions->avg('amount'),
            'largest_expense' => $transactions->max('amount'),
            'expense_frequency' => $this->getExpenseFrequency($transactions),
            'pending_approvals' => $this->getPendingApprovals($year, $department)
        ];
    }

    /**
     * Get variance analysis data
     */
    protected function getVarianceAnalysis($year, $department = null)
    {
        $budgets = Budget::where('year', $year);
        if ($department) {
            $budgets->where('department', $department);
        }
        $budgets = $budgets->get();

        $analysis = [];
        foreach ($budgets as $budget) {
            $variance = $budget->spent_amount - $budget->total_budget;
            $variancePercentage = $budget->total_budget > 0 ? 
                ($variance / $budget->total_budget) * 100 : 0;

            $analysis[] = [
                'department' => $budget->department ?? 'General',
                'budgeted' => $budget->total_budget,
                'actual' => $budget->spent_amount,
                'variance' => $variance,
                'variance_percentage' => round($variancePercentage, 2),
                'status' => $this->getVarianceStatus($variancePercentage),
                'trend' => $this->getVarianceTrend($budget->id, $year),
                'forecast' => $this->forecastDepartmentSpending($budget, $year)
            ];
        }

        return [
            'department_variances' => $analysis,
            'overall_variance' => $this->calculateOverallVariance($budgets),
            'variance_trends' => $this->getVarianceTrends($year, $department),
            'critical_variances' => array_filter($analysis, function ($item) {
                return abs($item['variance_percentage']) > 20;
            })
        ];
    }

    /**
     * Get department-wise budget breakdown
     */
    protected function getDepartmentBreakdown($year)
    {
        $departments = Budget::where('year', $year)
                            ->select('department', 
                                   DB::raw('SUM(total_budget) as total_budget'),
                                   DB::raw('SUM(spent_amount) as spent_amount'))
                            ->groupBy('department')
                            ->get();

        return $departments->map(function ($dept) {
            $utilization = $dept->total_budget > 0 ? 
                ($dept->spent_amount / $dept->total_budget) * 100 : 0;
            
            return [
                'department' => $dept->department ?? 'General',
                'allocated' => $dept->total_budget,
                'spent' => $dept->spent_amount,
                'remaining' => $dept->total_budget - $dept->spent_amount,
                'utilization_rate' => round($utilization, 2),
                'status' => $this->getBudgetStatus($utilization),
                'monthly_burn_rate' => $this->getMonthlyBurnRate($dept->department, $year),
                'projected_completion' => $this->projectBudgetCompletion($dept, $year)
            ];
        });
    }

    /**
     * Get monthly spending trends
     */
    protected function getMonthlyTrends($year, $department = null)
    {
        $startDate = Carbon::create($year, 1, 1);
        $endDate = Carbon::create($year, 12, 31);

        $query = Transaction::whereBetween('created_at', [$startDate, $endDate])
                           ->where('type', 'expense');

        if ($department) {
            $query->where('department', $department);
        }

        $monthlyData = $query->selectRaw('MONTH(created_at) as month, SUM(amount) as total')
                            ->groupBy('month')
                            ->orderBy('month')
                            ->get()
                            ->keyBy('month');

        $trends = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthName = Carbon::create($year, $month, 1)->format('M');
            $amount = $monthlyData->get($month)->total ?? 0;
            
            $trends[] = [
                'month' => $monthName,
                'month_number' => $month,
                'amount' => $amount,
                'budget_allocated' => $this->getMonthlyBudgetAllocation($year, $month, $department),
                'variance' => $this->getMonthlyVariance($year, $month, $department),
                'trend' => $this->getMonthlyTrend($year, $month, $department)
            ];
        }

        return $trends;
    }

    /**
     * Get budget alerts and notifications
     */
    public function getBudgetAlerts($year, $department = null)
    {
        $alerts = [];
        
        // Over-budget alerts
        $overBudgetDepts = $this->getOverBudgetDepartments($year, $department);
        foreach ($overBudgetDepts as $dept) {
            $alerts[] = [
                'type' => 'over_budget',
                'severity' => 'critical',
                'department' => $dept['department'],
                'message' => "Department '{$dept['department']}' is {$dept['variance_percentage']}% over budget",
                'amount' => $dept['variance'],
                'created_at' => now()
            ];
        }

        // Low budget alerts (less than 10% remaining)
        $lowBudgetDepts = $this->getLowBudgetDepartments($year, $department);
        foreach ($lowBudgetDepts as $dept) {
            $alerts[] = [
                'type' => 'low_budget',
                'severity' => 'warning',
                'department' => $dept['department'],
                'message' => "Department '{$dept['department']}' has only {$dept['remaining_percentage']}% budget remaining",
                'amount' => $dept['remaining'],
                'created_at' => now()
            ];
        }

        // High spending velocity alerts
        $highSpendingDepts = $this->getHighSpendingVelocityDepartments($year, $department);
        foreach ($highSpendingDepts as $dept) {
            $alerts[] = [
                'type' => 'high_spending',
                'severity' => 'warning',
                'department' => $dept['department'],
                'message' => "Department '{$dept['department']}' spending velocity is {$dept['velocity_increase']}% above normal",
                'amount' => $dept['current_velocity'],
                'created_at' => now()
            ];
        }

        // Unusual expense patterns
        $unusualExpenses = $this->getUnusualExpensePatterns($year, $department);
        foreach ($unusualExpenses as $expense) {
            $alerts[] = [
                'type' => 'unusual_expense',
                'severity' => 'info',
                'department' => $expense['department'],
                'message' => "Unusual expense pattern detected: {$expense['description']}",
                'amount' => $expense['amount'],
                'created_at' => now()
            ];
        }

        return collect($alerts)->sortByDesc('severity')->values()->all();
    }

    /**
     * Get financial forecasting data
     */
    protected function getFinancialForecasting($year, $department = null)
    {
        $currentMonth = now()->month;
        $monthsRemaining = 12 - $currentMonth;
        
        // Get historical spending patterns
        $historicalData = $this->getHistoricalSpendingPatterns($year, $department);
        
        // Calculate different forecasting models
        $linearForecast = $this->calculateLinearForecast($year, $department);
        $seasonalForecast = $this->calculateSeasonalForecast($year, $department);
        $trendForecast = $this->calculateTrendForecast($year, $department);
        
        return [
            'linear_forecast' => $linearForecast,
            'seasonal_forecast' => $seasonalForecast,
            'trend_forecast' => $trendForecast,
            'recommended_forecast' => $this->getRecommendedForecast($linearForecast, $seasonalForecast, $trendForecast),
            'confidence_level' => $this->calculateForecastConfidence($historicalData),
            'risk_factors' => $this->identifyRiskFactors($year, $department),
            'budget_recommendations' => $this->getBudgetRecommendations($year, $department),
            'months_remaining' => $monthsRemaining,
            'projected_year_end' => $this->projectYearEndSpending($this->getCurrentSpending($year, $department), $year)
        ];
    }

    // Helper methods for calculations
    protected function getBudgetStatus($utilizationRate)
    {
        if ($utilizationRate > 100) return 'over_budget';
        if ($utilizationRate > 90) return 'critical';
        if ($utilizationRate > 75) return 'warning';
        return 'healthy';
    }

    protected function calculateVariancePercentage($budget, $spent)
    {
        return $budget > 0 ? round((($spent - $budget) / $budget) * 100, 2) : 0;
    }

    protected function projectYearEndSpending($currentSpent, $year)
    {
        $monthsElapsed = now()->month;
        $monthlyAverage = $currentSpent / max(1, $monthsElapsed);
        return round($monthlyAverage * 12, 2);
    }

    protected function getCurrentMonthSpending($year, $department = null)
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $query = Transaction::whereBetween('created_at', [$startOfMonth, $endOfMonth])
                           ->where('type', 'expense');

        if ($department) {
            $query->where('department', $department);
        }

        return $query->sum('amount');
    }

    protected function getDailySpendingTrend($year, $department = null)
    {
        $last30Days = now()->subDays(30);
        
        $query = Transaction::where('created_at', '>=', $last30Days)
                           ->where('type', 'expense');

        if ($department) {
            $query->where('department', $department);
        }

        return $query->selectRaw('DATE(created_at) as date, SUM(amount) as total')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();
    }

    protected function getExpenseFrequency($transactions)
    {
        $daily = $transactions->groupBy(function ($item) {
            return $item->created_at->format('Y-m-d');
        })->count();

        $weekly = $transactions->groupBy(function ($item) {
            return $item->created_at->format('Y-W');
        })->count();

        return [
            'daily_average' => round($daily / 30, 2),
            'weekly_average' => round($weekly / 4, 2),
            'peak_day' => $this->getPeakSpendingDay($transactions),
            'peak_time' => $this->getPeakSpendingTime($transactions)
        ];
    }

    protected function getPendingApprovals($year, $department = null)
    {
        $query = PurchaseOrder::where('status', 'pending')
                             ->whereYear('created_at', $year);

        if ($department) {
            $query->where('department', $department);
        }

        return $query->sum('total_amount');
    }

    // Additional helper methods would be implemented here...
    protected function getVarianceStatus($percentage) { /* Implementation */ }
    protected function getVarianceTrend($budgetId, $year) { /* Implementation */ }
    protected function forecastDepartmentSpending($budget, $year) { /* Implementation */ }
    protected function calculateOverallVariance($budgets) { /* Implementation */ }
    protected function getVarianceTrends($year, $department) { /* Implementation */ }
    protected function getMonthlyBurnRate($department, $year) { /* Implementation */ }
    protected function projectBudgetCompletion($dept, $year) { /* Implementation */ }
    protected function getMonthlyBudgetAllocation($year, $month, $department) { /* Implementation */ }
    protected function getMonthlyVariance($year, $month, $department) { /* Implementation */ }
    protected function getMonthlyTrend($year, $month, $department) { /* Implementation */ }
    protected function getOverBudgetDepartments($year, $department) { /* Implementation */ }
    protected function getLowBudgetDepartments($year, $department) { /* Implementation */ }
    protected function getHighSpendingVelocityDepartments($year, $department) { /* Implementation */ }
    protected function getUnusualExpensePatterns($year, $department) { /* Implementation */ }
    protected function getHistoricalSpendingPatterns($year, $department) { /* Implementation */ }
    protected function calculateLinearForecast($year, $department) { /* Implementation */ }
    protected function calculateSeasonalForecast($year, $department) { /* Implementation */ }
    protected function calculateTrendForecast($year, $department) { /* Implementation */ }
    protected function getRecommendedForecast($linear, $seasonal, $trend) { /* Implementation */ }
    protected function calculateForecastConfidence($historicalData) { /* Implementation */ }
    protected function identifyRiskFactors($year, $department) { /* Implementation */ }
    protected function getBudgetRecommendations($year, $department) { /* Implementation */ }
    protected function getCurrentSpending($year, $department) { /* Implementation */ }
    protected function getTopExpenses($year, $department) { /* Implementation */ }
    protected function getBudgetUtilization($year, $department) { /* Implementation */ }
    protected function getPeakSpendingDay($transactions) { /* Implementation */ }
    protected function getPeakSpendingTime($transactions) { /* Implementation */ }

    /**
     * Clear cache for budget tracking
     */
    public function clearCache($year = null, $department = null)
    {
        $pattern = $this->cachePrefix . "*";
        if ($year) {
            $pattern = $this->cachePrefix . "dashboard_{$year}_*";
        }
        
        Cache::flush(); // For simplicity, flush all cache
    }

    /**
     * Update real-time spending data
     */
    public function updateRealTimeSpending($transactionId)
    {
        // This would be called when a new transaction is created
        $this->clearCache();
        
        // Trigger alerts if necessary
        $this->checkAndTriggerAlerts();
    }

    /**
     * Check and trigger budget alerts
     */
    protected function checkAndTriggerAlerts()
    {
        $alerts = $this->getBudgetAlerts(now()->year);
        
        foreach ($alerts as $alert) {
            if ($alert['severity'] === 'critical') {
                // Send immediate notification
                $this->sendBudgetAlert($alert);
            }
        }
    }

    /**
     * Send budget alert notification
     */
    protected function sendBudgetAlert($alert)
    {
        // Implementation for sending notifications
        // This could integrate with email, SMS, or in-app notifications
    }
}