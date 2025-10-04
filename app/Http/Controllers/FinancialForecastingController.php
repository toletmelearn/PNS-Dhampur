<?php

namespace App\Http\Controllers;

use App\Models\FinancialForecast;
use App\Models\ForecastDetail;
use App\Models\ForecastActual;
use App\Services\FinancialForecastingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class FinancialForecastingController extends Controller
{
    protected $forecastingService;

    public function __construct(FinancialForecastingService $forecastingService)
    {
        $this->forecastingService = $forecastingService;
    }

    /**
     * Display the financial forecasting dashboard
     */
    public function dashboard()
    {
        $dashboardData = $this->forecastingService->getForecastingDashboard();
        return view('finance.forecasting.dashboard', compact('dashboardData'));
    }

    /**
     * Display forecast overview
     */
    public function overview(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $department = $request->get('department');
        
        $overview = $this->forecastingService->getForecastOverview($year, $department);
        return response()->json($overview);
    }

    /**
     * Get revenue forecast data
     */
    public function revenueForecast(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $department = $request->get('department');
        $scenario = $request->get('scenario', 'base');
        
        $forecast = $this->forecastingService->getRevenueForecast($year, $department, $scenario);
        return response()->json($forecast);
    }

    /**
     * Get expense forecast data
     */
    public function expenseForecast(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $department = $request->get('department');
        $scenario = $request->get('scenario', 'base');
        
        $forecast = $this->forecastingService->getExpenseForecast($year, $department, $scenario);
        return response()->json($forecast);
    }

    /**
     * Get cash flow forecast
     */
    public function cashFlowForecast(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $months = $request->get('months', 12);
        
        $forecast = $this->forecastingService->getCashFlowForecast($year, $months);
        return response()->json($forecast);
    }

    /**
     * Get budget scenarios
     */
    public function budgetScenarios(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $department = $request->get('department');
        
        $scenarios = $this->forecastingService->getBudgetScenarios($year, $department);
        return response()->json($scenarios);
    }

    /**
     * Get risk analysis
     */
    public function riskAnalysis(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $department = $request->get('department');
        
        $analysis = $this->forecastingService->getRiskAnalysis($year, $department);
        return response()->json($analysis);
    }

    /**
     * Get seasonal patterns
     */
    public function seasonalPatterns(Request $request)
    {
        $years = $request->get('years', 3);
        $category = $request->get('category', 'all');
        
        $patterns = $this->forecastingService->getSeasonalPatterns($years, $category);
        return response()->json($patterns);
    }

    /**
     * Get variance predictions
     */
    public function variancePredictions(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('n'));
        
        $predictions = $this->forecastingService->getVariancePredictions($year, $month);
        return response()->json($predictions);
    }

    /**
     * Get forecast recommendations
     */
    public function recommendations(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $department = $request->get('department');
        
        $recommendations = $this->forecastingService->getForecastRecommendations($year, $department);
        return response()->json($recommendations);
    }

    /**
     * Get confidence metrics
     */
    public function confidenceMetrics(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $forecastType = $request->get('type', 'all');
        
        $metrics = $this->forecastingService->getConfidenceMetrics($year, $forecastType);
        return response()->json($metrics);
    }

    /**
     * List all forecasts
     */
    public function index(Request $request)
    {
        $query = FinancialForecast::with(['creator', 'approver']);

        // Apply filters
        if ($request->filled('year')) {
            $query->byYear($request->year);
        }

        if ($request->filled('department')) {
            $query->byDepartment($request->department);
        }

        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $forecasts = $query->paginate(15);

        if ($request->expectsJson()) {
            return response()->json($forecasts);
        }

        return view('finance.forecasting.index', compact('forecasts'));
    }

    /**
     * Show forecast details
     */
    public function show($id)
    {
        $forecast = FinancialForecast::with(['creator', 'approver', 'details', 'actuals'])
            ->findOrFail($id);

        return view('finance.forecasting.show', compact('forecast'));
    }

    /**
     * Create new forecast
     */
    public function create()
    {
        return view('finance.forecasting.create');
    }

    /**
     * Store new forecast
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'forecast_type' => 'required|in:revenue,expense,cash_flow,budget,comprehensive',
            'department_id' => 'nullable|exists:departments,id',
            'year' => 'required|integer|min:2020|max:2030',
            'month' => 'nullable|integer|min:1|max:12',
            'model_type' => 'required|in:linear,exponential,seasonal,arima,regression',
            'scenario_type' => 'required|in:optimistic,base,pessimistic,worst_case',
            'forecasted_revenue' => 'nullable|numeric|min:0',
            'forecasted_expenses' => 'nullable|numeric|min:0',
            'assumptions' => 'nullable|array',
            'risk_factors' => 'nullable|array',
            'methodology' => 'nullable|string',
            'data_sources' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $forecast = FinancialForecast::create($request->all());

        return response()->json([
            'message' => 'Forecast created successfully',
            'forecast' => $forecast
        ], 201);
    }

    /**
     * Update forecast
     */
    public function update(Request $request, $id)
    {
        $forecast = FinancialForecast::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'forecasted_revenue' => 'nullable|numeric|min:0',
            'forecasted_expenses' => 'nullable|numeric|min:0',
            'confidence_level' => 'nullable|numeric|min:0|max:100',
            'assumptions' => 'nullable|array',
            'risk_factors' => 'nullable|array',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $forecast->update($request->all());

        return response()->json([
            'message' => 'Forecast updated successfully',
            'forecast' => $forecast
        ]);
    }

    /**
     * Approve forecast
     */
    public function approve($id)
    {
        $forecast = FinancialForecast::findOrFail($id);
        $forecast->approve();

        return response()->json([
            'message' => 'Forecast approved successfully',
            'forecast' => $forecast
        ]);
    }

    /**
     * Reject forecast
     */
    public function reject(Request $request, $id)
    {
        $forecast = FinancialForecast::findOrFail($id);
        $forecast->reject($request->rejection_reason);

        return response()->json([
            'message' => 'Forecast rejected',
            'forecast' => $forecast
        ]);
    }

    /**
     * Submit forecast for approval
     */
    public function submit($id)
    {
        $forecast = FinancialForecast::findOrFail($id);
        $forecast->submit();

        return response()->json([
            'message' => 'Forecast submitted for approval',
            'forecast' => $forecast
        ]);
    }

    /**
     * Record actual data
     */
    public function recordActual(Request $request, $id)
    {
        $forecast = FinancialForecast::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'period_type' => 'required|in:monthly,quarterly,yearly',
            'year' => 'required|integer',
            'month' => 'nullable|integer|min:1|max:12',
            'quarter' => 'nullable|integer|min:1|max:4',
            'actual_revenue' => 'nullable|numeric',
            'actual_expenses' => 'nullable|numeric',
            'actual_cash_flow' => 'nullable|numeric',
            'data_source' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $actual = ForecastActual::create(array_merge(
            $request->all(),
            ['financial_forecast_id' => $forecast->id]
        ));

        return response()->json([
            'message' => 'Actual data recorded successfully',
            'actual' => $actual
        ], 201);
    }

    /**
     * Get forecast accuracy report
     */
    public function accuracyReport(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $department = $request->get('department');

        $forecasts = FinancialForecast::with('actuals')
            ->byYear($year)
            ->when($department, function($query) use ($department) {
                return $query->byDepartment($department);
            })
            ->approved()
            ->get();

        $accuracyData = [];
        foreach ($forecasts as $forecast) {
            $accuracy = $forecast->calculateAccuracy();
            if ($accuracy !== null) {
                $accuracyData[] = [
                    'forecast' => $forecast,
                    'accuracy' => $accuracy
                ];
            }
        }

        return response()->json($accuracyData);
    }

    /**
     * Export forecast report
     */
    public function exportReport(Request $request, $format = 'pdf')
    {
        $year = $request->get('year', date('Y'));
        $department = $request->get('department');
        $type = $request->get('type', 'comprehensive');

        // Generate report data
        $reportData = $this->forecastingService->generateForecastReport($year, $department, $type);

        switch ($format) {
            case 'pdf':
                return $this->exportToPdf($reportData);
            case 'excel':
                return $this->exportToExcel($reportData);
            case 'csv':
                return $this->exportToCsv($reportData);
            default:
                return response()->json(['error' => 'Invalid format'], 400);
        }
    }

    /**
     * Get forecast trends for charts
     */
    public function trends(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $type = $request->get('type', 'revenue');
        $period = $request->get('period', 'monthly');

        $trends = $this->forecastingService->getForecastTrends($year, $type, $period);
        return response()->json($trends);
    }

    // Helper methods for export functionality
    private function exportToPdf($data)
    {
        // Implementation for PDF export
        // This would use a PDF library like DomPDF or TCPDF
        return response()->json(['message' => 'PDF export functionality to be implemented']);
    }

    private function exportToExcel($data)
    {
        // Implementation for Excel export
        // This would use Laravel Excel package
        return response()->json(['message' => 'Excel export functionality to be implemented']);
    }

    private function exportToCsv($data)
    {
        // Implementation for CSV export
        return response()->json(['message' => 'CSV export functionality to be implemented']);
    }
}