<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\SalaryCalculator;
use App\Services\SalaryCalculationService;
use App\Models\User;
use App\Models\Salary;
use App\Models\SalaryStructure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class SalaryCalculatorTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $salaryCalculator;
    protected $salaryCalculationService;
    protected $employee;
    protected $salaryStructure;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->salaryCalculator = new SalaryCalculator();
        $this->salaryCalculationService = new SalaryCalculationService();
        
        // Create test employee
        $this->employee = User::factory()->create([
            'name' => 'Test Employee',
            'email' => 'employee@test.com',
            'role' => 'teacher',
            'employee_id' => 'EMP001'
        ]);

        // Create test salary structure
        $this->salaryStructure = SalaryStructure::factory()->create([
            'basic_salary' => 50000,
            'hra_percentage' => 40,
            'da_percentage' => 10,
            'medical_allowance' => 2000,
            'transport_allowance' => 1500,
            'pf_percentage' => 12,
            'esi_percentage' => 1.75,
            'professional_tax' => 200,
            'income_tax_percentage' => 10
        ]);
    }

    /** @test */
    public function salary_calculator_calculates_basic_monthly_salary()
    {
        $basicSalary = 50000;
        $result = $this->salaryCalculator->calculateMonthlySalary($basicSalary);

        $this->assertIsArray($result);
        $this->assertEquals($basicSalary, $result['basic_salary']);
        $this->assertArrayHasKey('gross_salary', $result);
        $this->assertArrayHasKey('net_salary', $result);
        $this->assertArrayHasKey('total_deductions', $result);
    }

    /** @test */
    public function salary_calculator_calculates_hra_correctly()
    {
        $basicSalary = 50000;
        $hraPercentage = 40;
        
        $hra = $this->salaryCalculator->calculateHRA($basicSalary, $hraPercentage);
        
        $this->assertEquals(20000, $hra);
    }

    /** @test */
    public function salary_calculator_calculates_da_correctly()
    {
        $basicSalary = 50000;
        $daPercentage = 10;
        
        $da = $this->salaryCalculator->calculateDA($basicSalary, $daPercentage);
        
        $this->assertEquals(5000, $da);
    }

    /** @test */
    public function salary_calculator_calculates_pf_deduction()
    {
        $basicSalary = 50000;
        $pfPercentage = 12;
        
        $pf = $this->salaryCalculator->calculatePF($basicSalary, $pfPercentage);
        
        $this->assertEquals(6000, $pf);
    }

    /** @test */
    public function salary_calculator_calculates_esi_deduction()
    {
        $grossSalary = 75000;
        $esiPercentage = 1.75;
        
        $esi = $this->salaryCalculator->calculateESI($grossSalary, $esiPercentage);
        
        $this->assertEquals(1312.5, $esi);
    }

    /** @test */
    public function salary_calculator_calculates_income_tax()
    {
        $annualSalary = 600000;
        $taxPercentage = 10;
        
        $tax = $this->salaryCalculator->calculateIncomeTax($annualSalary, $taxPercentage);
        
        $this->assertEquals(60000, $tax);
    }

    /** @test */
    public function salary_calculator_handles_zero_basic_salary()
    {
        $result = $this->salaryCalculator->calculateMonthlySalary(0);
        
        $this->assertEquals(0, $result['basic_salary']);
        $this->assertEquals(0, $result['gross_salary']);
        $this->assertEquals(0, $result['net_salary']);
    }

    /** @test */
    public function salary_calculator_handles_negative_salary()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Salary cannot be negative');
        
        $this->salaryCalculator->calculateMonthlySalary(-1000);
    }

    /** @test */
    public function salary_calculator_handles_very_large_salary()
    {
        $largeSalary = 10000000; // 1 crore
        $result = $this->salaryCalculator->calculateMonthlySalary($largeSalary);
        
        $this->assertIsArray($result);
        $this->assertEquals($largeSalary, $result['basic_salary']);
        $this->assertGreaterThan(0, $result['gross_salary']);
    }

    /** @test */
    public function salary_calculation_service_calculates_complete_salary()
    {
        $salaryData = [
            'basic_salary' => 50000,
            'hra_percentage' => 40,
            'da_percentage' => 10,
            'medical_allowance' => 2000,
            'transport_allowance' => 1500,
            'pf_percentage' => 12,
            'esi_percentage' => 1.75,
            'professional_tax' => 200
        ];

        $result = $this->salaryCalculationService->calculateSalary($salaryData);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('basic_salary', $result);
        $this->assertArrayHasKey('hra', $result);
        $this->assertArrayHasKey('da', $result);
        $this->assertArrayHasKey('medical_allowance', $result);
        $this->assertArrayHasKey('transport_allowance', $result);
        $this->assertArrayHasKey('gross_salary', $result);
        $this->assertArrayHasKey('pf_deduction', $result);
        $this->assertArrayHasKey('esi_deduction', $result);
        $this->assertArrayHasKey('professional_tax', $result);
        $this->assertArrayHasKey('total_deductions', $result);
        $this->assertArrayHasKey('net_salary', $result);
    }

    /** @test */
    public function salary_calculation_service_validates_required_fields()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Basic salary is required');
        
        $this->salaryCalculationService->calculateSalary([]);
    }

    /** @test */
    public function salary_calculation_service_handles_missing_allowances()
    {
        $salaryData = [
            'basic_salary' => 50000
        ];

        $result = $this->salaryCalculationService->calculateSalary($salaryData);

        $this->assertEquals(0, $result['hra']);
        $this->assertEquals(0, $result['da']);
        $this->assertEquals(0, $result['medical_allowance']);
        $this->assertEquals(0, $result['transport_allowance']);
    }

    /** @test */
    public function salary_calculator_calculates_annual_salary()
    {
        $monthlySalary = 75000;
        $annualSalary = $this->salaryCalculator->calculateAnnualSalary($monthlySalary);
        
        $this->assertEquals(900000, $annualSalary);
    }

    /** @test */
    public function salary_calculator_calculates_bonus()
    {
        $basicSalary = 50000;
        $bonusPercentage = 15;
        
        $bonus = $this->salaryCalculator->calculateBonus($basicSalary, $bonusPercentage);
        
        $this->assertEquals(7500, $bonus);
    }

    /** @test */
    public function salary_calculator_calculates_overtime()
    {
        $hourlyRate = 500;
        $overtimeHours = 10;
        $overtimeMultiplier = 1.5;
        
        $overtime = $this->salaryCalculator->calculateOvertime($hourlyRate, $overtimeHours, $overtimeMultiplier);
        
        $this->assertEquals(7500, $overtime);
    }

    /** @test */
    public function salary_calculator_handles_esi_exemption_for_high_salary()
    {
        $highGrossSalary = 25000; // Above ESI limit
        $esiPercentage = 1.75;
        
        $esi = $this->salaryCalculator->calculateESI($highGrossSalary, $esiPercentage, true);
        
        $this->assertEquals(0, $esi); // Should be exempt
    }

    /** @test */
    public function salary_calculator_applies_professional_tax_slabs()
    {
        $grossSalary = 75000;
        
        $professionalTax = $this->salaryCalculator->calculateProfessionalTax($grossSalary);
        
        $this->assertGreaterThan(0, $professionalTax);
        $this->assertLessThanOrEqual(200, $professionalTax); // Max PT limit
    }

    /** @test */
    public function salary_calculation_service_calculates_with_salary_structure()
    {
        $result = $this->salaryCalculationService->calculateWithStructure($this->salaryStructure);

        $this->assertIsArray($result);
        $this->assertEquals($this->salaryStructure->basic_salary, $result['basic_salary']);
        
        // Verify HRA calculation
        $expectedHRA = ($this->salaryStructure->basic_salary * $this->salaryStructure->hra_percentage) / 100;
        $this->assertEquals($expectedHRA, $result['hra']);
        
        // Verify DA calculation
        $expectedDA = ($this->salaryStructure->basic_salary * $this->salaryStructure->da_percentage) / 100;
        $this->assertEquals($expectedDA, $result['da']);
    }

    /** @test */
    public function salary_calculator_handles_decimal_percentages()
    {
        $basicSalary = 50000;
        $hraPercentage = 40.5; // Decimal percentage
        
        $hra = $this->salaryCalculator->calculateHRA($basicSalary, $hraPercentage);
        
        $this->assertEquals(20250, $hra);
    }

    /** @test */
    public function salary_calculator_rounds_calculations_properly()
    {
        $basicSalary = 33333;
        $hraPercentage = 40;
        
        $hra = $this->salaryCalculator->calculateHRA($basicSalary, $hraPercentage);
        
        // Should round to 2 decimal places
        $this->assertEquals(13333.2, $hra);
    }

    /** @test */
    public function salary_calculation_service_handles_custom_deductions()
    {
        $salaryData = [
            'basic_salary' => 50000,
            'custom_deductions' => [
                'loan_deduction' => 5000,
                'advance_deduction' => 2000,
                'other_deduction' => 1000
            ]
        ];

        $result = $this->salaryCalculationService->calculateSalary($salaryData);

        $this->assertArrayHasKey('custom_deductions', $result);
        $this->assertEquals(8000, $result['custom_deductions']);
    }

    /** @test */
    public function salary_calculator_calculates_gratuity()
    {
        $basicSalary = 50000;
        $yearsOfService = 10;
        
        $gratuity = $this->salaryCalculator->calculateGratuity($basicSalary, $yearsOfService);
        
        // Gratuity = (Basic + DA) * 15/26 * Years of service
        $expectedGratuity = ($basicSalary * 15 / 26) * $yearsOfService;
        $this->assertEquals(round($expectedGratuity, 2), $gratuity);
    }

    /** @test */
    public function salary_calculator_handles_leave_deductions()
    {
        $dailySalary = 2000;
        $leaveDays = 3;
        
        $leaveDeduction = $this->salaryCalculator->calculateLeaveDeduction($dailySalary, $leaveDays);
        
        $this->assertEquals(6000, $leaveDeduction);
    }

    /** @test */
    public function salary_calculation_service_generates_payslip_data()
    {
        $salaryData = [
            'employee_id' => $this->employee->id,
            'basic_salary' => 50000,
            'hra_percentage' => 40,
            'da_percentage' => 10,
            'medical_allowance' => 2000,
            'transport_allowance' => 1500,
            'pf_percentage' => 12,
            'esi_percentage' => 1.75,
            'professional_tax' => 200
        ];

        $payslipData = $this->salaryCalculationService->generatePayslipData($salaryData);

        $this->assertIsArray($payslipData);
        $this->assertArrayHasKey('employee', $payslipData);
        $this->assertArrayHasKey('salary_breakdown', $payslipData);
        $this->assertArrayHasKey('pay_period', $payslipData);
        $this->assertArrayHasKey('generated_at', $payslipData);
    }

    /** @test */
    public function salary_calculator_validates_percentage_ranges()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Percentage cannot exceed 100');
        
        $this->salaryCalculator->calculateHRA(50000, 150); // Invalid percentage
    }

    /** @test */
    public function salary_calculator_handles_zero_working_days()
    {
        $monthlySalary = 50000;
        $workingDays = 0;
        $totalDays = 30;
        
        $result = $this->salaryCalculator->calculateProportionateSalary($monthlySalary, $workingDays, $totalDays);
        
        $this->assertEquals(0, $result);
    }

    /** @test */
    public function salary_calculator_calculates_proportionate_salary()
    {
        $monthlySalary = 60000;
        $workingDays = 15;
        $totalDays = 30;
        
        $result = $this->salaryCalculator->calculateProportionateSalary($monthlySalary, $workingDays, $totalDays);
        
        $this->assertEquals(30000, $result);
    }

    /** @test */
    public function salary_calculation_service_handles_arrears()
    {
        $salaryData = [
            'basic_salary' => 50000,
            'arrears' => 10000
        ];

        $result = $this->salaryCalculationService->calculateSalary($salaryData);

        $this->assertArrayHasKey('arrears', $result);
        $this->assertEquals(10000, $result['arrears']);
        $this->assertGreaterThan(50000, $result['gross_salary']); // Should include arrears
    }

    /** @test */
    public function salary_calculator_handles_concurrent_calculations()
    {
        $basicSalary = 50000;
        
        // Simulate multiple concurrent calculations
        $results = [];
        for ($i = 0; $i < 5; $i++) {
            $results[] = $this->salaryCalculator->calculateMonthlySalary($basicSalary);
        }
        
        // All results should be identical
        foreach ($results as $result) {
            $this->assertEquals($basicSalary, $result['basic_salary']);
            $this->assertEquals($results[0]['gross_salary'], $result['gross_salary']);
            $this->assertEquals($results[0]['net_salary'], $result['net_salary']);
        }
    }

    /** @test */
    public function salary_calculation_service_caches_complex_calculations()
    {
        $salaryData = [
            'basic_salary' => 50000,
            'hra_percentage' => 40,
            'da_percentage' => 10
        ];

        $startTime = microtime(true);
        $result1 = $this->salaryCalculationService->calculateSalary($salaryData);
        $firstCallTime = microtime(true) - $startTime;

        $startTime = microtime(true);
        $result2 = $this->salaryCalculationService->calculateSalary($salaryData);
        $secondCallTime = microtime(true) - $startTime;

        // Results should be identical
        $this->assertEquals($result1, $result2);
        
        // Second call should be faster (cached)
        $this->assertLessThan($firstCallTime, $secondCallTime);
    }
}