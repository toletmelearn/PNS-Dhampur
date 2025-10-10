<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Rules\EnhancedFileValidation;
use Carbon\Carbon;

class EnhancedStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && (
            auth()->user()->hasAnyRole(['admin', 'principal', 'teacher']) ||
            auth()->user()->can('manage-students')
        );
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $studentId = $this->route('student') ? $this->route('student')->id : null;
        
        return [
            // Basic Information
            'first_name' => [
                'required',
                'string',
                'min:2',
                'max:50',
                'regex:/^[a-zA-Z\s\-\'\.]+$/',
                function ($attribute, $value, $fail) {
                    if (preg_match('/[<>"\'\&\(\){}]/', $value)) {
                        $fail('The ' . $attribute . ' contains invalid characters.');
                    }
                }
            ],
            'last_name' => [
                'required',
                'string',
                'min:2',
                'max:50',
                'regex:/^[a-zA-Z\s\-\'\.]+$/',
                function ($attribute, $value, $fail) {
                    if (preg_match('/[<>"\'\&\(\){}]/', $value)) {
                        $fail('The ' . $attribute . ' contains invalid characters.');
                    }
                }
            ],
            'middle_name' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^[a-zA-Z\s\-\'\.]*$/',
            ],
            'admission_no' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9\-]+$/',
                Rule::unique('students', 'admission_no')->ignore($studentId),
            ],
            'roll_number' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[A-Z0-9\-]+$/',
            ],
            
            // Personal Information
            'date_of_birth' => [
                'required',
                'date',
                'before:today',
                function ($attribute, $value, $fail) {
                    $dob = Carbon::parse($value);
                    $age = $dob->diffInYears(Carbon::now());
                    
                    if ($age < 3 || $age > 25) {
                        $fail('Student age must be between 3 and 25 years.');
                    }
                }
            ],
            'gender' => 'required|in:male,female,other',
            'blood_group' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'religion' => 'nullable|string|max:50',
            'caste' => 'nullable|string|max:50',
            'category' => 'nullable|in:general,obc,sc,st,ews',
            
            // Contact Information
            'email' => [
                'nullable',
                'email:rfc,dns',
                'max:255',
                Rule::unique('students', 'email')->ignore($studentId),
                function ($attribute, $value, $fail) {
                    if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $fail('The ' . $attribute . ' must be a valid email address.');
                    }
                }
            ],
            'phone' => [
                'nullable',
                'string',
                'regex:/^[6-9]\d{9}$/',
                Rule::unique('students', 'phone')->ignore($studentId),
            ],
            'emergency_contact' => [
                'required',
                'string',
                'regex:/^[6-9]\d{9}$/',
            ],
            
            // Address Information
            'address' => [
                'required',
                'string',
                'min:10',
                'max:500',
                function ($attribute, $value, $fail) {
                    if (preg_match('/[<>"\'\&\(\){}]/', $value)) {
                        $fail('The ' . $attribute . ' contains invalid characters.');
                    }
                }
            ],
            'city' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z\s\-\'\.]+$/',
            ],
            'state' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z\s\-\'\.]+$/',
            ],
            'pincode' => [
                'required',
                'string',
                'regex:/^[1-9][0-9]{5}$/',
            ],
            
            // Academic Information
            'class_id' => 'required|exists:class_models,id',
            'section' => 'nullable|string|max:10|regex:/^[A-Z]$/',
            'academic_year' => [
                'required',
                'string',
                'regex:/^\d{4}-\d{4}$/',
                function ($attribute, $value, $fail) {
                    $years = explode('-', $value);
                    if (count($years) !== 2 || (int)$years[1] !== (int)$years[0] + 1) {
                        $fail('The ' . $attribute . ' must be in format YYYY-YYYY (consecutive years).');
                    }
                    
                    $currentYear = date('Y');
                    if ((int)$years[0] < $currentYear - 2 || (int)$years[0] > $currentYear + 1) {
                        $fail('The ' . $attribute . ' must be within reasonable range.');
                    }
                }
            ],
            'admission_date' => [
                'required',
                'date',
                'before_or_equal:today',
                'after:' . Carbon::now()->subYears(20)->format('Y-m-d'),
            ],
            'status' => 'required|in:active,inactive,transferred,graduated,dropped',
            
            // Parent/Guardian Information
            'father_name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'regex:/^[a-zA-Z\s\-\'\.]+$/',
            ],
            'father_occupation' => 'nullable|string|max:100',
            'father_phone' => [
                'nullable',
                'string',
                'regex:/^[6-9]\d{9}$/',
            ],
            'mother_name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'regex:/^[a-zA-Z\s\-\'\.]+$/',
            ],
            'mother_occupation' => 'nullable|string|max:100',
            'mother_phone' => [
                'nullable',
                'string',
                'regex:/^[6-9]\d{9}$/',
            ],
            'guardian_name' => 'nullable|string|max:100',
            'guardian_relation' => 'nullable|string|max:50',
            'guardian_phone' => [
                'nullable',
                'string',
                'regex:/^[6-9]\d{9}$/',
            ],
            
            // Document Information
            'aadhaar_number' => [
                'nullable',
                'string',
                'regex:/^[2-9]{1}[0-9]{3}[0-9]{4}[0-9]{4}$/',
                Rule::unique('students', 'aadhaar_number')->ignore($studentId),
                function ($attribute, $value, $fail) {
                    if ($value && !$this->validateAadhaarChecksum($value)) {
                        $fail('The ' . $attribute . ' is not valid.');
                    }
                }
            ],
            'birth_certificate_no' => 'nullable|string|max:50',
            'previous_school' => 'nullable|string|max:200',
            'transfer_certificate_no' => 'nullable|string|max:50',
            
            // File Uploads
            'photo' => [
                'nullable',
                'file',
                new EnhancedFileValidation::image(2048), // 2MB max
            ],
            'birth_certificate' => [
                'nullable',
                'file',
                new EnhancedFileValidation::document(5120), // 5MB max
            ],
            'aadhaar_card' => [
                'nullable',
                'file',
                new EnhancedFileValidation::document(5120), // 5MB max
            ],
            'transfer_certificate' => [
                'nullable',
                'file',
                new EnhancedFileValidation::document(5120), // 5MB max
            ],
            
            // Medical Information
            'medical_conditions' => 'nullable|string|max:1000',
            'allergies' => 'nullable|string|max:500',
            'medications' => 'nullable|string|max:500',
            
            // Additional Information
            'transport_required' => 'nullable|boolean',
            'bus_route' => 'nullable|string|max:100',
            'hostel_required' => 'nullable|boolean',
            'scholarship_applicable' => 'nullable|boolean',
            'remarks' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'first_name.regex' => 'First name can only contain letters, spaces, hyphens, apostrophes, and periods.',
            'last_name.required' => 'Last name is required.',
            'last_name.regex' => 'Last name can only contain letters, spaces, hyphens, apostrophes, and periods.',
            'admission_no.required' => 'Admission number is required.',
            'admission_no.unique' => 'This admission number is already taken.',
            'admission_no.regex' => 'Admission number can only contain uppercase letters, numbers, and hyphens.',
            'date_of_birth.required' => 'Date of birth is required.',
            'date_of_birth.before' => 'Date of birth must be before today.',
            'gender.required' => 'Gender is required.',
            'gender.in' => 'Please select a valid gender.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'phone.regex' => 'Phone number must be a valid 10-digit Indian mobile number.',
            'phone.unique' => 'This phone number is already registered.',
            'emergency_contact.required' => 'Emergency contact is required.',
            'emergency_contact.regex' => 'Emergency contact must be a valid 10-digit Indian mobile number.',
            'address.required' => 'Address is required.',
            'address.min' => 'Address must be at least 10 characters long.',
            'city.required' => 'City is required.',
            'state.required' => 'State is required.',
            'pincode.required' => 'Pincode is required.',
            'pincode.regex' => 'Pincode must be a valid 6-digit Indian postal code.',
            'class_id.required' => 'Class is required.',
            'class_id.exists' => 'Selected class does not exist.',
            'academic_year.required' => 'Academic year is required.',
            'admission_date.required' => 'Admission date is required.',
            'father_name.required' => 'Father\'s name is required.',
            'mother_name.required' => 'Mother\'s name is required.',
            'aadhaar_number.regex' => 'Aadhaar number must be a valid 12-digit number.',
            'aadhaar_number.unique' => 'This Aadhaar number is already registered.',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'middle_name' => 'middle name',
            'admission_no' => 'admission number',
            'roll_number' => 'roll number',
            'date_of_birth' => 'date of birth',
            'blood_group' => 'blood group',
            'emergency_contact' => 'emergency contact',
            'class_id' => 'class',
            'academic_year' => 'academic year',
            'admission_date' => 'admission date',
            'father_name' => 'father\'s name',
            'father_occupation' => 'father\'s occupation',
            'father_phone' => 'father\'s phone',
            'mother_name' => 'mother\'s name',
            'mother_occupation' => 'mother\'s occupation',
            'mother_phone' => 'mother\'s phone',
            'guardian_name' => 'guardian\'s name',
            'guardian_relation' => 'guardian\'s relation',
            'guardian_phone' => 'guardian\'s phone',
            'aadhaar_number' => 'Aadhaar number',
            'birth_certificate_no' => 'birth certificate number',
            'previous_school' => 'previous school',
            'transfer_certificate_no' => 'transfer certificate number',
            'medical_conditions' => 'medical conditions',
            'transport_required' => 'transport requirement',
            'bus_route' => 'bus route',
            'hostel_required' => 'hostel requirement',
            'scholarship_applicable' => 'scholarship eligibility',
        ];
    }

    /**
     * Validate Aadhaar number checksum using Verhoeff algorithm.
     */
    private function validateAadhaarChecksum(string $aadhaar): bool
    {
        if (strlen($aadhaar) !== 12 || !ctype_digit($aadhaar)) {
            return false;
        }

        // Verhoeff algorithm implementation
        $multiplication_table = [
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

        $permutation_table = [
            [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
            [1, 5, 7, 6, 2, 8, 3, 0, 9, 4],
            [5, 8, 0, 3, 7, 9, 6, 1, 4, 2],
            [8, 9, 1, 6, 0, 4, 3, 5, 2, 7],
            [9, 4, 5, 3, 1, 2, 6, 8, 7, 0],
            [4, 2, 8, 6, 5, 7, 3, 9, 0, 1],
            [2, 7, 9, 3, 8, 0, 6, 4, 1, 5],
            [7, 0, 4, 6, 9, 1, 3, 2, 5, 8]
        ];

        $check = 0;
        for ($i = 0; $i < 12; $i++) {
            $check = $multiplication_table[$check][$permutation_table[($i + 1) % 8][(int)$aadhaar[$i]]];
        }

        return $check === 0;
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Additional custom validation logic
            $this->validateParentContact($validator);
            $this->validateAcademicConsistency($validator);
        });
    }

    /**
     * Validate that at least one parent contact is provided.
     */
    private function validateParentContact($validator): void
    {
        $fatherPhone = $this->input('father_phone');
        $motherPhone = $this->input('mother_phone');
        $guardianPhone = $this->input('guardian_phone');
        $emergencyContact = $this->input('emergency_contact');

        if (!$fatherPhone && !$motherPhone && !$guardianPhone && !$emergencyContact) {
            $validator->errors()->add('emergency_contact', 'At least one parent/guardian contact number is required.');
        }
    }

    /**
     * Validate academic information consistency.
     */
    private function validateAcademicConsistency($validator): void
    {
        $admissionDate = $this->input('admission_date');
        $academicYear = $this->input('academic_year');

        if ($admissionDate && $academicYear) {
            $admissionYear = date('Y', strtotime($admissionDate));
            $academicYearStart = explode('-', $academicYear)[0];

            if (abs($admissionYear - $academicYearStart) > 1) {
                $validator->errors()->add('academic_year', 'Academic year should be consistent with admission date.');
            }
        }
    }
}