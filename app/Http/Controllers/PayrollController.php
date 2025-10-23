<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\SalaryStructure;
use App\Models\PayrollDeduction;
use App\Services\SalaryCalculationService;
use App\Services\UserFriendlyErrorService;
use App\Http\Requests\CalculateSalaryRequest;
use App\Http\Requests\ProcessPayrollRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Helpers\SecurityHelper;
use Illuminate\Support\Facades\Auth;

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
    public function calculateSalary(CalculateSalaryRequest $request, User $employee)
    {
        $validated = $request->validated();
        $period = Carbon::createFromFormat('Y-m', $validated['month'])->startOfMonth();

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
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => UserFriendlyErrorService::getErrorMessage($e, 'general')
            ], 500);
        }
    }

    /**
     * Process bulk payroll for all employees
     */
    public function processBulkPayroll(ProcessPayrollRequest $request)
    {
        $validated = $request->validated();
        $period = Carbon::createFromFormat('Y-m', $validated['month'])->startOfMonth();
        $employeeIds = $validated['employee_ids'] ?? [];

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
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => UserFriendlyErrorService::getErrorMessage($e, 'general')
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
        // Return JSON for DataTable when requested via AJAX
        if ($request->ajax()) {
            $query = SalaryStructure::query();
    
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
                    $pattern = SecurityHelper::buildLikePattern($search);
                    $q->where('name', 'like', $pattern)
                      ->orWhere('code', 'like', $pattern)
                      ->orWhere('description', 'like', $pattern);
                });
            }
    
            if ($request->filled('status')) {
                $query->where('status', $request->get('status'));
            }
    
            if ($request->filled('grade_level')) {
                $query->where('grade_level', $request->get('grade_level'));
            }
    
            if ($request->filled('effective_from')) {
                $query->whereDate('effective_from', '>=', $request->get('effective_from'));
            }
    
            $structures = $query->latest()->get();
            $data = $structures->map(function (SalaryStructure $s) {
                $calc = $s->calculateSalary();
                return [
                    'id' => $s->id,
                    'structure_name' => $s->name ?? ($s->code ?: 'Structure #' . $s->id),
                    'grade_level' => $s->grade_level,
                    'basic_salary' => (float) $s->basic_salary,
                    'gross_salary' => (float) ($calc['gross_salary'] ?? 0),
                    'net_salary' => (float) ($calc['net_salary'] ?? 0),
                    'status' => $s->status,
                    'effective_from' => optional($s->effective_from)->toDateString(),
                ];
            });
    
            return response()->json(['data' => $data]);
        }
    
        // Non-AJAX: render view with filters
        $gradeLevels = SalaryStructure::distinct()->pluck('grade_level')->filter()->sort();
        return view('payroll.salary-structures', compact('gradeLevels'));
    }

    // storeSalaryStructure implementation moved below to align with frontend form fields

    /**
     * Show payroll deductions management
     */
    public function deductions(Request $request)
    {
        if ($request->ajax()) {
            $query = PayrollDeduction::with('employee');

            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
                    $pattern = SecurityHelper::buildLikePattern($search);
                    $q->where('description', 'like', $pattern)
                      ->orWhere('employee_name', 'like', $pattern);
                });
            }

            if ($request->filled('deduction_type')) {
                $query->where('deduction_type', $request->get('deduction_type'));
            }

            if ($request->filled('status')) {
                $query->where('status', $request->get('status'));
            }

            if ($request->filled('employee_id')) {
                $query->where('employee_id', $request->get('employee_id'));
            }

            if ($request->filled('month')) {
                try {
                    $period = Carbon::createFromFormat('Y-m', $request->get('month'));
                    $query->where('payroll_month', $period->month)
                          ->where('payroll_year', $period->year);
                } catch (\Exception $e) {}
            }

            if ($request->filled('min_amount')) {
                $query->where('deduction_amount', '>=', (float) $request->get('min_amount'));
            }

            $deductions = $query->latest()->get();
            $data = $deductions->map(function (PayrollDeduction $d) {
                $employeeName = $d->employee?->name ?? $d->employee_name ?? 'Unknown';
                $period = $d->payroll_year && $d->payroll_month ? sprintf('%04d-%02d', $d->payroll_year, $d->payroll_month) : optional($d->effective_from)->format('Y-m');
                return [
                    'id' => $d->id,
                    'employee_name' => $employeeName,
                    'deduction_type' => $d->deduction_type,
                    'description' => $d->description,
                    'amount' => (float) $d->deduction_amount,
                    'period' => $period,
                    'status' => $d->status,
                    'effective_date' => optional($d->effective_from)->toDateString(),
                ];
            });

            return response()->json(['data' => $data]);
        }

        return view('payroll.deductions');
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
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => UserFriendlyErrorService::getErrorMessage($e, 'general')
            ], 500);
        }
    }
    public function getSalaryStructure(SalaryStructure $salaryStructure)
{
    $response = [
        'id' => $salaryStructure->id,
        'structure_name' => $salaryStructure->name ?? ($salaryStructure->code ?: 'Structure #' . $salaryStructure->id),
        'grade_level' => $salaryStructure->grade_level,
        'basic_salary' => (float) $salaryStructure->basic_salary,
        'effective_from' => optional($salaryStructure->effective_from)->toDateString(),
        'effective_to' => optional($salaryStructure->effective_to)->toDateString(),
        'status' => $salaryStructure->status,
        'allowances' => $salaryStructure->allowances ?? [],
        'deductions' => $salaryStructure->deductions ?? [],
    ];

    return response()->json($response);
}

