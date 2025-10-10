<?php

namespace App\Traits;

use App\Services\InputSanitizationService;

trait SanitizesInput
{
    /**
     * Sanitize input data using the InputSanitizationService.
     */
    protected function sanitizeInput(mixed $data, array $options = []): mixed
    {
        $sanitizer = app(InputSanitizationService::class);
        return $sanitizer->sanitize($data, $options);
    }

    /**
     * Sanitize input data with field type mappings.
     */
    protected function sanitizeFields(array $data, array $fieldTypes = []): array
    {
        $sanitizer = app(InputSanitizationService::class);
        return $sanitizer->sanitizeFields($data, $fieldTypes);
    }

    /**
     * Sanitize school-specific field types.
     */
    protected function sanitizeSchoolField(string $fieldType, mixed $value): mixed
    {
        $sanitizer = app(InputSanitizationService::class);
        return $sanitizer->sanitizeSchoolField($fieldType, $value);
    }

    /**
     * Sanitize student form data.
     */
    protected function sanitizeStudentData(array $data): array
    {
        $fieldTypes = [
            'name' => 'name',
            'father_name' => 'name',
            'mother_name' => 'name',
            'guardian_name' => 'name',
            'email' => 'email',
            'phone' => 'phone',
            'mobile' => 'phone',
            'emergency_contact' => 'phone',
            'address' => 'text',
            'permanent_address' => 'text',
            'roll_number' => 'alphanumeric',
            'admission_number' => 'alphanumeric',
            'aadhaar' => 'numeric',
            'pan' => 'alphanumeric',
            'remarks' => 'html',
            'medical_conditions' => 'html',
            'notes' => 'html',
            'description' => 'html',
            'city' => 'name',
            'state' => 'name',
            'country' => 'name',
            'pincode' => 'numeric',
            'postal_code' => 'numeric',
        ];

        return $this->sanitizeFields($data, $fieldTypes);
    }

    /**
     * Sanitize teacher form data.
     */
    protected function sanitizeTeacherData(array $data): array
    {
        $fieldTypes = [
            'name' => 'name',
            'first_name' => 'name',
            'last_name' => 'name',
            'email' => 'email',
            'phone' => 'phone',
            'mobile' => 'phone',
            'emergency_contact' => 'phone',
            'address' => 'text',
            'permanent_address' => 'text',
            'employee_id' => 'alphanumeric',
            'qualification' => 'text',
            'experience' => 'text',
            'specialization' => 'text',
            'skills' => 'text',
            'salary' => 'numeric',
            'pan' => 'alphanumeric',
            'aadhaar' => 'numeric',
            'passport' => 'alphanumeric',
            'driving_license' => 'alphanumeric',
            'remarks' => 'html',
            'bio' => 'html',
            'notes' => 'html',
            'description' => 'html',
            'city' => 'name',
            'state' => 'name',
            'country' => 'name',
            'pincode' => 'numeric',
            'postal_code' => 'numeric',
        ];

        return $this->sanitizeFields($data, $fieldTypes);
    }

    /**
     * Sanitize fee form data.
     */
    protected function sanitizeFeeData(array $data): array
    {
        $fieldTypes = [
            'amount' => 'numeric',
            'discount' => 'numeric',
            'discount_amount' => 'numeric',
            'fine' => 'numeric',
            'late_fee' => 'numeric',
            'tax' => 'numeric',
            'tax_amount' => 'numeric',
            'total' => 'numeric',
            'paid_amount' => 'numeric',
            'balance' => 'numeric',
            'description' => 'text',
            'remarks' => 'html',
            'notes' => 'html',
            'reason' => 'text',
            'transaction_id' => 'alphanumeric',
            'receipt_number' => 'alphanumeric',
            'invoice_number' => 'alphanumeric',
            'reference' => 'alphanumeric',
            'payment_method' => 'text',
        ];

        return $this->sanitizeFields($data, $fieldTypes);
    }

    /**
     * Sanitize attendance form data.
     */
    protected function sanitizeAttendanceData(array $data): array
    {
        $fieldTypes = [
            'remarks' => 'html',
            'reason' => 'text',
            'medical_reason' => 'html',
            'notes' => 'html',
            'description' => 'text',
            'location' => 'text',
            'weather_condition' => 'text',
        ];

        return $this->sanitizeFields($data, $fieldTypes);
    }

