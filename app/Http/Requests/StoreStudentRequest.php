<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('principal') || auth()->user()->hasRole('teacher'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $maxDocumentSize = config('fileupload.max_file_sizes.document', 10240);
        $documentMimes = config('fileupload.allowed_file_types.document.mimes', 'pdf,jpg,jpeg,png,doc,docx');
        
        return [
            // Basic Information
            'first_name' => 'required|string|max:100|regex:/^[a-zA-Z\s]+$/',
            'last_name' => 'required|string|max:100|regex:/^[a-zA-Z\s]+$/',
            'admission_no' => 'nullable|string|unique:students,admission_no|max:20',
            'father_name' => 'nullable|string|max:255|regex:/^[a-zA-Z\s]+$/',
            'mother_name' => 'nullable|string|max:255|regex:/^[a-zA-Z\s]+$/',
            
            // Date of Birth with Age Validation (minimum 3 years, maximum 25 years for school students)
            'date_of_birth' => [
                'required',
                'date',
                'before:today',
                'after:' . now()->subYears(25)->format('Y-m-d'),
                'before:' . now()->subYears(3)->format('Y-m-d')
            ],
            
            'gender' => 'nullable|in:male,female,other',
            
            // Aadhaar with Checksum Validation
            'aadhaar' => [
                'nullable',
                'string',
                'unique:students,aadhaar',
                'regex:/^[0-9]{12}$/',
                function ($attribute, $value, $fail) {
                    if ($value && !$this->validateAadhaarChecksum($value)) {
                        $fail('The Aadhaar number is invalid. Please check the checksum.');
                    }
                }
            ],
            
            'class' => 'nullable|integer|exists:class_models,id',
            'roll_number' => 'nullable|string|max:20',
            'contact_number' => 'nullable|string|regex:/^[\+]?[0-9\s\-\(\)]{10,15}$/',
            'email' => 'nullable|email|unique:students,email|max:255',
            'address' => 'nullable|string|max:500',
            'status' => ['nullable', Rule::in(['active','inactive','left','alumni'])],
            'meta' => 'nullable|array',
            
            // File validation
            'birth_cert' => "nullable|file|mimes:{$documentMimes}|max:{$maxDocumentSize}",
            'aadhaar_file' => "nullable|file|mimes:{$documentMimes}|max:{$maxDocumentSize}",
            'other_docs.*' => "nullable|file|mimes:{$documentMimes}|max:{$maxDocumentSize}",
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'first_name.regex' => 'First name should only contain letters and spaces.',
            'last_name.required' => 'Last name is required.',
            'last_name.regex' => 'Last name should only contain letters and spaces.',
            'admission_no.unique' => 'This admission number is already taken.',
            'date_of_birth.required' => 'Date of birth is required.',
            'date_of_birth.before' => 'Date of birth cannot be in the future.',
            'date_of_birth.after' => 'Student must be younger than 25 years.',
            'aadhaar.regex' => 'Aadhaar number must be exactly 12 digits.',
            'aadhaar.unique' => 'This Aadhaar number is already registered.',
            'class.exists' => 'Selected class does not exist.',
            'contact_number.regex' => 'Please enter a valid contact number.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'birth_cert.mimes' => 'Birth certificate must be a valid document file.',
            'birth_cert.max' => 'Birth certificate file size cannot exceed the maximum limit.',
            'aadhaar_file.mimes' => 'Aadhaar document must be a valid document file.',
            'aadhaar_file.max' => 'Aadhaar document file size cannot exceed the maximum limit.',
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'admission_no' => 'admission number',
            'father_name' => 'father\'s name',
            'mother_name' => 'mother\'s name',
            'date_of_birth' => 'date of birth',
            'contact_number' => 'contact number',
            'birth_cert' => 'birth certificate',
            'aadhaar_file' => 'Aadhaar document',
        ];
    }

    /**
     * Validate Aadhaar number using Verhoeff checksum algorithm.
     * 
     * @param string $aadhaar
     * @return bool
     */
    private function validateAadhaarChecksum(string $aadhaar): bool
    {
        if (strlen($aadhaar) !== 12 || !ctype_digit($aadhaar)) {
            return false;
        }

        // Verhoeff algorithm multiplication table
        $multiplicationTable = [
            [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
            [1, 2, 3, 4, 0, 6, 7, 8, 9, 5],
            [2, 3, 4, 0, 1, 7, 8, 9, 5, 6],
            [3, 4, 0, 1, 2, 8, 9, 5, 6, 7],
            [4, 0, 1, 2, 3, 9, 5, 6, 7, 8],
            [5, 9, 8, 7, 6, 0, 4, 3, 2, 1],
            [6, 5, 9, 8, 7, 1, 0, 4, 3, 2],
            [7, 6, 5, 9, 8, 2, 1, 0, 4, 3],
            [8, 7, 6, 5, 9, 3, 2, 1, 0, 4],
            [9, 8, 7, 6, 5, 4, 3, 2, 1, 0]
        ];

        // Permutation table
        $permutationTable = [
            [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
            [1, 5, 7, 6, 2, 8, 3, 0, 9, 4],
            [5, 8, 0, 3, 7, 9, 6, 1, 4, 2],
            [8, 9, 1, 6, 0, 4, 3, 5, 2, 7],
            [9, 4, 5, 3, 1, 2, 6, 8, 7, 0],
            [4, 2, 8, 6, 5, 7, 3, 9, 0, 1],
            [2, 7, 9, 3, 8, 0, 6, 4, 1, 5],
            [7, 0, 4, 6, 9, 1, 3, 2, 5, 8]
        ];

        $checksum = 0;
        $aadhaarArray = str_split(strrev($aadhaar));

        for ($i = 0; $i < 12; $i++) {
            $checksum = $multiplicationTable[$checksum][$permutationTable[($i % 8)][(int)$aadhaarArray[$i]]];
        }

        return $checksum === 0;
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Additional age validation logic
            if ($this->date_of_birth) {
                $age = now()->diffInYears($this->date_of_birth);
                
                if ($age < 3) {
                    $validator->errors()->add('date_of_birth', 'Student must be at least 3 years old.');
                }
                
                if ($age > 25) {
                    $validator->errors()->add('date_of_birth', 'Student cannot be older than 25 years.');
                }
            }
        });
    }
}