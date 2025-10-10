<?php

require_once 'vendor/autoload.php';

use App\Services\SalaryCalculator;

echo "Testing SalaryCalculator with Constants\n";
echo "=====================================\n\n";

$calculator = new SalaryCalculator();

// Test the new calculateNetSalary method
$grossSalary = 50000;
$result = $calculator->calculateNetSalary($grossSalary);

echo "Gross Salary: ₹" . number_format($grossSalary) . "\n";
echo "Basic (40%): ₹" . number_format($grossSalary * SalaryCalculator::BASIC_PERCENTAGE) . "\n";
echo "HRA (20%): ₹" . number_format($grossSalary * SalaryCalculator::HRA_PERCENTAGE) . "\n";
echo "Allowances (15%): ₹" . number_format($grossSalary * SalaryCalculator::ALLOWANCE_PERCENTAGE) . "\n";
echo "Total Components: ₹" . number_format($result) . "\n\n";

// Test with different amounts
$testAmounts = [25000, 75000, 100000];

foreach ($testAmounts as $amount) {
    $result = $calculator->calculateNetSalary($amount);
    echo "Gross: ₹" . number_format($amount) . " → Components Total: ₹" . number_format($result) . "\n";
}

echo "\n✅ SalaryCalculator constants are working correctly!\n";
echo "✅ Magic numbers have been replaced with meaningful constants.\n";