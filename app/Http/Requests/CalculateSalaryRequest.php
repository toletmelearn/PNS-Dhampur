<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;
use App\Helpers\SecurityHelper;

class CalculateSalaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->hasRole('admin') || 
               auth()->user()->hasRole('principal') || 
               auth()->user()->hasRole('accountant') ||
               auth()->user()->hasRole('teacher'));
    }

    public function rules(): array
    {
        return [
            'month' => [
                'required',
                'date_format:Y-m',
                function ($attribute, $value, $fail) {
                    $selectedMonth = Carbon::createFromFormat('Y-m', $value);
                    $currentMonth = Carbon::now()->startOfMonth();
                    
                    // Allow current and previous months only
                    $previousMonth = $currentMonth->copy()->subMonth();
                    
                    if ($selectedMonth->greaterThan($currentMonth)) {
                        $fail('Cannot calculate salary for future months.');
                    }
                    
                    // Allow up to 6 months in the past for salary calculations
                    $sixMonthsAgo = $currentMonth->copy()->subMonths(6);
                    if ($selectedMonth->lessThan($sixMonthsAgo)) {
                        $fail('Cannot calculate salary for periods older than 6 months.');
                    }
                }
            ],
            'include_bonus' => 'nullable|boolean',
            'include_deductions' => 'nullable|boolean',
            'custom_bonus' => 'nullable|numeric|min:0|max:20000',
            'custom_deduction' => 'nullable|numeric|min:0|max:20000',
        ];
    }

    public function messages(): array
    {
        return [
            'month.required' => 'Salary calculation month is required.',
            'month.date_format' => 'Month must be in format YYYY-MM.',
            'custom_bonus.max' => 'Custom bonus cannot exceed ₹20,000.',
            'custom_deduction.max' => 'Custom deduction cannot exceed ₹20,000.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'month' => SecurityHelper::sanitizeInput($this->month),
            'include_bonus' => filter_var($this->include_bonus, FILTER_VALIDATE_BOOLEAN),
            'include_deductions' => filter_var($this->include_deductions, FILTER_VALIDATE_BOOLEAN),
        ]);
    }
}