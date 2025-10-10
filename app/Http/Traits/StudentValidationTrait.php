<?php

namespace App\Http\Traits;

use App\Rules\AgeValidation;

trait StudentValidationTrait
{
    use EmailValidationTrait, DateRangeValidationTrait, CommonValidationTrait;

    /**
     * Get student creation validation rules
     *
     * @return array
     */
    protected function getStudentCreationRules(): array
    {
        return array_merge(
            $this->getNameValidationRules(true, 255),
            $this->getCreateEmailValidationRules(),
            $this->getPhoneValidationRules(false, 20),
            $this->getAddressValidationRules(false, 1000),
            [
                'student_id' => ['required', 'string', 'max:50', 'unique:students,student_id'],
                'class_id' => ['required', 'integer', 'exists:classes,id'],
                'section_id' => ['nullable', 'integer', 'exists:sections,id'],
                'roll_number' => ['nullable', 'string', 'max:20'],
                'admission_number' => ['nullable', 'string', 'max:50', 'unique:students,admission_number'],
                'date_of_birth' => ['required', 'date', 'before:today', new AgeValidation(3, 25)],
                'gender' => ['required', 'string', 'in:male,female,other'],
                'blood_group' => ['nullable', 'string', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
                'religion' => ['nullable', 'string', 'max:100'],
                'caste' => ['nullable', 'string', 'max:100'],
                'category' => ['nullable', 'string', 'in:general,obc,sc,st,other'],
                'nationality' => ['nullable', 'string', 'max:100'],
                'mother_tongue' => ['nullable', 'string', 'max:100'],
                'admission_date' => ['required', 'date', 'before_or_equal:today'],
                'transport_required' => ['nullable', 'boolean'],
                'hostel_required' => ['nullable', 'boolean'],
                'medical_conditions' => ['nullable', 'string', 'max:1000'],
                'allergies' => ['nullable', 'string', 'max:500'],
                'emergency_contact' => ['nullable', 'string', 'max:20'],
                'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
                'is_active' => ['nullable', 'boolean'],
            ]
        );
    }

    /**
     * Get student update validation rules
     *
     * @param int $studentId Student ID for unique validation
     * @return array
     */
    protected function getStudentUpdateRules(int $studentId): array
    {
        return array_merge(
            $this->getNameValidationRules(true, 255),
            $this->getUpdateEmailValidationRules('students', $studentId),
            $this->getPhoneValidationRules(false, 20),
            $this->getAddressValidationRules(false, 1000),
            [
                'student_id' => ['required', 'string', 'max:50', "unique:students,student_id,{$studentId}"],
                'class_id' => ['required', 'integer', 'exists:classes,id'],
                'section_id' => ['nullable', 'integer', 'exists:sections,id'],
                'roll_number' => ['nullable', 'string', 'max:20'],
                'admission_number' => ['nullable', 'string', 'max:50', "unique:students,admission_number,{$studentId}"],
                'date_of_birth' => ['required', 'date', 'before:today', new AgeValidation(3, 25)],
                'gender' => ['required', 'string', 'in:male,female,other'],
                'blood_group' => ['nullable', 'string', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
                'religion' => ['nullable', 'string', 'max:100'],
                'caste' => ['nullable', 'string', 'max:100'],
                'category' => ['nullable', 'string', 'in:general,obc,sc,st,other'],
                'nationality' => ['nullable', 'string', 'max:100'],
                'mother_tongue' => ['nullable', 'string', 'max:100'],
                'admission_date' => ['required', 'date', 'before_or_equal:today'],
                'transport_required' => ['nullable', 'boolean'],
                'hostel_required' => ['nullable', 'boolean'],
                'medical_conditions' => ['nullable', 'string', 'max:1000'],
                'allergies' => ['nullable', 'string', 'max:500'],
                'emergency_contact' => ['nullable', 'string', 'max:20'],
                'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
                'is_active' => ['nullable', 'boolean'],
            ]
        );
    }

    /**
     * Get student search/filter validation rules
     *
     * @return array
     */
    protected function getStudentSearchRules(): array
    {
        return array_merge(
            $this->getNameValidationRules(false, 255),
            [
                'description' => ['nullable', 'string', 'max:500'],
                'is_public' => ['nullable', 'boolean'],
                'is_default' => ['nullable', 'boolean'],
                'class_id' => ['nullable', 'integer', 'exists:classes,id'],
                'section_id' => ['nullable', 'integer', 'exists:sections,id'],
                'student_id' => ['nullable', 'string', 'max:50'],
                'admission_number' => ['nullable', 'string', 'max:50'],
                'gender' => ['nullable', 'string', 'in:male,female,other'],
                'category' => ['nullable', 'string', 'in:general,obc,sc,st,other'],
                'is_active' => ['nullable', 'boolean'],
                'transport_required' => ['nullable', 'boolean'],
                'hostel_required' => ['nullable', 'boolean'],
            ]
        );
    }

    /**
     * Get student parent/guardian validation rules
     *
     * @return array
     */
    protected function getStudentParentRules(): array
    {
        return [
            'father_name' => ['nullable', 'string', 'max:255'],
            'father_phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s\(\)]+$/'],
            'father_email' => ['nullable', 'email', 'max:255'],
            'father_occupation' => ['nullable', 'string', 'max:255'],
            'father_income' => ['nullable', 'numeric', 'min:0'],
            'father_qualification' => ['nullable', 'string', 'max:255'],
            
            'mother_name' => ['nullable', 'string', 'max:255'],
            'mother_phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s\(\)]+$/'],
            'mother_email' => ['nullable', 'email', 'max:255'],
            'mother_occupation' => ['nullable', 'string', 'max:255'],
            'mother_income' => ['nullable', 'numeric', 'min:0'],
            'mother_qualification' => ['nullable', 'string', 'max:255'],
            
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s\(\)]+$/'],
            'guardian_email' => ['nullable', 'email', 'max:255'],
            'guardian_relation' => ['nullable', 'string', 'max:100'],
            'guardian_occupation' => ['nullable', 'string', 'max:255'],
            'guardian_address' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get student academic validation rules
     *
     * @return array
     */
    protected function getStudentAcademicRules(): array
    {
        return array_merge(
            $this->getAcademicYearValidationRules(true),
            [
                'previous_school' => ['nullable', 'string', 'max:255'],
                'previous_class' => ['nullable', 'string', 'max:50'],
                'tc_number' => ['nullable', 'string', 'max:100'],
                'tc_date' => ['nullable', 'date', 'before_or_equal:today'],
                'marks_obtained' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'total_marks' => ['nullable', 'numeric', 'min:0'],
                'percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'grade' => ['nullable', 'string', 'max:10'],
                'subjects' => ['nullable', 'array'],
                'subjects.*' => ['integer', 'exists:subjects,id'],
                'elective_subjects' => ['nullable', 'array'],
                'elective_subjects.*' => ['integer', 'exists:subjects,id'],
            ]
        );
    }

    /**
     * Get student fee validation rules
     *
     * @return array
     */
    protected function getStudentFeeRules(): array
    {
        return [
            'fee_structure_id' => ['nullable', 'integer', 'exists:fee_structures,id'],
            'discount_type' => ['nullable', 'string', 'in:percentage,fixed'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'scholarship_type' => ['nullable', 'string', 'max:255'],
            'scholarship_amount' => ['nullable', 'numeric', 'min:0'],
            'concession_type' => ['nullable', 'string', 'max:255'],
            'concession_amount' => ['nullable', 'numeric', 'min:0'],
            'transport_fee' => ['nullable', 'numeric', 'min:0'],
            'hostel_fee' => ['nullable', 'numeric', 'min:0'],
            'library_fee' => ['nullable', 'numeric', 'min:0'],
            'laboratory_fee' => ['nullable', 'numeric', 'min:0'],
            'examination_fee' => ['nullable', 'numeric', 'min:0'],
            'miscellaneous_fee' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * Get student document validation rules
     *
     * @return array
     */
    protected function getStudentDocumentRules(): array
    {
        return [
            'birth_certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'aadhar_card' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'transfer_certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'character_certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'caste_certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'income_certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'medical_certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'passport_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:1024'],
            'other_documents' => ['nullable', 'array'],
            'other_documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
        ];
    }

    /**
     * Get student bulk operation validation rules
     *
     * @return array
     */
    protected function getStudentBulkRules(): array
    {
        return [
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:students,id'],
            'action' => ['required', 'string', 'in:activate,deactivate,promote,transfer,delete'],
            'target_class_id' => ['nullable', 'integer', 'exists:classes,id'],
            'target_section_id' => ['nullable', 'integer', 'exists:sections,id'],
            'effective_date' => ['nullable', 'date', 'after_or_equal:today'],
            'reason' => ['nullable', 'string', 'max:500'],
            'notify_parents' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get student verification validation rules
     *
     * @return array
     */
    protected function getStudentVerificationValidationRules(): array
    {
        return [
            'verified_data' => ['required', 'array'],
            'verified_data.name' => ['nullable', 'string', 'max:255'],
            'verified_data.father_name' => ['nullable', 'string', 'max:255'],
            'verified_data.mother_name' => ['nullable', 'string', 'max:255'],
            'verified_data.dob' => ['nullable', 'date'],
            'verified_data.aadhaar' => ['nullable', 'string', 'regex:/^\d{12}$/', 'size:12'],
            'verified_data.address' => ['nullable', 'string', 'max:1000'],
            'verified_data.phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s\(\)]+$/'],
            'verified_data.email' => ['nullable', 'email', 'max:255'],
            'force' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get student validation messages
     *
     * @return array
     */
    protected function getStudentValidationMessages(): array
    {
        return array_merge(
            $this->getCommonValidationMessages(),
            $this->getEmailValidationMessages(),
            $this->getDateRangeValidationMessages(),
            [
                'student_id.required' => 'Student ID is required.',
                'student_id.unique' => 'This student ID is already taken.',
                'student_id.max' => 'Student ID cannot exceed :max characters.',
                
                'class_id.required' => 'Class is required.',
                'class_id.exists' => 'Selected class does not exist.',
                
                'section_id.exists' => 'Selected section does not exist.',
                
                'admission_number.unique' => 'This admission number is already taken.',
                'admission_number.max' => 'Admission number cannot exceed :max characters.',
                
                'date_of_birth.required' => 'Date of birth is required.',
                'date_of_birth.date' => 'Date of birth must be a valid date.',
                'date_of_birth.before' => 'Date of birth must be before today.',
                
                'gender.required' => 'Gender is required.',
                'gender.in' => 'Selected gender is invalid.',
                
                'blood_group.in' => 'Selected blood group is invalid.',
                
                'category.in' => 'Selected category is invalid.',
                
                'admission_date.required' => 'Admission date is required.',
                'admission_date.date' => 'Admission date must be a valid date.',
                'admission_date.before_or_equal' => 'Admission date cannot be in the future.',
                
                'photo.image' => 'Photo must be an image file.',
                'photo.mimes' => 'Photo must be a JPEG, PNG, or JPG file.',
                'photo.max' => 'Photo size cannot exceed 2MB.',
                
                'student_ids.required' => 'At least one student must be selected.',
                'student_ids.array' => 'Student selection must be a valid list.',
                'student_ids.min' => 'At least one student must be selected.',
                'student_ids.*.exists' => 'One or more selected students do not exist.',
                
                'action.required' => 'Action is required.',
                'action.in' => 'Selected action is invalid.',
                
                'target_class_id.exists' => 'Selected target class does not exist.',
                'target_section_id.exists' => 'Selected target section does not exist.',
                
                'effective_date.date' => 'Effective date must be a valid date.',
                'effective_date.after_or_equal' => 'Effective date cannot be in the past.',
                
                'father_phone.regex' => 'Father\'s phone number format is invalid.',
                'mother_phone.regex' => 'Mother\'s phone number format is invalid.',
                'guardian_phone.regex' => 'Guardian\'s phone number format is invalid.',
                
                'father_email.email' => 'Father\'s email must be a valid email address.',
                'mother_email.email' => 'Mother\'s email must be a valid email address.',
                'guardian_email.email' => 'Guardian\'s email must be a valid email address.',
                
                'subjects.array' => 'Subjects must be a valid list.',
                'subjects.*.exists' => 'One or more selected subjects do not exist.',
                
                'elective_subjects.array' => 'Elective subjects must be a valid list.',
                'elective_subjects.*.exists' => 'One or more selected elective subjects do not exist.',
                
                'discount_type.in' => 'Discount type must be either percentage or fixed.',
                'scholarship_amount.min' => 'Scholarship amount must be at least 0.',
                'concession_amount.min' => 'Concession amount must be at least 0.',
                
                'birth_certificate.mimes' => 'Birth certificate must be a PDF, JPG, JPEG, or PNG file.',
                'birth_certificate.max' => 'Birth certificate size cannot exceed 2MB.',
                
                'aadhar_card.mimes' => 'Aadhar card must be a PDF, JPG, JPEG, or PNG file.',
                'aadhar_card.max' => 'Aadhar card size cannot exceed 2MB.',
                
                'passport_photo.image' => 'Passport photo must be an image file.',
                'passport_photo.mimes' => 'Passport photo must be a JPG, JPEG, or PNG file.',
                'passport_photo.max' => 'Passport photo size cannot exceed 1MB.',
            ]
        );
    }
}