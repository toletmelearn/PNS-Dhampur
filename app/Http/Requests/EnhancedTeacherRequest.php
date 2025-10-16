<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Rules\EnhancedFileValidation;
use App\Rules\PasswordComplexity;
use Carbon\Carbon;

class EnhancedTeacherRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && (
            auth()->user()->hasAnyRole(['admin', 'principal']) ||
            auth()->user()->can('manage-teachers')
        );
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $teacherId = $this->route('teacher') ? $this->route('teacher')->id : null;
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');
        
        return [
            // Basic Information
            'name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'regex:/^[a-zA-Z\s\-\'\.]+$/',
                function ($attribute, $value, $fail) {
                    if (preg_match('/[<>"\'\&\(\){}]/', $value)) {
                        $fail('The ' . $attribute . ' contains invalid characters.');
                    }
                }
            ],
            'employee_id' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9\-]+$/',
                Rule::unique('teachers', 'employee_id')->ignore($teacherId),
            ],
            
            // Contact Information
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
                Rule::unique('users', 'email')->ignore($teacherId ? $this->getTeacherUserId($teacherId) : null),
                function ($attribute, $value, $fail) {
                    // Check for suspicious patterns
                    $suspiciousPatterns = [
                        '/script/i',
                        '/javascript/i',
                        '/vbscript/i',
                        '/onload/i',
                        '/onerror/i',
                        '/<.*>/i',
                        '/union.*select/i',
                        '/drop.*table/i',
                    ];
                    
                    foreach ($suspiciousPatterns as $pattern) {
                        if (preg_match($pattern, $value)) {
                            $fail('The ' . $attribute . ' contains invalid content.');
                            break;
                        }
                    }
                }
            ],
            'phone' => [
                'required',
                'string',
                'regex:/^[6-9]\d{9}$/',
                Rule::unique('teachers', 'phone')->ignore($teacherId),
            ],
            'emergency_contact' => [
                'required',
                'string',
                'regex:/^[6-9]\d{9}$/',
            ],
            
            // Authentication
            'password' => [
                $isUpdate ? 'nullable' : 'required',
                'string',
                'min:8',
                'max:128',
                new PasswordComplexity(),
                'confirmed',
            ],
            'password_confirmation' => [
                $isUpdate ? 'nullable' : 'required_with:password',
                'string',
            ],
            
            // Personal Information
            'date_of_birth' => [
                'required',
                'date',
                'before:' . Carbon::now()->subYears(21)->format('Y-m-d'),
                'after:' . Carbon::now()->subYears(70)->format('Y-m-d'),
            ],
            'gender' => 'required|in:male,female,other',
            'blood_group' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'religion' => 'nullable|string|max:50',
            'caste' => 'nullable|string|max:50',
            'category' => 'nullable|in:general,obc,sc,st,ews',
            'marital_status' => 'nullable|in:single,married,divorced,widowed',
            
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
            
            // Professional Information
            'qualification' => [
                'required',
                'string',
                'max:200',
                function ($attribute, $value, $fail) {
                    if (preg_match('/[<>"\'\&\(\){}]/', $value)) {
                        $fail('The ' . $attribute . ' contains invalid characters.');
                    }
                }
            ],
            'specialization' => 'nullable|string|max:200',
            'experience_years' => [
                'required',
                'integer',
                'min:0',
                'max:50',
                function ($attribute, $value, $fail) {
                    $dob = $this->input('date_of_birth');
                    if ($dob) {
                        $age = Carbon::parse($dob)->diffInYears(Carbon::now());
                        if ($value > ($age - 21)) {
                            $fail('Experience years cannot exceed possible working years based on age.');
                        }
                    }
                }
            ],
            'previous_experience' => 'nullable|string|max:1000',
            'subjects' => 'nullable|array',
            'subjects.*' => 'exists:subjects,id',
            
            // Employment Information
            'joining_date' => [
                'required',
                'date',
                'before_or_equal:today',
                'after:' . Carbon::now()->subYears(50)->format('Y-m-d'),
            ],
            'employment_type' => 'required|in:permanent,temporary,contract,guest',
            'designation' => 'required|string|max:100',
            'department' => 'nullable|string|max:100',
            'salary' => [
                'required',
                'numeric',
                'min:10000',
                'max:1000000',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'salary_type' => 'required|in:monthly,annual',
            'bank_account_no' => [
                'nullable',
                'string',
                'regex:/^[0-9]{9,18}$/',
            ],
            'bank_ifsc' => [
                'nullable',
                'string',
                'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/',
            ],
            'pan_number' => [
                'nullable',
                'string',
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
                Rule::unique('teachers', 'pan_number')->ignore($teacherId),
            ],
            
            // Status and Permissions
            'is_active' => 'required|boolean',
            'is_class_teacher' => 'nullable|boolean',
            'class_teacher_for' => 'nullable|exists:class_models,id',
            'can_login' => 'required|boolean',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
            
            // Document Information
            'aadhaar_number' => [
                'nullable',
                'string',
                'regex:/^[2-9]{1}[0-9]{3}[0-9]{4}[0-9]{4}$/',
                Rule::unique('teachers', 'aadhaar_number')->ignore($teacherId),
                function ($attribute, $value, $fail) {
                    if ($value && !$this->validateAadhaarChecksum($value)) {
                        $fail('The ' . $attribute . ' is not valid.');
                    }
                }
            ],
            'driving_license' => 'nullable|string|max:20',
            'passport_number' => 'nullable|string|max:20',
            
            // File Uploads
            'photo' => [
                'nullable',
                'file',
                EnhancedFileValidation::images(2048), // 2MB max
            ],
            'resume' => [
                'nullable',
                'file',
                EnhancedFileValidation::documents(5120), // 5MB max
            ],
            'qualification_certificates' => [
                'nullable',
                'array',
                'max:5',
            ],
            'qualification_certificates.*' => [
                'file',
                EnhancedFileValidation::documents(5120), // 5MB max
            ],
            'experience_certificates' => [
                'nullable',
                'array',
                'max:5',
            ],
            'experience_certificates.*' => [
                'file',
                EnhancedFileValidation::documents(5120), // 5MB max
            ],
            'aadhaar_card' => [
                'nullable',
                'file',
                EnhancedFileValidation::documents(5120), // 5MB max
            ],
            'pan_card' => [
                'nullable',
                'file',
                EnhancedFileValidation::documents(5120), // 5MB max
            ],
            
            // Medical Information
            'medical_conditions' => 'nullable|string|max:1000',
            'allergies' => 'nullable|string|max:500',
            'medications' => 'nullable|string|max:500',
            
            // Additional Information
            'hobbies' => 'nullable|string|max:500',
            'languages_known' => 'nullable|string|max:200',
            'awards_recognition' => 'nullable|string|max:1000',
            'research_publications' => 'nullable|string|max:1000',
            'professional_memberships' => 'nullable|string|max:500',
            'remarks' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Teacher name is required.',
            'name.regex' => 'Name can only contain letters, spaces, hyphens, apostrophes, and periods.',
            'employee_id.required' => 'Employee ID is required.',
            'employee_id.unique' => 'This employee ID is already taken.',
            'employee_id.regex' => 'Employee ID can only contain uppercase letters, numbers, and hyphens.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'phone.required' => 'Phone number is required.',
            'phone.regex' => 'Phone number must be a valid 10-digit Indian mobile number.',
            'phone.unique' => 'This phone number is already registered.',
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
            'date_of_birth.required' => 'Date of birth is required.',
            'date_of_birth.before' => 'Teacher must be at least 21 years old.',
            'date_of_birth.after' => 'Teacher age cannot exceed 70 years.',
            'gender.required' => 'Gender is required.',
            'address.required' => 'Address is required.',
            'address.min' => 'Address must be at least 10 characters long.',
            'city.required' => 'City is required.',
            'state.required' => 'State is required.',
            'pincode.required' => 'Pincode is required.',
            'pincode.regex' => 'Pincode must be a valid 6-digit Indian postal code.',
            'qualification.required' => 'Qualification is required.',
            'experience_years.required' => 'Experience years is required.',
            'experience_years.min' => 'Experience years cannot be negative.',
            'experience_years.max' => 'Experience years cannot exceed 50.',
            'joining_date.required' => 'Joining date is required.',
            'employment_type.required' => 'Employment type is required.',
            'designation.required' => 'Designation is required.',
            'salary.required' => 'Salary is required.',
            'salary.min' => 'Salary must be at least ₹10,000.',
            'salary.max' => 'Salary cannot exceed ₹10,00,000.',
            'is_active.required' => 'Active status is required.',
            'can_login.required' => 'Login permission is required.',
            'pan_number.regex' => 'PAN number must be in valid format (e.g., ABCDE1234F).',
            'pan_number.unique' => 'This PAN number is already registered.',
            'aadhaar_number.regex' => 'Aadhaar number must be a valid 12-digit number.',
            'aadhaar_number.unique' => 'This Aadhaar number is already registered.',
            'bank_ifsc.regex' => 'IFSC code must be in valid format (e.g., SBIN0001234).',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'employee_id' => 'employee ID',
            'date_of_birth' => 'date of birth',
            'blood_group' => 'blood group',
            'marital_status' => 'marital status',
            'emergency_contact' => 'emergency contact',
            'experience_years' => 'years of experience',
            'previous_experience' => 'previous experience',
            'joining_date' => 'joining date',
            'employment_type' => 'employment type',
            'salary_type' => 'salary type',
            'bank_account_no' => 'bank account number',
            'bank_ifsc' => 'bank IFSC code',
            'pan_number' => 'PAN number',
            'is_active' => 'active status',
            'is_class_teacher' => 'class teacher status',
            'class_teacher_for' => 'class teacher assignment',
            'can_login' => 'login permission',
            'aadhaar_number' => 'Aadhaar number',
            'driving_license' => 'driving license',
            'passport_number' => 'passport number',
            'qualification_certificates' => 'qualification certificates',
            'experience_certificates' => 'experience certificates',
            'aadhaar_card' => 'Aadhaar card',
            'pan_card' => 'PAN card',
            'medical_conditions' => 'medical conditions',
            'languages_known' => 'languages known',
            'awards_recognition' => 'awards and recognition',
            'research_publications' => 'research publications',
            'professional_memberships' => 'professional memberships',
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
     * Get teacher's user ID for email uniqueness validation.
     */
    private function getTeacherUserId($teacherId): ?int
    {
        $teacher = \App\Models\Teacher::find($teacherId);
        return $teacher ? $teacher->user_id : null;
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Additional custom validation logic
            $this->validateClassTeacherAssignment($validator);
            $this->validateSalaryConsistency($validator);
            $this->validateExperienceConsistency($validator);
        });
    }

    /**
     * Validate class teacher assignment.
     */
    private function validateClassTeacherAssignment($validator): void
    {
        $isClassTeacher = $this->input('is_class_teacher');
        $classTeacherFor = $this->input('class_teacher_for');

        if ($isClassTeacher && !$classTeacherFor) {
            $validator->errors()->add('class_teacher_for', 'Please select a class for the class teacher assignment.');
        }

        if (!$isClassTeacher && $classTeacherFor) {
            $validator->errors()->add('is_class_teacher', 'Please enable class teacher status to assign a class.');
        }
    }

    /**
     * Validate salary consistency.
     */
    private function validateSalaryConsistency($validator): void
    {
        $salary = $this->input('salary');
        $salaryType = $this->input('salary_type');
        $experienceYears = $this->input('experience_years');

        if ($salary && $salaryType && $experienceYears !== null) {
            $monthlySalary = $salaryType === 'annual' ? $salary / 12 : $salary;
            
            // Basic salary validation based on experience
            $minExpectedSalary = 15000 + ($experienceYears * 2000);
            
            if ($monthlySalary < $minExpectedSalary * 0.7) {
                $validator->errors()->add('salary', 'Salary seems too low for the given experience level.');
            }
        }
    }

    /**
     * Validate experience consistency.
     */
    private function validateExperienceConsistency($validator): void
    {
        $joiningDate = $this->input('joining_date');
        $experienceYears = $this->input('experience_years');
        $dob = $this->input('date_of_birth');

        if ($joiningDate && $experienceYears && $dob) {
            $joiningAge = Carbon::parse($dob)->diffInYears(Carbon::parse($joiningDate));
            $minimumWorkingAge = 21;
            
            if ($joiningAge < $minimumWorkingAge) {
                $validator->errors()->add('joining_date', 'Joining date is inconsistent with date of birth.');
            }
            
            if ($experienceYears > ($joiningAge - $minimumWorkingAge + 1)) {
                $validator->errors()->add('experience_years', 'Experience years exceed possible working years.');
            }
        }
    }
}
