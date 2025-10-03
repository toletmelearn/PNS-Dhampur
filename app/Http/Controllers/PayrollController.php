<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\SalaryStructure;
use App\Models\PayrollDeduction;
use App\Services\SalaryCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class PayrollController extends Controller
{
    protected $salaryService;

    public function __construct(SalaryCalculationService $salaryService)
    {
        $this->salaryService = $salaryService;
        $this->middleware('auth');
    }

    /**
     * Display payroll reports page
     */
    public function reports()
    {
        return view('payroll.reports');
    }

    /**
     * Generate payroll report
     */
    public function generateReport(Request $request)
    {
        $reportType = $request->input('report_type');
        $month = $request->input('month');
        $year = $request->input('year');
        $department = $request->input('department');
        $employee = $request->input('employee');
        
        $data = [
            'report_type' => $reportType,
            'period' => date('F Y', strtotime($month . '-01')),
            'employees' => [],
            'departments' => [],
            'deductions' => [],
            'months' => [],
            'comparisons' => []
        ];
        
        // Mock data for demonstration
        if ($reportType === 'monthly_summary') {
            $data['employees'] = [
                [
                    'name' => 'John Doe',
                    'department' => 'Teaching',
                    'basic_salary' => 50000,
                    'total_allowances' => 15000,
                    'gross_salary' => 65000,
                    'total_deductions' => 8000,
                    'net_salary' => 57000
                ],
                [
                    'name' => 'Jane Smith',
                    'department' => 'Administration',
                    'basic_salary' => 45000,
                    'total_allowances' => 12000,
                    'gross_salary' => 57000,
                    'total_deductions' => 7000,
                    'net_salary' => 50000
                ]
            ];
        }
        
        return response()->json($data);
    }

    /**
     * Export payroll report
     */
    public function exportReport(Request $request)
    {
        $format = $request->input('format', 'pdf');
        $reportType = $request->input('report_type');
        
        // Generate report data (same as generateReport)
        $data = $this->generateReport($request)->getData(true);
        
        if ($format === 'pdf') {
            // Generate PDF
            $pdf = PDF::loadView('payroll.reports-pdf', $data);
            return $pdf->download('payroll-report-' . date('Y-m-d') . '.pdf');
        } elseif ($format === 'excel') {
            // Generate Excel (would need Excel package)
            return response()->json(['message' => 'Excel export not implemented yet']);
        } else {
            // Generate CSV
            return response()->json(['message' => 'CSV export not implemented yet']);
        }
    }

    /**
     * Display payroll dashboard
     */
    public function index(Request $request)
    {
        $currentMonth = Carbon::now()->startOfMonth();
        $selectedMonth = $request->get('month', $currentMonth->format('Y-m'));
        $period = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();

        // Get payroll statistics
        $stats = $this->getPayrollStats($period);

        // Get employees with salary structures
        $employees = User::whereHas('salaryStructure', function($query) use ($period) {
            $query->where('effective_from', '<=', $period)
                  ->where(function($q) use ($period) {
                      $q->whereNull('effective_to')
                        ->orWhere('effective_to', '>=', $period);
                  })
                  ->where('status', 'active');
        })->with(['salaryStructure' => function($query) use ($period) {
            $query->where('effective_from', '<=', $period)
                  ->where(function($q) use ($period) {
                      $q->whereNull('effective_to')
                        ->orWhere('effective_to', '>=', $period);
                  })
                  ->where('status', 'active')
                  ->latest('effective_from');
        }])->get();

        return view('payroll.index', compact('employees', 'stats', 'period', 'selectedMonth'));
    }

    /**
     * Calculate salary for a specific employee
     */
    public function calculateSalary(Request $request, User $employee)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m'
        ]);

        $period = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();

        try {
            $salaryData = $this->salaryService->calculateMonthlySalary($employee, $period);
            
            return response()->json([
                'success' => true,
                'data' => $salaryData
            ]);
        } catch (\Exception $e) {
            Log::error('Salary calculation failed', [
                'employee_id' => $employee->id,
                'period' => $period->format('Y-m'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate salary: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process bulk payroll for all employees
     */
    public function processBulkPayroll(Request $request)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
            'employee_ids' => 'array',
            'employee_ids.*' => 'exists:users,id'
        ]);

        $period = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();
        $employeeIds = $request->employee_ids ?? [];

        try {
            $results = $this->salaryService->processBulkPayroll($employeeIds, $period);
            
            return response()->json([
                'success' => true,
                'message' => 'Bulk payroll processed successfully',
                'data' => $results
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk payroll processing failed', [
                'period' => $period->format('Y-m'),
                'employee_ids' => $employeeIds,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process bulk payroll: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate salary slip PDF
     */
    public function generateSalarySlip(Request $request, User $employee)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m'
        ]);

        $period = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();

        try {
            $salaryData = $this->salaryService->calculateMonthlySalary($employee, $period);
            
            $companyInfo = [
                'name' => config('app.name', 'School Management System'),
                'address' => 'School Address, City, State - PIN',
                'phone' => '+91-XXXXXXXXXX',
                'email' => 'info@school.com'
            ];

            $pdf = $this->salaryService->generateSalarySlipPDF($employee, $period, $salaryData, $companyInfo);
            
            $filename = "salary_slip_{$employee->name}_{$period->format('M_Y')}.pdf";
            
            return $pdf->download($filename);
        } catch (\Exception $e) {
            Log::error('Salary slip generation failed', [
                'employee_id' => $employee->id,
                'period' => $period->format('Y-m'),
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to generate salary slip: ' . $e->getMessage());
        }
    }

    /**
     * Generate payroll summary report
     */
    public function generatePayrollSummary(Request $request)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
            'format' => 'in:pdf,excel'
        ]);

        $period = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();
        $format = $request->get('format', 'pdf');

        try {
            $employees = User::whereHas('salaryStructure', function($query) use ($period) {
                $query->where('effective_from', '<=', $period)
                      ->where(function($q) use ($period) {
                          $q->whereNull('effective_to')
                            ->orWhere('effective_to', '>=', $period);
                      })
                      ->where('status', 'active');
            })->get();

            $payrollData = [];
            $totals = [
                'gross_salary' => 0,
                'total_deductions' => 0,
                'net_salary' => 0,
                'employer_contributions' => 0
            ];

            foreach ($employees as $employee) {
                $salaryData = $this->salaryService->calculateMonthlySalary($employee, $period);
                $payrollData[] = [
                    'employee' => $employee,
                    'salary_data' => $salaryData
                ];

                $totals['gross_salary'] += $salaryData['gross_salary'];
                $totals['total_deductions'] += $salaryData['total_deductions'];
                $totals['net_salary'] += $salaryData['net_salary'];
                $totals['employer_contributions'] += array_sum($salaryData['employer_contributions']);
            }

            if ($format === 'pdf') {
                $pdf = $this->salaryService->generatePayrollSummaryPDF($payrollData, $period, $totals);
                $filename = "payroll_summary_{$period->format('M_Y')}.pdf";
                return $pdf->download($filename);
            }

            // Excel format would be implemented here
            return back()->with('info', 'Excel export not implemented yet');

        } catch (\Exception $e) {
            Log::error('Payroll summary generation failed', [
                'period' => $period->format('Y-m'),
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to generate payroll summary: ' . $e->getMessage());
        }
    }

    /**
     * Show salary structure management
     */
    public function salaryStructures(Request $request)
    {
        $query = SalaryStructure::with('user');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('grade_level')) {
            $query->where('grade_level', $request->grade_level);
        }

        $salaryStructures = $query->latest()->paginate(15);
        
        $gradeLevels = SalaryStructure::distinct()->pluck('grade_level')->filter()->sort();

        return view('payroll.salary-structures', compact('salaryStructures', 'gradeLevels'));
    }

    /**
     * Create or update salary structure
     */
    public function storeSalaryStructure(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'basic_salary' => 'required|numeric|min:0',
            'allowances' => 'array',
            'allowances.*' => 'numeric|min:0',
            'deductions' => 'array',
            'deductions.*' => 'numeric|min:0',
            'benefits' => 'array',
            'benefits.*' => 'numeric|min:0',
            'grade_level' => 'nullable|string|max:50',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'status' => 'required|in:active,inactive,draft'
        ]);

        try {
            DB::beginTransaction();

            // Deactivate existing active salary structures for this user
            if ($request->status === 'active') {
                SalaryStructure::where('user_id', $request->user_id)
                    ->where('status', 'active')
                    ->update(['status' => 'inactive']);
            }

            $salaryStructure = SalaryStructure::create([
                'user_id' => $request->user_id,
                'basic_salary' => $request->basic_salary,
                'allowances' => $request->allowances ?? [],
                'deductions' => $request->deductions ?? [],
                'benefits' => $request->benefits ?? [],
                'grade_level' => $request->grade_level,
                'effective_from' => $request->effective_from,
                'effective_to' => $request->effective_to,
                'status' => $request->status,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Salary structure created successfully',
                'data' => $salaryStructure->load('user')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Salary structure creation failed', [
                'user_id' => $request->user_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create salary structure: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show payroll deductions management
     */
    public function deductions(Request $request)
    {
        $query = PayrollDeduction::with('user');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('deduction_type')) {
            $query->where('deduction_type', $request->deduction_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('month')) {
            $period = Carbon::createFromFormat('Y-m', $request->month);
            $query->where('payroll_month', $period->month)
                  ->where('payroll_year', $period->year);
        }

        $deductions = $query->latest()->paginate(15);

        return view('payroll.deductions', compact('deductions'));
    }

    /**
     * Get payroll statistics for dashboard
     */
    private function getPayrollStats($period)
    {
        $employees = User::whereHas('salaryStructure', function($query) use ($period) {
            $query->where('effective_from', '<=', $period)
                  ->where(function($q) use ($period) {
                      $q->whereNull('effective_to')
                        ->orWhere('effective_to', '>=', $period);
                  })
                  ->where('status', 'active');
        })->count();

        $processedPayroll = PayrollDeduction::where('payroll_month', $period->month)
            ->where('payroll_year', $period->year)
            ->where('status', 'processed')
            ->distinct('user_id')
            ->count();

        $totalGrossSalary = 0;
        $totalDeductions = 0;
        $totalNetSalary = 0;

        // Calculate totals (this could be cached for performance)
        $employeesWithSalary = User::whereHas('salaryStructure', function($query) use ($period) {
            $query->where('effective_from', '<=', $period)
                  ->where(function($q) use ($period) {
                      $q->whereNull('effective_to')
                        ->orWhere('effective_to', '>=', $period);
                  })
                  ->where('status', 'active');
        })->with('salaryStructure')->get();

        foreach ($employeesWithSalary as $employee) {
            if ($employee->salaryStructure) {
                $totalGrossSalary += $employee->salaryStructure->gross_salary;
                $totalDeductions += $employee->salaryStructure->total_deductions;
                $totalNetSalary += $employee->salaryStructure->net_salary;
            }
        }

        return [
            'total_employees' => $employees,
            'processed_payroll' => $processedPayroll,
            'pending_payroll' => $employees - $processedPayroll,
            'total_gross_salary' => $totalGrossSalary,
            'total_deductions' => $totalDeductions,
            'total_net_salary' => $totalNetSalary,
            'processing_percentage' => $employees > 0 ? round(($processedPayroll / $employees) * 100, 2) : 0
        ];
    }

    /**
     * Calculate annual tax liability for an employee
     */
    public function calculateAnnualTax(Request $request, User $employee)
    {
        $request->validate([
            'financial_year' => 'required|string|regex:/^\d{4}-\d{4}$/'
        ]);

        try {
            $taxLiability = $this->salaryService->calculateAnnualTaxLiability($employee, $request->financial_year);
            
            return response()->json([
                'success' => true,
                'data' => $taxLiability
            ]);
        } catch (\Exception $e) {
            Log::error('Annual tax calculation failed', [
                'employee_id' => $employee->id,
                'financial_year' => $request->financial_year,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate annual tax: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate salary calculation
     */
    public function validateSalaryCalculation(Request $request, User $employee)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m'
        ]);

        $period = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();

        try {
            $validation = $this->salaryService->validateSalaryCalculation($employee, $period);
            
            return response()->json([
                'success' => true,
                'data' => $validation
            ]);
        } catch (\Exception $e) {
            Log::error('Salary validation failed', [
                'employee_id' => $employee->id,
                'period' => $period->format('Y-m'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to validate salary calculation: ' . $e->getMessage()
            ], 500);
        }
    }
}