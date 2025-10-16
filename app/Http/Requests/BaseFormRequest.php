<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

abstract class BaseFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Override in child classes as needed
    }

    /**
     * Get the validation rules that apply to the request.
     */
    abstract public function rules(): array;

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'required' => 'The :attribute field is required.',
            'string' => 'The :attribute must be a string.',
            'email' => 'The :attribute must be a valid email address.',
            'unique' => 'The :attribute has already been taken.',
            'min' => 'The :attribute must be at least :min characters.',
            'max' => 'The :attribute may not be greater than :max characters.',
            'confirmed' => 'The :attribute confirmation does not match.',
            'numeric' => 'The :attribute must be a number.',
            'integer' => 'The :attribute must be an integer.',
            'date' => 'The :attribute is not a valid date.',
            'image' => 'The :attribute must be an image.',
            'mimes' => 'The :attribute must be a file of type: :values.',
            'max_file_size' => 'The :attribute may not be greater than :max kilobytes.',
            'regex' => 'The :attribute format is invalid.',
            'in' => 'The selected :attribute is invalid.',
            'exists' => 'The selected :attribute is invalid.',
            'boolean' => 'The :attribute field must be true or false.',
            'array' => 'The :attribute must be an array.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'email' => 'email address',
            'phone' => 'phone number',
            'date_of_birth' => 'date of birth',
            'admission_date' => 'admission date',
            'parent_name' => 'parent name',
            'parent_phone' => 'parent phone',
            'parent_email' => 'parent email',
            'class_id' => 'class',
            'section_id' => 'section',
            'roll_number' => 'roll number',
            'student_id' => 'student ID',
            'teacher_id' => 'teacher ID',
            'subject_id' => 'subject',
            'exam_id' => 'exam',
            'marks' => 'marks',
            'attendance_date' => 'attendance date',
            'fee_amount' => 'fee amount',
            'payment_date' => 'payment date',
            'salary_amount' => 'salary amount',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        // Log validation failure for security monitoring
        Log::warning('Form validation failed', [
            'url' => $this->fullUrl(),
            'method' => $this->method(),
            'user_id' => Auth::id(),
            'ip' => $this->ip(),
            'user_agent' => $this->userAgent(),
            'errors' => $validator->errors()->toArray(),
            'input' => $this->getSafeInput(),
            'timestamp' => now(),
        ]);

        // Return JSON response for AJAX requests
        if ($this->expectsJson()) {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $validator->errors(),
                    'status_code' => 422,
                    'timestamp' => now()->toISOString(),
                ], 422)
            );
        }

        // Default Laravel behavior for non-AJAX requests
        parent::failedValidation($validator);
    }

    /**
     * Get safe input data (excluding sensitive fields) for logging
     */
    protected function getSafeInput(): array
    {
        $input = $this->all();
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'current_password',
            'new_password',
            '_token',
            'api_token',
            'access_token',
            'refresh_token',
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($input[$field])) {
                $input[$field] = '[REDACTED]';
            }
        }

        return $input;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Sanitize input data
        $this->sanitizeInput();
        
        // Trim whitespace from string inputs
        $this->trimStrings();
        
        // Convert empty strings to null for optional fields
        $this->convertEmptyStringsToNull();
    }

    /**
     * Sanitize input data to prevent XSS and other attacks
     */
    protected function sanitizeInput(): void
    {
        $input = $this->all();
        $sanitized = [];

        foreach ($input as $key => $value) {
            if (is_string($value)) {
                // Basic XSS protection
                $sanitized[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        $this->replace($sanitized);
    }

    /**
     * Recursively sanitize array values
     */
    protected function sanitizeArray(array $array): array
    {
        $sanitized = [];

        foreach ($array as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Trim whitespace from string inputs
     */
    protected function trimStrings(): void
    {
        $input = $this->all();
        $trimmed = [];

        foreach ($input as $key => $value) {
            if (is_string($value)) {
                $trimmed[$key] = trim($value);
            } elseif (is_array($value)) {
                $trimmed[$key] = $this->trimArrayStrings($value);
            } else {
                $trimmed[$key] = $value;
            }
        }

        $this->replace($trimmed);
    }

    /**
     * Recursively trim strings in arrays
     */
    protected function trimArrayStrings(array $array): array
    {
        $trimmed = [];

        foreach ($array as $key => $value) {
            if (is_string($value)) {
                $trimmed[$key] = trim($value);
            } elseif (is_array($value)) {
                $trimmed[$key] = $this->trimArrayStrings($value);
            } else {
                $trimmed[$key] = $value;
            }
        }

        return $trimmed;
    }

    /**
     * Convert empty strings to null for optional fields
     */
    protected function convertEmptyStringsToNull(): void
    {
        $input = $this->all();
        $converted = [];

        foreach ($input as $key => $value) {
            if (is_string($value) && $value === '') {
                $converted[$key] = null;
            } elseif (is_array($value)) {
                $converted[$key] = $this->convertEmptyStringsInArray($value);
            } else {
                $converted[$key] = $value;
            }
        }

        $this->replace($converted);
    }

    /**
     * Recursively convert empty strings to null in arrays
     */
    protected function convertEmptyStringsInArray(array $array): array
    {
        $converted = [];

        foreach ($array as $key => $value) {
            if (is_string($value) && $value === '') {
                $converted[$key] = null;
            } elseif (is_array($value)) {
                $converted[$key] = $this->convertEmptyStringsInArray($value);
            } else {
                $converted[$key] = $value;
            }
        }

        return $converted;
    }

    /**
     * Get common validation rules for reuse
     */
    protected function getCommonRules(): array
    {
        return [
            'name_rules' => ['required', 'string', 'min:2', 'max:100', 'regex:/^[a-zA-Z\s]+$/'],
            'email_rules' => ['required', 'email', 'max:255'],
            'phone_rules' => ['required', 'string', 'regex:/^[0-9]{10}$/', 'min:10', 'max:15'],
            'date_rules' => ['required', 'date', 'before_or_equal:today'],
            'optional_date_rules' => ['nullable', 'date'],
            'password_rules' => ['required', 'string', 'min:8', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/'],
            'image_rules' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'document_rules' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'numeric_rules' => ['required', 'numeric', 'min:0'],
            'integer_rules' => ['required', 'integer', 'min:0'],
            'boolean_rules' => ['required', 'boolean'],
            'array_rules' => ['required', 'array', 'min:1'],
        ];
    }
}