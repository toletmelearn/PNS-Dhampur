<?php

namespace Tests\Feature;

use App\Services\SalaryCalculator;
use Tests\TestCase;

class SalaryCalculatorNewMethodTest extends TestCase
{
    protected $salaryCalculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->salaryCalculator = new SalaryCalculator();
    }

    /** @test */
    public function test_calculate_net_salary_with_constants()
    {
        $grossSalary = 50000;
        
        $result = $this->salaryCalculator->calculateNetSalary($grossSalary);
        
        // Expected calculations:
        // Basic: 50000 * 0.4 = 20000
        // HRA: 50000 * 0.2 = 10000
        // Allowances: 50000 * 0.15 = 7500
        // Total: 20000 + 10000 + 7500 = 37500
        
        $this->assertEquals(37500, $result);
    }

    /** @test */
    public function test_calculate_net_salary_with_different_amounts()
    {
        // Test with 100,000 gross salary
        $grossSalary = 100000;
        $result = $this->salaryCalculator->calculateNetSalary($grossSalary);
        
        // Expected: 40000 + 20000 + 15000 = 75000
        $this->assertEquals(75000, $result);
        
        // Test with 25,000 gross salary
        $grossSalary = 25000;
        $result = $this->salaryCalculator->calculateNetSalary($grossSalary);
        
        // Expected: 10000 + 5000 + 3750 = 18750
        $this->assertEquals(18750, $result);
    }

    /** @test */
    public function test_calculate_net_salary_with_zero()
    {
        $grossSalary = 0;
        $result = $this->salaryCalculator->calculateNetSalary($grossSalary);
        
        $this->assertEquals(0, $result);
    }

    /** @test */
    public function test_salary_component_percentages()
    {
        $grossSalary = 60000;
        
        // Test individual components
        $basic = $grossSalary * SalaryCalculator::BASIC_PERCENTAGE;
        $hra = $grossSalary * SalaryCalculator::HRA_PERCENTAGE;
        $allowances = $grossSalary * SalaryCalculator::ALLOWANCE_PERCENTAGE;
        
        $this->assertEquals(24000, $basic); // 40% of 60000
        $this->assertEquals(12000, $hra);   // 20% of 60000
        $this->assertEquals(9000, $allowances); // 15% of 60000
        
        $total = $basic + $hra + $allowances;
        $result = $this->salaryCalculator->calculateNetSalary($grossSalary);
        
        $this->assertEquals($total, $result);
        $this->assertEquals(45000, $result);
    }

    /** @test */
    public function test_constants_are_defined()
    {
        $this->assertEquals(0.4, SalaryCalculator::BASIC_PERCENTAGE);
        $this->assertEquals(0.2, SalaryCalculator::HRA_PERCENTAGE);
        $this->assertEquals(0.15, SalaryCalculator::ALLOWANCE_PERCENTAGE);
    }
}