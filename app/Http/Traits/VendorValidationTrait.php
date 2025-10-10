<?php

namespace App\Http\Traits;

trait VendorValidationTrait
{
    use EmailValidationTrait, CommonValidationTrait;

    /**
     * Get vendor creation validation rules
     *
     * @return array
     */
    protected function getVendorCreationRules(): array
    {
        return array_merge(
            $this->getNameValidationRules(true, 255),
            $this->getCreateEmailValidationRules('vendors'),
            $this->getPhoneValidationRules(true),
            $this->getAddressValidationRules(),
            [
                'company_name' => ['required', 'string', 'max:255'],
                'vendor_code' => ['nullable', 'string', 'max:50', 'unique:vendors,vendor_code', 'alpha_num'],
                'vendor_type' => ['required', 'string', 'in:supplier,contractor,service_provider,consultant'],
                'business_type' => ['nullable', 'string', 'in:individual,partnership,company,corporation,government'],
                'registration_number' => ['nullable', 'string', 'max:100', 'unique:vendors,registration_number'],
                'tax_number' => ['nullable', 'string', 'max:50', 'unique:vendors,tax_number'],
                'gst_number' => ['nullable', 'string', 'max:15', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/', 'unique:vendors,gst_number'],
                'pan_number' => ['nullable', 'string', 'max:10', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', 'unique:vendors,pan_number'],
                'website' => ['nullable', 'url', 'max:255'],
                'established_date' => ['nullable', 'date', 'before_or_equal:today'],
                'contact_person' => ['required', 'string', 'max:255'],
                'contact_designation' => ['nullable', 'string', 'max:100'],
                'contact_phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s\(\)]+$/'],
                'contact_email' => ['nullable', 'email', 'max:255'],
                'alternate_contact_person' => ['nullable', 'string', 'max:255'],
                'alternate_contact_phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s\(\)]+$/'],
                'alternate_contact_email' => ['nullable', 'email', 'max:255'],
                'bank_name' => ['nullable', 'string', 'max:255'],
                'bank_branch' => ['nullable', 'string', 'max:255'],
                'account_number' => ['nullable', 'string', 'max:50'],
                'ifsc_code' => ['nullable', 'string', 'max:11', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'],
                'account_holder_name' => ['nullable', 'string', 'max:255'],
                'payment_terms' => ['nullable', 'string', 'in:immediate,net_15,net_30,net_45,net_60,net_90,custom'],
                'credit_limit' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
                'credit_days' => ['nullable', 'integer', 'min:0', 'max:365'],
                'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'categories' => ['nullable', 'array'],
                'categories.*' => ['integer', 'exists:vendor_categories,id'],
                'services' => ['nullable', 'array'],
                'services.*' => ['string', 'max:255'],
                'products' => ['nullable', 'array'],
                'products.*' => ['string', 'max:255'],
                'certifications' => ['nullable', 'array'],
                'certifications.*' => ['string', 'max:255'],
                'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
                'notes' => ['nullable', 'string', 'max:2000'],
                'is_active' => ['nullable', 'boolean'],
                'is_verified' => ['nullable', 'boolean'],
                'verification_date' => ['nullable', 'date', 'before_or_equal:today'],
                'verified_by' => ['nullable', 'integer', 'exists:users,id'],
                'contract_start_date' => ['nullable', 'date'],
                'contract_end_date' => ['nullable', 'date', 'after:contract_start_date'],
                'insurance_expiry' => ['nullable', 'date', 'after:today'],
                'license_expiry' => ['nullable', 'date', 'after:today'],
                'documents' => ['nullable', 'array'],
                'documents.*' => ['file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'], // 5MB max
            ]
        );
    }

    /**
     * Get vendor update validation rules
     *
     * @param int $vendorId Vendor ID for unique validation
     * @return array
     */
    protected function getVendorUpdateRules(int $vendorId): array
    {
        return array_merge(
            $this->getNameValidationRules(true, 255),
            $this->getUpdateEmailValidationRules('vendors', $vendorId),
            $this->getPhoneValidationRules(true),
            $this->getAddressValidationRules(),
            [
                'company_name' => ['required', 'string', 'max:255'],
                'vendor_code' => ['nullable', 'string', 'max:50', "unique:vendors,vendor_code,{$vendorId}", 'alpha_num'],
                'vendor_type' => ['required', 'string', 'in:supplier,contractor,service_provider,consultant'],
                'business_type' => ['nullable', 'string', 'in:individual,partnership,company,corporation,government'],
                'registration_number' => ['nullable', 'string', 'max:100', "unique:vendors,registration_number,{$vendorId}"],
                'tax_number' => ['nullable', 'string', 'max:50', "unique:vendors,tax_number,{$vendorId}"],
                'gst_number' => ['nullable', 'string', 'max:15', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/', "unique:vendors,gst_number,{$vendorId}"],
                'pan_number' => ['nullable', 'string', 'max:10', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', "unique:vendors,pan_number,{$vendorId}"],
                'website' => ['nullable', 'url', 'max:255'],
                'established_date' => ['nullable', 'date', 'before_or_equal:today'],
                'contact_person' => ['required', 'string', 'max:255'],
                'contact_designation' => ['nullable', 'string', 'max:100'],
                'contact_phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s\(\)]+$/'],
                'contact_email' => ['nullable', 'email', 'max:255'],
                'alternate_contact_person' => ['nullable', 'string', 'max:255'],
                'alternate_contact_phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s\(\)]+$/'],
                'alternate_contact_email' => ['nullable', 'email', 'max:255'],
                'bank_name' => ['nullable', 'string', 'max:255'],
                'bank_branch' => ['nullable', 'string', 'max:255'],
                'account_number' => ['nullable', 'string', 'max:50'],
                'ifsc_code' => ['nullable', 'string', 'max:11', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'],
                'account_holder_name' => ['nullable', 'string', 'max:255'],
                'payment_terms' => ['nullable', 'string', 'in:immediate,net_15,net_30,net_45,net_60,net_90,custom'],
                'credit_limit' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
                'credit_days' => ['nullable', 'integer', 'min:0', 'max:365'],
                'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'categories' => ['nullable', 'array'],
                'categories.*' => ['integer', 'exists:vendor_categories,id'],
                'services' => ['nullable', 'array'],
                'services.*' => ['string', 'max:255'],
                'products' => ['nullable', 'array'],
                'products.*' => ['string', 'max:255'],
                'certifications' => ['nullable', 'array'],
                'certifications.*' => ['string', 'max:255'],
                'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
                'notes' => ['nullable', 'string', 'max:2000'],
                'is_active' => ['nullable', 'boolean'],
                'is_verified' => ['nullable', 'boolean'],
                'verification_date' => ['nullable', 'date', 'before_or_equal:today'],
                'verified_by' => ['nullable', 'integer', 'exists:users,id'],
                'contract_start_date' => ['nullable', 'date'],
                'contract_end_date' => ['nullable', 'date', 'after:contract_start_date'],
                'insurance_expiry' => ['nullable', 'date', 'after:today'],
                'license_expiry' => ['nullable', 'date', 'after:today'],
                'documents' => ['nullable', 'array'],
                'documents.*' => ['file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'], // 5MB max
            ]
        );
    }

    /**
     * Get vendor evaluation validation rules
     *
     * @return array
     */
    protected function getVendorEvaluationRules(): array
    {
        return [
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'evaluation_date' => ['required', 'date', 'before_or_equal:today'],
            'evaluator_id' => ['required', 'integer', 'exists:users,id'],
            'quality_rating' => ['required', 'numeric', 'min:1', 'max:5'],
            'delivery_rating' => ['required', 'numeric', 'min:1', 'max:5'],
            'service_rating' => ['required', 'numeric', 'min:1', 'max:5'],
            'price_rating' => ['required', 'numeric', 'min:1', 'max:5'],
            'communication_rating' => ['required', 'numeric', 'min:1', 'max:5'],
            'overall_rating' => ['required', 'numeric', 'min:1', 'max:5'],
            'strengths' => ['nullable', 'string', 'max:1000'],
            'weaknesses' => ['nullable', 'string', 'max:1000'],
            'recommendations' => ['nullable', 'string', 'max:1000'],
            'would_recommend' => ['required', 'boolean'],
            'future_business' => ['required', 'string', 'in:yes,no,maybe'],
            'evaluation_period_start' => ['required', 'date'],
            'evaluation_period_end' => ['required', 'date', 'after:evaluation_period_start'],
            'total_orders' => ['nullable', 'integer', 'min:0'],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
            'on_time_deliveries' => ['nullable', 'integer', 'min:0'],
            'quality_issues' => ['nullable', 'integer', 'min:0'],
            'complaints' => ['nullable', 'integer', 'min:0'],
            'compliments' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Get vendor contract validation rules
     *
     * @return array
     */
    protected function getVendorContractRules(): array
    {
        return [
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'contract_number' => ['required', 'string', 'max:100', 'unique:vendor_contracts,contract_number'],
            'contract_type' => ['required', 'string', 'in:supply,service,maintenance,consulting,annual'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'auto_renewal' => ['nullable', 'boolean'],
            'renewal_period' => ['nullable', 'integer', 'min:1', 'max:60'], // months
            'notice_period' => ['nullable', 'integer', 'min:1', 'max:365'], // days
            'contract_value' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'currency' => ['nullable', 'string', 'size:3'],
            'payment_terms' => ['required', 'string', 'in:advance,net_15,net_30,net_45,net_60,net_90,milestone'],
            'penalty_clause' => ['nullable', 'string', 'max:1000'],
            'termination_clause' => ['nullable', 'string', 'max:1000'],
            'sla_terms' => ['nullable', 'string', 'max:2000'],
            'deliverables' => ['nullable', 'array'],
            'deliverables.*' => ['string', 'max:500'],
            'milestones' => ['nullable', 'array'],
            'milestones.*.title' => ['required', 'string', 'max:255'],
            'milestones.*.due_date' => ['required', 'date'],
            'milestones.*.amount' => ['required', 'numeric', 'min:0'],
            'milestones.*.description' => ['nullable', 'string', 'max:500'],
            'terms_conditions' => ['nullable', 'string', 'max:5000'],
            'signed_by_vendor' => ['nullable', 'boolean'],
            'signed_by_company' => ['nullable', 'boolean'],
            'vendor_signatory' => ['nullable', 'string', 'max:255'],
            'company_signatory' => ['nullable', 'string', 'max:255'],
            'vendor_signature_date' => ['nullable', 'date'],
            'company_signature_date' => ['nullable', 'date'],
            'contract_document' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'], // 10MB max
            'status' => ['nullable', 'string', 'in:draft,active,expired,terminated,renewed'],
        ];
    }

    /**
     * Get vendor payment validation rules
     *
     * @return array
     */
    protected function getVendorPaymentRules(): array
    {
        return [
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'invoice_number' => ['required', 'string', 'max:100'],
            'invoice_date' => ['required', 'date', 'before_or_equal:today'],
            'due_date' => ['required', 'date', 'after_or_equal:invoice_date'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'tax_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'discount_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'total_amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'currency' => ['nullable', 'string', 'size:3'],
            'payment_method' => ['required', 'string', 'in:bank_transfer,cheque,cash,online,upi,card'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'payment_date' => ['nullable', 'date', 'before_or_equal:today'],
            'bank_charges' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'tds_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'tds_percentage' => ['nullable', 'numeric', 'min:0', 'max:30'],
            'description' => ['nullable', 'string', 'max:1000'],
            'purchase_order_id' => ['nullable', 'integer', 'exists:purchase_orders,id'],
            'contract_id' => ['nullable', 'integer', 'exists:vendor_contracts,id'],
            'approved_by' => ['nullable', 'integer', 'exists:users,id'],
            'approval_date' => ['nullable', 'date', 'before_or_equal:today'],
            'status' => ['nullable', 'string', 'in:pending,approved,paid,rejected,cancelled'],
            'invoice_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], // 5MB max
            'payment_receipt' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], // 5MB max
        ];
    }

    /**
     * Get vendor bulk operation validation rules
     *
     * @return array
     */
    protected function getVendorBulkRules(): array
    {
        return [
            'vendor_ids' => ['required', 'array', 'min:1'],
            'vendor_ids.*' => ['integer', 'exists:vendors,id'],
            'action' => ['required', 'string', 'in:activate,deactivate,verify,unverify,delete,update_category,update_rating'],
            'category_id' => ['nullable', 'integer', 'exists:vendor_categories,id'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'reason' => ['nullable', 'string', 'max:500'],
            'send_notification' => ['nullable', 'boolean'],
            'effective_date' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }

    /**
     * Get vendor import validation rules
     *
     * @return array
     */
    protected function getVendorImportRules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,xlsx,xls', 'max:10240'], // 10MB max
            'has_header' => ['nullable', 'boolean'],
            'delimiter' => ['nullable', 'string', 'in:comma,semicolon,tab'],
            'encoding' => ['nullable', 'string', 'in:utf-8,iso-8859-1'],
            'default_vendor_type' => ['required', 'string', 'in:supplier,contractor,service_provider,consultant'],
            'default_category_id' => ['nullable', 'integer', 'exists:vendor_categories,id'],
            'skip_duplicates' => ['nullable', 'boolean'],
            'update_existing' => ['nullable', 'boolean'],
            'send_welcome_email' => ['nullable', 'boolean'],
            'auto_verify' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get vendor validation messages
     *
     * @return array
     */
    protected function getVendorValidationMessages(): array
    {
        return array_merge(
            $this->getCommonValidationMessages(),
            $this->getEmailValidationMessages(),
            [
                'company_name.required' => 'Company name is required.',
                'company_name.max' => 'Company name cannot exceed :max characters.',
                
                'vendor_code.unique' => 'This vendor code is already taken.',
                'vendor_code.max' => 'Vendor code cannot exceed :max characters.',
                'vendor_code.alpha_num' => 'Vendor code may only contain letters and numbers.',
                
                'vendor_type.required' => 'Vendor type is required.',
                'vendor_type.in' => 'Selected vendor type is invalid.',
                
                'business_type.in' => 'Selected business type is invalid.',
                
                'registration_number.unique' => 'This registration number is already taken.',
                'registration_number.max' => 'Registration number cannot exceed :max characters.',
                
                'tax_number.unique' => 'This tax number is already taken.',
                'tax_number.max' => 'Tax number cannot exceed :max characters.',
                
                'gst_number.regex' => 'GST number format is invalid.',
                'gst_number.unique' => 'This GST number is already taken.',
                'gst_number.max' => 'GST number cannot exceed :max characters.',
                
                'pan_number.regex' => 'PAN number format is invalid.',
                'pan_number.unique' => 'This PAN number is already taken.',
                'pan_number.max' => 'PAN number cannot exceed :max characters.',
                
                'website.url' => 'Website must be a valid URL.',
                'website.max' => 'Website URL cannot exceed :max characters.',
                
                'established_date.date' => 'Established date must be a valid date.',
                'established_date.before_or_equal' => 'Established date cannot be in the future.',
                
                'contact_person.required' => 'Contact person is required.',
                'contact_person.max' => 'Contact person name cannot exceed :max characters.',
                
                'contact_designation.max' => 'Contact designation cannot exceed :max characters.',
                
                'contact_phone.regex' => 'Contact phone number format is invalid.',
                'contact_phone.max' => 'Contact phone number cannot exceed :max characters.',
                
                'contact_email.email' => 'Contact email must be a valid email address.',
                'contact_email.max' => 'Contact email cannot exceed :max characters.',
                
                'alternate_contact_phone.regex' => 'Alternate contact phone number format is invalid.',
                'alternate_contact_phone.max' => 'Alternate contact phone number cannot exceed :max characters.',
                
                'alternate_contact_email.email' => 'Alternate contact email must be a valid email address.',
                'alternate_contact_email.max' => 'Alternate contact email cannot exceed :max characters.',
                
                'ifsc_code.regex' => 'IFSC code format is invalid.',
                'ifsc_code.max' => 'IFSC code cannot exceed :max characters.',
                
                'payment_terms.in' => 'Selected payment terms are invalid.',
                
                'credit_limit.numeric' => 'Credit limit must be a valid number.',
                'credit_limit.min' => 'Credit limit cannot be negative.',
                'credit_limit.max' => 'Credit limit cannot exceed :max.',
                
                'credit_days.integer' => 'Credit days must be a valid number.',
                'credit_days.min' => 'Credit days cannot be negative.',
                'credit_days.max' => 'Credit days cannot exceed :max.',
                
                'discount_percentage.numeric' => 'Discount percentage must be a valid number.',
                'discount_percentage.min' => 'Discount percentage cannot be negative.',
                'discount_percentage.max' => 'Discount percentage cannot exceed :max%.',
                
                'categories.array' => 'Categories must be a valid list.',
                'categories.*.exists' => 'One or more selected categories do not exist.',
                
                'services.array' => 'Services must be a valid list.',
                'services.*.max' => 'Each service name cannot exceed :max characters.',
                
                'products.array' => 'Products must be a valid list.',
                'products.*.max' => 'Each product name cannot exceed :max characters.',
                
                'certifications.array' => 'Certifications must be a valid list.',
                'certifications.*.max' => 'Each certification cannot exceed :max characters.',
                
                'rating.numeric' => 'Rating must be a valid number.',
                'rating.min' => 'Rating cannot be less than :min.',
                'rating.max' => 'Rating cannot exceed :max.',
                
                'notes.max' => 'Notes cannot exceed :max characters.',
                
                'verification_date.date' => 'Verification date must be a valid date.',
                'verification_date.before_or_equal' => 'Verification date cannot be in the future.',
                
                'verified_by.exists' => 'Selected verifier does not exist.',
                
                'contract_start_date.date' => 'Contract start date must be a valid date.',
                
                'contract_end_date.date' => 'Contract end date must be a valid date.',
                'contract_end_date.after' => 'Contract end date must be after start date.',
                
                'insurance_expiry.date' => 'Insurance expiry must be a valid date.',
                'insurance_expiry.after' => 'Insurance expiry must be in the future.',
                
                'license_expiry.date' => 'License expiry must be a valid date.',
                'license_expiry.after' => 'License expiry must be in the future.',
                
                'documents.array' => 'Documents must be a valid list.',
                'documents.*.file' => 'Each document must be a valid file.',
                'documents.*.mimes' => 'Each document must be a PDF, DOC, DOCX, JPG, JPEG, or PNG file.',
                'documents.*.max' => 'Each document cannot exceed 5MB.',
                
                // Evaluation messages
                'vendor_id.required' => 'Vendor is required.',
                'vendor_id.exists' => 'Selected vendor does not exist.',
                
                'evaluation_date.required' => 'Evaluation date is required.',
                'evaluation_date.date' => 'Evaluation date must be a valid date.',
                'evaluation_date.before_or_equal' => 'Evaluation date cannot be in the future.',
                
                'evaluator_id.required' => 'Evaluator is required.',
                'evaluator_id.exists' => 'Selected evaluator does not exist.',
                
                'quality_rating.required' => 'Quality rating is required.',
                'quality_rating.numeric' => 'Quality rating must be a valid number.',
                'quality_rating.min' => 'Quality rating must be at least :min.',
                'quality_rating.max' => 'Quality rating cannot exceed :max.',
                
                'delivery_rating.required' => 'Delivery rating is required.',
                'delivery_rating.numeric' => 'Delivery rating must be a valid number.',
                'delivery_rating.min' => 'Delivery rating must be at least :min.',
                'delivery_rating.max' => 'Delivery rating cannot exceed :max.',
                
                'service_rating.required' => 'Service rating is required.',
                'service_rating.numeric' => 'Service rating must be a valid number.',
                'service_rating.min' => 'Service rating must be at least :min.',
                'service_rating.max' => 'Service rating cannot exceed :max.',
                
                'price_rating.required' => 'Price rating is required.',
                'price_rating.numeric' => 'Price rating must be a valid number.',
                'price_rating.min' => 'Price rating must be at least :min.',
                'price_rating.max' => 'Price rating cannot exceed :max.',
                
                'communication_rating.required' => 'Communication rating is required.',
                'communication_rating.numeric' => 'Communication rating must be a valid number.',
                'communication_rating.min' => 'Communication rating must be at least :min.',
                'communication_rating.max' => 'Communication rating cannot exceed :max.',
                
                'overall_rating.required' => 'Overall rating is required.',
                'overall_rating.numeric' => 'Overall rating must be a valid number.',
                'overall_rating.min' => 'Overall rating must be at least :min.',
                'overall_rating.max' => 'Overall rating cannot exceed :max.',
                
                'would_recommend.required' => 'Recommendation status is required.',
                
                'future_business.required' => 'Future business preference is required.',
                'future_business.in' => 'Selected future business preference is invalid.',
                
                'evaluation_period_start.required' => 'Evaluation period start date is required.',
                'evaluation_period_start.date' => 'Evaluation period start date must be a valid date.',
                
                'evaluation_period_end.required' => 'Evaluation period end date is required.',
                'evaluation_period_end.date' => 'Evaluation period end date must be a valid date.',
                'evaluation_period_end.after' => 'Evaluation period end date must be after start date.',
                
                // Contract messages
                'contract_number.required' => 'Contract number is required.',
                'contract_number.unique' => 'This contract number is already taken.',
                'contract_number.max' => 'Contract number cannot exceed :max characters.',
                
                'contract_type.required' => 'Contract type is required.',
                'contract_type.in' => 'Selected contract type is invalid.',
                
                'title.required' => 'Contract title is required.',
                'title.max' => 'Contract title cannot exceed :max characters.',
                
                'start_date.required' => 'Contract start date is required.',
                'start_date.date' => 'Contract start date must be a valid date.',
                
                'end_date.required' => 'Contract end date is required.',
                'end_date.date' => 'Contract end date must be a valid date.',
                'end_date.after' => 'Contract end date must be after start date.',
                
                'contract_value.required' => 'Contract value is required.',
                'contract_value.numeric' => 'Contract value must be a valid number.',
                'contract_value.min' => 'Contract value cannot be negative.',
                'contract_value.max' => 'Contract value cannot exceed :max.',
                
                'currency.size' => 'Currency code must be exactly :size characters.',
                
                'payment_terms.required' => 'Payment terms are required.',
                
                'renewal_period.integer' => 'Renewal period must be a valid number.',
                'renewal_period.min' => 'Renewal period must be at least :min month(s).',
                'renewal_period.max' => 'Renewal period cannot exceed :max months.',
                
                'notice_period.integer' => 'Notice period must be a valid number.',
                'notice_period.min' => 'Notice period must be at least :min day(s).',
                'notice_period.max' => 'Notice period cannot exceed :max days.',
                
                'contract_document.file' => 'Contract document must be a valid file.',
                'contract_document.mimes' => 'Contract document must be a PDF, DOC, or DOCX file.',
                'contract_document.max' => 'Contract document cannot exceed 10MB.',
                
                'status.in' => 'Selected contract status is invalid.',
                
                // Payment messages
                'invoice_number.required' => 'Invoice number is required.',
                'invoice_number.max' => 'Invoice number cannot exceed :max characters.',
                
                'invoice_date.required' => 'Invoice date is required.',
                'invoice_date.date' => 'Invoice date must be a valid date.',
                'invoice_date.before_or_equal' => 'Invoice date cannot be in the future.',
                
                'due_date.required' => 'Due date is required.',
                'due_date.date' => 'Due date must be a valid date.',
                'due_date.after_or_equal' => 'Due date must be on or after invoice date.',
                
                'amount.required' => 'Amount is required.',
                'amount.numeric' => 'Amount must be a valid number.',
                'amount.min' => 'Amount must be greater than :min.',
                'amount.max' => 'Amount cannot exceed :max.',
                
                'total_amount.required' => 'Total amount is required.',
                'total_amount.numeric' => 'Total amount must be a valid number.',
                'total_amount.min' => 'Total amount must be greater than :min.',
                'total_amount.max' => 'Total amount cannot exceed :max.',
                
                'payment_method.required' => 'Payment method is required.',
                'payment_method.in' => 'Selected payment method is invalid.',
                
                'payment_date.date' => 'Payment date must be a valid date.',
                'payment_date.before_or_equal' => 'Payment date cannot be in the future.',
                
                'tds_percentage.numeric' => 'TDS percentage must be a valid number.',
                'tds_percentage.min' => 'TDS percentage cannot be negative.',
                'tds_percentage.max' => 'TDS percentage cannot exceed :max%.',
                
                'purchase_order_id.exists' => 'Selected purchase order does not exist.',
                'contract_id.exists' => 'Selected contract does not exist.',
                'approved_by.exists' => 'Selected approver does not exist.',
                
                'approval_date.date' => 'Approval date must be a valid date.',
                'approval_date.before_or_equal' => 'Approval date cannot be in the future.',
                
                'invoice_document.file' => 'Invoice document must be a valid file.',
                'invoice_document.mimes' => 'Invoice document must be a PDF, JPG, JPEG, or PNG file.',
                'invoice_document.max' => 'Invoice document cannot exceed 5MB.',
                
                'payment_receipt.file' => 'Payment receipt must be a valid file.',
                'payment_receipt.mimes' => 'Payment receipt must be a PDF, JPG, JPEG, or PNG file.',
                'payment_receipt.max' => 'Payment receipt cannot exceed 5MB.',
                
                // Bulk operation messages
                'vendor_ids.required' => 'At least one vendor must be selected.',
                'vendor_ids.array' => 'Vendor selection must be a valid list.',
                'vendor_ids.min' => 'At least one vendor must be selected.',
                'vendor_ids.*.exists' => 'One or more selected vendors do not exist.',
                
                'action.required' => 'Action is required.',
                'action.in' => 'Selected action is invalid.',
                
                'category_id.exists' => 'Selected category does not exist.',
                
                'effective_date.date' => 'Effective date must be a valid date.',
                'effective_date.after_or_equal' => 'Effective date cannot be in the past.',
                
                // Import messages
                'file.required' => 'File is required.',
                'file.file' => 'Uploaded file is invalid.',
                'file.mimes' => 'File must be a CSV or Excel file.',
                'file.max' => 'File size cannot exceed 10MB.',
                
                'delimiter.in' => 'Selected delimiter is invalid.',
                'encoding.in' => 'Selected encoding is invalid.',
                
                'default_vendor_type.required' => 'Default vendor type is required.',
                'default_vendor_type.in' => 'Selected default vendor type is invalid.',
                
                'default_category_id.exists' => 'Selected default category does not exist.',
            ]
        );
    }
}