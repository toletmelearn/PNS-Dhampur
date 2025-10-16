<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StudentFormRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user has permission to manage students
        return $this->user()->can('manage_students') ?? true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = $this->getCommonRules();
        $studentId = $this->route('student') ? $this->route('student')->id : null;

        return [
            // Personal Information
            'first_name' => $rules['name_rules'],
            'last_name' => $rules['name_rules'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('students', 'email')->ignore($studentId),
            ],
            'phone' => [
                'nullable',
                'string',
                'regex:/^[0-9]{10,15}$/',
                Rule::unique('students', 'phone')->ignore($studentId),
            ],
            'date_of_birth' => [
                'required',
                'date',
                'before:' . now()->subYears(3)->format('Y-m-d'), // At least 3 years old
                'after:' . now()->subYears(25)->format('Y-m-d'), // Not more than 25 years old
            ],
            'gender' => ['required', 'in:male,female,other'],
            'blood_group' => ['nullable', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'religion' => ['nullable', 'string', 'max:50'],
            'caste' => ['nullable', 'string', 'max:50'],
            'category' => ['nullable', 'in:general,obc,sc,st,other'],
            
            // Academic Information
            'admission_number' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9]+$/',
                Rule::unique('students', 'admission_number')->ignore($studentId),
            ],
            'roll_number' => [
                'required',
                'string',
                'max:10',
                'regex:/^[A-Z0-9]+$/',
            ],
            'class_id' => ['required', 'exists:classes,id'],
            'section_id' => ['required', 'exists:sections,id'],
            'admission_date' => [
                'required',
                'date',
                'before_or_equal:today',
                'after:' . now()->subYears(20)->format('Y-m-d'),
            ],
            'academic_year' => [
                'required',
                'string',
                'regex:/^\d{4}-\d{4}$/',
            ],
            'previous_school' => ['nullable', 'string', 'max:255'],
            'transfer_certificate' => ['nullable', 'string', 'max:100'],
            
            // Address Information
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z\s]+$/'],
            'state' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z\s]+$/'],
            'pincode' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
            'country' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z\s]+$/'],
            
            // Parent/Guardian Information
            'father_name' => $rules['name_rules'],
            'father_phone' => [
                'required',
                'string',
                'regex:/^[0-9]{10,15}$/',
            ],
            'father_email' => ['nullable', 'email', 'max:255'],
            'father_occupation' => ['nullable', 'string', 'max:100'],
            'father_income' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            
            'mother_name' => $rules['name_rules'],
            'mother_phone' => [
                'nullable',
                'string',
                'regex:/^[0-9]{10,15}$/',
            ],
            'mother_email' => ['nullable', 'email', 'max:255'],
            'mother_occupation' => ['nullable', 'string', 'max:100'],
            'mother_income' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            
            'guardian_name' => ['nullable', 'string', 'min:2', 'max:100'],
            'guardian_phone' => [
                'nullable',
                'string',
                'regex:/^[0-9]{10,15}$/',
            ],
            'guardian_email' => ['nullable', 'email', 'max:255'],
            'guardian_relation' => ['nullable', 'string', 'max:50'],
            
            // Medical Information
            'medical_conditions' => ['nullable', 'string', 'max:1000'],
            'allergies' => ['nullable', 'string', 'max:500'],
            'medications' => ['nullable', 'string', 'max:500'],
            'emergency_contact_name' => ['nullable', 'string', 'max:100'],
            'emergency_contact_phone' => [
                'nullable',
                'string',
                'regex:/^[0-9]{10,15}$/',
            ],
            'emergency_contact_relation' => ['nullable', 'string', 'max:50'],
            
            // Documents and Files
            'photo' => $rules['image_rules'],
            'birth_certificate' => $rules['document_rules'],
            'aadhar_card' => $rules['document_rules'],
            'transfer_certificate_file' => $rules['document_rules'],
            'medical_certificate' => $rules['document_rules'],
            
            // Additional Information
            'transport_required' => $rules['boolean_rules'],
            'bus_route_id' => ['nullable', 'exists:bus_routes,id'],
            'hostel_required' => $rules['boolean_rules'],
            'hostel_room_id' => ['nullable', 'exists:hostel_rooms,id'],
            'library_card_number' => ['nullable', 'string', 'max:20'],
            'sports_activities' => ['nullable', 'array'],
            'sports_activities.*' => ['string', 'max:100'],
            'extra_curricular' => ['nullable', 'array'],
            'extra_curricular.*' => ['string', 'max:100'],
            
            // Status and Flags
            'status' => ['required', 'in:active,inactive,suspended,graduated,transferred'],
            'is_scholarship' => $rules['boolean_rules'],
            'scholarship_amount' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'scholarship_type' => ['nullable', 'string', 'max:100'],
            'is_fee_concession' => $rules['boolean_rules'],
            'fee_concession_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'fee_concession_reason' => ['nullable', 'string', 'max:255'],
            
            // Notes and Remarks
            'remarks' => ['nullable', 'string', 'max:1000'],
            'special_needs' => ['nullable', 'string', 'max:500'],
            'behavioral_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'admission_number.regex' => 'The admission number must contain only uppercase letters and numbers.',
            'roll_number.regex' => 'The roll number must contain only uppercase letters and numbers.',
            'phone.regex' => 'The phone number must be 10-15 digits.',
            'father_phone.regex' => 'The father\'s phone number must be 10-15 digits.',
            'mother_phone.regex' => 'The mother\'s phone number must be 10-15 digits.',
            'guardian_phone.regex' => 'The guardian\'s phone number must be 10-15 digits.',
            'emergency_contact_phone.regex' => 'The emergency contact phone number must be 10-15 digits.',
            'pincode.regex' => 'The pincode must be exactly 6 digits.',
            'academic_year.regex' => 'The academic year must be in format YYYY-YYYY (e.g., 2023-2024).',
            'date_of_birth.before' => 'The student must be at least 3 years old.',
            'date_of_birth.after' => 'The student cannot be more than 25 years old.',
            'admission_date.after' => 'The admission date cannot be more than 20 years ago.',
            'fee_concession_percentage.max' => 'The fee concession percentage cannot exceed 100%.',
            'father_income.max' => 'The father\'s income cannot exceed 99,99,999.',
            'mother_income.max' => 'The mother\'s income cannot exceed 99,99,999.',
            'scholarship_amount.max' => 'The scholarship amount cannot exceed 9,99,999.',
        ]);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Custom validation: Check if roll number is unique within the class and section
            if ($this->filled(['roll_number', 'class_id', 'section_id'])) {
                $studentId = $this->route('student') ? $this->route('student')->id : null;
                
                $existingStudent = \App\Models\Student::where('roll_number', $this->roll_number)
                    ->where('class_id', $this->class_id)
                    ->where('section_id', $this->section_id)
                    ->when($studentId, function ($query) use ($studentId) {
                        return $query->where('id', '!=', $studentId);
                    })
                    ->first();

                if ($existingStudent) {
                    $validator->errors()->add('roll_number', 'The roll number must be unique within the selected class and section.');
                }
            }

            // Custom validation: Check if transport is required but no bus route selected
            if ($this->transport_required && !$this->bus_route_id) {
                $validator->errors()->add('bus_route_id', 'Bus route is required when transport is needed.');
            }

            // Custom validation: Check if hostel is required but no room selected
            if ($this->hostel_required && !$this->hostel_room_id) {
                $validator->errors()->add('hostel_room_id', 'Hostel room is required when hostel accommodation is needed.');
            }

            // Custom validation: Check if scholarship amount is provided when scholarship is enabled
            if ($this->is_scholarship && !$this->scholarship_amount) {
                $validator->errors()->add('scholarship_amount', 'Scholarship amount is required when scholarship is enabled.');
            }

            // Custom validation: Check if fee concession percentage is provided when concession is enabled
            if ($this->is_fee_concession && !$this->fee_concession_percentage) {
                $validator->errors()->add('fee_concession_percentage', 'Fee concession percentage is required when fee concession is enabled.');
            }

            // Custom validation: Validate academic year format and logic
            if ($this->filled('academic_year')) {
                $years = explode('-', $this->academic_year);
                if (count($years) === 2) {
                    $startYear = (int) $years[0];
                    $endYear = (int) $years[1];
                    
                    if ($endYear !== $startYear + 1) {
                        $validator->errors()->add('academic_year', 'The academic year must be consecutive years (e.g., 2023-2024).');
                    }
                }
            }
        });
    }
}