public function storeSalaryStructure(Request $request)
{
    $data = $request->validate([
        'structure_name' => 'required|string|max:255',
        'grade_level' => 'required|string|max:50',
        'basic_salary' => 'required|numeric|min:0',
        'effective_from' => 'required|date',
        'effective_to' => 'nullable|date|after:effective_from',
        'status' => 'required|in:draft,active,inactive,archived',
        'allowances' => 'array',
        'allowances.*' => 'numeric|min:0',
        'deductions' => 'array',
        'deductions.*' => 'numeric|min:0',
    ]);

    $structure = SalaryStructure::create([
        'name' => $data['structure_name'],
        'grade_level' => $data['grade_level'],
        'basic_salary' => $data['basic_salary'],
        'allowances' => $request->input('allowances', []),
        'deductions' => $request->input('deductions', []),
        'effective_from' => $data['effective_from'],
        'effective_to' => $data['effective_to'] ?? null,
        'status' => $data['status'],
        'created_by' => Auth::id(),
        'updated_by' => Auth::id(),
    ]);

    return response()->json(['message' => 'Salary structure created successfully', 'id' => $structure->id]);
}

public function updateSalaryStructure(Request $request, SalaryStructure $salaryStructure)
{
    $data = $request->validate([
        'structure_name' => 'required|string|max:255',
        'grade_level' => 'required|string|max:50',
        'basic_salary' => 'required|numeric|min:0',
        'effective_from' => 'required|date',
        'effective_to' => 'nullable|date|after:effective_from',
        'status' => 'required|in:draft,active,inactive,archived',
        'allowances' => 'array',
        'allowances.*' => 'numeric|min:0',
        'deductions' => 'array',
        'deductions.*' => 'numeric|min:0',
    ]);

    $salaryStructure->update([
        'name' => $data['structure_name'],
        'grade_level' => $data['grade_level'],
        'basic_salary' => $data['basic_salary'],
        'allowances' => $request->input('allowances', []),
        'deductions' => $request->input('deductions', []),
        'effective_from' => $data['effective_from'],
        'effective_to' => $data['effective_to'] ?? null,
        'status' => $data['status'],
        'updated_by' => Auth::id(),
    ]);

    return response()->json(['message' => 'Salary structure updated successfully']);
}

public function destroySalaryStructure(SalaryStructure $salaryStructure)
{
    $salaryStructure->delete();
    return response()->json(['message' => 'Salary structure deleted successfully']);
}

public function getDeduction(PayrollDeduction $deduction)
{
    $response = [
        'id' => $deduction->id,
        'employee_id' => $deduction->employee_id,
        'deduction_type' => $deduction->deduction_type,
        'description' => $deduction->description,
        'amount' => (float) $deduction->deduction_amount,
        'calculation_method' => $deduction->calculation_method === PayrollDeduction::METHOD_FIXED_AMOUNT ? 'fixed' : ($deduction->calculation_method === PayrollDeduction::METHOD_PERCENTAGE ? 'percentage_gross' : 'manual'),
        'rate' => (float) ($deduction->deduction_rate ?? 0),
        'effective_from' => optional($deduction->effective_from)->toDateString(),
        'effective_to' => optional($deduction->effective_to)->toDateString(),
        'priority' => data_get($deduction->compliance_data, 'priority', 'medium'),
        'is_recurring' => (bool) $deduction->is_recurring,
        'remarks' => $deduction->remarks,
        'loan_advance_details' => data_get($deduction->compliance_data, 'loan_advance_details'),
        'statutory_details' => [
            'pan_number' => $deduction->pan_number,
            'pf_number' => $deduction->pf_number,
            'esi_number' => $deduction->esi_number,
            'uan_number' => data_get($deduction->compliance_data, 'uan_number')
        ],
    ];

    return response()->json($response);
}

