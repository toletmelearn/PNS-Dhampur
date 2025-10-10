<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class EnhancedFeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && (
            auth()->user()->hasAnyRole(['admin', 'principal', 'accountant']) ||
            auth()->user()->can('manage-fees')
        );
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $feeId = $this->route('fee') ? $this->route('fee')->id : null;
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');
        
        return [
            // Student Information
            'student_id' => [
                'required',
                'integer',
                'exists:students,id',
                function ($attribute, $value, $fail) {
                    $student = \App\Models\Student::find($value);
                    if ($student && $student->status !== 'active') {
                        $fail('Fee can only be assigned to active students.');
                    }
                }
            ],
            
            // Fee Structure Information
            'fee_type' => [
                'required',
                'string',
                'in:tuition,admission,examination,library,laboratory,sports,transport,hostel,development,miscellaneous',
            ],
            'fee_category' => [
                'nullable',
                'string',
                'in:mandatory,optional,penalty,refund',
            ],
            
            // Amount Information
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                'max:999999.99',
                'regex:/^\d+(\.\d{1,2})?$/',
                function ($attribute, $value, $fail) {
                    // Validate amount based on fee type
                    $feeType = $this->input('fee_type');
                    $maxAmounts = [
                        'tuition' => 100000,
                        'admission' => 50000,
                        'examination' => 5000,
                        'library' => 2000,
                        'laboratory' => 5000,
                        'sports' => 3000,
                        'transport' => 20000,
                        'hostel' => 50000,
                        'development' => 10000,
                        'miscellaneous' => 10000,
                    ];
                    
                    if (isset($maxAmounts[$feeType]) && $value > $maxAmounts[$feeType]) {
                        $fail("The {$attribute} for {$feeType} fee cannot exceed ₹" . number_format($maxAmounts[$feeType]));
                    }
                }
            ],
            'discount' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'discount_type' => [
                'nullable',
                'string',
                'in:percentage,fixed',
                'required_with:discount',
            ],
            'discount_reason' => [
                'nullable',
                'string',
                'max:200',
                'required_with:discount',
            ],
            'late_fee' => [
                'nullable',
                'numeric',
                'min:0',
                'max:10000',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'tax_amount' => [
                'nullable',
                'numeric',
                'min:0',
                'max:99999.99',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'tax_percentage' => [
                'nullable',
                'numeric',
                'min:0',
                'max:30',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            
            // Date Information
            'due_date' => [
                'required',
                'date',
                'after:today',
                'before:' . Carbon::now()->addYear()->format('Y-m-d'),
                function ($attribute, $value, $fail) {
                    $dueDate = Carbon::parse($value);
                    $today = Carbon::now();
                    
                    // Due date should not be more than 1 year in future
                    if ($dueDate->diffInDays($today) > 365) {
                        $fail('Due date cannot be more than 1 year in the future.');
                    }
                    
                    // Due date should be at least 7 days from today for new fees
                    if (!$this->isMethod('PUT') && !$this->isMethod('PATCH') && $dueDate->diffInDays($today) < 7) {
                        $fail('Due date should be at least 7 days from today.');
                    }
                }
            ],
            'issue_date' => [
                'nullable',
                'date',
                'before_or_equal:today',
                'after:' . Carbon::now()->subYear()->format('Y-m-d'),
            ],
            
            // Academic Information
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
            'term' => [
                'nullable',
                'string',
                'in:annual,semester1,semester2,quarter1,quarter2,quarter3,quarter4,monthly',
            ],
            'month' => [
                'nullable',
                'integer',
                'min:1',
                'max:12',
                'required_if:term,monthly',
            ],
            'class_id' => [
                'nullable',
                'integer',
                'exists:class_models,id',
                function ($attribute, $value, $fail) {
                    $studentId = $this->input('student_id');
                    if ($studentId && $value) {
                        $student = \App\Models\Student::find($studentId);
                        if ($student && $student->class_id != $value) {
                            $fail('The selected class does not match the student\'s current class.');
                        }
                    }
                }
            ],
            
            // Payment Information
            'payment_method' => [
                'nullable',
                'string',
                'in:cash,cheque,dd,online,upi,card,bank_transfer',
            ],
            'transaction_id' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[A-Za-z0-9\-_]+$/',
                Rule::unique('fee_payments', 'transaction_id')->ignore($feeId),
            ],
            'reference_number' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9\-_]+$/',
            ],
            
            // Status Information
            'status' => [
                'required',
                'string',
                'in:pending,paid,partially_paid,overdue,cancelled,refunded',
            ],
            'payment_status' => [
                'nullable',
                'string',
                'in:pending,processing,completed,failed,cancelled,refunded',
            ],
            
            // Additional Information
            'description' => [
                'nullable',
                'string',
                'max:500',
                function ($attribute, $value, $fail) {
                    if ($value && preg_match('/[<>"\'\&\(\){}]/', $value)) {
                        $fail('The ' . $attribute . ' contains invalid characters.');
                    }
                }
            ],
            'remarks' => [
                'nullable',
                'string',
                'max:1000',
                function ($attribute, $value, $fail) {
                    if ($value && preg_match('/[<>"\'\&\(\){}]/', $value)) {
                        $fail('The ' . $attribute . ' contains invalid characters.');
                    }
                }
            ],
            'internal_notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
            
            // Installment Information
            'is_installment' => 'nullable|boolean',
            'installment_count' => [
                'nullable',
                'integer',
                'min:2',
                'max:12',
                'required_if:is_installment,true',
            ],
            'installment_amount' => [
                'nullable',
                'numeric',
                'min:0.01',
                'regex:/^\d+(\.\d{1,2})?$/',
                'required_if:is_installment,true',
            ],
            
            // Concession Information
            'concession_type' => [
                'nullable',
                'string',
                'in:merit,financial,sports,cultural,staff_ward,sibling,disability,other',
            ],
            'concession_amount' => [
                'nullable',
                'numeric',
                'min:0',
                'regex:/^\d+(\.\d{1,2})?$/',
                'required_with:concession_type',
            ],
            'concession_percentage' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'concession_approved_by' => [
                'nullable',
                'integer',
                'exists:users,id',
                'required_with:concession_type',
            ],
            
            // Notification Settings
            'send_sms_reminder' => 'nullable|boolean',
            'send_email_reminder' => 'nullable|boolean',
            'reminder_days_before' => [
                'nullable',
                'integer',
                'min:1',
                'max:30',
            ],
            
            // Bulk Operations
            'apply_to_class' => 'nullable|boolean',
            'apply_to_all_students' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'student_id.required' => 'Student selection is required.',
            'student_id.exists' => 'Selected student does not exist.',
            'fee_type.required' => 'Fee type is required.',
            'fee_type.in' => 'Please select a valid fee type.',
            'amount.required' => 'Fee amount is required.',
            'amount.min' => 'Fee amount must be greater than zero.',
            'amount.max' => 'Fee amount cannot exceed ₹9,99,999.99.',
            'amount.regex' => 'Fee amount must be a valid monetary value with up to 2 decimal places.',
            'discount.max' => 'Discount cannot exceed 100%.',
            'discount_type.required_with' => 'Discount type is required when discount is provided.',
            'discount_reason.required_with' => 'Discount reason is required when discount is provided.',
            'late_fee.max' => 'Late fee cannot exceed ₹10,000.',
            'due_date.required' => 'Due date is required.',
            'due_date.after' => 'Due date must be in the future.',
            'due_date.before' => 'Due date cannot be more than 1 year in the future.',
            'issue_date.before_or_equal' => 'Issue date cannot be in the future.',
            'issue_date.after' => 'Issue date cannot be more than 1 year in the past.',
            'academic_year.required' => 'Academic year is required.',
            'academic_year.regex' => 'Academic year must be in format YYYY-YYYY.',
            'month.required_if' => 'Month is required for monthly fee terms.',
            'month.min' => 'Month must be between 1 and 12.',
            'month.max' => 'Month must be between 1 and 12.',
            'transaction_id.unique' => 'This transaction ID already exists.',
            'transaction_id.regex' => 'Transaction ID can only contain letters, numbers, hyphens, and underscores.',
            'status.required' => 'Fee status is required.',
            'status.in' => 'Please select a valid fee status.',
            'installment_count.required_if' => 'Installment count is required for installment fees.',
            'installment_count.min' => 'Minimum 2 installments are required.',
            'installment_count.max' => 'Maximum 12 installments are allowed.',
            'installment_amount.required_if' => 'Installment amount is required for installment fees.',
            'concession_amount.required_with' => 'Concession amount is required when concession type is selected.',
            'concession_approved_by.required_with' => 'Concession approval is required when concession type is selected.',
            'concession_approved_by.exists' => 'Selected approver does not exist.',
            'reminder_days_before.min' => 'Reminder days must be at least 1.',
            'reminder_days_before.max' => 'Reminder days cannot exceed 30.',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'student_id' => 'student',
            'fee_type' => 'fee type',
            'fee_category' => 'fee category',
            'discount_type' => 'discount type',
            'discount_reason' => 'discount reason',
            'late_fee' => 'late fee',
            'tax_amount' => 'tax amount',
            'tax_percentage' => 'tax percentage',
            'due_date' => 'due date',
            'issue_date' => 'issue date',
            'academic_year' => 'academic year',
            'class_id' => 'class',
            'payment_method' => 'payment method',
            'transaction_id' => 'transaction ID',
            'reference_number' => 'reference number',
            'payment_status' => 'payment status',
            'internal_notes' => 'internal notes',
            'is_installment' => 'installment option',
            'installment_count' => 'number of installments',
            'installment_amount' => 'installment amount',
            'concession_type' => 'concession type',
            'concession_amount' => 'concession amount',
            'concession_percentage' => 'concession percentage',
            'concession_approved_by' => 'concession approver',
            'send_sms_reminder' => 'SMS reminder',
            'send_email_reminder' => 'email reminder',
            'reminder_days_before' => 'reminder days',
            'apply_to_class' => 'apply to class',
            'apply_to_all_students' => 'apply to all students',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Additional custom validation logic
            $this->validateDiscountConsistency($validator);
            $this->validateInstallmentConsistency($validator);
            $this->validateConcessionConsistency($validator);
            $this->validateDuplicateFee($validator);
            $this->validateTaxConsistency($validator);
        });
    }

    /**
     * Validate discount consistency.
     */
    private function validateDiscountConsistency($validator): void
    {
        $discount = $this->input('discount');
        $discountType = $this->input('discount_type');
        $amount = $this->input('amount');

        if ($discount && $discountType && $amount) {
            if ($discountType === 'fixed' && $discount > $amount) {
                $validator->errors()->add('discount', 'Fixed discount cannot exceed the fee amount.');
            }
            
            if ($discountType === 'percentage' && $discount > 100) {
                $validator->errors()->add('discount', 'Percentage discount cannot exceed 100%.');
            }
        }
    }

    /**
     * Validate installment consistency.
     */
    private function validateInstallmentConsistency($validator): void
    {
        $isInstallment = $this->input('is_installment');
        $installmentCount = $this->input('installment_count');
        $installmentAmount = $this->input('installment_amount');
        $amount = $this->input('amount');

        if ($isInstallment && $installmentCount && $installmentAmount && $amount) {
            $totalInstallmentAmount = $installmentCount * $installmentAmount;
            $tolerance = 0.01; // Allow 1 paisa difference for rounding
            
            if (abs($totalInstallmentAmount - $amount) > $tolerance) {
                $validator->errors()->add('installment_amount', 'Total installment amount must equal the fee amount.');
            }
        }
    }

    /**
     * Validate concession consistency.
     */
    private function validateConcessionConsistency($validator): void
    {
        $concessionAmount = $this->input('concession_amount');
        $concessionPercentage = $this->input('concession_percentage');
        $amount = $this->input('amount');

        if ($concessionAmount && $concessionPercentage && $amount) {
            $calculatedConcession = ($amount * $concessionPercentage) / 100;
            $tolerance = 0.01; // Allow 1 paisa difference for rounding
            
            if (abs($calculatedConcession - $concessionAmount) > $tolerance) {
                $validator->errors()->add('concession_amount', 'Concession amount does not match the calculated percentage.');
            }
        }

        if ($concessionAmount && $amount && $concessionAmount > $amount) {
            $validator->errors()->add('concession_amount', 'Concession amount cannot exceed the fee amount.');
        }
    }

    /**
     * Validate duplicate fee.
     */
    private function validateDuplicateFee($validator): void
    {
        $studentId = $this->input('student_id');
        $feeType = $this->input('fee_type');
        $academicYear = $this->input('academic_year');
        $term = $this->input('term');
        $month = $this->input('month');
        
        if ($studentId && $feeType && $academicYear) {
            $query = \App\Models\Fee::where('student_id', $studentId)
                ->where('fee_type', $feeType)
                ->where('academic_year', $academicYear);
                
            if ($term) {
                $query->where('term', $term);
            }
            
            if ($month) {
                $query->where('month', $month);
            }
            
            // Exclude current record if updating
            $feeId = $this->route('fee') ? $this->route('fee')->id : null;
            if ($feeId) {
                $query->where('id', '!=', $feeId);
            }
            
            if ($query->exists()) {
                $validator->errors()->add('fee_type', 'A fee of this type already exists for the selected student and period.');
            }
        }
    }

    /**
     * Validate tax consistency.
     */
    private function validateTaxConsistency($validator): void
    {
        $taxAmount = $this->input('tax_amount');
        $taxPercentage = $this->input('tax_percentage');
        $amount = $this->input('amount');

        if ($taxAmount && $taxPercentage && $amount) {
            $calculatedTax = ($amount * $taxPercentage) / 100;
            $tolerance = 0.01; // Allow 1 paisa difference for rounding
            
            if (abs($calculatedTax - $taxAmount) > $tolerance) {
                $validator->errors()->add('tax_amount', 'Tax amount does not match the calculated percentage.');
            }
        }
    }
}