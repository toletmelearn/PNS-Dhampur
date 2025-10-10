<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use App\Http\Traits\InputSanitizationTrait;
use App\Http\Traits\EmailValidationTrait;
use App\Http\Traits\CommonValidationTrait;

abstract class EnhancedBaseRequest extends FormRequest
{
    use InputSanitizationTrait, EmailValidationTrait, CommonValidationTrait;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Override in child classes as needed
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    abstract public function rules();

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'required' => 'The :attribute field is required.',
            'string' => 'The :attribute must be a string.',
            'email' => 'The :attribute must be a valid email address.',
            'unique' => 'The :attribute has already been taken.',
            'min' => 'The :attribute must be at least :min characters.',
            'max' => 'The :attribute may not be greater than :max characters.',
            'numeric' => 'The :attribute must be a number.',
            'integer' => 'The :attribute must be an integer.',
            'date' => 'The :attribute is not a valid date.',
            'file' => 'The :attribute must be a file.',
            'image' => 'The :attribute must be an image.',
            'mimes' => 'The :attribute must be a file of type: :values.',
            'max_file_size' => 'The :attribute may not be greater than :max kilobytes.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'email' => 'email address',
            'phone' => 'phone number',
            'mobile' => 'mobile number',
            'dob' => 'date of birth',
            'password' => 'password',
            'password_confirmation' => 'password confirmation',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // Get sanitization rules for this request
        $sanitizationRules = $this->getSanitizationRules();
        
        // Sanitize input before validation
        $sanitizedInput = $this->sanitizeRequestInput($this->all(), $sanitizationRules);
        
        // Replace request data with sanitized data
        $this->replace($sanitizedInput);
        
        // Log sanitization activity for security monitoring
        $this->logSanitizationActivity();
    }

    /**
     * Get sanitization rules for this request.
     * Override in child classes to provide specific rules.
     *
     * @return array
     */
    protected function getSanitizationRules(): array
    {
        return [
            'name' => 'string',
            'email' => 'email',
            'phone' => 'phone',
            'mobile' => 'phone',
            'description' => 'string',
            'address' => 'string',
            'search' => 'search',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            // Additional custom validation logic
            $this->performSecurityValidation($validator);
            $this->performBusinessLogicValidation($validator);
        });
    }

    /**
     * Perform security-related validation
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    protected function performSecurityValidation(Validator $validator)
    {
        // Check for suspicious patterns in input
        $suspiciousPatterns = [
            'sql_injection' => '/(\bUNION\b|\bSELECT\b|\bINSERT\b|\bUPDATE\b|\bDELETE\b|\bDROP\b)/i',
            'xss' => '/<script[^>]*>.*?<\/script>/i',
            'path_traversal' => '/\.\.[\/\\\\]/',
            'command_injection' => '/[;&|`$(){}[\]]/i',
        ];

        foreach ($this->all() as $field => $value) {
            if (is_string($value)) {
                foreach ($suspiciousPatterns as $type => $pattern) {
                    if (preg_match($pattern, $value)) {
                        $validator->errors()->add($field, "The {$field} field contains suspicious content.");
                        
                        // Log security incident
                        Log::warning('Suspicious input detected in form request', [
                            'type' => $type,
                            'field' => $field,
                            'pattern' => $pattern,
                            'value_sample' => substr($value, 0, 100),
                            'request_class' => get_class($this),
                            'ip' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                            'user_id' => auth()->id(),
                        ]);
                        break;
                    }
                }
            }
        }
    }

    /**
     * Perform business logic validation
     * Override in child classes for specific business rules
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    protected function performBusinessLogicValidation(Validator $validator)
    {
        // Override in child classes
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        // Log validation failures for security monitoring
        Log::info('Form validation failed', [
            'request_class' => get_class($this),
            'errors' => $validator->errors()->toArray(),
            'input' => $this->except(['password', 'password_confirmation']),
            'ip' => request()->ip(),
            'user_id' => auth()->id(),
        ]);

        // For API requests, return JSON response
        if ($this->expectsJson()) {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $validator->errors()
                ], 422)
            );
        }

        // For web requests, use default behavior
        parent::failedValidation($validator);
    }

    /**
     * Log sanitization activity for security monitoring
     *
     * @return void
     */
    protected function logSanitizationActivity()
    {
        // Only log if there were changes made during sanitization
        $originalInput = request()->all();
        $currentInput = $this->all();
        
        if ($originalInput !== $currentInput) {
            Log::info('Input sanitization performed', [
                'request_class' => get_class($this),
                'changes_detected' => true,
                'ip' => request()->ip(),
                'user_id' => auth()->id(),
            ]);
        }
    }

    /**
     * Get common file validation rules
     *
     * @param array $allowedMimes
     * @param int $maxSize
     * @return array
     */
    protected function getFileValidationRules(array $allowedMimes = ['pdf', 'jpg', 'jpeg', 'png'], int $maxSize = 2048): array
    {
        return [
            'required',
            'file',
            'mimes:' . implode(',', $allowedMimes),
            'max:' . $maxSize,
        ];
    }

    /**
     * Get common image validation rules
     *
     * @param int $maxSize
     * @return array
     */
    protected function getImageValidationRules(int $maxSize = 2048): array
    {
        return [
            'required',
            'image',
            'mimes:jpeg,jpg,png,gif',
            'max:' . $maxSize,
            'dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000',
        ];
    }
}