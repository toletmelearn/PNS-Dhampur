<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Helpers\SecurityHelper;

class StoreFeeRequest extends FormRequest
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
            'student_id' => 'required|integer|exists:students,id',
            'fee_type' => [
                'required',
                'string',
                'max:255',
                Rule::in(['tuition', 'admission', 'examination', 'library', 'sports', 'transport', 'hostel', 'other']),
                function ($attribute, $value, $fail) {
                    if (SecurityHelper::containsMaliciousContent($value)) {
                        $fail('The fee type contains potentially malicious content.');
                    }
                }
            ],
            'amount' => 'required|numeric|min:0|max:100000',
            'due_date' => 'required|date|after_or_equal:today',
            'academic_year' => [
                'required',
                'string',
                'max:20',
                'regex:/^\d{4}-\d{4}$/',
                function ($attribute, $value, $fail) {
                    if (SecurityHelper::containsMaliciousContent($value)) {
                        $fail('The academic year contains potentially malicious content.');
                    }
                }
            ],
            'month' => 'nullable|string|max:20',
            'late_fee' => 'nullable|numeric|min:0|max:5000',
            'discount' => 'nullable|numeric|min:0',
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
            'student_id.required' => 'Student selection is required.',
            'fee_type.required' => 'Fee type is required.',
            'amount.required' => 'Fee amount is required.',
            'due_date.required' => 'Due date is required.',
            'academic_year.required' => 'Academic year is required.',
            'academic_year.regex' => 'Academic year must be in format YYYY-YYYY.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'fee_type' => SecurityHelper::sanitizeInput($this->fee_type),
            'remarks' => SecurityHelper::sanitizeInput($this->remarks),
            'academic_year' => SecurityHelper::sanitizeInput($this->academic_year),
        ]);
    }
}