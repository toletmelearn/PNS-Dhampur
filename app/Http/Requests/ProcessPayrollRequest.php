<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Helpers\SecurityHelper;
use Carbon\Carbon;

class ProcessPayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->hasRole('admin') || 
               auth()->user()->hasRole('principal') || 
               auth()->user()->hasRole('accountant'));
    }

    public function rules(): array
    {
        return [
            'month' => [
                'required',
                'date_format:Y-m',
                function ($attribute, $value, $fail) {
                    // Ensure month is not in the future
                    $selectedMonth = Carbon::createFromFormat('Y-m', $value);
                    $currentMonth = Carbon::now()->startOfMonth();
                    
                    if ($selectedMonth->greaterThan($currentMonth)) {
                        $fail('Cannot process payroll for future months.');
                    }
                    
                    // Ensure month is not too far in the past (max 3 months)
                    $threeMonthsAgo = $currentMonth->copy()->subMonths(3);
                    if ($selectedMonth->lessThan($threeMonthsAgo)) {
                        $fail('Cannot process payroll for periods older than 3 months.');
                    }
                }
            ],
            'employee_ids' => [
                'nullable',
                'array',
                function ($attribute, $value, $fail) {
                    if ($value && count($value) > 100) {
                        $fail('Cannot process more than 100 employees at once.');
                    }
                }
            ],
            'employee_ids.*' => [
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    // Additional validation can be added here for employee status
                }
            ],
            'bonus_amount' => 'nullable|numeric|min:0|max:50000',
            'deduction_amount' => 'nullable|numeric|min:0|max:50000',
            'remarks' => [
                'nullable',
                'string',
                'max:1000',
                function ($attribute, $value, $fail) {
                    if ($value && SecurityHelper::containsMaliciousContent($value)) {
                        $fail('The remarks contain potentially malicious content.');
                    }
                }
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'month.required' => 'Payroll month is required.',
            'month.date_format' => 'Month must be in format YYYY-MM.',
            'employee_ids.array' => 'Employee selection must be an array.',
            'employee_ids.*.exists' => 'One or more selected employees do not exist.',
            'bonus_amount.max' => 'Bonus amount cannot exceed ₹50,000.',
            'deduction_amount.max' => 'Deduction amount cannot exceed ₹50,000.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'remarks' => SecurityHelper::sanitizeInput($this->remarks),
            'month' => SecurityHelper::sanitizeInput($this->month),
        ]);
    }
}