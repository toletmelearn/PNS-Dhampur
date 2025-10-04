<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Transaction;
use App\Models\DepartmentBudget;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Fee;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FinancialForecastingService
{
    protected $cachePrefix = 'financial_forecast_';
    protected $cacheTTL = 600; // 10 minutes

    /**
     * Get comprehensive financial forecasting dashboard
     */
    public function getForecastingDashboard($year = null, $department = null, $months = 12)
    {
        $year = $year ?? now()->year;
        $cacheKey = $this->cachePrefix . "dashboard_{$year}_{$department}_{$months}";
        
        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($year, $department, $months) {
            return [
                'overview' => $this->getForecastOverview($year, $department, $months),
                'revenue_forecast' => $this->getRevenueForecast($year, $department, $months),
                'expense_forecast' => $this->getExpenseForecast($year, $department, $months),
                'cash_flow_forecast' => $this->getCashFlowForecast($year, $department, $months),
                'budget_scenarios' => $this->getBudgetScenarios($year, $department, $months),
                'risk_analysis' => $this->getRiskAnalysis($year, $department),
                'seasonal_patterns' => $this->getSeasonalPatterns($year, $department),
                'variance_predictions' => $this->getVariancePredictions($year, $department),
                'recommendations' => $this->getForecastRecommendations($year, $department),
                'confidence_metrics' => $this->getConfidenceMetrics($year, $department)
            ];
        });
    }

    /**
     * Get forecast overview with key metrics
     */
    protected function getForecastOverview($year, $department, $months)
    {
        $currentSpending = $this->getCurrentSpending($year, $department);
        $currentRevenue = $this->getCurrentRevenue($year, $department);
        $historicalData = $this->getHistoricalData($year - 1, $department);
        
        $projectedRevenue = $this->projectAnnualRevenue($year, $department);
        $projectedExpenses = $this->projectAnnualExpenses($year, $department);
        $projectedSurplus = $projectedRevenue - $projectedExpenses;
        
        return [
            'current_month' => now()->month,
            'months_remaining' => 12 - now()->month,
            'current_spending' => $currentSpending,
            'current_revenue' => $currentRevenue,
            'projected_revenue' => $projectedRevenue,
            'projected_expenses' => $projectedExpenses,
            'projected_surplus' => $projectedSurplus,
            'budget_utilization' => $this->calculateBudgetUtilization($year, $department),
            'growth_rate' => $this->calculateGrowthRate($year, $department),
            'burn_rate' => $this->calculateBurnRate($year, $department),
            'runway_months' => $this->calculateRunwayMonths($year, $department),
            'forecast_accuracy' => $this->calculateForecastAccuracy($year, $department)
        ];
    }

    /**
     * Get revenue forecasting with multiple models
     */
    protected function getRevenueForecast($year, $department, $months)
    {
        $historicalRevenue = $this->getHistoricalRevenue($year, $department);
        $studentEnrollment = $this->getStudentEnrollmentTrends();
        $feeStructure = $this->getCurrentFeeStructure();
        
        return [
            'linear_model' => $this->calculateLinearRevenueForecast($historicalRevenue, $months),
            'seasonal_model' => $this->calculateSeasonalRevenueForecast($historicalRevenue, $months),
            'enrollment_based' => $this->calculateEnrollmentBasedForecast($studentEnrollment, $feeStructure, $months),
            'trend_model' => $this->calculateTrendRevenueForecast($historicalRevenue, $months),
            'composite_forecast' => $this->calculateCompositeRevenueForecast($historicalRevenue, $months),
            'monthly_breakdown' => $this->getMonthlyRevenueForecast($year, $department, $months),
            'confidence_intervals' => $this->calculateRevenueConfidenceIntervals($historicalRevenue),
            'scenario_analysis' => $this->getRevenueScenarios($year, $department, $months)
        ];
    }

    /**
     * Get expense forecasting with category breakdown
     */
    protected function getExpenseForecast($year, $department, $months)
    {
        $historicalExpenses = $this->getHistoricalExpenses($year, $department);
        $staffingCosts = $this->getStaffingCostProjections($year, $department);
        $operationalCosts = $this->getOperationalCostProjections($year, $department);
        
        return [
            'total_forecast' => $this->calculateTotalExpenseForecast($historicalExpenses, $months),
            'category_breakdown' => [
                'salaries' => $this->forecastSalaryCosts($year, $department, $months),
                'utilities' => $this->forecastUtilityCosts($year, $department, $months),
                'maintenance' => $this->forecastMaintenanceCosts($year, $department, $months),
                'supplies' => $this->forecastSupplyCosts($year, $department, $months),
                'equipment' => $this->forecastEquipmentCosts($year, $department, $months),
                'other' => $this->forecastOtherCosts($year, $department, $months)
            ],
            'fixed_vs_variable' => $this->analyzeFixedVsVariableCosts($historicalExpenses),
            'seasonal_adjustments' => $this->calculateSeasonalExpenseAdjustments($historicalExpenses),
            'inflation_impact' => $this->calculateInflationImpact($year, $department),
            'cost_optimization' => $this->identifyCostOptimizationOpportunities($historicalExpenses)
        ];
    }

    /**
     * Get cash flow forecasting
     */
    protected function getCashFlowForecast($year, $department, $months)
    {
        $revenueForecast = $this->getRevenueForecast($year, $department, $months);
        $expenseForecast = $this->getExpenseForecast($year, $department, $months);
        
        $monthlyData = [];
        for ($i = 1; $i <= $months; $i++) {
            $month = Carbon::create($year, $i, 1);
            $monthlyData[] = [
                'month' => $month->format('Y-m'),
                'month_name' => $month->format('F Y'),
                'revenue' => $revenueForecast['monthly_breakdown'][$i - 1]['amount'] ?? 0,
                'expenses' => $this->getMonthlyExpenseForecast($year, $i, $department),
                'net_cash_flow' => ($revenueForecast['monthly_breakdown'][$i - 1]['amount'] ?? 0) - $this->getMonthlyExpenseForecast($year, $i, $department),
                'cumulative_cash_flow' => 0 // Will be calculated in loop
            ];
        }
        
        // Calculate cumulative cash flow
        $cumulative = 0;
        foreach ($monthlyData as &$data) {
            $cumulative += $data['net_cash_flow'];
            $data['cumulative_cash_flow'] = $cumulative;
        }
        
        return [
            'monthly_data' => $monthlyData,
            'cash_flow_summary' => [
                'total_inflow' => array_sum(array_column($monthlyData, 'revenue')),
                'total_outflow' => array_sum(array_column($monthlyData, 'expenses')),
                'net_cash_flow' => array_sum(array_column($monthlyData, 'net_cash_flow')),
                'peak_cash_month' => $this->findPeakCashMonth($monthlyData),
                'lowest_cash_month' => $this->findLowestCashMonth($monthlyData)
            ],
            'liquidity_analysis' => $this->analyzeLiquidity($monthlyData),
            'working_capital_forecast' => $this->forecastWorkingCapital($year, $department)
        ];
    }

    /**
     * Get budget scenarios (optimistic, realistic, pessimistic)
     */
    protected function getBudgetScenarios($year, $department, $months)
    {
        $baseRevenue = $this->getCurrentRevenue($year, $department);
        $baseExpenses = $this->getCurrentSpending($year, $department);
        
        return [
            'optimistic' => [
                'revenue_growth' => 15,
                'expense_growth' => 5,
                'projected_revenue' => $baseRevenue * 1.15,
                'projected_expenses' => $baseExpenses * 1.05,
                'net_result' => ($baseRevenue * 1.15) - ($baseExpenses * 1.05),
                'probability' => 20
            ],
            'realistic' => [
                'revenue_growth' => 8,
                'expense_growth' => 8,
                'projected_revenue' => $baseRevenue * 1.08,
                'projected_expenses' => $baseExpenses * 1.08,
                'net_result' => ($baseRevenue * 1.08) - ($baseExpenses * 1.08),
                'probability' => 60
            ],
            'pessimistic' => [
                'revenue_growth' => 2,
                'expense_growth' => 12,
                'projected_revenue' => $baseRevenue * 1.02,
                'projected_expenses' => $baseExpenses * 1.12,
                'net_result' => ($baseRevenue * 1.02) - ($baseExpenses * 1.12),
                'probability' => 20
            ],
            'monte_carlo' => $this->runMonteCarloSimulation($year, $department, 1000),
            'sensitivity_analysis' => $this->performSensitivityAnalysis($year, $department)
        ];
    }

    /**
     * Get risk analysis for financial forecasting
     */
    protected function getRiskAnalysis($year, $department)
    {
        return [
            'revenue_risks' => [
                'enrollment_decline' => $this->assessEnrollmentRisk(),
                'fee_collection_delays' => $this->assessFeeCollectionRisk(),
                'competition_impact' => $this->assessCompetitionRisk(),
                'economic_factors' => $this->assessEconomicRisk()
            ],
            'expense_risks' => [
                'salary_inflation' => $this->assessSalaryInflationRisk(),
                'utility_cost_increase' => $this->assessUtilityCostRisk(),
                'maintenance_overruns' => $this->assessMaintenanceRisk(),
                'regulatory_compliance' => $this->assessComplianceRisk()
            ],
            'operational_risks' => [
                'staff_turnover' => $this->assessStaffTurnoverRisk(),
                'infrastructure_failure' => $this->assessInfrastructureRisk(),
                'technology_obsolescence' => $this->assessTechnologyRisk()
            ],
            'risk_mitigation' => $this->generateRiskMitigationStrategies($year, $department),
            'overall_risk_score' => $this->calculateOverallRiskScore($year, $department)
        ];
    }

    /**
     * Get seasonal patterns analysis
     */
    protected function getSeasonalPatterns($year, $department)
    {
        $historicalData = $this->getMultiYearHistoricalData($department, 3);
        
        return [
            'revenue_seasonality' => $this->analyzeRevenueSeasonality($historicalData),
            'expense_seasonality' => $this->analyzeExpenseSeasonality($historicalData),
            'enrollment_patterns' => $this->analyzeEnrollmentPatterns(),
            'fee_collection_patterns' => $this->analyzeFeeCollectionPatterns(),
            'seasonal_adjustments' => $this->calculateSeasonalAdjustments($historicalData),
            'peak_months' => $this->identifyPeakMonths($historicalData),
            'low_months' => $this->identifyLowMonths($historicalData)
        ];
    }

    /**
     * Get variance predictions
     */
    protected function getVariancePredictions($year, $department)
    {
        $budgets = $this->getCurrentBudgets($year, $department);
        $forecasts = $this->getExpenseForecast($year, $department, 12);
        
        $predictions = [];
        foreach ($budgets as $budget) {
            $predictions[] = [
                'department' => $budget->department,
                'budgeted_amount' => $budget->allocated_budget,
                'forecasted_amount' => $forecasts['total_forecast'],
                'predicted_variance' => $forecasts['total_forecast'] - $budget->allocated_budget,
                'variance_percentage' => $this->calculateVariancePercentage($budget->allocated_budget, $forecasts['total_forecast']),
                'confidence_level' => $this->calculateVarianceConfidence($budget, $forecasts),
                'risk_level' => $this->assessVarianceRisk($budget, $forecasts)
            ];
        }
        
        return [
            'department_predictions' => $predictions,
            'overall_variance' => array_sum(array_column($predictions, 'predicted_variance')),
            'high_risk_departments' => array_filter($predictions, fn($p) => $p['risk_level'] === 'high'),
            'variance_trends' => $this->analyzeVarianceTrends($year, $department)
        ];
    }

    /**
     * Get forecast recommendations
     */
    protected function getForecastRecommendations($year, $department)
    {
        $overview = $this->getForecastOverview($year, $department, 12);
        $riskAnalysis = $this->getRiskAnalysis($year, $department);
        
        $recommendations = [];
        
        // Budget recommendations
        if ($overview['projected_surplus'] < 0) {
            $recommendations[] = [
                'type' => 'budget_adjustment',
                'priority' => 'high',
                'title' => 'Budget Deficit Alert',
                'description' => 'Projected deficit of ₹' . number_format(abs($overview['projected_surplus'])),
                'actions' => [
                    'Review and reduce non-essential expenses',
                    'Explore additional revenue streams',
                    'Consider fee structure adjustments',
                    'Implement cost optimization measures'
                ]
            ];
        }
        
        // Cash flow recommendations
        if ($overview['burn_rate'] > $overview['current_revenue'] / 12) {
            $recommendations[] = [
                'type' => 'cash_flow',
                'priority' => 'medium',
                'title' => 'High Burn Rate',
                'description' => 'Monthly burn rate exceeds average monthly revenue',
                'actions' => [
                    'Implement expense controls',
                    'Accelerate fee collection',
                    'Review payment terms with vendors',
                    'Consider short-term financing options'
                ]
            ];
        }
        
        // Revenue recommendations
        if ($overview['growth_rate'] < 5) {
            $recommendations[] = [
                'type' => 'revenue_growth',
                'priority' => 'medium',
                'title' => 'Low Revenue Growth',
                'description' => 'Revenue growth below expected levels',
                'actions' => [
                    'Enhance marketing and outreach',
                    'Introduce new programs or services',
                    'Review pricing strategy',
                    'Improve student retention'
                ]
            ];
        }
        
        return [
            'recommendations' => $recommendations,
            'action_items' => $this->generateActionItems($overview, $riskAnalysis),
            'budget_targets' => $this->suggestBudgetTargets($year, $department),
            'monitoring_kpis' => $this->suggestMonitoringKPIs($year, $department)
        ];
    }

    /**
     * Get confidence metrics for forecasts
     */
    protected function getConfidenceMetrics($year, $department)
    {
        $historicalAccuracy = $this->calculateHistoricalAccuracy($department);
        $dataQuality = $this->assessDataQuality($year, $department);
        $modelPerformance = $this->evaluateModelPerformance($department);
        
        return [
            'overall_confidence' => $this->calculateOverallConfidence($historicalAccuracy, $dataQuality, $modelPerformance),
            'revenue_confidence' => $this->calculateRevenueConfidence($year, $department),
            'expense_confidence' => $this->calculateExpenseConfidence($year, $department),
            'model_accuracy' => $modelPerformance,
            'data_completeness' => $dataQuality,
            'forecast_reliability' => $this->assessForecastReliability($year, $department),
            'uncertainty_factors' => $this->identifyUncertaintyFactors($year, $department)
        ];
    }

    // Helper methods for calculations

    protected function getCurrentSpending($year, $department = null)
    {
        $query = Transaction::where('type', 'expense')
                           ->whereYear('created_at', $year);
        
        if ($department) {
            $query->where('department', $department);
        }
        
        return $query->sum('amount') ?? 0;
    }

    protected function getCurrentRevenue($year, $department = null)
    {
        $query = Transaction::where('type', 'income')
                           ->whereYear('created_at', $year);
        
        if ($department) {
            $query->where('department', $department);
        }
        
        return $query->sum('amount') ?? 0;
    }

    protected function getHistoricalData($year, $department = null)
    {
        $query = Transaction::whereYear('created_at', $year);
        
        if ($department) {
            $query->where('department', $department);
        }
        
        return $query->selectRaw('
            MONTH(created_at) as month,
            SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as revenue,
            SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as expenses
        ')
        ->groupBy('month')
        ->orderBy('month')
        ->get();
    }

    protected function projectAnnualRevenue($year, $department = null)
    {
        $currentMonth = now()->month;
        $currentRevenue = $this->getCurrentRevenue($year, $department);
        
        if ($currentMonth == 0) return 0;
        
        $monthlyAverage = $currentRevenue / $currentMonth;
        return $monthlyAverage * 12;
    }

    protected function projectAnnualExpenses($year, $department = null)
    {
        $currentMonth = now()->month;
        $currentExpenses = $this->getCurrentSpending($year, $department);
        
        if ($currentMonth == 0) return 0;
        
        $monthlyAverage = $currentExpenses / $currentMonth;
        return $monthlyAverage * 12;
    }

    protected function calculateBudgetUtilization($year, $department = null)
    {
        $totalBudget = DepartmentBudget::where('budget_year', $year);
        
        if ($department) {
            $totalBudget->where('department', $department);
        }
        
        $totalBudget = $totalBudget->sum('allocated_budget');
        $currentSpending = $this->getCurrentSpending($year, $department);
        
        return $totalBudget > 0 ? round(($currentSpending / $totalBudget) * 100, 2) : 0;
    }

    protected function calculateGrowthRate($year, $department = null)
    {
        $currentRevenue = $this->getCurrentRevenue($year, $department);
        $previousRevenue = $this->getCurrentRevenue($year - 1, $department);
        
        if ($previousRevenue == 0) return 0;
        
        return round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 2);
    }

    protected function calculateBurnRate($year, $department = null)
    {
        $currentMonth = now()->month;
        $currentExpenses = $this->getCurrentSpending($year, $department);
        
        return $currentMonth > 0 ? $currentExpenses / $currentMonth : 0;
    }

    protected function calculateRunwayMonths($year, $department = null)
    {
        $currentRevenue = $this->getCurrentRevenue($year, $department);
        $burnRate = $this->calculateBurnRate($year, $department);
        
        return $burnRate > 0 ? $currentRevenue / $burnRate : 0;
    }

    protected function calculateForecastAccuracy($year, $department = null)
    {
        // This would compare previous forecasts with actual results
        // For now, return a placeholder value
        return 85.5; // 85.5% accuracy
    }

    protected function calculateVariancePercentage($budget, $actual)
    {
        return $budget > 0 ? round((($actual - $budget) / $budget) * 100, 2) : 0;
    }

    // Additional helper methods would be implemented here...
    protected function getHistoricalRevenue($year, $department) { return collect(); }
    protected function getStudentEnrollmentTrends() { return collect(); }
    protected function getCurrentFeeStructure() { return collect(); }
    protected function calculateLinearRevenueForecast($data, $months) { return []; }
    protected function calculateSeasonalRevenueForecast($data, $months) { return []; }
    protected function calculateEnrollmentBasedForecast($enrollment, $fees, $months) { return []; }
    protected function calculateTrendRevenueForecast($data, $months) { return []; }
    protected function calculateCompositeRevenueForecast($data, $months) { return []; }
    protected function getMonthlyRevenueForecast($year, $department, $months) { return []; }
    protected function calculateRevenueConfidenceIntervals($data) { return []; }
    protected function getRevenueScenarios($year, $department, $months) { return []; }
    
    // More helper methods...
    protected function runMonteCarloSimulation($year, $department, $iterations) { return []; }
    protected function performSensitivityAnalysis($year, $department) { return []; }
    protected function assessEnrollmentRisk() { return 'low'; }
    protected function assessFeeCollectionRisk() { return 'medium'; }
    protected function assessCompetitionRisk() { return 'low'; }
    protected function assessEconomicRisk() { return 'medium'; }
    
    /**
     * Clear forecast cache
     */
    public function clearCache($year = null, $department = null)
    {
        Cache::flush(); // For simplicity, flush all cache
    }
}