public function storeDeduction(Request $request)
{
    $data = $request->validate([
        'employee_id' => 'required|exists:users,id',
        'deduction_type' => 'required|in:statutory,voluntary,disciplinary,advance,loan,other',
        'description' => 'required|string|max:1000',
        'amount' => 'required|numeric|min:0',
        'calculation_method' => 'nullable|string',
        'rate' => 'nullable|numeric|min:0|max:100',
        'effective_from' => 'required|date',
        'effective_to' => 'nullable|date|after:effective_from',
        'priority' => 'nullable|string|in:low,medium,high,urgent',
        'is_recurring' => 'nullable|boolean',
        'remarks' => 'nullable|string|max:500',
    ]);

    $calcMethod = match($request->get('calculation_method')) {
        'fixed' => PayrollDeduction::METHOD_FIXED_AMOUNT,
        'percentage_gross', 'percentage_basic' => PayrollDeduction::METHOD_PERCENTAGE,
        default => PayrollDeduction::METHOD_MANUAL,
    };

    $employee = User::findOrFail($data['employee_id']);

    $compliance = [
        'priority' => $request->get('priority'),
        'loan_advance_details' => $request->only(['loan_total_amount', 'loan_installments', 'loan_interest_rate']) ?: null,
        'uan_number' => $request->get('uan_number'),
    ];

    $deduction = PayrollDeduction::create([
        'employee_id' => $employee->id,
        'employee_name' => $employee->name,
        'deduction_type' => $data['deduction_type'],
        'description' => $data['description'],
        'gross_salary' => 0, // Will be set during processing
        'basic_salary' => null,
        'deduction_rate' => $request->get('rate'),
        'deduction_amount' => $data['amount'],
        'employer_contribution' => 0,
        'calculation_method' => $calcMethod,
        'pan_number' => $request->get('pan_number'),
        'pf_number' => $request->get('pf_number'),
        'esi_number' => $request->get('esi_number'),
        'tds_amount' => 0,
        'pf_employee' => 0,
        'pf_employer' => 0,
        'esi_employee' => 0,
        'esi_employer' => 0,
        'effective_from' => $data['effective_from'],
        'effective_to' => $data['effective_to'] ?? null,
        'status' => PayrollDeduction::STATUS_PENDING,
        'is_recurring' => (bool) ($data['is_recurring'] ?? false),
        'remarks' => $data['remarks'] ?? null,
        'created_by' => Auth::id(),
        'updated_by' => Auth::id(),
        'compliance_data' => $compliance,
    ]);

    return response()->json(['message' => 'Deduction created successfully', 'id' => $deduction->id]);
}

public function updateDeduction(Request $request, PayrollDeduction $deduction)
{
    $data = $request->validate([
        'employee_id' => 'required|exists:users,id',
        'deduction_type' => 'required|in:statutory,voluntary,disciplinary,advance,loan,other',
        'description' => 'required|string|max:1000',
        'amount' => 'required|numeric|min:0',
        'calculation_method' => 'nullable|string',
        'rate' => 'nullable|numeric|min:0|max:100',
        'effective_from' => 'required|date',
        'effective_to' => 'nullable|date|after:effective_from',
        'priority' => 'nullable|string|in:low,medium,high,urgent',
        'is_recurring' => 'nullable|boolean',
        'remarks' => 'nullable|string|max:500',
    ]);

    $calcMethod = match($request->get('calculation_method')) {
        'fixed' => PayrollDeduction::METHOD_FIXED_AMOUNT,
        'percentage_gross', 'percentage_basic' => PayrollDeduction::METHOD_PERCENTAGE,
        default => PayrollDeduction::METHOD_MANUAL,
    };

    $employee = User::findOrFail($data['employee_id']);

    $compliance = [
        'priority' => $request->get('priority'),
        'loan_advance_details' => $request->only(['loan_total_amount', 'loan_installments', 'loan_interest_rate']) ?: null,
        'uan_number' => $request->get('uan_number'),
    ];

    $deduction->update([
        'employee_id' => $employee->id,
        'employee_name' => $employee->name,
        'deduction_type' => $data['deduction_type'],
        'description' => $data['description'],
        'deduction_rate' => $request->get('rate'),
        'deduction_amount' => $data['amount'],
        'calculation_method' => $calcMethod,
        'pan_number' => $request->get('pan_number'),
        'pf_number' => $request->get('pf_number'),
        'esi_number' => $request->get('esi_number'),
        'effective_from' => $data['effective_from'],
        'effective_to' => $data['effective_to'] ?? null,
        'status' => $deduction->status === PayrollDeduction::STATUS_CANCELLED ? $deduction->status : PayrollDeduction::STATUS_PENDING,
        'is_recurring' => (bool) ($data['is_recurring'] ?? false),
        'remarks' => $data['remarks'] ?? null,
        'updated_by' => Auth::id(),
        'compliance_data' => $compliance,
    ]);

    return response()->json(['message' => 'Deduction updated successfully']);
}

