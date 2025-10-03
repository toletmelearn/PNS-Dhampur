<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollDeduction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'employee_name',
        'employee_code',
        'payroll_year',
        'payroll_month',
        'payroll_date',
        'payroll_cycle',
        'deduction_type',
        'deduction_code',
        'deduction_name',
        'description',
        'gross_salary',
        'basic_salary',
        'deduction_rate',
        'deduction_amount',
        'employer_contribution',
        'calculation_method',
        'pan_number',
        'pf_number',
        'esi_number',
        'tds_amount',
        'pf_employee',
        'pf_employer',
        'esi_employee',
        'esi_employer',
        'professional_tax',
        'loan_id',
        'loan_balance',
        'installment_number',
        'total_installments',
        'installment_amount',
        'policy_number',
        'premium_amount',
        'beneficiary',
        'is_recovery',
        'recovery_reason',
        'recovery_balance',
        'status',
        'approved_by',
        'approved_at',
        'approval_remarks',
        'is_processed',
        'processed_at',
        'processed_by',
        'transaction_reference',
        'effective_from',
        'effective_to',
        'is_recurring',
        'frequency_months',
        'challan_number',
        'challan_date',
        'return_filed',
        'compliance_data',
        'is_adjustment',
        'adjustment_reason',
        'adjustment_amount',
        'original_deduction_id',
        'calculation_details',
        'remarks',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'payroll_date' => 'date',
        'gross_salary' => 'decimal:2',
        'basic_salary' => 'decimal:2',
        'deduction_rate' => 'decimal:2',
        'deduction_amount' => 'decimal:2',
        'employer_contribution' => 'decimal:2',
        'tds_amount' => 'decimal:2',
        'pf_employee' => 'decimal:2',
        'pf_employer' => 'decimal:2',
        'esi_employee' => 'decimal:2',
        'esi_employer' => 'decimal:2',
        'professional_tax' => 'decimal:2',
        'loan_balance' => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'premium_amount' => 'decimal:2',
        'recovery_balance' => 'decimal:2',
        'adjustment_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'challan_date' => 'date',
        'compliance_data' => 'array',
        'calculation_details' => 'array',
        'is_recovery' => 'boolean',
        'is_processed' => 'boolean',
        'is_recurring' => 'boolean',
        'is_adjustment' => 'boolean'
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_PROCESSED = 'processed';
    const STATUS_CANCELLED = 'cancelled';

    // Deduction type constants
    const TYPE_STATUTORY = 'statutory';
    const TYPE_VOLUNTARY = 'voluntary';
    const TYPE_DISCIPLINARY = 'disciplinary';
    const TYPE_ADVANCE = 'advance';
    const TYPE_LOAN = 'loan';
    const TYPE_OTHER = 'other';

    // Calculation method constants
    const METHOD_PERCENTAGE = 'percentage';
    const METHOD_FIXED_AMOUNT = 'fixed_amount';
    const METHOD_SLAB_BASED = 'slab_based';
    const METHOD_MANUAL = 'manual';

    // Common deduction codes
    const DEDUCTION_CODES = [
        'PF' => 'Provident Fund',
        'ESI' => 'Employee State Insurance',
        'PT' => 'Professional Tax',
        'TDS' => 'Tax Deducted at Source',
        'LIC' => 'Life Insurance Premium',
        'MEDICAL' => 'Medical Insurance',
        'LOAN' => 'Loan Recovery',
        'ADVANCE' => 'Advance Recovery',
        'FINE' => 'Fine/Penalty'
    ];

    /**
     * Relationships
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function originalDeduction(): BelongsTo
    {
        return $this->belongsTo(PayrollDeduction::class, 'original_deduction_id');
    }

    /**
     * Scopes
     */
    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeForPayrollPeriod($query, $year, $month)
    {
        return $query->where('payroll_year', $year)->where('payroll_month', $month);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('deduction_type', $type);
    }

    public function scopeByCode($query, $code)
    {
        return $query->where('deduction_code', $code);
    }

    public function scopeStatutory($query)
    {
        return $query->where('deduction_type', self::TYPE_STATUTORY);
    }

    public function scopeVoluntary($query)
    {
        return $query->where('deduction_type', self::TYPE_VOLUNTARY);
    }

    public function scopeProcessed($query)
    {
        return $query->where('is_processed', true);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    /**
     * Accessors
     */
    public function getPayrollPeriodAttribute(): string
    {
        return sprintf('%04d-%02d', $this->payroll_year, $this->payroll_month);
    }

    public function getIsStatutoryAttribute(): bool
    {
        return $this->deduction_type === self::TYPE_STATUTORY;
    }

    public function getIsVoluntaryAttribute(): bool
    {
        return $this->deduction_type === self::TYPE_VOLUNTARY;
    }

    public function getTotalDeductionAttribute(): float
    {
        return $this->deduction_amount + $this->employer_contribution;
    }

    /**
     * Helper Methods
     */
    public static function calculatePF(float $basicSalary, float $rate = 12.0): array
    {
        $employeeContribution = $basicSalary * $rate / 100;
        $employerContribution = $basicSalary * $rate / 100;
        
        return [
            'employee' => round($employeeContribution, 2),
            'employer' => round($employerContribution, 2),
            'total' => round($employeeContribution + $employerContribution, 2)
        ];
    }

    public static function calculateESI(float $grossSalary, float $employeeRate = 0.75, float $employerRate = 3.25): array
    {
        // ESI is applicable only if gross salary is <= 25000
        if ($grossSalary > 25000) {
            return ['employee' => 0, 'employer' => 0, 'total' => 0];
        }

        $employeeContribution = $grossSalary * $employeeRate / 100;
        $employerContribution = $grossSalary * $employerRate / 100;
        
        return [
            'employee' => round($employeeContribution, 2),
            'employer' => round($employerContribution, 2),
            'total' => round($employeeContribution + $employerContribution, 2)
        ];
    }

    public static function calculateProfessionalTax(float $grossSalary): float
    {
        // Professional tax slabs (example for Maharashtra)
        if ($grossSalary <= 15000) {
            return 0;
        } elseif ($grossSalary <= 25000) {
            return 175;
        } else {
            return 200;
        }
    }

    public static function calculateTDS(float $grossSalary, float $annualSalary, array $exemptions = []): float
    {
        // Simplified TDS calculation
        $taxableIncome = $annualSalary - array_sum($exemptions);
        
        if ($taxableIncome <= 250000) {
            return 0;
        } elseif ($taxableIncome <= 500000) {
            $tax = ($taxableIncome - 250000) * 0.05;
        } elseif ($taxableIncome <= 1000000) {
            $tax = 12500 + ($taxableIncome - 500000) * 0.20;
        } else {
            $tax = 112500 + ($taxableIncome - 1000000) * 0.30;
        }
        
        return round($tax / 12, 2); // Monthly TDS
    }

    public function approve(User $approver, string $remarks = null): bool
    {
        return $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'approval_remarks' => $remarks
        ]);
    }

    public function process(User $processor, string $transactionRef = null): bool
    {
        return $this->update([
            'status' => self::STATUS_PROCESSED,
            'is_processed' => true,
            'processed_by' => $processor->id,
            'processed_at' => now(),
            'transaction_reference' => $transactionRef
        ]);
    }

    public function cancel(string $reason = null): bool
    {
        return $this->update([
            'status' => self::STATUS_CANCELLED,
            'remarks' => $reason
        ]);
    }

    public function createAdjustment(float $adjustmentAmount, string $reason): self
    {
        return self::create([
            'employee_id' => $this->employee_id,
            'employee_name' => $this->employee_name,
            'employee_code' => $this->employee_code,
            'payroll_year' => $this->payroll_year,
            'payroll_month' => $this->payroll_month,
            'payroll_date' => $this->payroll_date,
            'deduction_type' => $this->deduction_type,
            'deduction_code' => $this->deduction_code,
            'deduction_name' => $this->deduction_name . ' (Adjustment)',
            'deduction_amount' => $adjustmentAmount,
            'calculation_method' => self::METHOD_MANUAL,
            'is_adjustment' => true,
            'adjustment_reason' => $reason,
            'original_deduction_id' => $this->id,
            'status' => self::STATUS_PENDING,
            'created_by' => auth()->id()
        ]);
    }

    public function getNextRecurringDate(): ?string
    {
        if (!$this->is_recurring) {
            return null;
        }

        $nextMonth = $this->payroll_month + $this->frequency_months;
        $nextYear = $this->payroll_year;

        if ($nextMonth > 12) {
            $nextYear += floor(($nextMonth - 1) / 12);
            $nextMonth = (($nextMonth - 1) % 12) + 1;
        }

        return sprintf('%04d-%02d', $nextYear, $nextMonth);
    }
}