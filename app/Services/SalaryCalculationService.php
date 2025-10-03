<?php

namespace App\Services;

use App\Models\User;
use App\Models\SalaryStructure;
use App\Models\PayrollDeduction;
use Illuminate\Support\Collection;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class SalaryCalculationService
{
    protected $taxSlabs = [
        ['min' => 0, 'max' => 250000, 'rate' => 0],
        ['min' => 250001, 'max' => 500000, 'rate' => 5],
        ['min' => 500001, 'max' => 1000000, 'rate' => 20],
        ['min' => 1000001, 'max' => PHP_INT_MAX, 'rate' => 30]
    ];

    protected $pfRate = 12.0; // 12% for both employee and employer
    protected $esiEmployeeRate = 0.75; // 0.75% for employee
    protected $esiEmployerRate = 3.25; // 3.25% for employer
    protected $esiSalaryLimit = 25000; // ESI applicable up to 25k gross

    /**
     * Calculate salary for an employee for a specific month
     */
    public function calculateMonthlySalary(User $employee, int $year, int $month, array $overrides = []): array
    {
        // Get employee's salary structure
        $salaryStructure = $this->getSalaryStructure($employee);
        
        if (!$salaryStructure) {
            throw new \Exception("No active salary structure found for employee: {$employee->name}");
        }

        // Get base salary calculation
        $baseSalary = $salaryStructure->calculateSalary($overrides);
        
        // Calculate statutory deductions
        $statutoryDeductions = $this->calculateStatutoryDeductions(
            $baseSalary['basic_salary'],
            $baseSalary['gross_salary'],
            $employee,
            $year
        );

        // Get voluntary deductions
        $voluntaryDeductions = $this->getVoluntaryDeductions($employee, $year, $month);

        // Calculate total deductions
        $totalDeductions = array_sum($statutoryDeductions) + array_sum($voluntaryDeductions);

        // Calculate net salary
        $netSalary = $baseSalary['gross_salary'] - $totalDeductions;

        return [
            'employee' => $employee,
            'salary_structure' => $salaryStructure,
            'period' => ['year' => $year, 'month' => $month],
            'basic_salary' => $baseSalary['basic_salary'],
            'allowances' => $baseSalary['allowances'],
            'total_allowances' => $baseSalary['total_allowances'],
            'gross_salary' => $baseSalary['gross_salary'],
            'statutory_deductions' => $statutoryDeductions,
            'voluntary_deductions' => $voluntaryDeductions,
            'total_deductions' => $totalDeductions,
            'net_salary' => $netSalary,
            'employer_contributions' => $this->calculateEmployerContributions($baseSalary['basic_salary'], $baseSalary['gross_salary'])
        ];
    }

    /**
     * Calculate statutory deductions (PF, ESI, PT, TDS)
     */
    protected function calculateStatutoryDeductions(float $basicSalary, float $grossSalary, User $employee, int $year): array
    {
        $deductions = [];

        // Provident Fund (12% of basic salary)
        $pfCalculation = PayrollDeduction::calculatePF($basicSalary, $this->pfRate);
        $deductions['pf'] = $pfCalculation['employee'];

        // Employee State Insurance (0.75% of gross salary, applicable up to 25k)
        $esiCalculation = PayrollDeduction::calculateESI($grossSalary, $this->esiEmployeeRate, $this->esiEmployerRate);
        $deductions['esi'] = $esiCalculation['employee'];

        // Professional Tax (state-specific)
        $deductions['professional_tax'] = PayrollDeduction::calculateProfessionalTax($grossSalary);

        // Tax Deducted at Source (based on annual salary and exemptions)
        $annualGross = $grossSalary * 12;
        $exemptions = $this->getEmployeeExemptions($employee);
        $deductions['tds'] = PayrollDeduction::calculateTDS($grossSalary, $annualGross, $exemptions);

        return $deductions;
    }

    /**
     * Calculate employer contributions
     */
    protected function calculateEmployerContributions(float $basicSalary, float $grossSalary): array
    {
        $contributions = [];

        // Employer PF contribution (12% of basic salary)
        $pfCalculation = PayrollDeduction::calculatePF($basicSalary, $this->pfRate);
        $contributions['pf'] = $pfCalculation['employer'];

        // Employer ESI contribution (3.25% of gross salary)
        $esiCalculation = PayrollDeduction::calculateESI($grossSalary, $this->esiEmployeeRate, $this->esiEmployerRate);
        $contributions['esi'] = $esiCalculation['employer'];

        return $contributions;
    }

    /**
     * Get voluntary deductions for an employee
     */
    protected function getVoluntaryDeductions(User $employee, int $year, int $month): array
    {
        $deductions = PayrollDeduction::forEmployee($employee->id)
            ->forPayrollPeriod($year, $month)
            ->voluntary()
            ->where('status', PayrollDeduction::STATUS_APPROVED)
            ->get();

        $voluntaryDeductions = [];
        foreach ($deductions as $deduction) {
            $voluntaryDeductions[$deduction->deduction_code] = $deduction->deduction_amount;
        }

        return $voluntaryDeductions;
    }

    /**
     * Get employee's salary structure
     */
    protected function getSalaryStructure(User $employee): ?SalaryStructure
    {
        // This can be enhanced to match based on employee grade, department, etc.
        return SalaryStructure::active()
            ->effective()
            ->first();
    }

    /**
     * Get employee tax exemptions
     */
    protected function getEmployeeExemptions(User $employee): array
    {
        // Default exemptions - can be made configurable
        return [
            'standard_deduction' => 50000,
            'hra_exemption' => 0, // Calculate based on HRA received
            'section_80c' => 150000, // PF, PPF, ELSS, etc.
            'section_80d' => 25000, // Medical insurance
        ];
    }

    /**
     * Process payroll for multiple employees
     */
    public function processBulkPayroll(Collection $employees, int $year, int $month): array
    {
        $results = [];
        $errors = [];

        foreach ($employees as $employee) {
            try {
                $salary = $this->calculateMonthlySalary($employee, $year, $month);
                $this->savePayrollDeductions($salary);
                $results[] = $salary;
            } catch (\Exception $e) {
                $errors[] = [
                    'employee' => $employee,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'processed' => $results,
            'errors' => $errors,
            'summary' => [
                'total_employees' => $employees->count(),
                'processed_count' => count($results),
                'error_count' => count($errors),
                'total_gross' => collect($results)->sum('gross_salary'),
                'total_deductions' => collect($results)->sum('total_deductions'),
                'total_net' => collect($results)->sum('net_salary')
            ]
        ];
    }

    /**
     * Save payroll deductions to database
     */
    protected function savePayrollDeductions(array $salaryData): void
    {
        $employee = $salaryData['employee'];
        $year = $salaryData['period']['year'];
        $month = $salaryData['period']['month'];
        $payrollDate = Carbon::create($year, $month, 1)->endOfMonth();

        // Save statutory deductions
        foreach ($salaryData['statutory_deductions'] as $code => $amount) {
            if ($amount > 0) {
                PayrollDeduction::updateOrCreate([
                    'employee_id' => $employee->id,
                    'payroll_year' => $year,
                    'payroll_month' => $month,
                    'deduction_code' => strtoupper($code)
                ], [
                    'employee_name' => $employee->name,
                    'employee_code' => $employee->employee_code ?? null,
                    'payroll_date' => $payrollDate,
                    'deduction_type' => PayrollDeduction::TYPE_STATUTORY,
                    'deduction_name' => PayrollDeduction::DEDUCTION_CODES[strtoupper($code)] ?? ucfirst($code),
                    'gross_salary' => $salaryData['gross_salary'],
                    'basic_salary' => $salaryData['basic_salary'],
                    'deduction_amount' => $amount,
                    'calculation_method' => PayrollDeduction::METHOD_PERCENTAGE,
                    'status' => PayrollDeduction::STATUS_APPROVED,
                    'effective_from' => $payrollDate->startOfMonth(),
                    'created_by' => auth()->id() ?? 1
                ]);
            }
        }
    }

    /**
     * Generate salary slip PDF
     */
    public function generateSalarySlipPDF(array $salaryData): string
    {
        $employee = $salaryData['employee'];
        $period = Carbon::create($salaryData['period']['year'], $salaryData['period']['month'], 1);
        
        $data = [
            'employee' => $employee,
            'salary_data' => $salaryData,
            'period' => $period,
            'company' => [
                'name' => config('app.name', 'PNS Dhampur'),
                'address' => 'Dhampur, Uttar Pradesh',
                'phone' => '+91-XXXXXXXXXX',
                'email' => 'info@pnsdhampur.edu.in'
            ]
        ];

        $pdf = Pdf::loadView('payroll.salary-slip', $data);
        $filename = "salary_slip_{$employee->id}_{$period->format('Y_m')}.pdf";
        
        return $pdf->download($filename);
    }

    /**
     * Generate payroll summary report
     */
    public function generatePayrollSummary(int $year, int $month): array
    {
        $deductions = PayrollDeduction::forPayrollPeriod($year, $month)
            ->with('employee')
            ->get();

        $summary = [
            'period' => ['year' => $year, 'month' => $month],
            'total_employees' => $deductions->groupBy('employee_id')->count(),
            'total_gross' => $deductions->sum('gross_salary'),
            'total_deductions' => $deductions->sum('deduction_amount'),
            'total_net' => $deductions->sum('gross_salary') - $deductions->sum('deduction_amount'),
            'deduction_breakdown' => $deductions->groupBy('deduction_code')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total_amount' => $group->sum('deduction_amount'),
                    'average_amount' => $group->avg('deduction_amount')
                ];
            }),
            'department_wise' => $deductions->groupBy('employee.department')->map(function ($group) {
                return [
                    'employee_count' => $group->groupBy('employee_id')->count(),
                    'total_gross' => $group->sum('gross_salary'),
                    'total_deductions' => $group->sum('deduction_amount'),
                    'total_net' => $group->sum('gross_salary') - $group->sum('deduction_amount')
                ];
            })
        ];

        return $summary;
    }

    /**
     * Calculate annual tax liability
     */
    public function calculateAnnualTax(User $employee, int $year): array
    {
        // Get annual salary
        $monthlySalaries = [];
        for ($month = 1; $month <= 12; $month++) {
            try {
                $monthlySalaries[] = $this->calculateMonthlySalary($employee, $year, $month);
            } catch (\Exception $e) {
                // Skip months where calculation fails
                continue;
            }
        }

        $annualGross = collect($monthlySalaries)->sum('gross_salary');
        $exemptions = $this->getEmployeeExemptions($employee);
        $taxableIncome = $annualGross - array_sum($exemptions);

        $tax = $this->calculateTaxBySlab($taxableIncome);
        $monthlyTDS = collect($monthlySalaries)->sum(function ($salary) {
            return $salary['statutory_deductions']['tds'] ?? 0;
        });

        return [
            'employee' => $employee,
            'year' => $year,
            'annual_gross' => $annualGross,
            'exemptions' => $exemptions,
            'taxable_income' => $taxableIncome,
            'calculated_tax' => $tax,
            'tds_deducted' => $monthlyTDS,
            'tax_payable' => max(0, $tax - $monthlyTDS),
            'refund_due' => max(0, $monthlyTDS - $tax)
        ];
    }

    /**
     * Calculate tax based on slabs
     */
    protected function calculateTaxBySlab(float $taxableIncome): float
    {
        $tax = 0;
        
        foreach ($this->taxSlabs as $slab) {
            if ($taxableIncome > $slab['min']) {
                $taxableInThisSlab = min($taxableIncome, $slab['max']) - $slab['min'] + 1;
                $tax += $taxableInThisSlab * $slab['rate'] / 100;
            }
        }

        return round($tax, 2);
    }

    /**
     * Validate salary calculation
     */
    public function validateSalaryCalculation(array $salaryData): array
    {
        $errors = [];
        $warnings = [];

        // Check if net salary is positive
        if ($salaryData['net_salary'] < 0) {
            $errors[] = 'Net salary cannot be negative';
        }

        // Check if deductions exceed gross salary
        if ($salaryData['total_deductions'] > $salaryData['gross_salary']) {
            $errors[] = 'Total deductions exceed gross salary';
        }

        // Check PF calculation
        $expectedPF = $salaryData['basic_salary'] * $this->pfRate / 100;
        if (abs($salaryData['statutory_deductions']['pf'] - $expectedPF) > 0.01) {
            $warnings[] = 'PF calculation may be incorrect';
        }

        // Check ESI applicability
        if ($salaryData['gross_salary'] > $this->esiSalaryLimit && $salaryData['statutory_deductions']['esi'] > 0) {
            $warnings[] = 'ESI should not be applicable for gross salary > 25,000';
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
}