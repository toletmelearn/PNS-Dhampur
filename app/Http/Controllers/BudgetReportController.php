<?php

namespace App\Http\Controllers;

use App\Services\BudgetReportingService;
use App\Models\BudgetVsActualReport;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use PDF;
use Excel;

class BudgetReportController extends Controller
{
    protected $budgetReportingService;

    public function __construct(BudgetReportingService $budgetReportingService)
    {
        $this->budgetReportingService = $budgetReportingService;
    }

    /**
     * Display budget vs actual dashboard
     */
    public function dashboard(Request $request): JsonResponse
    {
        try {
            $year = $request->get('year', Carbon::now()->year);
            $department = $request->get('department');

            $dashboardData = $this->budgetReportingService->getBudgetVsActualDashboard($year, $department);

            return response()->json([
                'success' => true,
                'data' => $dashboardData,
                'message' => 'Budget vs Actual dashboard data retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving dashboard data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get budget summary
     */
    public function summary(Request $request): JsonResponse
    {
        try {
            $year = $request->get('year', Carbon::now()->year);
            $department = $request->get('department');

            $summary = $this->budgetReportingService->getBudgetSummary($year, $department);

            return response()->json([
                'success' => true,
                'data' => $summary,
                'message' => 'Budget summary retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving budget summary: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get monthly comparison data
     */
    public function monthlyComparison(Request $request): JsonResponse
    {
        try {
            $year = $request->get('year', Carbon::now()->year);
            $department = $request->get('department');

            $monthlyData = $this->budgetReportingService->getMonthlyComparison($year, $department);

            return response()->json([
                'success' => true,
                'data' => $monthlyData,
                'message' => 'Monthly comparison data retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving monthly comparison: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get variance analysis
     */
    public function varianceAnalysis(Request $request): JsonResponse
    {
        try {
            $year = $request->get('year', Carbon::now()->year);
            $department = $request->get('department');

            $analysis = $this->budgetReportingService->getVarianceAnalysis($year, $department);

            return response()->json([
                'success' => true,
                'data' => $analysis,
                'message' => 'Variance analysis retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving variance analysis: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get department performance
     */
    public function departmentPerformance(Request $request): JsonResponse
    {
        try {
            $year = $request->get('year', Carbon::now()->year);

            $performance = $this->budgetReportingService->getDepartmentPerformance($year);

            return response()->json([
                'success' => true,
                'data' => $performance,
                'message' => 'Department performance data retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving department performance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get trend analysis
     */
    public function trendAnalysis(Request $request): JsonResponse
    {
        try {
            $year = $request->get('year', Carbon::now()->year);
            $department = $request->get('department');

            $trends = $this->budgetReportingService->getTrendAnalysis($year, $department);

            return response()->json([
                'success' => true,
                'data' => $trends,
                'message' => 'Trend analysis retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving trend analysis: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get risk indicators
     */
    public function riskIndicators(Request $request): JsonResponse
    {
        try {
            $year = $request->get('year', Carbon::now()->year);
            $department = $request->get('department');

            $risks = $this->budgetReportingService->getRiskIndicators($year, $department);

            return response()->json([
                'success' => true,
                'data' => $risks,
                'message' => 'Risk indicators retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving risk indicators: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate monthly report
     */
    public function generateMonthlyReport(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'year' => 'required|integer|min:2020|max:2030',
                'month' => 'required|integer|min:1|max:12',
                'department' => 'nullable|string'
            ]);

            $year = $request->get('year');
            $month = $request->get('month');
            $department = $request->get('department');

            $report = $this->budgetReportingService->generateMonthlyReport($year, $month, $department);

            return response()->json([
                'success' => true,
                'data' => $report,
                'message' => 'Monthly report generated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating monthly report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all budget vs actual reports
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = BudgetVsActualReport::with(['department', 'generator', 'approver']);

            // Apply filters
            if ($request->has('year')) {
                $query->where('year', $request->get('year'));
            }

            if ($request->has('month')) {
                $query->where('month', $request->get('month'));
            }

            if ($request->has('department_id')) {
                $query->where('department_id', $request->get('department_id'));
            }

            if ($request->has('status')) {
                $query->where('status', $request->get('status'));
            }

            $reports = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $reports,
                'message' => 'Reports retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving reports: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show specific report
     */
    public function show($id): JsonResponse
    {
        try {
            $report = BudgetVsActualReport::with(['department', 'generator', 'approver'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $report,
                'message' => 'Report retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update report
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $request->validate([
                'status' => 'sometimes|in:draft,pending,approved,rejected',
                'notes' => 'nullable|string',
                'action_required' => 'nullable|boolean'
            ]);

            $report = BudgetVsActualReport::findOrFail($id);
            $report->update($request->only(['status', 'notes', 'action_required']));

            if ($request->get('status') === 'approved') {
                $report->update([
                    'approved_by' => auth()->id(),
                    'approved_at' => now()
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $report->fresh(['department', 'generator', 'approver']),
                'message' => 'Report updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete report
     */
    public function destroy($id): JsonResponse
    {
        try {
            $report = BudgetVsActualReport::findOrFail($id);
            $report->delete();

            return response()->json([
                'success' => true,
                'message' => 'Report deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export report to PDF
     */
    public function exportPDF(Request $request): \Illuminate\Http\Response
    {
        try {
            $year = $request->get('year', Carbon::now()->year);
            $month = $request->get('month');
            $department = $request->get('department');

            $data = $this->budgetReportingService->getBudgetVsActualDashboard($year, $department);
            
            $pdf = PDF::loadView('reports.budget-vs-actual-pdf', [
                'data' => $data,
                'year' => $year,
                'month' => $month,
                'department' => $department,
                'generated_at' => now()
            ]);

            $filename = 'budget-vs-actual-' . $year . 
                ($month ? '-' . str_pad($month, 2, '0', STR_PAD_LEFT) : '') . 
                ($department ? '-' . str_replace(' ', '-', strtolower($department)) : '') . 
                '.pdf';

            return $pdf->download($filename);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export report to Excel
     */
    public function exportExcel(Request $request)
    {
        try {
            $year = $request->get('year', Carbon::now()->year);
            $month = $request->get('month');
            $department = $request->get('department');

            $data = $this->budgetReportingService->getBudgetVsActualDashboard($year, $department);

            $filename = 'budget-vs-actual-' . $year . 
                ($month ? '-' . str_pad($month, 2, '0', STR_PAD_LEFT) : '') . 
                ($department ? '-' . str_replace(' ', '-', strtolower($department)) : '') . 
                '.xlsx';

            return Excel::download(new BudgetVsActualExport($data), $filename);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting Excel: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get departments for filter dropdown
     */
    public function getDepartments(): JsonResponse
    {
        try {
            $departments = Department::select('id', 'name')
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $departments,
                'message' => 'Departments retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving departments: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available years for filter dropdown
     */
    public function getAvailableYears(): JsonResponse
    {
        try {
            $years = BudgetVsActualReport::select('year')
                ->distinct()
                ->orderBy('year', 'desc')
                ->pluck('year');

            // Add current year if not present
            $currentYear = Carbon::now()->year;
            if (!$years->contains($currentYear)) {
                $years->prepend($currentYear);
            }

            return response()->json([
                'success' => true,
                'data' => $years->values(),
                'message' => 'Available years retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving available years: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get quick stats for dashboard cards
     */
    public function quickStats(Request $request): JsonResponse
    {
        try {
            $year = $request->get('year', Carbon::now()->year);
            $department = $request->get('department');

            $summary = $this->budgetReportingService->getBudgetSummary($year, $department);

            $quickStats = [
                'total_budget' => [
                    'value' => $summary['total_budgeted'],
                    'label' => 'Total Budget',
                    'format' => 'currency'
                ],
                'total_spent' => [
                    'value' => $summary['total_spent'],
                    'label' => 'Total Spent',
                    'format' => 'currency'
                ],
                'utilization_rate' => [
                    'value' => $summary['utilization_rate'],
                    'label' => 'Utilization Rate',
                    'format' => 'percentage'
                ],
                'budget_health' => [
                    'value' => $summary['budget_health_score'],
                    'label' => 'Budget Health Score',
                    'format' => 'score'
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $quickStats,
                'message' => 'Quick stats retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving quick stats: ' . $e->getMessage()
            ], 500);
        }
    }
}