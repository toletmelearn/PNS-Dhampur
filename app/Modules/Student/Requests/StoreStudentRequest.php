<?php

namespace App\Modules\Student\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole(['admin', 'teacher']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Basic Information
            'name' => 'required|string|max:255|regex:/^[a-zA-Z\s]+$/',
            'email' => 'nullable|email|max:255|unique:students,email',
            'phone' => 'nullable|string|max:20|regex:/^[0-9+\-\s()]+$/',
            'date_of_birth' => 'required|date|before:today|after:' . now()->subYears(25)->toDateString(),
            'gender' => 'required|in:male,female,other',
            'blood_group' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'religion' => 'nullable|string|max:100',
            'nationality' => 'nullable|string|max:100',
            'mother_tongue' => 'nullable|string|max:100',
            
            // Address Information
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            
            // Academic Information
            'class_id' => 'required|integer|exists:classes,id',
            'section' => 'required|string|max:10',
            'roll_number' => [
                'nullable',
                'integer',
                'min:1',
                Rule::unique('students')->where(function ($query) {
                    return $query->where('class_id', $this->class_id)
                                ->where('section', $this->section);
                })
            ],
            'admission_date' => 'required|date|before_or_equal:today',
            'previous_school' => 'nullable|string|max:255',
            'transfer_certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            
            // Medical Information
            'medical_conditions' => 'nullable|string|max:1000',
            'allergies' => 'nullable|string|max:500',
            'emergency_contact_name' => 'required|string|max:255',
            'emergency_contact_phone' => 'required|string|max:20|regex:/^[0-9+\-\s()]+$/',
            'emergency_contact_relation' => 'required|string|max:100',
            
            // Parent/Guardian Information
            'parent_data.father_name' => 'required|string|max:255',
            'parent_data.father_occupation' => 'nullable|string|max:255',
            'parent_data.father_phone' => 'nullable|string|max:20|regex:/^[0-9+\-\s()]+$/',
            'parent_data.father_email' => 'nullable|email|max:255',
            'parent_data.mother_name' => 'required|string|max:255',
            'parent_data.mother_occupation' => 'nullable|string|max:255',
            'parent_data.mother_phone' => 'nullable|string|max:20|regex:/^[0-9+\-\s()]+$/',
            'parent_data.mother_email' => 'nullable|email|max:255',
            'parent_data.guardian_name' => 'nullable|string|max:255',
            'parent_data.guardian_relation' => 'nullable|string|max:100',
            'parent_data.guardian_phone' => 'nullable|string|max:20|regex:/^[0-9+\-\s()]+$/',
            'parent_data.guardian_email' => 'nullable|email|max:255',
            'parent_data.annual_income' => 'nullable|numeric|min:0',
            
            // Files
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048|dimensions:min_width=200,min_height=200',
            'documents' => 'nullable|array|max:5',
            'documents.*' => 'file|mimes:pdf,jpg,jpeg,png|max:5120',
            
            // Additional Information
            'transportation' => 'nullable|in:bus,private,walking,bicycle',
            'bus_route_id' => 'nullable|integer|exists:bus_routes,id|required_if:transportation,bus',
            'special_needs' => 'nullable|string|max:1000',
            'extracurricular_interests' => 'nullable|array',
            'extracurricular_interests.*' => 'string|max:100',
            'remarks' => 'nullable|string|max:1000',
            'status' => 'nullable|in:active,inactive,transferred,graduated',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Student name is required.',
            'name.regex' => 'Student name should only contain letters and spaces.',
            'email.unique' => 'This email address is already registered.',
            'date_of_birth.required' => 'Date of birth is required.',
            'date_of_birth.before' => 'Date of birth must be before today.',
            'date_of_birth.after' => 'Student must be younger than 25 years.',
            'class_id.required' => 'Please select a class.',
            'class_id.exists' => 'Selected class does not exist.',
            'roll_number.unique' => 'This roll number is already assigned in the selected class and section.',
            'admission_date.required' => 'Admission date is required.',
            'admission_date.before_or_equal' => 'Admission date cannot be in the future.',
            'emergency_contact_name.required' => 'Emergency contact name is required.',
            'emergency_contact_phone.required' => 'Emergency contact phone is required.',
            'parent_data.father_name.required' => 'Father\'s name is required.',
            'parent_data.mother_name.required' => 'Mother\'s name is required.',
            'photo.image' => 'Photo must be an image file.',
            'photo.dimensions' => 'Photo must be at least 200x200 pixels.',
            'documents.max' => 'You can upload maximum 5 documents.',
            'documents.*.max' => 'Each document must be smaller than 5MB.',
            'bus_route_id.required_if' => 'Bus route is required when transportation is set to bus.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'parent_data.father_name' => 'father\'s name',
            'parent_data.father_occupation' => 'father\'s occupation',
            'parent_data.father_phone' => 'father\'s phone',
            'parent_data.father_email' => 'father\'s email',
            'parent_data.mother_name' => 'mother\'s name',
            'parent_data.mother_occupation' => 'mother\'s occupation',
            'parent_data.mother_phone' => 'mother\'s phone',
            'parent_data.mother_email' => 'mother\'s email',
            'parent_data.guardian_name' => 'guardian\'s name',
            'parent_data.guardian_relation' => 'guardian\'s relation',
            'parent_data.guardian_phone' => 'guardian\'s phone',
            'parent_data.guardian_email' => 'guardian\'s email',
            'parent_data.annual_income' => 'annual income',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Clean and format phone numbers
        if ($this->has('phone')) {
            $this->merge([
                'phone' => preg_replace('/[^\d+\-\s()]/', '', $this->phone)
            ]);
        }

        if ($this->has('emergency_contact_phone')) {
            $this->merge([
                'emergency_contact_phone' => preg_replace('/[^\d+\-\s()]/', '', $this->emergency_contact_phone)
            ]);
        }

        // Set default status if not provided
        if (!$this->has('status')) {
            $this->merge(['status' => 'active']);
        }

        // Convert extracurricular interests to array if it's a string
        if ($this->has('extracurricular_interests') && is_string($this->extracurricular_interests)) {
            $this->merge([
                'extracurricular_interests' => array_filter(explode(',', $this->extracurricular_interests))
            ]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Check if at least one parent contact is provided
            $fatherPhone = $this->input('parent_data.father_phone');
            $motherPhone = $this->input('parent_data.mother_phone');
            $guardianPhone = $this->input('parent_data.guardian_phone');

            if (empty($fatherPhone) && empty($motherPhone) && empty($guardianPhone)) {
                $validator->errors()->add('parent_data.father_phone', 'At least one parent/guardian phone number is required.');
            }

            // Validate age based on class
            if ($this->has('class_id') && $this->has('date_of_birth')) {
                $age = now()->diffInYears($this->date_of_birth);
                $class = \App\Models\Classes::find($this->class_id);
                
                if ($class && isset($class->min_age) && $age < $class->min_age) {
                    $validator->errors()->add('date_of_birth', "Student must be at least {$class->min_age} years old for this class.");
                }
                
                if ($class && isset($class->max_age) && $age > $class->max_age) {
                    $validator->errors()->add('date_of_birth', "Student must be at most {$class->max_age} years old for this class.");
                }
            }
        });
    }
}