    /**
     * Sanitize user profile data.
     */
    protected function sanitizeProfileData(array $data): array
    {
        $fieldTypes = [
            'name' => 'name',
            'first_name' => 'name',
            'last_name' => 'name',
            'email' => 'email',
            'phone' => 'phone',
            'mobile' => 'phone',
            'address' => 'text',
            'bio' => 'html',
            'description' => 'html',
            'website' => 'url',
            'social_media' => 'url',
            'city' => 'name',
            'state' => 'name',
            'country' => 'name',
            'pincode' => 'numeric',
            'postal_code' => 'numeric',
        ];

        return $this->sanitizeFields($data, $fieldTypes);
    }

    /**
     * Sanitize settings data.
     */
    protected function sanitizeSettingsData(array $data): array
    {
        $fieldTypes = [
            'school_name' => 'text',
            'school_address' => 'text',
            'school_phone' => 'phone',
            'school_email' => 'email',
            'school_website' => 'url',
            'principal_name' => 'name',
            'vice_principal_name' => 'name',
            'description' => 'html',
            'value' => 'text',
            'setting_value' => 'text',
            'config_value' => 'text',
            'remarks' => 'html',
            'notes' => 'html',
        ];

        return $this->sanitizeFields($data, $fieldTypes);
    }

    /**
     * Sanitize file upload metadata.
     */
    protected function sanitizeFileData(array $data): array
    {
        $sanitizer = app(InputSanitizationService::class);
        return $sanitizer->sanitizeFileUpload($data);
    }

    /**
     * Check if input contains XSS attempts.
     */
    protected function containsXSS(string $input): bool
    {
        $sanitizer = app(InputSanitizationService::class);
        return $sanitizer->containsXSS($input);
    }

    /**
     * Check if input contains SQL injection attempts.
     */
    protected function containsSQLInjection(string $input): bool
    {
        $sanitizer = app(InputSanitizationService::class);
        return $sanitizer->containsSQLInjection($input);
    }

    /**
     * Log suspicious input for security monitoring.
     */
    protected function logSuspiciousInput(string $input, string $type, array $context = []): void
    {
        $sanitizer = app(InputSanitizationService::class);
        $sanitizer->logSuspiciousInput($input, $type, $context);
    }

    /**
     * Sanitize and validate request data with custom rules.
     */
    protected function sanitizeAndValidate(array $data, array $rules = [], array $fieldTypes = []): array
    {
        // First sanitize the data
        $sanitized = $this->sanitizeFields($data, $fieldTypes);

        // Then validate if rules are provided
        if (!empty($rules)) {
            $validator = validator($sanitized, $rules);
            
            if ($validator->fails()) {
                throw new \Illuminate\Validation\ValidationException($validator);
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize data based on context (student, teacher, fee, etc.).
     */
    protected function sanitizeByContext(array $data, string $context): array
    {
        switch ($context) {
            case 'student':
                return $this->sanitizeStudentData($data);
            case 'teacher':
                return $this->sanitizeTeacherData($data);
            case 'fee':
                return $this->sanitizeFeeData($data);
            case 'attendance':
                return $this->sanitizeAttendanceData($data);
            case 'profile':
                return $this->sanitizeProfileData($data);
            case 'settings':
                return $this->sanitizeSettingsData($data);
            case 'file':
                return $this->sanitizeFileData($data);
            default:
                return $this->sanitizeFields($data);
        }
    }

    /**
     * Bulk sanitize multiple contexts of data.
     */
    protected function bulkSanitize(array $datasets): array
    {
        $sanitized = [];
        
        foreach ($datasets as $context => $data) {
            if (is_array($data)) {
                $sanitized[$context] = $this->sanitizeByContext($data, $context);
            } else {
                $sanitized[$context] = $this->sanitizeInput($data);
            }
        }
        
        return $sanitized;
    }

    /**
     * Sanitize with strict mode (removes all HTML and special characters).
     */
    protected function strictSanitize(mixed $data): mixed
    {
        return $this->sanitizeInput($data, ['mode' => 'strict']);
    }

    /**
     * Sanitize allowing safe HTML tags.
     */
    protected function htmlSanitize(mixed $data): mixed
    {
        return $this->sanitizeInput($data, ['mode' => 'html']);
    }

    /**
     * Sanitize as plain text (removes HTML but keeps formatting).
     */
    protected function textSanitize(mixed $data): mixed
    {
        return $this->sanitizeInput($data, ['mode' => 'text']);
    }
}