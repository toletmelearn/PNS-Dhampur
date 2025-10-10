<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Salary;
use App\Models\SalaryStructure;
use App\Services\SalaryCalculator;
use App\Services\SalaryCalculationService;
use Carbon\Carbon;

class SalaryCalculationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $admin;
    protected $teacher;
    protected $salaryStructure;
    protected $salaryCalculator;
    protected $salaryCalculationService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@test.com'
        ]);

        // Create teacher user
        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'email' => 'teacher@test.com'
        ]);

        // Create teacher record
        Teacher::factory()->create([
            'user_id' => $this->teacher->id,
            'name' => 'Test Teacher',
            'employee_id' => 'T001',
            'experience_years' => 5
        ]);

        // Create salary structure
        $this->salaryStructure = SalaryStructure::create([
            'name' => 'Primary Teacher Grade A',
            'code' => 'PTA',
            'description' => 'Salary structure for primary teachers',
            'basic_salary' => 30000,
            'minimum_salary' => 25000,
            'maximum_salary' => 50000,
            'increment_percentage' => 5,
            'allowances' => [
                'hra' => ['percentage' => 20],
                'da' => ['percentage' => 15],
                'ta' => ['amount' => 2000],
                'medical' => ['amount' => 1500]
            ],
            'deductions' => [
                'pf' => ['percentage' => 12],
                'esi' => ['percentage' => 1.75],
                'professional_tax' => ['amount' => 200]
            ],
            'is_active' => true
        ]);

        $this->salaryCalculator = new SalaryCalculator();
        $this->salaryCalculationService = new SalaryCalculationService();
    }

    /** @test */
    public function test_basic_salary_calculation()
    {
        $basic = 30000;
        $allowances = ['hra' => 6000, 'da' => 4500, 'ta' => 2000];
        $deductions = ['pf' => 3600, 'esi' => 525];

        $result = $this->salaryCalculator->calculateNetSalaryDetailed($basic, $allowances, $deductions);

        $this->assertEquals(30000, $result['basic_salary']);
        $this->assertEquals(12500, $result['total_allowances']);
        $this->assertEquals(42500, $result['gross_salary']);
        $this->assertGreaterThan(4000, $result['total_deductions']); // Including statutory deductions
        $this->assertLessThan(42500, $result['net_salary']);
    }

    /** @test */
    public function test_allowance_calculation()
    {
        $basicSalary = 30000;
        
        // Test percentage-based allowance
        $hraAmount = $this->salaryStructure->calculateAllowance('hra', $basicSalary);
        $this->assertEquals(6000, $hraAmount); // 20% of 30000

        // Test fixed amount allowance
        $taAmount = $this->salaryStructure->calculateAllowance('ta', $basicSalary);
        $this->assertEquals(2000, $taAmount);
    }

    /** @test */
    public function test_deduction_calculation()
    {
        $grossSalary = 42500;
        
        // Test percentage-based deduction
        $pfAmount = $this->salaryStructure->calculateDeduction('pf', $grossSalary);
        $this->assertEquals(5100, $pfAmount); // 12% of 42500

        // Test fixed amount deduction
        $ptAmount = $this->salaryStructure->calculateDeduction('professional_tax', $grossSalary);
        $this->assertEquals(200, $ptAmount);
    }

    /** @test */
    public function test_complete_salary_structure_calculation()
    {
        $result = $this->salaryStructure->calculateSalary();

        $this->assertEquals(30000, $result['basic_salary']);
        $this->assertArrayHasKey('allowances', $result);
        $this->assertArrayHasKey('deductions', $result);
        $this->assertArrayHasKey('net_salary', $result);
        
        // Verify allowances
        $this->assertEquals(6000, $result['allowances']['hra']); // 20% of 30000
        $this->assertEquals(4500, $result['allowances']['da']); // 15% of 30000
        $this->assertEquals(2000, $result['allowances']['ta']); // Fixed amount
        $this->assertEquals(1500, $result['allowances']['medical']); // Fixed amount

        // Verify net salary is calculated correctly
        $expectedNet = $result['gross_salary'] - $result['total_deductions'];
        $this->assertEquals($expectedNet, $result['net_salary']);
    }

    /** @test */
    public function test_salary_creation_via_api()
    {
        $this->actingAs($this->admin);

        $salaryData = [
            'teacher_id' => $this->teacher->teacher->id,
            'month' => 1,
            'year' => 2024,
            'basic' => 30000,
            'allowances' => ['hra' => 6000, 'da' => 4500, 'ta' => 2000],
            'deductions' => ['pf' => 3600, 'esi' => 525, 'pt' => 200]
        ];

        $response = $this->postJson('/api/salaries', $salaryData);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'id',
            'teacher_id',
            'month',
            'year',
            'basic',
            'allowances',
            'deductions',
            'net_salary'
        ]);

        // Verify salary was created in database
        $this->assertDatabaseHas('salaries', [
            'teacher_id' => $this->user->teacher->id,
            'month' => 1,
            'year' => 2024,
            'basic' => 30000
        ]);
    }

    /** @test */
    public function test_automatic_net_salary_calculation()
    {
        $this->actingAs($this->admin);

        $salaryData = [
            'teacher_id' => $this->teacher->teacher->id,
            'month' => 2,
            'year' => 2024,
            'basic' => 35000,
            'allowances' => ['hra' => 7000, 'da' => 5250],
            'deductions' => ['pf' => 4200, 'esi' => 612]
            // Note: net_salary not provided, should be calculated automatically
        ];

        $response = $this->postJson('/api/salaries', $salaryData);

        $response->assertStatus(201);
        
        $salary = Salary::latest()->first();
        $expectedNet = 35000 + 7000 + 5250 - 4200 - 612;
        $this->assertEquals($expectedNet, $salary->net_salary);
    }

    /** @test */
    public function test_salary_calculation_with_overrides()
    {
        $overrides = ['basic_salary' => 35000];
        $result = $this->salaryStructure->calculateSalary($overrides);

        $this->assertEquals(35000, $result['basic_salary']);
        $this->assertEquals(7000, $result['allowances']['hra']); // 20% of 35000
        $this->assertEquals(5250, $result['allowances']['da']); // 15% of 35000
    }

    /** @test */
    public function test_statutory_deductions_calculation()
    {
        $basic = 30000;
        $gross = 42500;

        $statutoryDeductions = $this->salaryCalculator->calculateStatutoryDeductions($basic, $gross);

        $this->assertArrayHasKey('provident_fund', $statutoryDeductions);
        $this->assertArrayHasKey('employee_state_insurance', $statutoryDeductions);
        $this->assertArrayHasKey('professional_tax', $statutoryDeductions);
        $this->assertArrayHasKey('income_tax', $statutoryDeductions);

        // Verify PF calculation (12% of basic)
        $this->assertEquals(3600, $statutoryDeductions['provident_fund']);
    }

    /** @test */
    public function test_employer_contributions_calculation()
    {
        $basic = 30000;
        $gross = 42500;

        $contributions = $this->salaryCalculator->calculateEmployerContributions($basic, $gross);

        $this->assertArrayHasKey('employer_pf', $contributions);
        $this->assertArrayHasKey('employer_esi', $contributions);
        $this->assertArrayHasKey('gratuity', $contributions);

        // Verify employer PF (12% of basic)
        $this->assertEquals(3600, $contributions['employer_pf']);
    }

    /** @test */
    public function test_leave_deductions_calculation()
    {
        $year = 2024;
        $month = 1;
        $basicSalary = 30000;

        $leaveDeductions = $this->salaryCalculator->calculateLeaveDeductions(
            $this->teacher, 
            $year, 
            $month, 
            $basicSalary
        );

        $this->assertArrayHasKey('total_working_days', $leaveDeductions);
        $this->assertArrayHasKey('present_days', $leaveDeductions);
        $this->assertArrayHasKey('absent_days', $leaveDeductions);
        $this->assertArrayHasKey('per_day_salary', $leaveDeductions);
        $this->assertArrayHasKey('leave_deduction', $leaveDeductions);

        $this->assertGreaterThan(0, $leaveDeductions['total_working_days']);
        $this->assertGreaterThan(0, $leaveDeductions['per_day_salary']);
    }

    /** @test */
    public function test_monthly_salary_calculation_service()
    {
        // Assign salary structure to teacher
        $this->teacher->teacher->update(['salary_structure_id' => $this->salaryStructure->id]);

        $result = $this->salaryCalculationService->calculateMonthlySalary($this->teacher, 2024, 1);

        $this->assertArrayHasKey('employee', $result);
        $this->assertArrayHasKey('salary_structure', $result);
        $this->assertArrayHasKey('basic_salary', $result);
        $this->assertArrayHasKey('allowances', $result);
        $this->assertArrayHasKey('deductions', $result);
        $this->assertArrayHasKey('net_salary', $result);

        $this->assertEquals($this->teacher->id, $result['employee']->id);
        $this->assertEquals(30000, $result['basic_salary']);
    }

    /** @test */
    public function test_salary_calculation_validation_errors()
    {
        $this->actingAs($this->admin);

        // Test missing required fields
        $response = $this->postJson('/api/salaries', []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['teacher_id', 'month', 'year']);

        // Test invalid teacher_id
        $response = $this->postJson('/api/salaries', [
            'teacher_id' => 999,
            'month' => 1,
            'year' => 2024
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['teacher_id']);

        // Test invalid month
        $response = $this->postJson('/api/salaries', [
            'teacher_id' => $this->teacher->teacher->id,
            'month' => 13,
            'year' => 2024
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['month']);
    }

    /** @test */
    public function test_zero_salary_calculation()
    {
        $result = $this->salaryCalculator->calculateNetSalaryDetailed(0, [], []);

        $this->assertEquals(0, $result['basic_salary']);
        $this->assertEquals(0, $result['total_allowances']);
        $this->assertEquals(0, $result['gross_salary']);
        $this->assertEquals(0, $result['net_salary']);
    }

    /** @test */
    public function test_negative_values_handling()
    {
        // Test that negative values are handled appropriately
        $this->expectException(\Exception::class);
        $this->salaryCalculator->calculateNetSalaryDetailed(-1000, [], []);
    }

    /** @test */
    public function test_large_salary_calculation()
    {
        $basic = 100000;
        $allowances = ['hra' => 20000, 'da' => 15000, 'ta' => 5000];
        $deductions = ['pf' => 12000, 'esi' => 2450];

        $result = $this->salaryCalculator->calculateNetSalaryDetailed($basic, $allowances, $deductions);

        $this->assertEquals(100000, $result['basic_salary']);
        $this->assertEquals(40000, $result['total_allowances']);
        $this->assertEquals(140000, $result['gross_salary']);
        $this->assertLessThan(140000, $result['net_salary']);
    }

    /** @test */
    public function test_unauthorized_salary_access()
    {
        // Test that non-admin users cannot create salaries
        $this->actingAs($this->teacher);

        $response = $this->postJson('/api/salaries', [
            'teacher_id' => $this->teacher->teacher->id,
            'month' => 1,
            'year' => 2024,
            'basic' => 30000
        ]);

        $response->assertStatus(403);
    }
}
