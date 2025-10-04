<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DepartmentBudget;
use App\Models\User;
use App\Services\DepartmentBudgetService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DepartmentBudgetController extends Controller
{
    protected $budgetService;

    public function __construct(DepartmentBudgetService $budgetService)
    {
        $this->budgetService = $budgetService;
    }

    /**
     * Display department budget dashboard
     */
    public function index(Request $request)
    {
        $year = $request->get('year', Carbon::now()->year);
        $dashboardData = $this->budgetService->getDashboardData($year);
        
        $years = range(Carbon::now()->year - 2, Carbon::now()->year + 1);
        $departments = DepartmentBudget::getDepartmentList();
        
        return view('budget.department.index', compact('dashboardData', 'years', 'departments'));
    }

    /**
     * Show form for creating new department budget
     */
    public function create()
    {
        $departments = DepartmentBudget::getDepartmentList();
        $budgetManagers = User::where('role', 'manager')
            ->orWhere('department', 'Finance')
            ->get();
        
        return view('budget.department.create', compact('departments', 'budgetManagers'));
    }

    /**
     * Store new department budget
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'department' => 'required|string|max:50',
            'budget_year' => 'required|integer|min:2020|max:2030',
            'allocated_budget' => 'required|numeric|min:0',
            'budget_manager_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $budget = $this->budgetService->createOrUpdateBudget(array_merge(
                $request->validated(),
                ['auto_allocate' => $request->has('auto_allocate')]
            ));

            return redirect()->route('department-budget.index')
                ->with('success', 'Department budget created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to create department budget: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display specific department budget
     */
    public function show($id)
    {
        $budget = DepartmentBudget::with(['budgetManager', 'approvedBy', 'transactions'])
            ->findOrFail($id);
        
        $monthlyData = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthName = Carbon::create($budget->budget_year, $month, 1)->format('F');
            $monthlyBudgets = $budget->monthly_budgets ?? [];
            $monthlySpent = $budget->monthly_spent ?? [];
            
            $monthlyData[] = [
                'month' => $monthName,
                'budgeted' => $monthlyBudgets[$month] ?? 0,
                'spent' => $monthlySpent[$month] ?? 0,
                'variance' => $budget->getMonthlyVariance($month)
            ];
        }
        
        $quarterlyData = [];
        for ($quarter = 1; $quarter <= 4; $quarter++) {
            $quarterlyData[] = [
                'quarter' => "Q{$quarter}",
                'budgeted' => $budget->{"quarterly_q{$quarter}_budget"} ?? 0,
                'spent' => $budget->{"quarterly_q{$quarter}_spent"} ?? 0,
                'variance' => $budget->getQuarterlyVariance($quarter)
            ];
        }
        
        return view('budget.department.show', compact('budget', 'monthlyData', 'quarterlyData'));
    }

    /**
     * Show form for editing department budget
     */
    public function edit($id)
    {
        $budget = DepartmentBudget::findOrFail($id);
        
        if (!$budget->canBeModified()) {
            return redirect()->back()
                ->with('error', 'This budget cannot be modified in its current status.');
        }
        
        $departments = DepartmentBudget::getDepartmentList();
        $budgetManagers = User::where('role', 'manager')
            ->orWhere('department', 'Finance')
            ->get();
        
        return view('budget.department.edit', compact('budget', 'departments', 'budgetManagers'));
    }

    /**
     * Update department budget
     */
    public function update(Request $request, $id)
    {
        $budget = DepartmentBudget::findOrFail($id);
        
        if (!$budget->canBeModified()) {
            return redirect()->back()
                ->with('error', 'This budget cannot be modified in its current status.');
        }
        
        $validator = Validator::make($request->all(), [
            'allocated_budget' => 'required|numeric|min:0',
            'budget_manager_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $this->budgetService->createOrUpdateBudget(array_merge(
                $request->validated(),
                [
                    'department' => $budget->department,
                    'budget_year' => $budget->budget_year,
                    'auto_allocate' => $request->has('auto_allocate')
                ]
            ));

            return redirect()->route('department-budget.show', $budget->id)
                ->with('success', 'Department budget updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update department budget: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Approve department budget
     */
    public function approve($id)
    {
        try {
            $budget = $this->budgetService->approveBudget($id, Auth::id());
            
            return redirect()->back()
                ->with('success', 'Budget approved successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to approve budget: ' . $e->getMessage());
        }
    }

    /**
     * Reject department budget
     */
    public function reject(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator);
        }

        $budget = DepartmentBudget::findOrFail($id);
        
        if (!$budget->canBeModified()) {
            return redirect()->back()
                ->with('error', 'This budget cannot be rejected in its current status.');
        }
        
        $budget->reject(Auth::id(), $request->reason);
        
        return redirect()->back()
            ->with('success', 'Budget rejected successfully.');
    }

    /**
     * Transfer budget between departments
     */
    public function transfer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_department' => 'required|string|different:to_department',
            'to_department' => 'required|string|different:from_department',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $this->budgetService->transferBudget(
                $request->from_department,
                $request->to_department,
                $request->amount,
                $request->reason,
                Auth::id()
            );

            return redirect()->back()
                ->with('success', 'Budget transfer completed successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Budget transfer failed: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Get department budget forecast
     */
    public function forecast(Request $request, $department)
    {
        $months = $request->get('months', 6);
        $forecast = $this->budgetService->generateForecast($department, $months);
        
        if (!$forecast) {
            return response()->json(['error' => 'Department budget not found'], 404);
        }
        
        return response()->json($forecast);
    }

    /**
     * Get budget utilization report
     */
    public function utilizationReport(Request $request)
    {
        $year = $request->get('year', Carbon::now()->year);
        $department = $request->get('department');
        
        $report = $this->budgetService->getUtilizationReport($year, $department);
        
        if ($request->expectsJson()) {
            return response()->json($report);
        }
        
        $departments = DepartmentBudget::getDepartmentList();
        $years = range(Carbon::now()->year - 2, Carbon::now()->year + 1);
        
        return view('budget.department.utilization-report', compact('report', 'departments', 'years', 'year', 'department'));
    }

    /**
     * Export budget report
     */
    public function exportReport(Request $request)
    {
        $year = $request->get('year', Carbon::now()->year);
        $department = $request->get('department');
        $format = $request->get('format', 'excel');
        
        $report = $this->budgetService->getUtilizationReport($year, $department);
        
        $filename = "department_budget_report_{$year}";
        if ($department) {
            $filename .= "_{$department}";
        }
        
        switch ($format) {
            case 'pdf':
                return $this->exportToPdf($report, $filename);
            case 'csv':
                return $this->exportToCsv($report, $filename);
            default:
                return $this->exportToExcel($report, $filename);
        }
    }

    /**
     * Get budget alerts
     */
    public function alerts(Request $request)
    {
        $year = $request->get('year', Carbon::now()->year);
        $alerts = $this->budgetService->getBudgetAlerts($year);
        
        return response()->json($alerts);
    }

    /**
     * Update budget spending (called when transactions are created)
     */
    public function updateSpending(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'department' => 'required|string',
            'year' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid parameters'], 400);
        }

        $budget = $this->budgetService->updateBudgetSpending(
            $request->department,
            $request->year
        );
        
        if (!$budget) {
            return response()->json(['error' => 'Budget not found'], 404);
        }
        
        return response()->json([
            'success' => true,
            'budget' => $budget,
            'utilization_percentage' => $budget->utilization_percentage,
            'status' => $budget->budget_status
        ]);
    }

    /**
     * Get monthly comparison data
     */
    public function monthlyComparison(Request $request)
    {
        $year = $request->get('year', Carbon::now()->year);
        $department = $request->get('department');
        
        if ($department) {
            $budget = DepartmentBudget::where('department', $department)
                ->where('budget_year', $year)
                ->first();
            
            if (!$budget) {
                return response()->json(['error' => 'Budget not found'], 404);
            }
            
            $monthlyData = [];
            for ($month = 1; $month <= 12; $month++) {
                $monthName = Carbon::create($year, $month, 1)->format('M');
                $monthlyBudgets = $budget->monthly_budgets ?? [];
                $monthlySpent = $budget->monthly_spent ?? [];
                
                $monthlyData[] = [
                    'month' => $monthName,
                    'budgeted' => $monthlyBudgets[$month] ?? 0,
                    'spent' => $monthlySpent[$month] ?? 0,
                    'variance' => $budget->getMonthlyVariance($month)
                ];
            }
            
            return response()->json($monthlyData);
        }
        
        // Return aggregated data for all departments
        $monthlyTrends = $this->budgetService->getMonthlyTrends($year);
        return response()->json($monthlyTrends);
    }

    /**
     * Export to Excel
     */
    private function exportToExcel($report, $filename)
    {
        // Implementation for Excel export
        // This would typically use a package like PhpSpreadsheet or Laravel Excel
        return response()->json(['message' => 'Excel export not implemented yet']);
    }

    /**
     * Export to PDF
     */
    private function exportToPdf($report, $filename)
    {
        // Implementation for PDF export
        // This would typically use a package like DomPDF or wkhtmltopdf
        return response()->json(['message' => 'PDF export not implemented yet']);
    }

    /**
     * Export to CSV
     */
    private function exportToCsv($report, $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
        ];
        
        $callback = function() use ($report) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Department',
                'Allocated Budget',
                'Spent Amount',
                'Available Amount',
                'Utilization %',
                'Variance',
                'Variance %',
                'Status'
            ]);
            
            // CSV data
            foreach ($report as $row) {
                fputcsv($file, [
                    $row['department'],
                    $row['allocated_budget'],
                    $row['spent_amount'],
                    $row['available_amount'],
                    $row['utilization_percentage'],
                    $row['variance'],
                    $row['variance_percentage'],
                    $row['status']
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}