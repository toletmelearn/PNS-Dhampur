<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryStructure extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'basic_salary',
        'minimum_salary',
        'maximum_salary',
        'increment_percentage',
        'allowances',
        'allowance_rules',
        'deductions',
        'deduction_rules',
        'benefits',
        'benefit_rules',
        'grade_level',
        'experience_required',
        'qualification_required',
        'effective_from',
        'effective_to',
        'status',
        'approved_by',
        'approved_at',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'minimum_salary' => 'decimal:2',
        'maximum_salary' => 'decimal:2',
        'allowances' => 'array',
        'allowance_rules' => 'array',
        'deductions' => 'array',
        'deduction_rules' => 'array',
        'benefits' => 'array',
        'benefit_rules' => 'array',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'approved_at' => 'datetime',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_ARCHIVED = 'archived';

    // Default allowance types
    const ALLOWANCE_TYPES = [
        'hra' => 'House Rent Allowance',
        'da' => 'Dearness Allowance',
        'ta' => 'Transport Allowance',
        'medical' => 'Medical Allowance',
        'special' => 'Special Allowance',
        'overtime' => 'Overtime Allowance',
        'performance' => 'Performance Allowance'
    ];

    // Default deduction types
    const DEDUCTION_TYPES = [
        'pf' => 'Provident Fund',
        'esi' => 'Employee State Insurance',
        'professional_tax' => 'Professional Tax',
        'tds' => 'Tax Deducted at Source',
        'loan' => 'Loan Recovery',
        'advance' => 'Advance Recovery'
    ];

    /**
     * Relationships
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function payrollDeductions(): HasMany
    {
        return $this->hasMany(PayrollDeduction::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeEffective($query, $date = null)
    {
        $date = $date ?: now()->toDateString();
        return $query->where('effective_from', '<=', $date)
                    ->where(function($q) use ($date) {
                        $q->whereNull('effective_to')
                          ->orWhere('effective_to', '>=', $date);
                    });
    }

    public function scopeByGrade($query, $grade)
    {
        return $query->where('grade_level', $grade);
    }

    /**
     * Accessors
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getIsEffectiveAttribute(): bool
    {
        $now = now()->toDateString();
        return $this->effective_from <= $now && 
               ($this->effective_to === null || $this->effective_to >= $now);
    }

    public function getTotalAllowancesAttribute(): float
    {
        if (!$this->allowances) {
            return 0;
        }

        $total = 0;
        foreach ($this->allowances as $allowance) {
            if (isset($allowance['amount'])) {
                $total += $allowance['amount'];
            } elseif (isset($allowance['percentage'])) {
                $total += ($this->basic_salary * $allowance['percentage'] / 100);
            }
        }

        return $total;
    }

    public function getTotalDeductionsAttribute(): float
    {
        if (!$this->deductions) {
            return 0;
        }

        $total = 0;
        foreach ($this->deductions as $deduction) {
            if (isset($deduction['amount'])) {
                $total += $deduction['amount'];
            } elseif (isset($deduction['percentage'])) {
                $total += ($this->basic_salary * $deduction['percentage'] / 100);
            }
        }

        return $total;
    }

    public function getGrossSalaryAttribute(): float
    {
        return $this->basic_salary + $this->total_allowances;
    }

    public function getNetSalaryAttribute(): float
    {
        return $this->gross_salary - $this->total_deductions;
    }

    /**
     * Helper Methods
     */
    public function calculateAllowance(string $type, float $basicSalary = null): float
    {
        $basicSalary = $basicSalary ?: $this->basic_salary;
        
        if (!$this->allowances || !isset($this->allowances[$type])) {
            return 0;
        }

        $allowance = $this->allowances[$type];
        
        if (isset($allowance['amount'])) {
            return $allowance['amount'];
        }
        
        if (isset($allowance['percentage'])) {
            return $basicSalary * $allowance['percentage'] / 100;
        }

        return 0;
    }

    public function calculateDeduction(string $type, float $grossSalary = null): float
    {
        $grossSalary = $grossSalary ?: $this->gross_salary;
        
        if (!$this->deductions || !isset($this->deductions[$type])) {
            return 0;
        }

        $deduction = $this->deductions[$type];
        
        if (isset($deduction['amount'])) {
            return $deduction['amount'];
        }
        
        if (isset($deduction['percentage'])) {
            return $grossSalary * $deduction['percentage'] / 100;
        }

        return 0;
    }

    public function calculateSalary(array $overrides = []): array
    {
        $basicSalary = $overrides['basic_salary'] ?? $this->basic_salary;
        $allowances = [];
        $deductions = [];
        
        // Calculate allowances
        if ($this->allowances) {
            foreach ($this->allowances as $type => $config) {
                $allowances[$type] = $this->calculateAllowance($type, $basicSalary);
            }
        }
        
        // Calculate deductions
        $grossSalary = $basicSalary + array_sum($allowances);
        if ($this->deductions) {
            foreach ($this->deductions as $type => $config) {
                $deductions[$type] = $this->calculateDeduction($type, $grossSalary);
            }
        }
        
        $totalAllowances = array_sum($allowances);
        $totalDeductions = array_sum($deductions);
        $netSalary = $grossSalary - $totalDeductions;
        
        return [
            'basic_salary' => $basicSalary,
            'allowances' => $allowances,
            'total_allowances' => $totalAllowances,
            'gross_salary' => $grossSalary,
            'deductions' => $deductions,
            'total_deductions' => $totalDeductions,
            'net_salary' => $netSalary
        ];
    }

    public function isApplicableFor(User $employee): bool
    {
        // Check if structure is active and effective
        if (!$this->is_active || !$this->is_effective) {
            return false;
        }

        // Add custom logic based on employee attributes
        // This can be extended based on business requirements
        
        return true;
    }

    public function approve(User $approver): bool
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'approved_by' => $approver->id,
            'approved_at' => now()
        ]);

        return true;
    }

    public function archive(): bool
    {
        return $this->update(['status' => self::STATUS_ARCHIVED]);
    }

    public function duplicate(array $overrides = []): self
    {
        $attributes = $this->toArray();
        unset($attributes['id'], $attributes['created_at'], $attributes['updated_at'], $attributes['deleted_at']);
        
        $attributes = array_merge($attributes, $overrides);
        $attributes['status'] = self::STATUS_DRAFT;
        $attributes['approved_by'] = null;
        $attributes['approved_at'] = null;
        
        return self::create($attributes);
    }
}