<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\BudgetTrackingService;
use App\Models\Budget;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class BudgetTrackingController extends Controller
{
    protected $budgetTrackingService;

    public function __construct(BudgetTrackingService $budgetTrackingService)
    {
        $this->budgetTrackingService = $budgetTrackingService;
        $this->middleware('auth');
    }

    /**
     * Display the budget tracking dashboard
     */
    public function dashboard(Request $request)
    {
        $period = $request->get('period', 'current_month');
        $department = $request->get('department');
        
        $dashboardData = $this->budgetTrackingService->getDashboardData($period, $department);
        
        return view('budget.dashboard', compact('dashboardData', 'period', 'department'));
    }

    /**
     * Get real-time expense monitoring data
     */
    public function expenseMonitoring(Request $request)
    {
        $filters = $request->only(['period', 'department', 'category', 'status']);
        $expenseData = $this->budgetTrackingService->getExpenseMonitoringData($filters);
        
        if ($request->ajax()) {
            return response()->json($expenseData);
        }
        
        return view('budget.expense-monitoring', compact('expenseData', 'filters'));
    }

    /**
     * Get budget variance analysis
     */
    public function varianceAnalysis(Request $request)
    {
        $period = $request->get('period', 'current_month');
        $department = $request->get('department');
        
        $varianceData = $this->budgetTrackingService->getBudgetVarianceAnalysis($period, $department);
        
        if ($request->ajax()) {
            return response()->json($varianceData);
        }
        
        return view('budget.variance-analysis', compact('varianceData', 'period', 'department'));
    }

    /**
     * Get department-wise allocation data
     */
    public function departmentAllocation(Request $request)
    {
        $period = $request->get('period', 'current_year');
        $allocationData = $this->budgetTrackingService->getDepartmentAllocation($period);
        
        if ($request->ajax()) {
            return response()->json($allocationData);
        }
        
        return view('budget.department-allocation', compact('allocationData', 'period'));
    }

    /**
     * Get monthly budget vs actual reports
     */
    public function monthlyReports(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $department = $request->get('department');
        
        $monthlyData = $this->budgetTrackingService->getMonthlyBudgetVsActual($year, $department);
        
        if ($request->ajax()) {
            return response()->json($monthlyData);
        }
        
        return view('budget.monthly-reports', compact('monthlyData', 'year', 'department'));
    }

    /**
     * Get financial forecasting data
     */
    public function financialForecasting(Request $request)
    {
        $months = $request->get('months', 6);
        $department = $request->get('department');
        
        $forecastData = $this->budgetTrackingService->getFinancialForecasting($months, $department);
        
        if ($request->ajax()) {
            return response()->json($forecastData);
        }
        
        return view('budget.financial-forecasting', compact('forecastData', 'months', 'department'));
    }

    /**
     * Get budget alerts
     */
    public function budgetAlerts(Request $request)
    {
        $type = $request->get('type', 'all'); // all, critical, warning, info
        $alerts = $this->budgetTrackingService->getBudgetAlerts($type);
        
        if ($request->ajax()) {
            return response()->json($alerts);
        }
        
        return view('budget.alerts', compact('alerts', 'type'));
    }

    /**
     * Mark alert as read
     */
    public function markAlertAsRead(Request $request)
    {
        $alertId = $request->get('alert_id');
        $result = $this->budgetTrackingService->markAlertAsRead($alertId);
        
        return response()->json(['success' => $result]);
    }

    /**
     * Get expense trends
     */
    public function expenseTrends(Request $request)
    {
        $period = $request->get('period', '12_months');
        $category = $request->get('category');
        $department = $request->get('department');
        
        $trendsData = $this->budgetTrackingService->getExpenseTrends($period, $category, $department);
        
        return response()->json($trendsData);
    }

    /**
     * Export budget report
     */
    public function exportReport(Request $request)
    {
        $format = $request->get('format', 'excel'); // excel, pdf, csv
        $reportType = $request->get('report_type', 'monthly'); // monthly, variance, department, forecast
        $filters = $request->only(['period', 'department', 'year', 'months']);
        
        try {
            $filePath = $this->budgetTrackingService->exportReport($reportType, $format, $filters);
            
            return response()->download($filePath)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to export report: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get budget overview widget data
     */
    public function budgetOverview(Request $request)
    {
        $period = $request->get('period', 'current_month');
        $overview = $this->budgetTrackingService->getBudgetOverview($period);
        
        return response()->json($overview);
    }

    /**
     * Get top expenses
     */
    public function topExpenses(Request $request)
    {
        $period = $request->get('period', 'current_month');
        $limit = $request->get('limit', 10);
        $department = $request->get('department');
        
        $topExpenses = $this->budgetTrackingService->getTopExpenses($period, $limit, $department);
        
        return response()->json($topExpenses);
    }

    /**
     * Get expense categories breakdown
     */
    public function expenseCategories(Request $request)
    {
        $period = $request->get('period', 'current_month');
        $department = $request->get('department');
        
        $categories = $this->budgetTrackingService->getExpenseCategoriesBreakdown($period, $department);
        
        return response()->json($categories);
    }

    /**
     * Get budget utilization data
     */
    public function budgetUtilization(Request $request)
    {
        $period = $request->get('period', 'current_month');
        $department = $request->get('department');
        
        $utilization = $this->budgetTrackingService->getBudgetUtilization($period, $department);
        
        return response()->json($utilization);
    }

    /**
     * Get cash flow analysis
     */
    public function cashFlowAnalysis(Request $request)
    {
        $period = $request->get('period', '12_months');
        $cashFlow = $this->budgetTrackingService->getCashFlowAnalysis($period);
        
        return response()->json($cashFlow);
    }

    /**
     * Get budget performance metrics
     */
    public function performanceMetrics(Request $request)
    {
        $period = $request->get('period', 'current_year');
        $department = $request->get('department');
        
        $metrics = $this->budgetTrackingService->getBudgetPerformanceMetrics($period, $department);
        
        return response()->json($metrics);
    }

    /**
     * Create or update budget
     */
    public function storeBudget(Request $request)
    {
        $request->validate([
            'department' => 'required|string',
            'category' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'period' => 'required|string',
            'year' => 'required|integer',
            'month' => 'nullable|integer|min:1|max:12'
        ]);

        try {
            $budget = $this->budgetTrackingService->createOrUpdateBudget($request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Budget saved successfully',
                'budget' => $budget
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save budget: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get budget comparison data
     */
    public function budgetComparison(Request $request)
    {
        $currentPeriod = $request->get('current_period', 'current_month');
        $comparePeriod = $request->get('compare_period', 'previous_month');
        $department = $request->get('department');
        
        $comparison = $this->budgetTrackingService->getBudgetComparison($currentPeriod, $comparePeriod, $department);
        
        return response()->json($comparison);
    }

    /**
     * Get expense approval workflow data
     */
    public function expenseApprovals(Request $request)
    {
        $status = $request->get('status', 'pending');
        $department = $request->get('department');
        
        $approvals = $this->budgetTrackingService->getExpenseApprovals($status, $department);
        
        if ($request->ajax()) {
            return response()->json($approvals);
        }
        
        return view('budget.expense-approvals', compact('approvals', 'status', 'department'));
    }

    /**
     * Approve or reject expense
     */
    public function processExpenseApproval(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
            'action' => 'required|in:approve,reject',
            'notes' => 'nullable|string'
        ]);

        try {
            $result = $this->budgetTrackingService->processExpenseApproval(
                $request->transaction_id,
                $request->action,
                $request->notes,
                auth()->id()
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Expense ' . $request->action . 'd successfully',
                'transaction' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process approval: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get real-time notifications
     */
    public function getNotifications(Request $request)
    {
        $type = $request->get('type', 'all');
        $limit = $request->get('limit', 10);
        
        $notifications = $this->budgetTrackingService->getRealtimeNotifications($type, $limit);
        
        return response()->json($notifications);
    }

    /**
     * Clear cache
     */
    public function clearCache(Request $request)
    {
        $cacheKeys = $request->get('cache_keys', []);
        
        if (empty($cacheKeys)) {
            // Clear all budget-related cache
            Cache::tags(['budget_tracking'])->flush();
        } else {
            foreach ($cacheKeys as $key) {
                Cache::forget($key);
            }
        }
        
        return response()->json(['success' => true, 'message' => 'Cache cleared successfully']);
    }

    /**
     * Get budget settings
     */
    public function getSettings()
    {
        $settings = $this->budgetTrackingService->getBudgetSettings();
        
        return response()->json($settings);
    }

    /**
     * Update budget settings
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'alert_thresholds' => 'array',
            'notification_preferences' => 'array',
            'default_approval_workflow' => 'boolean',
            'auto_categorization' => 'boolean'
        ]);

        try {
            $settings = $this->budgetTrackingService->updateBudgetSettings($request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully',
                'settings' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings: ' . $e->getMessage()
            ], 500);
        }
    }
}