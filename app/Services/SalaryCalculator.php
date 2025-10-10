<?php

namespace App\Services;

use App\Models\User;
use App\Models\SalaryStructure;
use App\Models\PayrollDeduction;
use App\Models\BiometricAttendance;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class SalaryCalculator
{
    // Salary component percentages as requested
    const BASIC_PERCENTAGE = 0.4; // 40% of gross salary
    const HRA_PERCENTAGE = 0.2; // 20% of gross salary
    const ALLOWANCE_PERCENTAGE = 0.15; // 15% of gross salary
    
    // Constants for magic numbers
    const HEALTH_EDUCATION_CESS_RATE = 1.04; // 4% Health and Education Cess
    const SUNDAY_DAY_OF_WEEK = 0; // Sunday identifier in Carbon
    const PROFESSIONAL_TAX_LOWER_THRESHOLD = 15000; // Professional tax exemption limit
    const PROFESSIONAL_TAX_UPPER_THRESHOLD = 25000; // Professional tax upper threshold
    const PROFESSIONAL_TAX_LOWER_AMOUNT = 175; // Professional tax for middle slab
    const PROFESSIONAL_TAX_UPPER_AMOUNT = 200; // Professional tax for upper slab
    const STANDARD_DEDUCTION_AMOUNT = 50000; // Annual standard deduction
    const MONTHS_IN_YEAR = 12; // Number of months in a year
    const PERCENTAGE_DIVISOR = 100; // For percentage calculations
    const CALCULATION_TOLERANCE = 0.01; // Tolerance for validation calculations

    // Tax slabs for FY 2024-25 (New Tax Regime)
    protected $taxSlabs = [
        ['min' => 0, 'max' => 300000, 'rate' => 0],
        ['min' => 300001, 'max' => 600000, 'rate' => 5],
        ['min' => 600001, 'max' => 900000, 'rate' => 10],
        ['min' => 900001, 'max' => 1200000, 'rate' => 15],
        ['min' => 1200001, 'max' => 1500000, 'rate' => 20],
        ['min' => 1500001, 'max' => PHP_INT_MAX, 'rate' => 30]
    ];

    // Statutory rates
    protected $pfRate = 12.0; // 12% for both employee and employer
    protected $esiEmployeeRate = 0.75; // 0.75% for employee
    protected $esiEmployerRate = 3.25; // 3.25% for employer
    protected $esiSalaryLimit = 25000; // ESI applicable up to 25k gross
    protected $pfSalaryLimit = 15000; // PF calculated on basic salary up to 15k

    /**
     * Calculate net salary using the new percentage constants
     */
    public function calculateNetSalary($grossSalary)
    {
        $basic = $grossSalary * self::BASIC_PERCENTAGE;
        $hra = $grossSalary * self::HRA_PERCENTAGE;
        $allowances = $grossSalary * self::ALLOWANCE_PERCENTAGE;
        
        return $basic + $hra + $allowances;
    }

    /**
     * Calculate net salary with all deductions (legacy method)
     */
    public function calculateNetSalaryDetailed($basic, $allowances, $deductions = [])
    {
        try {
            // Calculate gross salary
            $totalAllowances = is_array($allowances) ? array_sum($allowances) : $allowances;
            $grossSalary = $basic + $totalAllowances;

            // Calculate statutory deductions
            $statutoryDeductions = $this->calculateStatutoryDeductions($basic, $grossSalary);

            // Add voluntary deductions
            $voluntaryDeductions = is_array($deductions) ? array_sum($deductions) : $deductions;

            // Calculate total deductions
            $totalDeductions = array_sum($statutoryDeductions) + $voluntaryDeductions;

            // Calculate net salary
            $netSalary = $grossSalary - $totalDeductions;

            return [
                'basic_salary' => $basic,
                'allowances' => is_array($allowances) ? $allowances : ['total' => $allowances],
                'total_allowances' => $totalAllowances,
                'gross_salary' => $grossSalary,
                'statutory_deductions' => $statutoryDeductions,
                'voluntary_deductions' => is_array($deductions) ? $deductions : ['other' => $deductions],
                'total_deductions' => $totalDeductions,
                'net_salary' => $netSalary,
                'employer_contributions' => $this->calculateEmployerContributions($basic, $grossSalary)
            ];

        } catch (\Exception $e) {
            throw new \Exception("Salary calculation failed: " . $e->getMessage());
        }
    }

    /**
     * Calculate statutory deductions (PF, ESI, TDS, Professional Tax)
     */
    protected function calculateStatutoryDeductions($basicSalary, $grossSalary)
    {
        $deductions = [];

        // Provident Fund calculation (12% of basic salary, max on 15k)
        $pfBasic = min($basicSalary, $this->pfSalaryLimit);
        $deductions['pf'] = round($pfBasic * $this->pfRate / self::PERCENTAGE_DIVISOR, 2);

        // ESI calculation (0.75% of gross salary, applicable up to 25k)
        if ($grossSalary <= $this->esiSalaryLimit) {
            $deductions['esi'] = round($grossSalary * $this->esiEmployeeRate / self::PERCENTAGE_DIVISOR, 2);
        } else {
            $deductions['esi'] = 0;
        }

        // Professional Tax (state-specific, using Maharashtra rates)
        $deductions['professional_tax'] = $this->calculateProfessionalTax($grossSalary);

        // TDS calculation (monthly)
        $annualGross = $grossSalary * self::MONTHS_IN_YEAR;
        $deductions['tds'] = $this->calculateTDS($annualGross);

        return $deductions;
    }

    /**
     * Calculate employer contributions
     */
    protected function calculateEmployerContributions($basicSalary, $grossSalary)
    {
        $contributions = [];

        // Employer PF contribution (12% of basic salary, max on 15k)
        $pfBasic = min($basicSalary, $this->pfSalaryLimit);
        $contributions['pf'] = round($pfBasic * $this->pfRate / self::PERCENTAGE_DIVISOR, 2);

        // Employer ESI contribution (3.25% of gross salary, applicable up to 25k)
        if ($grossSalary <= $this->esiSalaryLimit) {
            $contributions['esi'] = round($grossSalary * $this->esiEmployerRate / self::PERCENTAGE_DIVISOR, 2);
        } else {
            $contributions['esi'] = 0;
        }

        return $contributions;
    }

    /**
     * Calculate Professional Tax based on salary slabs
     */
    protected function calculateProfessionalTax($grossSalary)
    {
        // Maharashtra Professional Tax rates
        if ($grossSalary <= self::PROFESSIONAL_TAX_LOWER_THRESHOLD) {
            return 0;
        } elseif ($grossSalary <= self::PROFESSIONAL_TAX_UPPER_THRESHOLD) {
            return self::PROFESSIONAL_TAX_LOWER_AMOUNT;
        } else {
            return self::PROFESSIONAL_TAX_UPPER_AMOUNT;
        }
    }

    /**
     * Calculate TDS based on annual salary and tax slabs
     */
    protected function calculateTDS($annualSalary)
    {
        // Standard deduction
        $standardDeduction = self::STANDARD_DEDUCTION_AMOUNT;
        $taxableIncome = max(0, $annualSalary - $standardDeduction);

        $tax = 0;
        foreach ($this->taxSlabs as $slab) {
            if ($taxableIncome > $slab['min']) {
                $taxableAmount = min($taxableIncome, $slab['max']) - $slab['min'] + 1;
                $tax += $taxableAmount * $slab['rate'] / self::PERCENTAGE_DIVISOR;
            }
        }

        // Add 4% Health and Education Cess
        $tax = $tax * self::HEALTH_EDUCATION_CESS_RATE;

        // Return monthly TDS
        return round($tax / self::MONTHS_IN_YEAR, 2);
    }

    /**
     * Calculate leave deductions based on attendance
     */
    public function calculateLeaveDeductions(User $employee, $year, $month, $basicSalary)
    {
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        $totalWorkingDays = $this->getWorkingDaysInMonth($year, $month);

        // Get attendance records
        $attendanceRecords = BiometricAttendance::where('teacher_id', $employee->id)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get();

        $presentDays = $attendanceRecords->where('status', 'present')->count();
        $absentDays = $totalWorkingDays - $presentDays;

        // Calculate per day salary
        $perDaySalary = $basicSalary / $totalWorkingDays;

        // Deduct for absent days (assuming no paid leave for simplicity)
        $leaveDeduction = $absentDays * $perDaySalary;

        return [
            'total_working_days' => $totalWorkingDays,
            'present_days' => $presentDays,
            'absent_days' => $absentDays,
            'per_day_salary' => round($perDaySalary, 2),
            'leave_deduction' => round($leaveDeduction, 2)
        ];
    }

    /**
     * Get working days in a month (excluding Sundays)
     */
    protected function getWorkingDaysInMonth($year, $month)
    {
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        $workingDays = 0;

        while ($startDate->lte($endDate)) {
            // Exclude Sundays (0 = Sunday)
            if ($startDate->dayOfWeek !== self::SUNDAY_DAY_OF_WEEK) {
                $workingDays++;
            }
            $startDate->addDay();
        }

        return $workingDays;
    }

    /**
     * Generate payslip PDF
     */
    public function generatePayslip($salaryData, $employee, $year, $month)
    {
        try {
            // Add leave deductions to salary data
            $leaveData = $this->calculateLeaveDeductions($employee, $year, $month, $salaryData['basic_salary']);
            $salaryData['leave_details'] = $leaveData;
            
            // Adjust net salary for leave deductions
            $salaryData['net_salary'] -= $leaveData['leave_deduction'];
            $salaryData['total_deductions'] += $leaveData['leave_deduction'];

            // Prepare data for PDF
            $pdfData = [
                'employee' => $employee,
                'salary_data' => $salaryData,
                'period' => [
                    'month' => Carbon::create($year, $month, 1)->format('F Y'),
                    'year' => $year,
                    'month_num' => $month
                ],
                'company' => [
                    'name' => config('app.name', 'PNS Dhampur'),
                    'address' => 'Dhampur, Uttar Pradesh',
                    'phone' => '+91-XXXXXXXXXX',
                    'email' => 'info@pnsdhampur.com'
                ],
                'generated_at' => now()->format('d/m/Y H:i:s')
            ];

            // Generate PDF
            $pdf = Pdf::loadView('payroll.payslip-pdf', $pdfData);
            $pdf->setPaper('A4', 'portrait');

            // Generate filename
            $filename = "payslip_{$employee->employee_id}_{$year}_{$month}.pdf";
            
            // Save to storage
            $path = "payslips/{$year}/{$month}";
            Storage::makeDirectory($path);
            $fullPath = "{$path}/{$filename}";
            
            Storage::put($fullPath, $pdf->output());

            return [
                'success' => true,
                'filename' => $filename,
                'path' => $fullPath,
                'download_url' => Storage::url($fullPath),
                'pdf_content' => $pdf->output()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Calculate annual tax summary
     */
    public function calculateAnnualTaxSummary(User $employee, $year)
    {
        $monthlySalaries = [];
        $totalGross = 0;
        $totalTDS = 0;

        // Calculate for each month
        for ($month = 1; $month <= self::MONTHS_IN_YEAR; $month++) {
            try {
                // Get salary structure for employee
                $salaryStructure = SalaryStructure::where('user_id', $employee->id)
                    ->where('status', 'active')
                    ->first();

                if ($salaryStructure) {
                    $salaryCalc = $salaryStructure->calculateSalary();
                    $netSalary = $this->calculateNetSalaryDetailed(
                        $salaryCalc['basic_salary'],
                        $salaryCalc['allowances'],
                        []
                    );

                    $monthlySalaries[$month] = $netSalary;
                    $totalGross += $netSalary['gross_salary'];
                    $totalTDS += $netSalary['statutory_deductions']['tds'];
                }
            } catch (\Exception $e) {
                // Skip months with errors
                continue;
            }
        }

        // Calculate actual tax liability
        $standardDeduction = self::STANDARD_DEDUCTION_AMOUNT;
        $taxableIncome = max(0, $totalGross - $standardDeduction);
        $actualTax = 0;

        foreach ($this->taxSlabs as $slab) {
            if ($taxableIncome > $slab['min']) {
                $taxableAmount = min($taxableIncome, $slab['max']) - $slab['min'] + 1;
                $actualTax += $taxableAmount * $slab['rate'] / self::PERCENTAGE_DIVISOR;
            }
        }

        // Add cess
        $actualTax = $actualTax * self::HEALTH_EDUCATION_CESS_RATE;

        return [
            'employee' => $employee,
            'year' => $year,
            'monthly_salaries' => $monthlySalaries,
            'total_gross' => $totalGross,
            'standard_deduction' => $standardDeduction,
            'taxable_income' => $taxableIncome,
            'actual_tax' => round($actualTax, 2),
            'tds_deducted' => round($totalTDS, 2),
            'tax_payable' => round(max(0, $actualTax - $totalTDS), 2),
            'refund_due' => round(max(0, $totalTDS - $actualTax), 2)
        ];
    }

    /**
     * Validate salary calculation
     */
    public function validateCalculation($salaryData)
    {
        $errors = [];
        $warnings = [];

        // Basic validations
        if ($salaryData['net_salary'] < 0) {
            $errors[] = 'Net salary cannot be negative';
        }

        if ($salaryData['total_deductions'] > $salaryData['gross_salary']) {
            $errors[] = 'Total deductions exceed gross salary';
        }

        // PF validation
        $expectedPF = min($salaryData['basic_salary'], $this->pfSalaryLimit) * $this->pfRate / self::PERCENTAGE_DIVISOR;
        if (abs($salaryData['statutory_deductions']['pf'] - $expectedPF) > self::CALCULATION_TOLERANCE) {
            $warnings[] = 'PF calculation may be incorrect';
        }

        // ESI validation
        if ($salaryData['gross_salary'] > $this->esiSalaryLimit && $salaryData['statutory_deductions']['esi'] > 0) {
            $warnings[] = 'ESI should not be applicable for gross salary > ₹25,000';
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Bulk salary calculation for multiple employees
     */
    public function calculateBulkSalaries($employees, $year, $month)
    {
        $results = [];
        $errors = [];

        foreach ($employees as $employee) {
            try {
                // Get salary structure
                $salaryStructure = SalaryStructure::where('user_id', $employee->id)
                    ->where('status', 'active')
                    ->first();

                if (!$salaryStructure) {
                    $errors[] = [
                        'employee' => $employee,
                        'error' => 'No active salary structure found'
                    ];
                    continue;
                }

                $salaryCalc = $salaryStructure->calculateSalary();
                $netSalary = $this->calculateNetSalaryDetailed(
                    $salaryCalc['basic_salary'],
                    $salaryCalc['allowances'],
                    []
                );

                $results[] = [
                    'employee' => $employee,
                    'salary_data' => $netSalary,
                    'period' => ['year' => $year, 'month' => $month]
                ];

            } catch (\Exception $e) {
                $errors[] = [
                    'employee' => $employee,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'results' => $results,
            'errors' => $errors,
            'summary' => [
                'total_employees' => count($employees),
                'processed' => count($results),
                'failed' => count($errors),
                'total_gross' => collect($results)->sum('salary_data.gross_salary'),
                'total_net' => collect($results)->sum('salary_data.net_salary')
            ]
        ];
    }
}