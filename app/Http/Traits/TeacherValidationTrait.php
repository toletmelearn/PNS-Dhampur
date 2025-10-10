<?php

namespace App\Http\Traits;

use App\Rules\PasswordComplexity;

trait TeacherValidationTrait
{
    use EmailValidationTrait, DateRangeValidationTrait, CommonValidationTrait;

    /**
     * Get teacher creation validation rules
     *
     * @return array
     */
    protected function getTeacherCreationRules(): array
    {
        return array_merge(
            $this->getNameValidationRules(true, 255),
            $this->getCreateEmailValidationRules(),
            $this->getPhoneValidationRules(true, 20),
            $this->getAddressValidationRules(false, 1000),
            [
                'employee_id' => ['required', 'string', 'max:50', 'unique:teachers,employee_id'],
                'password' => ['required', 'string', 'min:8', new PasswordComplexity()],
                'qualification' => ['required', 'string', 'max:255'],
                'experience_years' => ['required', 'integer', 'min:0', 'max:50'],
                'salary' => ['required', 'numeric', 'min:0'],
                'joining_date' => ['required', 'date', 'before_or_equal:today'],
                'date_of_birth' => ['required', 'date', 'before:today'],
                'gender' => ['required', 'string', 'in:male,female,other'],
                'blood_group' => ['nullable', 'string', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
                'marital_status' => ['nullable', 'string', 'in:single,married,divorced,widowed'],
                'nationality' => ['nullable', 'string', 'max:100'],
                'religion' => ['nullable', 'string', 'max:100'],
                'caste' => ['nullable', 'string', 'max:100'],
                'category' => ['nullable', 'string', 'in:general,obc,sc,st,other'],
                'emergency_contact' => ['required', 'string', 'max:20'],
                'emergency_contact_name' => ['required', 'string', 'max:255'],
                'emergency_contact_relation' => ['required', 'string', 'max:100'],
                'department_id' => ['nullable', 'integer', 'exists:departments,id'],
                'designation' => ['required', 'string', 'max:255'],
                'employment_type' => ['required', 'string', 'in:permanent,temporary,contract,part_time'],
                'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
                'is_active' => ['nullable', 'boolean'],
                'can_login' => ['nullable', 'boolean'],
            ]
        );
    }

    /**
     * Get teacher update validation rules
     *
     * @param int $teacherId Teacher ID for unique validation
     * @return array
     */
    protected function getTeacherUpdateRules(int $teacherId): array
    {
        return array_merge(
            $this->getNameValidationRules(true, 255),
            $this->getUpdateEmailValidationRules('teachers', $teacherId),
            $this->getPhoneValidationRules(true, 20),
            $this->getAddressValidationRules(false, 1000),
            [
                'employee_id' => ['required', 'string', 'max:50', "unique:teachers,employee_id,{$teacherId}"],
                'password' => ['nullable', 'string', 'min:8', new PasswordComplexity()],
                'qualification' => ['required', 'string', 'max:255'],
                'experience_years' => ['required', 'integer', 'min:0', 'max:50'],
                'salary' => ['required', 'numeric', 'min:0'],
                'joining_date' => ['required', 'date', 'before_or_equal:today'],
                'date_of_birth' => ['required', 'date', 'before:today'],
                'gender' => ['required', 'string', 'in:male,female,other'],
                'blood_group' => ['nullable', 'string', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
                'marital_status' => ['nullable', 'string', 'in:single,married,divorced,widowed'],
                'nationality' => ['nullable', 'string', 'max:100'],
                'religion' => ['nullable', 'string', 'max:100'],
                'caste' => ['nullable', 'string', 'max:100'],
                'category' => ['nullable', 'string', 'in:general,obc,sc,st,other'],
                'emergency_contact' => ['required', 'string', 'max:20'],
                'emergency_contact_name' => ['required', 'string', 'max:255'],
                'emergency_contact_relation' => ['required', 'string', 'max:100'],
                'department_id' => ['nullable', 'integer', 'exists:departments,id'],
                'designation' => ['required', 'string', 'max:255'],
                'employment_type' => ['required', 'string', 'in:permanent,temporary,contract,part_time'],
                'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
                'is_active' => ['nullable', 'boolean'],
                'can_login' => ['nullable', 'boolean'],
            ]
        );
    }

    /**
     * Get teacher subject assignment validation rules
     *
     * @return array
     */
    protected function getTeacherSubjectRules(): array
    {
        return [
            'subjects' => ['required', 'array', 'min:1'],
            'subjects.*' => ['integer', 'exists:subjects,id'],
            'classes' => ['nullable', 'array'],
            'classes.*' => ['integer', 'exists:classes,id'],
            'sections' => ['nullable', 'array'],
            'sections.*' => ['integer', 'exists:sections,id'],
            'is_class_teacher' => ['nullable', 'boolean'],
            'class_teacher_for' => ['nullable', 'integer', 'exists:classes,id'],
            'section_teacher_for' => ['nullable', 'integer', 'exists:sections,id'],
        ];
    }

    /**
     * Get teacher qualification validation rules
     *
     * @return array
     */
    protected function getTeacherQualificationRules(): array
    {
        return [
            'qualifications' => ['nullable', 'array'],
            'qualifications.*.degree' => ['required', 'string', 'max:255'],
            'qualifications.*.institution' => ['required', 'string', 'max:255'],
            'qualifications.*.year' => ['required', 'integer', 'min:1950', 'max:' . date('Y')],
            'qualifications.*.percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'qualifications.*.grade' => ['nullable', 'string', 'max:10'],
            'qualifications.*.specialization' => ['nullable', 'string', 'max:255'],
            'qualifications.*.certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ];
    }

    /**
     * Get teacher experience validation rules
     *
     * @return array
     */
    protected function getTeacherExperienceRules(): array
    {
        return [
            'experiences' => ['nullable', 'array'],
            'experiences.*.organization' => ['required', 'string', 'max:255'],
            'experiences.*.designation' => ['required', 'string', 'max:255'],
            'experiences.*.from_date' => ['required', 'date'],
            'experiences.*.to_date' => ['nullable', 'date', 'after:experiences.*.from_date'],
            'experiences.*.salary' => ['nullable', 'numeric', 'min:0'],
            'experiences.*.responsibilities' => ['nullable', 'string', 'max:1000'],
            'experiences.*.reason_for_leaving' => ['nullable', 'string', 'max:500'],
            'experiences.*.certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ];
    }

    /**
     * Get teacher document validation rules
     *
     * @return array
     */
    protected function getTeacherDocumentRules(): array
    {
        return [
            'resume' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'aadhar_card' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'pan_card' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'driving_license' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'passport' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'bank_passbook' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'salary_certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'medical_certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'police_verification' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'other_documents' => ['nullable', 'array'],
            'other_documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
        ];
    }

    /**
     * Get teacher attendance validation rules
     *
     * @return array
     */
    protected function getTeacherAttendanceRules(): array
    {
        return array_merge(
            $this->getDateRangeValidationRules(),
            [
                'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
                'date' => ['required', 'date', 'before_or_equal:today'],
                'status' => ['required', 'string', 'in:present,absent,late,half_day,on_leave'],
                'check_in_time' => ['nullable', 'date_format:H:i'],
                'check_out_time' => ['nullable', 'date_format:H:i', 'after:check_in_time'],
                'working_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
                'overtime_hours' => ['nullable', 'numeric', 'min:0', 'max:12'],
                'break_time' => ['nullable', 'numeric', 'min:0', 'max:8'],
                'late_minutes' => ['nullable', 'integer', 'min:0'],
                'early_departure_minutes' => ['nullable', 'integer', 'min:0'],
                'location' => ['nullable', 'string', 'max:255'],
                'ip_address' => ['nullable', 'ip'],
                'device_info' => ['nullable', 'string', 'max:500'],
                'remarks' => ['nullable', 'string', 'max:500'],
            ]
        );
    }

    /**
     * Get teacher leave validation rules
     *
     * @return array
     */
    protected function getTeacherLeaveRules(): array
    {
        return array_merge(
            $this->getDateRangeValidationRules(),
            $this->getReasonValidationRules(true, 1000),
            [
                'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
                'leave_type' => ['required', 'string', 'in:sick,casual,earned,maternity,paternity,emergency,unpaid'],
                'from_date' => ['required', 'date', 'after_or_equal:today'],
                'to_date' => ['required', 'date', 'after_or_equal:from_date'],
                'days_count' => ['required', 'integer', 'min:1'],
                'half_day' => ['nullable', 'boolean'],
                'session' => ['nullable', 'string', 'in:morning,afternoon'],
                'substitute_teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
                'emergency_contact' => ['nullable', 'string', 'max:20'],
                'medical_certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
                'supporting_documents' => ['nullable', 'array'],
                'supporting_documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:2048'],
            ]
        );
    }

    /**
     * Get teacher salary validation rules
     *
     * @return array
     */
    protected function getTeacherSalaryRules(): array
    {
        return [
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2000', 'max:' . (date('Y') + 1)],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'hra' => ['nullable', 'numeric', 'min:0'],
            'da' => ['nullable', 'numeric', 'min:0'],
            'ta' => ['nullable', 'numeric', 'min:0'],
            'medical_allowance' => ['nullable', 'numeric', 'min:0'],
            'special_allowance' => ['nullable', 'numeric', 'min:0'],
            'overtime_amount' => ['nullable', 'numeric', 'min:0'],
            'bonus' => ['nullable', 'numeric', 'min:0'],
            'incentive' => ['nullable', 'numeric', 'min:0'],
            'pf_deduction' => ['nullable', 'numeric', 'min:0'],
            'esi_deduction' => ['nullable', 'numeric', 'min:0'],
            'tax_deduction' => ['nullable', 'numeric', 'min:0'],
            'loan_deduction' => ['nullable', 'numeric', 'min:0'],
            'other_deductions' => ['nullable', 'numeric', 'min:0'],
            'advance_deduction' => ['nullable', 'numeric', 'min:0'],
            'gross_salary' => ['required', 'numeric', 'min:0'],
            'total_deductions' => ['required', 'numeric', 'min:0'],
            'net_salary' => ['required', 'numeric', 'min:0'],
            'payment_date' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', 'in:cash,bank_transfer,cheque'],
            'bank_account' => ['nullable', 'string', 'max:50'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get teacher bulk operation validation rules
     *
     * @return array
     */
    protected function getTeacherBulkRules(): array
    {
        return [
            'teacher_ids' => ['required', 'array', 'min:1'],
            'teacher_ids.*' => ['integer', 'exists:teachers,id'],
            'action' => ['required', 'string', 'in:activate,deactivate,transfer,promote,salary_update,delete'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'designation' => ['nullable', 'string', 'max:255'],
            'salary_amount' => ['nullable', 'numeric', 'min:0'],
            'effective_date' => ['nullable', 'date', 'after_or_equal:today'],
            'reason' => ['nullable', 'string', 'max:500'],
            'notify_teachers' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get teacher validation messages
     *
     * @return array
     */
    protected function getTeacherValidationMessages(): array
    {
        return array_merge(
            $this->getCommonValidationMessages(),
            $this->getEmailValidationMessages(),
            $this->getDateRangeValidationMessages(),
            [
                'employee_id.required' => 'Employee ID is required.',
                'employee_id.unique' => 'This employee ID is already taken.',
                'employee_id.max' => 'Employee ID cannot exceed :max characters.',
                
                'password.required' => 'Password is required.',
                'password.min' => 'Password must be at least :min characters.',
                
                'qualification.required' => 'Qualification is required.',
                'qualification.max' => 'Qualification cannot exceed :max characters.',
                
                'experience_years.required' => 'Experience years is required.',
                'experience_years.integer' => 'Experience years must be a valid number.',
                'experience_years.min' => 'Experience years must be at least :min.',
                'experience_years.max' => 'Experience years cannot exceed :max.',
                
                'salary.required' => 'Salary is required.',
                'salary.numeric' => 'Salary must be a valid number.',
                'salary.min' => 'Salary must be at least :min.',
                
                'joining_date.required' => 'Joining date is required.',
                'joining_date.date' => 'Joining date must be a valid date.',
                'joining_date.before_or_equal' => 'Joining date cannot be in the future.',
                
                'date_of_birth.required' => 'Date of birth is required.',
                'date_of_birth.date' => 'Date of birth must be a valid date.',
                'date_of_birth.before' => 'Date of birth must be before today.',
                
                'gender.required' => 'Gender is required.',
                'gender.in' => 'Selected gender is invalid.',
                
                'blood_group.in' => 'Selected blood group is invalid.',
                'marital_status.in' => 'Selected marital status is invalid.',
                'category.in' => 'Selected category is invalid.',
                
                'emergency_contact.required' => 'Emergency contact is required.',
                'emergency_contact_name.required' => 'Emergency contact name is required.',
                'emergency_contact_relation.required' => 'Emergency contact relation is required.',
                
                'department_id.exists' => 'Selected department does not exist.',
                
                'designation.required' => 'Designation is required.',
                'designation.max' => 'Designation cannot exceed :max characters.',
                
                'employment_type.required' => 'Employment type is required.',
                'employment_type.in' => 'Selected employment type is invalid.',
                
                'photo.image' => 'Photo must be an image file.',
                'photo.mimes' => 'Photo must be a JPEG, PNG, or JPG file.',
                'photo.max' => 'Photo size cannot exceed 2MB.',
                
                'subjects.required' => 'At least one subject must be assigned.',
                'subjects.array' => 'Subjects must be a valid list.',
                'subjects.min' => 'At least one subject must be assigned.',
                'subjects.*.exists' => 'One or more selected subjects do not exist.',
                
                'classes.array' => 'Classes must be a valid list.',
                'classes.*.exists' => 'One or more selected classes do not exist.',
                
                'sections.array' => 'Sections must be a valid list.',
                'sections.*.exists' => 'One or more selected sections do not exist.',
                
                'class_teacher_for.exists' => 'Selected class for class teacher does not exist.',
                'section_teacher_for.exists' => 'Selected section for section teacher does not exist.',
                
                'teacher_id.required' => 'Teacher is required.',
                'teacher_id.exists' => 'Selected teacher does not exist.',
                
                'date.required' => 'Date is required.',
                'date.date' => 'Date must be a valid date.',
                'date.before_or_equal' => 'Date cannot be in the future.',
                
                'status.required' => 'Attendance status is required.',
                'status.in' => 'Selected attendance status is invalid.',
                
                'check_in_time.date_format' => 'Check-in time must be in HH:MM format.',
                'check_out_time.date_format' => 'Check-out time must be in HH:MM format.',
                'check_out_time.after' => 'Check-out time must be after check-in time.',
                
                'working_hours.numeric' => 'Working hours must be a valid number.',
                'working_hours.min' => 'Working hours must be at least :min.',
                'working_hours.max' => 'Working hours cannot exceed :max.',
                
                'leave_type.required' => 'Leave type is required.',
                'leave_type.in' => 'Selected leave type is invalid.',
                
                'from_date.required' => 'From date is required.',
                'from_date.date' => 'From date must be a valid date.',
                'from_date.after_or_equal' => 'From date cannot be in the past.',
                
                'to_date.required' => 'To date is required.',
                'to_date.date' => 'To date must be a valid date.',
                'to_date.after_or_equal' => 'To date must be after or equal to from date.',
                
                'days_count.required' => 'Days count is required.',
                'days_count.integer' => 'Days count must be a valid number.',
                'days_count.min' => 'Days count must be at least :min.',
                
                'session.in' => 'Selected session is invalid.',
                
                'substitute_teacher_id.exists' => 'Selected substitute teacher does not exist.',
                
                'medical_certificate.mimes' => 'Medical certificate must be a PDF, JPG, JPEG, or PNG file.',
                'medical_certificate.max' => 'Medical certificate size cannot exceed 2MB.',
                
                'month.required' => 'Month is required.',
                'month.integer' => 'Month must be a valid number.',
                'month.min' => 'Month must be at least :min.',
                'month.max' => 'Month cannot exceed :max.',
                
                'year.required' => 'Year is required.',
                'year.integer' => 'Year must be a valid number.',
                'year.min' => 'Year must be at least :min.',
                'year.max' => 'Year cannot exceed :max.',
                
                'basic_salary.required' => 'Basic salary is required.',
                'basic_salary.numeric' => 'Basic salary must be a valid number.',
                'basic_salary.min' => 'Basic salary must be at least :min.',
                
                'gross_salary.required' => 'Gross salary is required.',
                'gross_salary.numeric' => 'Gross salary must be a valid number.',
                'gross_salary.min' => 'Gross salary must be at least :min.',
                
                'total_deductions.required' => 'Total deductions is required.',
                'total_deductions.numeric' => 'Total deductions must be a valid number.',
                'total_deductions.min' => 'Total deductions must be at least :min.',
                
                'net_salary.required' => 'Net salary is required.',
                'net_salary.numeric' => 'Net salary must be a valid number.',
                'net_salary.min' => 'Net salary must be at least :min.',
                
                'payment_method.in' => 'Selected payment method is invalid.',
                
                'teacher_ids.required' => 'At least one teacher must be selected.',
                'teacher_ids.array' => 'Teacher selection must be a valid list.',
                'teacher_ids.min' => 'At least one teacher must be selected.',
                'teacher_ids.*.exists' => 'One or more selected teachers do not exist.',
                
                'action.required' => 'Action is required.',
                'action.in' => 'Selected action is invalid.',
                
                'effective_date.date' => 'Effective date must be a valid date.',
                'effective_date.after_or_equal' => 'Effective date cannot be in the past.',
            ]
        );
    }
}