public function destroyDeduction(PayrollDeduction $deduction)
{
    $deduction->delete();
    return response()->json(['message' => 'Deduction deleted successfully']);
}

public function approveDeduction(PayrollDeduction $deduction)
{
    if ($deduction->status !== PayrollDeduction::STATUS_PENDING) {
        return response()->json(['message' => 'Deduction cannot be approved'], 400);
    }

    $deduction->status = PayrollDeduction::STATUS_APPROVED;
    $deduction->approved_by = Auth::id();
    $deduction->approved_at = now();
    $deduction->save();

    return response()->json(['message' => 'Deduction approved successfully']);
}

public function getSalaryStructureStats(Request $request)
{
    $month = $request->get('month');
    $year = $request->get('year');
    $period = null;
    if ($month && $year) {
        try {
            $period = Carbon::createFromDate((int)$year, (int)$month, 1);
        } catch (\Exception $e) {
            $period = now();
        }
    } else {
        $period = now();
    }

    $totalStructures = SalaryStructure::count();
    $activeStructures = SalaryStructure::where('status', SalaryStructure::STATUS_ACTIVE)->count();
    $gradeLevels = SalaryStructure::distinct()->pluck('grade_level')->filter()->count();

    $activeList = SalaryStructure::where('status', SalaryStructure::STATUS_ACTIVE)->get();
    $grossSum = 0; $netSum = 0; $count = 0;
    foreach ($activeList as $s) {
        $calc = $s->calculateSalary();
        $grossSum += $calc['gross_salary'] ?? 0;
        $netSum += $calc['net_salary'] ?? 0;
        $count++;
    }
    $avgGross = $count > 0 ? round($grossSum / $count, 2) : 0;

    $totalDeductionsCount = PayrollDeduction::count();
    $pendingApprovals = PayrollDeduction::where('status', PayrollDeduction::STATUS_PENDING)->count();
    $activeLoans = PayrollDeduction::where('deduction_type', PayrollDeduction::TYPE_LOAN)
        ->whereIn('status', [PayrollDeduction::STATUS_APPROVED, PayrollDeduction::STATUS_PROCESSED])
        ->where(function($q) use ($period) {
            $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $period->toDateString());
        })
        ->count();

    $monthlySum = PayrollDeduction::where('payroll_year', $period->year)
        ->where('payroll_month', $period->month)
        ->sum('deduction_amount');

    // For reports page additional stats
    $totalEmployees = User::count();
    $totalPayroll = round($grossSum, 2);
    $netPayable = round($netSum, 2);

    return response()->json([
        'total_structures' => $totalStructures,
        'active_structures' => $activeStructures,
        'grade_levels' => $gradeLevels,
        'avg_gross_salary' => $avgGross,
        'total_deductions' => $totalDeductionsCount,
        'pending_approvals' => $pendingApprovals,
        'active_loans' => $activeLoans,
        'monthly_deductions' => round($monthlySum, 2),
        'total_employees' => $totalEmployees,
        'total_payroll' => $totalPayroll,
        'net_payable' => $netPayable,
    ]);
}

public function getEmployees(Request $request)
{
    $employees = User::select('id', 'name', 'employee_code')
        ->orderBy('name')
        ->get()
        ->map(function($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'employee_id' => $u->employee_code,
            ];
        });

    return response()->json($employees);
}
}