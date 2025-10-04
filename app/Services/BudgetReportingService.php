<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\DepartmentBudget;
use App\Models\BudgetVsActualReport;
use App\Models\Transaction;
use App\Models\Department;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class BudgetReportingService
{
    /**
     * Generate comprehensive budget vs actual dashboard
     */
    public function getBudgetVsActualDashboard($year = null, $department = null)
    {
        $year = $year ?? Carbon::now()->year;
        
        return [
            'summary' => $this->getBudgetSummary($year, $department),
            'monthly_comparison' => $this->getMonthlyComparison($year, $department),
            'variance_analysis' => $this->getVarianceAnalysis($year, $department),
            'department_performance' => $this->getDepartmentPerformance($year),
            'trend_analysis' => $this->getTrendAnalysis($year, $department),
            'risk_indicators' => $this->getRiskIndicators($year, $department),
            'forecast_vs_actual' => $this->getForecastVsActual($year, $department),
            'action_items' => $this->getActionItems($year, $department)
        ];
    }

    /**
     * Get budget summary with key metrics
     */
    public function getBudgetSummary($year, $department = null)
    {
        $cacheKey = "budget_summary_{$year}_" . ($department ?? 'all');
        
        return Cache::remember($cacheKey, 3600, function () use ($year, $department) {
            $query = DepartmentBudget::where('budget_year', $year);
            
            if ($department) {
                $query->where('department', $department);
            }
            
            $budgets = $query->get();
            
            $totalBudgeted = $budgets->sum('total_budget');
            $totalSpent = $budgets->sum('total_spent');
            $totalRemaining = $totalBudgeted - $totalSpent;
            $utilizationRate = $totalBudgeted > 0 ? ($totalSpent / $totalBudgeted) * 100 : 0;
            
            // Calculate YTD metrics
            $currentMonth = Carbon::now()->month;
            $ytdBudgeted = $this->calculateYTDBudgeted($budgets, $currentMonth);
            $ytdVariance = $totalSpent - $ytdBudgeted;
            $ytdVariancePercentage = $ytdBudgeted > 0 ? ($ytdVariance / $ytdBudgeted) * 100 : 0;
            
            return [
                'total_budgeted' => $totalBudgeted,
                'total_spent' => $totalSpent,
                'total_remaining' => $totalRemaining,
                'utilization_rate' => round($utilizationRate, 2),
                'ytd_budgeted' => $ytdBudgeted,
                'ytd_variance' => $ytdVariance,
                'ytd_variance_percentage' => round($ytdVariancePercentage, 2),
                'departments_count' => $budgets->count(),
                'over_budget_count' => $budgets->where('total_spent', '>', 'total_budget')->count(),
                'under_budget_count' => $budgets->where('total_spent', '<', 'total_budget')->count(),
                'on_track_count' => $budgets->filter(function ($budget) {
                    $variance = abs(($budget->total_spent - $budget->total_budget) / max($budget->total_budget, 1)) * 100;
                    return $variance <= 5;
                })->count(),
                'budget_health_score' => $this->calculateBudgetHealthScore($budgets),
                'projected_year_end' => $this->projectYearEndSpending($totalSpent, $year),
                'burn_rate' => $this->calculateBurnRate($totalSpent, $currentMonth)
            ];
        });
    }

    /**
     * Get monthly budget vs actual comparison
     */
    public function getMonthlyComparison($year, $department = null)
    {
        $monthlyData = [];
        
        for ($month = 1; $month <= 12; $month++) {
            $monthName = Carbon::create($year, $month, 1)->format('M');
            $monthlyBudget = $this->getMonthlyBudget($year, $month, $department);
            $monthlyActual = $this->getMonthlyActual($year, $month, $department);
            $variance = $monthlyActual - $monthlyBudget;
            $variancePercentage = $monthlyBudget > 0 ? ($variance / $monthlyBudget) * 100 : 0;
            
            $monthlyData[] = [
                'month' => $monthName,
                'month_number' => $month,
                'budgeted' => $monthlyBudget,
                'actual' => $monthlyActual,
                'variance' => $variance,
                'variance_percentage' => round($variancePercentage, 2),
                'status' => $this->getVarianceStatus($variancePercentage),
                'cumulative_budgeted' => $this->getCumulativeBudget($year, $month, $department),
                'cumulative_actual' => $this->getCumulativeActual($year, $month, $department),
                'forecast' => $this->getForecastForMonth($year, $month, $department)
            ];
        }
        
        return $monthlyData;
    }

    /**
     * Get detailed variance analysis
     */
    public function getVarianceAnalysis($year, $department = null)
    {
        $query = DepartmentBudget::where('budget_year', $year);
        
        if ($department) {
            $query->where('department', $department);
        }
        
        $budgets = $query->get();
        $analysis = [];
        
        foreach ($budgets as $budget) {
            $variance = $budget->total_spent - $budget->total_budget;
            $variancePercentage = $budget->total_budget > 0 ? 
                ($variance / $budget->total_budget) * 100 : 0;
            
            $analysis[] = [
                'department' => $budget->department,
                'budgeted' => $budget->total_budget,
                'actual' => $budget->total_spent,
                'variance' => $variance,
                'variance_percentage' => round($variancePercentage, 2),
                'status' => $this->getVarianceStatus($variancePercentage),
                'risk_level' => $this->assessRiskLevel($variancePercentage, $budget->total_spent),
                'trend' => $this->getVarianceTrend($budget->id, $year),
                'monthly_variances' => $this->getMonthlyVariances($budget),
                'category_breakdown' => $this->getCategoryBreakdown($budget),
                'recommendations' => $this->getVarianceRecommendations($variancePercentage, $budget)
            ];
        }
        
        return [
            'department_analysis' => $analysis,
            'overall_variance' => $this->calculateOverallVariance($budgets),
            'variance_distribution' => $this->getVarianceDistribution($analysis),
            'critical_variances' => array_filter($analysis, function ($item) {
                return abs($item['variance_percentage']) > 20;
            }),
            'top_performers' => $this->getTopPerformers($analysis),
            'underperformers' => $this->getUnderperformers($analysis)
        ];
    }

    /**
     * Get department performance metrics
     */
    public function getDepartmentPerformance($year)
    {
        $departments = Department::all();
        $performance = [];
        
        foreach ($departments as $dept) {
            $budget = DepartmentBudget::where('department', $dept->name)
                ->where('budget_year', $year)
                ->first();
            
            if ($budget) {
                $utilizationRate = $budget->total_budget > 0 ? 
                    ($budget->total_spent / $budget->total_budget) * 100 : 0;
                
                $performance[] = [
                    'department' => $dept->name,
                    'budget_allocated' => $budget->total_budget,
                    'amount_spent' => $budget->total_spent,
                    'utilization_rate' => round($utilizationRate, 2),
                    'efficiency_score' => $this->calculateEfficiencyScore($budget),
                    'compliance_score' => $this->calculateComplianceScore($budget),
                    'performance_grade' => $this->getPerformanceGrade($utilizationRate),
                    'monthly_consistency' => $this->getMonthlyConsistency($budget),
                    'forecast_accuracy' => $this->getForecastAccuracy($budget),
                    'spending_pattern' => $this->getSpendingPattern($budget)
                ];
            }
        }
        
        return $performance;
    }

    /**
     * Get trend analysis data
     */
    public function getTrendAnalysis($year, $department = null)
    {
        $currentYear = $year;
        $previousYear = $year - 1;
        
        return [
            'year_over_year' => $this->getYearOverYearComparison($currentYear, $previousYear, $department),
            'quarterly_trends' => $this->getQuarterlyTrends($year, $department),
            'seasonal_patterns' => $this->getSeasonalPatterns($year, $department),
            'spending_velocity' => $this->getSpendingVelocity($year, $department),
            'budget_adherence_trend' => $this->getBudgetAdherenceTrend($year, $department),
            'variance_trends' => $this->getVarianceTrends($year, $department)
        ];
    }

    /**
     * Get risk indicators
     */
    public function getRiskIndicators($year, $department = null)
    {
        return [
            'budget_overruns' => $this->getBudgetOverruns($year, $department),
            'rapid_spending' => $this->getRapidSpendingAlerts($year, $department),
            'forecast_deviations' => $this->getForecastDeviations($year, $department),
            'seasonal_risks' => $this->getSeasonalRisks($year, $department),
            'cash_flow_risks' => $this->getCashFlowRisks($year, $department),
            'compliance_risks' => $this->getComplianceRisks($year, $department)
        ];
    }

    /**
     * Get forecast vs actual comparison
     */
    public function getForecastVsActual($year, $department = null)
    {
        // This would integrate with the FinancialForecastingService
        return [
            'revenue_forecast_accuracy' => $this->getRevenueForecastAccuracy($year, $department),
            'expense_forecast_accuracy' => $this->getExpenseForecastAccuracy($year, $department),
            'budget_forecast_variance' => $this->getBudgetForecastVariance($year, $department),
            'prediction_confidence' => $this->getPredictionConfidence($year, $department)
        ];
    }

    /**
     * Get action items based on analysis
     */
    public function getActionItems($year, $department = null)
    {
        $actionItems = [];
        
        // Get high variance departments
        $highVarianceDepts = $this->getHighVarianceDepartments($year, $department);
        foreach ($highVarianceDepts as $dept) {
            $actionItems[] = [
                'type' => 'high_variance',
                'priority' => 'high',
                'department' => $dept['department'],
                'description' => "Review budget variance of {$dept['variance_percentage']}% in {$dept['department']}",
                'recommended_action' => 'Conduct detailed spending review and implement corrective measures',
                'due_date' => Carbon::now()->addDays(7)
            ];
        }
        
        // Get budget overruns
        $overruns = $this->getBudgetOverruns($year, $department);
        foreach ($overruns as $overrun) {
            $actionItems[] = [
                'type' => 'budget_overrun',
                'priority' => 'critical',
                'department' => $overrun['department'],
                'description' => "Budget exceeded by ₹{$overrun['excess_amount']} in {$overrun['department']}",
                'recommended_action' => 'Immediate spending freeze and budget reallocation review',
                'due_date' => Carbon::now()->addDays(3)
            ];
        }
        
        return $actionItems;
    }

    /**
     * Generate monthly budget vs actual report
     */
    public function generateMonthlyReport($year, $month, $department = null)
    {
        $reportData = [
            'report_name' => "Monthly Budget vs Actual - " . Carbon::create($year, $month, 1)->format('M Y'),
            'report_type' => 'monthly_budget_vs_actual',
            'period_type' => 'monthly',
            'year' => $year,
            'month' => $month,
            'department_id' => $department ? Department::where('name', $department)->first()?->id : null,
            'generated_at' => now()
        ];
        
        // Calculate budget and actual amounts
        $budgetedAmount = $this->getMonthlyBudget($year, $month, $department);
        $actualAmount = $this->getMonthlyActual($year, $month, $department);
        
        $reportData = array_merge($reportData, [
            'budgeted_amount' => $budgetedAmount,
            'actual_amount' => $actualAmount
        ]);
        
        // Create and save the report
        $report = BudgetVsActualReport::create($reportData);
        
        return $report;
    }

    // Helper methods
    private function calculateYTDBudgeted($budgets, $currentMonth)
    {
        $ytdBudgeted = 0;
        foreach ($budgets as $budget) {
            $monthlyBudgets = $budget->monthly_budgets ?? [];
            for ($month = 1; $month <= $currentMonth; $month++) {
                $ytdBudgeted += $monthlyBudgets[$month] ?? 0;
            }
        }
        return $ytdBudgeted;
    }

    private function calculateBudgetHealthScore($budgets)
    {
        if ($budgets->isEmpty()) return 0;
        
        $totalScore = 0;
        foreach ($budgets as $budget) {
            $variance = abs(($budget->total_spent - $budget->total_budget) / max($budget->total_budget, 1)) * 100;
            if ($variance <= 5) $score = 100;
            elseif ($variance <= 10) $score = 80;
            elseif ($variance <= 20) $score = 60;
            else $score = 40;
            
            $totalScore += $score;
        }
        
        return round($totalScore / $budgets->count(), 2);
    }

    private function projectYearEndSpending($currentSpent, $year)
    {
        $currentMonth = Carbon::now()->month;
        $monthsRemaining = 12 - $currentMonth;
        
        if ($currentMonth == 0) return $currentSpent;
        
        $averageMonthlySpending = $currentSpent / $currentMonth;
        return $currentSpent + ($averageMonthlySpending * $monthsRemaining);
    }

    private function calculateBurnRate($totalSpent, $currentMonth)
    {
        return $currentMonth > 0 ? $totalSpent / $currentMonth : 0;
    }

    private function getMonthlyBudget($year, $month, $department = null)
    {
        $query = DepartmentBudget::where('budget_year', $year);
        
        if ($department) {
            $query->where('department', $department);
        }
        
        $budgets = $query->get();
        $totalMonthlyBudget = 0;
        
        foreach ($budgets as $budget) {
            $monthlyBudgets = $budget->monthly_budgets ?? [];
            $totalMonthlyBudget += $monthlyBudgets[$month] ?? 0;
        }
        
        return $totalMonthlyBudget;
    }

    private function getMonthlyActual($year, $month, $department = null)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();
        
        $query = Transaction::where('type', 'expense')
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [$startDate, $endDate]);
        
        if ($department) {
            $query->where('department', $department);
        }
        
        return $query->sum('amount');
    }

    private function getVarianceStatus($variancePercentage)
    {
        $absVariance = abs($variancePercentage);
        
        if ($absVariance <= 5) return 'excellent';
        if ($absVariance <= 10) return 'good';
        if ($absVariance <= 20) return 'acceptable';
        return 'poor';
    }

    private function assessRiskLevel($variancePercentage, $totalSpent)
    {
        $absVariance = abs($variancePercentage);
        
        if ($absVariance > 30 || $totalSpent > 1000000) return 'critical';
        if ($absVariance > 20) return 'high';
        if ($absVariance > 10) return 'medium';
        return 'low';
    }

    private function getVarianceTrend($budgetId, $year)
    {
        // Implementation for getting variance trend
        return 'stable'; // Placeholder
    }

    private function getMonthlyVariances($budget)
    {
        $variances = [];
        $monthlyBudgets = $budget->monthly_budgets ?? [];
        $monthlySpent = $budget->monthly_spent ?? [];
        
        for ($month = 1; $month <= 12; $month++) {
            $budgeted = $monthlyBudgets[$month] ?? 0;
            $spent = $monthlySpent[$month] ?? 0;
            $variance = $spent - $budgeted;
            
            $variances[] = [
                'month' => $month,
                'budgeted' => $budgeted,
                'spent' => $spent,
                'variance' => $variance,
                'variance_percentage' => $budgeted > 0 ? ($variance / $budgeted) * 100 : 0
            ];
        }
        
        return $variances;
    }

    private function getCategoryBreakdown($budget)
    {
        // Implementation for category breakdown
        return []; // Placeholder
    }

    private function getVarianceRecommendations($variancePercentage, $budget)
    {
        $recommendations = [];
        
        if (abs($variancePercentage) > 20) {
            $recommendations[] = 'Conduct immediate budget review';
            $recommendations[] = 'Implement stricter spending controls';
        } elseif (abs($variancePercentage) > 10) {
            $recommendations[] = 'Monitor spending more closely';
            $recommendations[] = 'Review budget allocation';
        }
        
        return $recommendations;
    }

    // Additional helper methods would be implemented here...
    private function calculateOverallVariance($budgets) { return 0; }
    private function getVarianceDistribution($analysis) { return []; }
    private function getTopPerformers($analysis) { return []; }
    private function getUnderperformers($analysis) { return []; }
    private function calculateEfficiencyScore($budget) { return 85; }
    private function calculateComplianceScore($budget) { return 90; }
    private function getPerformanceGrade($utilizationRate) { return 'B+'; }
    private function getMonthlyConsistency($budget) { return 'high'; }
    private function getForecastAccuracy($budget) { return 88; }
    private function getSpendingPattern($budget) { return 'consistent'; }
    private function getYearOverYearComparison($current, $previous, $dept) { return []; }
    private function getQuarterlyTrends($year, $dept) { return []; }
    private function getSeasonalPatterns($year, $dept) { return []; }
    private function getSpendingVelocity($year, $dept) { return []; }
    private function getBudgetAdherenceTrend($year, $dept) { return []; }
    private function getVarianceTrends($year, $dept) { return []; }
    private function getBudgetOverruns($year, $dept) { return []; }
    private function getRapidSpendingAlerts($year, $dept) { return []; }
    private function getForecastDeviations($year, $dept) { return []; }
    private function getSeasonalRisks($year, $dept) { return []; }
    private function getCashFlowRisks($year, $dept) { return []; }
    private function getComplianceRisks($year, $dept) { return []; }
    private function getRevenueForecastAccuracy($year, $dept) { return 85; }
    private function getExpenseForecastAccuracy($year, $dept) { return 82; }
    private function getBudgetForecastVariance($year, $dept) { return 12; }
    private function getPredictionConfidence($year, $dept) { return 78; }
    private function getHighVarianceDepartments($year, $dept) { return []; }
    private function getCumulativeBudget($year, $month, $dept) { return 0; }
    private function getCumulativeActual($year, $month, $dept) { return 0; }
    private function getForecastForMonth($year, $month, $dept) { return 0; }
}