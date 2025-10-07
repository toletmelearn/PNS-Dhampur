<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Http\Traits\DateRangeValidationTrait;

class StoreTeacherRequest extends FormRequest
{
    use DateRangeValidationTrait;
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('principal'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Basic Information
            'name' => 'required|string|max:255|regex:/^[a-zA-Z\s]+$/',
            'email' => 'required|email|unique:users,email|max:255',
            'phone' => 'nullable|string|regex:/^[\+]?[0-9\s\-\(\)]{10,15}$/|max:20',
            'password' => 'required|string|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
            'password_confirmation' => 'required|string|min:8',
            
            // Personal Information
            'date_of_birth' => 'nullable|date|before:today|after:1950-01-01',
            'gender' => 'nullable|in:male,female,other',
            'blood_group' => 'nullable|string|in:A+,A-,B+,B-,AB+,AB-,O+,O-|max:5',
            'aadhaar' => 'nullable|string|unique:teachers,aadhaar|regex:/^[0-9]{12}$/',
            'pan_number' => 'nullable|string|unique:teachers,pan_number|regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/|max:10',
            'address' => 'nullable|string|max:1000',
            'emergency_contact' => 'nullable|string|regex:/^[\+]?[0-9\s\-\(\)]{10,15}$/|max:20',
            'emergency_contact_name' => 'nullable|string|max:255|regex:/^[a-zA-Z\s]+$/',
            'emergency_contact_relation' => 'nullable|string|max:100',
            
            // Professional Information
            'employee_id' => 'nullable|string|unique:teachers,employee_id|max:50',
            'qualification' => 'required|string|max:500',
            'specialization' => 'nullable|string|max:255',
            'joining_date' => 'required|date|before_or_equal:today|after:1990-01-01',
            'employment_type' => 'required|in:permanent,temporary,contract,part_time',
            'designation' => 'nullable|string|max:100',
            'department' => 'nullable|string|max:100',
            
            // Experience Validation
            'experience' => 'nullable|array',
            'experience.*.company' => 'required_with:experience|string|max:255',
            'experience.*.position' => 'required_with:experience|string|max:255',
            'experience.*.start_date' => 'required_with:experience|date|before:today',
            'experience.*.end_date' => 'nullable|date|after:experience.*.start_date|before_or_equal:today',
            'experience.*.duration_years' => 'nullable|numeric|min:0|max:50',
            'experience.*.description' => 'nullable|string|max:1000',
            'experience.*.reference_contact' => 'nullable|string|max:255',
            'experience.*.is_current' => 'nullable|boolean',
            'total_experience_years' => 'required|integer|min:0|max:50',
            
            // Salary Information
            'salary' => 'required|array',
            'salary.basic' => 'required|numeric|min:0|max:999999.99',
            'salary.hra' => 'nullable|numeric|min:0|max:999999.99',
            'salary.da' => 'nullable|numeric|min:0|max:999999.99',
            'salary.ta' => 'nullable|numeric|min:0|max:999999.99',
            'salary.medical_allowance' => 'nullable|numeric|min:0|max:999999.99',
            'salary.special_allowance' => 'nullable|numeric|min:0|max:999999.99',
            'salary.other_allowances' => 'nullable|numeric|min:0|max:999999.99',
            'salary.pf_deduction' => 'nullable|numeric|min:0|max:999999.99',
            'salary.esi_deduction' => 'nullable|numeric|min:0|max:999999.99',
            'salary.professional_tax' => 'nullable|numeric|min:0|max:999999.99',
            'salary.income_tax' => 'nullable|numeric|min:0|max:999999.99',
            'salary.other_deductions' => 'nullable|numeric|min:0|max:999999.99',
            'salary.gross_salary' => 'nullable|numeric|min:0|max:9999999.99',
            'salary.net_salary' => 'nullable|numeric|min:0|max:9999999.99',
            'salary.effective_from' => 'required_with:salary|date|before_or_equal:today',
            
            // Document Validation
            'documents' => 'nullable|array|max:10',
            'documents.*' => [
                'required_with:documents',
                'file',
                'mimes:pdf,doc,docx,jpg,jpeg,png',
                'max:10240', // 10MB
                function ($attribute, $value, $fail) {
                    // Additional file validation for security
                    $allowedMimeTypes = [
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'image/jpeg',
                        'image/jpg',
                        'image/png'
                    ];
                    
                    if (!in_array($value->getMimeType(), $allowedMimeTypes)) {
                        $fail('The file must be a valid PDF, DOC, DOCX, JPG, JPEG, or PNG file.');
                    }
                }
            ],
            'document_types' => 'nullable|array',
            'document_types.*' => 'required_with:documents|string|in:resume,degree_certificate,experience_letter,id_proof,address_proof,photo,other',
            
            // Subject and Class Assignments
            'subjects' => 'nullable|array',
            'subjects.*' => 'exists:subjects,id',
            'classes' => 'nullable|array',
            'classes.*' => 'exists:class_models,id',
            'is_class_teacher' => 'nullable|boolean',
            'class_teacher_for' => 'nullable|exists:class_models,id|required_if:is_class_teacher,true',
            
            // Additional Information
            'bio' => 'nullable|string|max:2000',
            'skills' => 'nullable|string|max:1000',
            'certifications' => 'nullable|string|max:1000',
            'languages_known' => 'nullable|string|max:500',
            'hobbies' => 'nullable|string|max:500',
            'achievements' => 'nullable|string|max:1000',
            
            // System Fields
            'status' => 'nullable|in:active,inactive,suspended,on_leave',
            'probation_period_months' => 'nullable|integer|min:0|max:24',
            'is_probation_completed' => 'nullable|boolean',
            'notes' => 'nullable|string|max:2000',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Teacher name is required.',
            'name.regex' => 'Teacher name should only contain letters and spaces.',
            'email.required' => 'Email address is required.',
            'email.unique' => 'This email address is already registered.',
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
            'phone.regex' => 'Please enter a valid phone number.',
            'aadhaar.regex' => 'Aadhaar number must be exactly 12 digits.',
            'aadhaar.unique' => 'This Aadhaar number is already registered.',
            'pan_number.regex' => 'PAN number format is invalid (e.g., ABCDE1234F).',
            'pan_number.unique' => 'This PAN number is already registered.',
            'employee_id.unique' => 'This employee ID is already assigned.',
            'qualification.required' => 'Educational qualification is required.',
            'joining_date.required' => 'Joining date is required.',
            'joining_date.before_or_equal' => 'Joining date cannot be in the future.',
            'total_experience_years.required' => 'Total experience in years is required.',
            'salary.basic.required' => 'Basic salary is required.',
            'salary.basic.min' => 'Basic salary must be greater than or equal to 0.',
            'documents.*.mimes' => 'Document must be a PDF, DOC, DOCX, JPG, JPEG, or PNG file.',
            'documents.*.max' => 'Document size cannot exceed 10MB.',
            'experience.*.company.required_with' => 'Company name is required for experience entry.',
            'experience.*.position.required_with' => 'Position is required for experience entry.',
            'experience.*.start_date.required_with' => 'Start date is required for experience entry.',
            'experience.*.end_date.after' => 'End date must be after start date.',
            'class_teacher_for.required_if' => 'Please select a class if this teacher is a class teacher.',
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'teacher name',
            'email' => 'email address',
            'phone' => 'phone number',
            'date_of_birth' => 'date of birth',
            'blood_group' => 'blood group',
            'aadhaar' => 'Aadhaar number',
            'pan_number' => 'PAN number',
            'emergency_contact' => 'emergency contact',
            'emergency_contact_name' => 'emergency contact name',
            'employee_id' => 'employee ID',
            'joining_date' => 'joining date',
            'employment_type' => 'employment type',
            'total_experience_years' => 'total experience',
            'salary.basic' => 'basic salary',
            'salary.hra' => 'HRA',
            'salary.da' => 'DA',
            'salary.ta' => 'TA',
            'class_teacher_for' => 'class teacher assignment',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate salary calculations
            $this->validateSalaryCalculations($validator);
            
            // Validate experience consistency
            $this->validateExperienceConsistency($validator);
            
            // Validate document requirements
            $this->validateDocumentRequirements($validator);
        });
    }

    /**
     * Validate salary calculations.
     */
    protected function validateSalaryCalculations($validator): void
    {
        $salary = $this->input('salary', []);
        
        if (!empty($salary)) {
            $basic = (float) ($salary['basic'] ?? 0);
            $allowances = (float) ($salary['hra'] ?? 0) + 
                         (float) ($salary['da'] ?? 0) + 
                         (float) ($salary['ta'] ?? 0) + 
                         (float) ($salary['medical_allowance'] ?? 0) + 
                         (float) ($salary['special_allowance'] ?? 0) + 
                         (float) ($salary['other_allowances'] ?? 0);
            
            $deductions = (float) ($salary['pf_deduction'] ?? 0) + 
                         (float) ($salary['esi_deduction'] ?? 0) + 
                         (float) ($salary['professional_tax'] ?? 0) + 
                         (float) ($salary['income_tax'] ?? 0) + 
                         (float) ($salary['other_deductions'] ?? 0);
            
            $calculatedGross = $basic + $allowances;
            $calculatedNet = $calculatedGross - $deductions;
            
            // Validate gross salary if provided
            if (isset($salary['gross_salary']) && abs($calculatedGross - (float) $salary['gross_salary']) > 0.01) {
                $validator->errors()->add('salary.gross_salary', 'Gross salary calculation does not match the sum of basic salary and allowances.');
            }
            
            // Validate net salary if provided
            if (isset($salary['net_salary']) && abs($calculatedNet - (float) $salary['net_salary']) > 0.01) {
                $validator->errors()->add('salary.net_salary', 'Net salary calculation does not match gross salary minus deductions.');
            }
        }
    }

    /**
     * Validate experience consistency.
     */
    protected function validateExperienceConsistency($validator): void
    {
        $experiences = $this->input('experience', []);
        $totalExperience = (int) $this->input('total_experience_years', 0);
        
        if (!empty($experiences)) {
            $calculatedExperience = 0;
            
            foreach ($experiences as $index => $experience) {
                if (isset($experience['duration_years'])) {
                    $calculatedExperience += (float) $experience['duration_years'];
                }
                
                // Check for overlapping experience periods using DateRangeValidationTrait
                if (isset($experience['start_date']) && isset($experience['end_date'])) {
                    foreach ($experiences as $otherIndex => $otherExperience) {
                        if ($index !== $otherIndex && 
                            isset($otherExperience['start_date']) && 
                            isset($otherExperience['end_date'])) {
                            
                            // Use trait method to check for date range overlap
                            if ($this->datesOverlap(
                                $experience['start_date'], 
                                $experience['end_date'],
                                $otherExperience['start_date'], 
                                $otherExperience['end_date']
                            )) {
                                $validator->errors()->add("experience.{$index}.start_date", 'Experience periods cannot overlap.');
                                break;
                            }
                        }
                    }
                }
            }
            
            // Allow some tolerance in experience calculation (±1 year)
            if (abs($calculatedExperience - $totalExperience) > 1) {
                $validator->errors()->add('total_experience_years', 'Total experience years should match the sum of individual experience durations.');
            }
        }
    }

    /**
     * Validate document requirements.
     */
    protected function validateDocumentRequirements($validator): void
    {
        $documents = $this->file('documents', []);
        $documentTypes = $this->input('document_types', []);
        
        // Ensure document types match document count
        if (count($documents) !== count($documentTypes)) {
            $validator->errors()->add('document_types', 'Document types must be specified for each uploaded document.');
        }
        
        // Check for required document types for permanent employees
        $employmentType = $this->input('employment_type');
        if ($employmentType === 'permanent') {
            $requiredTypes = ['resume', 'degree_certificate', 'id_proof'];
            $providedTypes = array_values($documentTypes);
            
            foreach ($requiredTypes as $requiredType) {
                if (!in_array($requiredType, $providedTypes)) {
                    $validator->errors()->add('document_types', "Document type '{$requiredType}' is required for permanent employees.");
                }
            }
        }
    }
}