<?php

namespace App\Http\Traits;

trait AssetValidationTrait
{
    use CommonValidationTrait;

    /**
     * Get asset creation validation rules
     *
     * @return array
     */
    protected function getAssetCreationRules(): array
    {
        return array_merge(
            $this->getNameValidationRules(true, 255),
            [
                'asset_code' => ['required', 'string', 'max:50', 'unique:assets,asset_code', 'alpha_num'],
                'asset_tag' => ['nullable', 'string', 'max:100', 'unique:assets,asset_tag'],
                'category_id' => ['required', 'integer', 'exists:asset_categories,id'],
                'subcategory_id' => ['nullable', 'integer', 'exists:asset_subcategories,id'],
                'brand' => ['nullable', 'string', 'max:100'],
                'model' => ['nullable', 'string', 'max:100'],
                'serial_number' => ['nullable', 'string', 'max:100', 'unique:assets,serial_number'],
                'manufacturer' => ['nullable', 'string', 'max:100'],
                'supplier_id' => ['nullable', 'integer', 'exists:vendors,id'],
                'purchase_date' => ['nullable', 'date', 'before_or_equal:today'],
                'purchase_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
                'current_value' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
                'depreciation_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'depreciation_method' => ['nullable', 'string', 'in:straight_line,declining_balance,sum_of_years'],
                'useful_life_years' => ['nullable', 'integer', 'min:1', 'max:100'],
                'warranty_start_date' => ['nullable', 'date'],
                'warranty_end_date' => ['nullable', 'date', 'after:warranty_start_date'],
                'warranty_provider' => ['nullable', 'string', 'max:255'],
                'warranty_terms' => ['nullable', 'string', 'max:1000'],
                'location_id' => ['nullable', 'integer', 'exists:locations,id'],
                'department_id' => ['nullable', 'integer', 'exists:departments,id'],
                'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
                'assigned_date' => ['nullable', 'date', 'before_or_equal:today'],
                'condition' => ['required', 'string', 'in:excellent,good,fair,poor,damaged,obsolete'],
                'status' => ['required', 'string', 'in:available,assigned,in_use,maintenance,repair,disposed,lost,stolen'],
                'criticality' => ['nullable', 'string', 'in:low,medium,high,critical'],
                'maintenance_schedule' => ['nullable', 'string', 'in:daily,weekly,monthly,quarterly,semi_annual,annual,as_needed'],
                'next_maintenance_date' => ['nullable', 'date', 'after:today'],
                'last_maintenance_date' => ['nullable', 'date', 'before_or_equal:today'],
                'maintenance_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
                'insurance_policy_number' => ['nullable', 'string', 'max:100'],
                'insurance_provider' => ['nullable', 'string', 'max:255'],
                'insurance_expiry_date' => ['nullable', 'date', 'after:today'],
                'insurance_value' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
                'specifications' => ['nullable', 'array'],
                'specifications.*.key' => ['required', 'string', 'max:100'],
                'specifications.*.value' => ['required', 'string', 'max:500'],
                'accessories' => ['nullable', 'array'],
                'accessories.*' => ['string', 'max:255'],
                'notes' => ['nullable', 'string', 'max:2000'],
                'qr_code' => ['nullable', 'string', 'max:255', 'unique:assets,qr_code'],
                'barcode' => ['nullable', 'string', 'max:255', 'unique:assets,barcode'],
                'rfid_tag' => ['nullable', 'string', 'max:100', 'unique:assets,rfid_tag'],
                'disposal_date' => ['nullable', 'date'],
                'disposal_method' => ['nullable', 'string', 'in:sold,donated,recycled,destroyed,returned'],
                'disposal_value' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
                'disposal_reason' => ['nullable', 'string', 'max:500'],
                'is_active' => ['nullable', 'boolean'],
                'is_trackable' => ['nullable', 'boolean'],
                'requires_checkout' => ['nullable', 'boolean'],
                'images' => ['nullable', 'array'],
                'images.*' => ['image', 'mimes:jpeg,png,jpg', 'max:2048'], // 2MB max per image
                'documents' => ['nullable', 'array'],
                'documents.*' => ['file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'], // 5MB max per document
            ]
        );
    }

    /**
     * Get asset update validation rules
     *
     * @param int $assetId Asset ID for unique validation
     * @return array
     */
    protected function getAssetUpdateRules(int $assetId): array
    {
        return array_merge(
            $this->getNameValidationRules(true, 255),
            [
                'asset_code' => ['required', 'string', 'max:50', "unique:assets,asset_code,{$assetId}", 'alpha_num'],
                'asset_tag' => ['nullable', 'string', 'max:100', "unique:assets,asset_tag,{$assetId}"],
                'category_id' => ['required', 'integer', 'exists:asset_categories,id'],
                'subcategory_id' => ['nullable', 'integer', 'exists:asset_subcategories,id'],
                'brand' => ['nullable', 'string', 'max:100'],
                'model' => ['nullable', 'string', 'max:100'],
                'serial_number' => ['nullable', 'string', 'max:100', "unique:assets,serial_number,{$assetId}"],
                'manufacturer' => ['nullable', 'string', 'max:100'],
                'supplier_id' => ['nullable', 'integer', 'exists:vendors,id'],
                'purchase_date' => ['nullable', 'date', 'before_or_equal:today'],
                'purchase_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
                'current_value' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
                'depreciation_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'depreciation_method' => ['nullable', 'string', 'in:straight_line,declining_balance,sum_of_years'],
                'useful_life_years' => ['nullable', 'integer', 'min:1', 'max:100'],
                'warranty_start_date' => ['nullable', 'date'],
                'warranty_end_date' => ['nullable', 'date', 'after:warranty_start_date'],
                'warranty_provider' => ['nullable', 'string', 'max:255'],
                'warranty_terms' => ['nullable', 'string', 'max:1000'],
                'location_id' => ['nullable', 'integer', 'exists:locations,id'],
                'department_id' => ['nullable', 'integer', 'exists:departments,id'],
                'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
                'assigned_date' => ['nullable', 'date', 'before_or_equal:today'],
                'condition' => ['required', 'string', 'in:excellent,good,fair,poor,damaged,obsolete'],
                'status' => ['required', 'string', 'in:available,assigned,in_use,maintenance,repair,disposed,lost,stolen'],
                'criticality' => ['nullable', 'string', 'in:low,medium,high,critical'],
                'maintenance_schedule' => ['nullable', 'string', 'in:daily,weekly,monthly,quarterly,semi_annual,annual,as_needed'],
                'next_maintenance_date' => ['nullable', 'date', 'after:today'],
                'last_maintenance_date' => ['nullable', 'date', 'before_or_equal:today'],
                'maintenance_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
                'insurance_policy_number' => ['nullable', 'string', 'max:100'],
                'insurance_provider' => ['nullable', 'string', 'max:255'],
                'insurance_expiry_date' => ['nullable', 'date', 'after:today'],
                'insurance_value' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
                'specifications' => ['nullable', 'array'],
                'specifications.*.key' => ['required', 'string', 'max:100'],
                'specifications.*.value' => ['required', 'string', 'max:500'],
                'accessories' => ['nullable', 'array'],
                'accessories.*' => ['string', 'max:255'],
                'notes' => ['nullable', 'string', 'max:2000'],
                'qr_code' => ['nullable', 'string', 'max:255', "unique:assets,qr_code,{$assetId}"],
                'barcode' => ['nullable', 'string', 'max:255', "unique:assets,barcode,{$assetId}"],
                'rfid_tag' => ['nullable', 'string', 'max:100', "unique:assets,rfid_tag,{$assetId}"],
                'disposal_date' => ['nullable', 'date'],
                'disposal_method' => ['nullable', 'string', 'in:sold,donated,recycled,destroyed,returned'],
                'disposal_value' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
                'disposal_reason' => ['nullable', 'string', 'max:500'],
                'is_active' => ['nullable', 'boolean'],
                'is_trackable' => ['nullable', 'boolean'],
                'requires_checkout' => ['nullable', 'boolean'],
                'images' => ['nullable', 'array'],
                'images.*' => ['image', 'mimes:jpeg,png,jpg', 'max:2048'], // 2MB max per image
                'documents' => ['nullable', 'array'],
                'documents.*' => ['file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'], // 5MB max per document
            ]
        );
    }

    /**
     * Get asset assignment validation rules
     *
     * @return array
     */
    protected function getAssetAssignmentRules(): array
    {
        return [
            'asset_id' => ['required', 'integer', 'exists:assets,id'],
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
            'assigned_by' => ['nullable', 'integer', 'exists:users,id'],
            'assignment_date' => ['required', 'date', 'before_or_equal:today'],
            'expected_return_date' => ['nullable', 'date', 'after:assignment_date'],
            'assignment_type' => ['required', 'string', 'in:permanent,temporary,loan'],
            'purpose' => ['nullable', 'string', 'max:500'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'condition_at_assignment' => ['required', 'string', 'in:excellent,good,fair,poor,damaged'],
            'accessories_included' => ['nullable', 'array'],
            'accessories_included.*' => ['string', 'max:255'],
            'terms_conditions' => ['nullable', 'string', 'max:2000'],
            'user_acknowledgment' => ['nullable', 'boolean'],
            'acknowledgment_date' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'checkout_signature' => ['nullable', 'string'],
            'checkout_photos' => ['nullable', 'array'],
            'checkout_photos.*' => ['image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ];
    }

    /**
     * Get asset return validation rules
     *
     * @return array
     */
    protected function getAssetReturnRules(): array
    {
        return [
            'assignment_id' => ['required', 'integer', 'exists:asset_assignments,id'],
            'return_date' => ['required', 'date', 'before_or_equal:today'],
            'returned_by' => ['nullable', 'integer', 'exists:users,id'],
            'received_by' => ['required', 'integer', 'exists:users,id'],
            'condition_at_return' => ['required', 'string', 'in:excellent,good,fair,poor,damaged'],
            'accessories_returned' => ['nullable', 'array'],
            'accessories_returned.*' => ['string', 'max:255'],
            'missing_accessories' => ['nullable', 'array'],
            'missing_accessories.*' => ['string', 'max:255'],
            'damage_reported' => ['nullable', 'boolean'],
            'damage_description' => ['nullable', 'string', 'max:1000'],
            'damage_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'late_return' => ['nullable', 'boolean'],
            'late_return_reason' => ['nullable', 'string', 'max:500'],
            'penalty_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'user_satisfaction' => ['nullable', 'integer', 'min:1', 'max:5'],
            'feedback' => ['nullable', 'string', 'max:1000'],
            'return_notes' => ['nullable', 'string', 'max:1000'],
            'return_signature' => ['nullable', 'string'],
            'return_photos' => ['nullable', 'array'],
            'return_photos.*' => ['image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ];
    }

    /**
     * Get asset maintenance validation rules
     *
     * @return array
     */
    protected function getAssetMaintenanceRules(): array
    {
        return [
            'asset_id' => ['required', 'integer', 'exists:assets,id'],
            'maintenance_type' => ['required', 'string', 'in:preventive,corrective,emergency,upgrade,inspection'],
            'scheduled_date' => ['required', 'date'],
            'completed_date' => ['nullable', 'date', 'after_or_equal:scheduled_date'],
            'technician_id' => ['nullable', 'integer', 'exists:users,id'],
            'vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
            'description' => ['required', 'string', 'max:1000'],
            'work_performed' => ['nullable', 'string', 'max:2000'],
            'parts_used' => ['nullable', 'array'],
            'parts_used.*.name' => ['required', 'string', 'max:255'],
            'parts_used.*.quantity' => ['required', 'integer', 'min:1'],
            'parts_used.*.cost' => ['required', 'numeric', 'min:0'],
            'labor_hours' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'labor_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'parts_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'total_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'priority' => ['required', 'string', 'in:low,medium,high,urgent'],
            'status' => ['required', 'string', 'in:scheduled,in_progress,completed,cancelled,postponed'],
            'downtime_hours' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'next_maintenance_date' => ['nullable', 'date', 'after:completed_date'],
            'warranty_work' => ['nullable', 'boolean'],
            'warranty_claim_number' => ['nullable', 'string', 'max:100'],
            'before_condition' => ['nullable', 'string', 'in:excellent,good,fair,poor,damaged'],
            'after_condition' => ['nullable', 'string', 'in:excellent,good,fair,poor,damaged'],
            'issues_found' => ['nullable', 'string', 'max:2000'],
            'recommendations' => ['nullable', 'string', 'max:2000'],
            'safety_notes' => ['nullable', 'string', 'max:1000'],
            'completion_notes' => ['nullable', 'string', 'max:1000'],
            'customer_signature' => ['nullable', 'string'],
            'technician_signature' => ['nullable', 'string'],
            'maintenance_photos' => ['nullable', 'array'],
            'maintenance_photos.*' => ['image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'maintenance_documents' => ['nullable', 'array'],
            'maintenance_documents.*' => ['file', 'mimes:pdf,doc,docx', 'max:5120'],
        ];
    }

    /**
     * Get asset audit validation rules
     *
     * @return array
     */
    protected function getAssetAuditRules(): array
    {
        return [
            'audit_name' => ['required', 'string', 'max:255'],
            'audit_date' => ['required', 'date', 'before_or_equal:today'],
            'auditor_id' => ['required', 'integer', 'exists:users,id'],
            'audit_type' => ['required', 'string', 'in:physical,financial,compliance,disposal,full'],
            'scope' => ['required', 'string', 'in:all_assets,by_category,by_location,by_department,specific_assets'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:asset_categories,id'],
            'location_ids' => ['nullable', 'array'],
            'location_ids.*' => ['integer', 'exists:locations,id'],
            'department_ids' => ['nullable', 'array'],
            'department_ids.*' => ['integer', 'exists:departments,id'],
            'asset_ids' => ['nullable', 'array'],
            'asset_ids.*' => ['integer', 'exists:assets,id'],
            'audit_criteria' => ['nullable', 'string', 'max:2000'],
            'findings' => ['nullable', 'string', 'max:5000'],
            'discrepancies' => ['nullable', 'array'],
            'discrepancies.*.asset_id' => ['required', 'integer', 'exists:assets,id'],
            'discrepancies.*.type' => ['required', 'string', 'in:missing,damaged,location_mismatch,condition_change,unauthorized_use'],
            'discrepancies.*.description' => ['required', 'string', 'max:500'],
            'discrepancies.*.severity' => ['required', 'string', 'in:low,medium,high,critical'],
            'recommendations' => ['nullable', 'string', 'max:2000'],
            'action_items' => ['nullable', 'array'],
            'action_items.*.description' => ['required', 'string', 'max:500'],
            'action_items.*.assigned_to' => ['required', 'integer', 'exists:users,id'],
            'action_items.*.due_date' => ['required', 'date', 'after:today'],
            'action_items.*.priority' => ['required', 'string', 'in:low,medium,high,urgent'],
            'status' => ['required', 'string', 'in:planned,in_progress,completed,cancelled'],
            'completion_date' => ['nullable', 'date', 'after_or_equal:audit_date'],
            'audit_report' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'], // 10MB max
        ];
    }

    /**
     * Get asset bulk operation validation rules
     *
     * @return array
     */
    protected function getAssetBulkRules(): array
    {
        return [
            'asset_ids' => ['required', 'array', 'min:1'],
            'asset_ids.*' => ['integer', 'exists:assets,id'],
            'action' => ['required', 'string', 'in:update_status,update_condition,update_location,update_department,assign,unassign,dispose,maintenance_schedule,delete'],
            'status' => ['nullable', 'string', 'in:available,assigned,in_use,maintenance,repair,disposed,lost,stolen'],
            'condition' => ['nullable', 'string', 'in:excellent,good,fair,poor,damaged,obsolete'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'assignment_date' => ['nullable', 'date', 'before_or_equal:today'],
            'maintenance_schedule' => ['nullable', 'string', 'in:daily,weekly,monthly,quarterly,semi_annual,annual,as_needed'],
            'next_maintenance_date' => ['nullable', 'date', 'after:today'],
            'disposal_date' => ['nullable', 'date'],
            'disposal_method' => ['nullable', 'string', 'in:sold,donated,recycled,destroyed,returned'],
            'disposal_reason' => ['nullable', 'string', 'max:500'],
            'reason' => ['nullable', 'string', 'max:500'],
            'send_notification' => ['nullable', 'boolean'],
            'effective_date' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }

    /**
     * Get asset import validation rules
     *
     * @return array
     */
    protected function getAssetImportRules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,xlsx,xls', 'max:10240'], // 10MB max
            'has_header' => ['nullable', 'boolean'],
            'delimiter' => ['nullable', 'string', 'in:comma,semicolon,tab'],
            'encoding' => ['nullable', 'string', 'in:utf-8,iso-8859-1'],
            'default_category_id' => ['required', 'integer', 'exists:asset_categories,id'],
            'default_location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'default_department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'default_condition' => ['required', 'string', 'in:excellent,good,fair,poor,damaged,obsolete'],
            'default_status' => ['required', 'string', 'in:available,assigned,in_use,maintenance,repair,disposed,lost,stolen'],
            'skip_duplicates' => ['nullable', 'boolean'],
            'update_existing' => ['nullable', 'boolean'],
            'generate_asset_codes' => ['nullable', 'boolean'],
            'asset_code_prefix' => ['nullable', 'string', 'max:10'],
        ];
    }

    /**
     * Get asset validation messages
     *
     * @return array
     */
    protected function getAssetValidationMessages(): array
    {
        return array_merge(
            $this->getCommonValidationMessages(),
            [
                'asset_code.required' => 'Asset code is required.',
                'asset_code.unique' => 'This asset code is already taken.',
                'asset_code.max' => 'Asset code cannot exceed :max characters.',
                'asset_code.alpha_num' => 'Asset code may only contain letters and numbers.',
                
                'asset_tag.unique' => 'This asset tag is already taken.',
                'asset_tag.max' => 'Asset tag cannot exceed :max characters.',
                
                'category_id.required' => 'Asset category is required.',
                'category_id.exists' => 'Selected asset category does not exist.',
                
                'subcategory_id.exists' => 'Selected asset subcategory does not exist.',
                
                'brand.max' => 'Brand cannot exceed :max characters.',
                'model.max' => 'Model cannot exceed :max characters.',
                
                'serial_number.unique' => 'This serial number is already taken.',
                'serial_number.max' => 'Serial number cannot exceed :max characters.',
                
                'manufacturer.max' => 'Manufacturer cannot exceed :max characters.',
                
                'supplier_id.exists' => 'Selected supplier does not exist.',
                
                'purchase_date.date' => 'Purchase date must be a valid date.',
                'purchase_date.before_or_equal' => 'Purchase date cannot be in the future.',
                
                'purchase_cost.numeric' => 'Purchase cost must be a valid number.',
                'purchase_cost.min' => 'Purchase cost cannot be negative.',
                'purchase_cost.max' => 'Purchase cost cannot exceed :max.',
                
                'current_value.numeric' => 'Current value must be a valid number.',
                'current_value.min' => 'Current value cannot be negative.',
                'current_value.max' => 'Current value cannot exceed :max.',
                
                'depreciation_rate.numeric' => 'Depreciation rate must be a valid number.',
                'depreciation_rate.min' => 'Depreciation rate cannot be negative.',
                'depreciation_rate.max' => 'Depreciation rate cannot exceed :max%.',
                
                'depreciation_method.in' => 'Selected depreciation method is invalid.',
                
                'useful_life_years.integer' => 'Useful life years must be a valid number.',
                'useful_life_years.min' => 'Useful life years must be at least :min.',
                'useful_life_years.max' => 'Useful life years cannot exceed :max.',
                
                'warranty_start_date.date' => 'Warranty start date must be a valid date.',
                
                'warranty_end_date.date' => 'Warranty end date must be a valid date.',
                'warranty_end_date.after' => 'Warranty end date must be after start date.',
                
                'warranty_provider.max' => 'Warranty provider cannot exceed :max characters.',
                'warranty_terms.max' => 'Warranty terms cannot exceed :max characters.',
                
                'location_id.exists' => 'Selected location does not exist.',
                'department_id.exists' => 'Selected department does not exist.',
                'assigned_to.exists' => 'Selected assignee does not exist.',
                
                'assigned_date.date' => 'Assigned date must be a valid date.',
                'assigned_date.before_or_equal' => 'Assigned date cannot be in the future.',
                
                'condition.required' => 'Asset condition is required.',
                'condition.in' => 'Selected asset condition is invalid.',
                
                'status.required' => 'Asset status is required.',
                'status.in' => 'Selected asset status is invalid.',
                
                'criticality.in' => 'Selected criticality level is invalid.',
                
                'maintenance_schedule.in' => 'Selected maintenance schedule is invalid.',
                
                'next_maintenance_date.date' => 'Next maintenance date must be a valid date.',
                'next_maintenance_date.after' => 'Next maintenance date must be in the future.',
                
                'last_maintenance_date.date' => 'Last maintenance date must be a valid date.',
                'last_maintenance_date.before_or_equal' => 'Last maintenance date cannot be in the future.',
                
                'maintenance_cost.numeric' => 'Maintenance cost must be a valid number.',
                'maintenance_cost.min' => 'Maintenance cost cannot be negative.',
                'maintenance_cost.max' => 'Maintenance cost cannot exceed :max.',
                
                'insurance_policy_number.max' => 'Insurance policy number cannot exceed :max characters.',
                'insurance_provider.max' => 'Insurance provider cannot exceed :max characters.',
                
                'insurance_expiry_date.date' => 'Insurance expiry date must be a valid date.',
                'insurance_expiry_date.after' => 'Insurance expiry date must be in the future.',
                
                'insurance_value.numeric' => 'Insurance value must be a valid number.',
                'insurance_value.min' => 'Insurance value cannot be negative.',
                'insurance_value.max' => 'Insurance value cannot exceed :max.',
                
                'specifications.array' => 'Specifications must be a valid list.',
                'specifications.*.key.required' => 'Specification key is required.',
                'specifications.*.key.max' => 'Specification key cannot exceed :max characters.',
                'specifications.*.value.required' => 'Specification value is required.',
                'specifications.*.value.max' => 'Specification value cannot exceed :max characters.',
                
                'accessories.array' => 'Accessories must be a valid list.',
                'accessories.*.max' => 'Each accessory name cannot exceed :max characters.',
                
                'notes.max' => 'Notes cannot exceed :max characters.',
                
                'qr_code.unique' => 'This QR code is already taken.',
                'qr_code.max' => 'QR code cannot exceed :max characters.',
                
                'barcode.unique' => 'This barcode is already taken.',
                'barcode.max' => 'Barcode cannot exceed :max characters.',
                
                'rfid_tag.unique' => 'This RFID tag is already taken.',
                'rfid_tag.max' => 'RFID tag cannot exceed :max characters.',
                
                'disposal_date.date' => 'Disposal date must be a valid date.',
                
                'disposal_method.in' => 'Selected disposal method is invalid.',
                
                'disposal_value.numeric' => 'Disposal value must be a valid number.',
                'disposal_value.min' => 'Disposal value cannot be negative.',
                'disposal_value.max' => 'Disposal value cannot exceed :max.',
                
                'disposal_reason.max' => 'Disposal reason cannot exceed :max characters.',
                
                'images.array' => 'Images must be a valid list.',
                'images.*.image' => 'Each file must be an image.',
                'images.*.mimes' => 'Each image must be a JPEG, PNG, or JPG file.',
                'images.*.max' => 'Each image cannot exceed 2MB.',
                
                'documents.array' => 'Documents must be a valid list.',
                'documents.*.file' => 'Each document must be a valid file.',
                'documents.*.mimes' => 'Each document must be a PDF, DOC, DOCX, JPG, JPEG, or PNG file.',
                'documents.*.max' => 'Each document cannot exceed 5MB.',
                
                // Assignment messages
                'asset_id.required' => 'Asset is required.',
                'asset_id.exists' => 'Selected asset does not exist.',
                
                'assigned_by.exists' => 'Selected assigner does not exist.',
                
                'assignment_date.required' => 'Assignment date is required.',
                'assignment_date.date' => 'Assignment date must be a valid date.',
                'assignment_date.before_or_equal' => 'Assignment date cannot be in the future.',
                
                'expected_return_date.date' => 'Expected return date must be a valid date.',
                'expected_return_date.after' => 'Expected return date must be after assignment date.',
                
                'assignment_type.required' => 'Assignment type is required.',
                'assignment_type.in' => 'Selected assignment type is invalid.',
                
                'purpose.max' => 'Purpose cannot exceed :max characters.',
                
                'condition_at_assignment.required' => 'Condition at assignment is required.',
                'condition_at_assignment.in' => 'Selected condition at assignment is invalid.',
                
                'accessories_included.array' => 'Accessories included must be a valid list.',
                'accessories_included.*.max' => 'Each accessory name cannot exceed :max characters.',
                
                'terms_conditions.max' => 'Terms and conditions cannot exceed :max characters.',
                
                'acknowledgment_date.date' => 'Acknowledgment date must be a valid date.',
                'acknowledgment_date.before_or_equal' => 'Acknowledgment date cannot be in the future.',
                
                'checkout_photos.array' => 'Checkout photos must be a valid list.',
                'checkout_photos.*.image' => 'Each checkout photo must be an image.',
                'checkout_photos.*.mimes' => 'Each checkout photo must be a JPEG, PNG, or JPG file.',
                'checkout_photos.*.max' => 'Each checkout photo cannot exceed 2MB.',
                
                // Return messages
                'assignment_id.required' => 'Assignment is required.',
                'assignment_id.exists' => 'Selected assignment does not exist.',
                
                'return_date.required' => 'Return date is required.',
                'return_date.date' => 'Return date must be a valid date.',
                'return_date.before_or_equal' => 'Return date cannot be in the future.',
                
                'returned_by.exists' => 'Selected returner does not exist.',
                
                'received_by.required' => 'Receiver is required.',
                'received_by.exists' => 'Selected receiver does not exist.',
                
                'condition_at_return.required' => 'Condition at return is required.',
                'condition_at_return.in' => 'Selected condition at return is invalid.',
                
                'accessories_returned.array' => 'Accessories returned must be a valid list.',
                'accessories_returned.*.max' => 'Each returned accessory name cannot exceed :max characters.',
                
                'missing_accessories.array' => 'Missing accessories must be a valid list.',
                'missing_accessories.*.max' => 'Each missing accessory name cannot exceed :max characters.',
                
                'damage_description.max' => 'Damage description cannot exceed :max characters.',
                
                'damage_cost.numeric' => 'Damage cost must be a valid number.',
                'damage_cost.min' => 'Damage cost cannot be negative.',
                'damage_cost.max' => 'Damage cost cannot exceed :max.',
                
                'late_return_reason.max' => 'Late return reason cannot exceed :max characters.',
                
                'penalty_amount.numeric' => 'Penalty amount must be a valid number.',
                'penalty_amount.min' => 'Penalty amount cannot be negative.',
                'penalty_amount.max' => 'Penalty amount cannot exceed :max.',
                
                'user_satisfaction.integer' => 'User satisfaction must be a valid number.',
                'user_satisfaction.min' => 'User satisfaction must be at least :min.',
                'user_satisfaction.max' => 'User satisfaction cannot exceed :max.',
                
                'feedback.max' => 'Feedback cannot exceed :max characters.',
                'return_notes.max' => 'Return notes cannot exceed :max characters.',
                
                'return_photos.array' => 'Return photos must be a valid list.',
                'return_photos.*.image' => 'Each return photo must be an image.',
                'return_photos.*.mimes' => 'Each return photo must be a JPEG, PNG, or JPG file.',
                'return_photos.*.max' => 'Each return photo cannot exceed 2MB.',
                
                // Maintenance messages
                'maintenance_type.required' => 'Maintenance type is required.',
                'maintenance_type.in' => 'Selected maintenance type is invalid.',
                
                'scheduled_date.required' => 'Scheduled date is required.',
                'scheduled_date.date' => 'Scheduled date must be a valid date.',
                
                'completed_date.date' => 'Completed date must be a valid date.',
                'completed_date.after_or_equal' => 'Completed date must be on or after scheduled date.',
                
                'technician_id.exists' => 'Selected technician does not exist.',
                'vendor_id.exists' => 'Selected vendor does not exist.',
                
                'description.required' => 'Description is required.',
                'description.max' => 'Description cannot exceed :max characters.',
                
                'work_performed.max' => 'Work performed cannot exceed :max characters.',
                
                'parts_used.array' => 'Parts used must be a valid list.',
                'parts_used.*.name.required' => 'Part name is required.',
                'parts_used.*.name.max' => 'Part name cannot exceed :max characters.',
                'parts_used.*.quantity.required' => 'Part quantity is required.',
                'parts_used.*.quantity.integer' => 'Part quantity must be a valid number.',
                'parts_used.*.quantity.min' => 'Part quantity must be at least :min.',
                'parts_used.*.cost.required' => 'Part cost is required.',
                'parts_used.*.cost.numeric' => 'Part cost must be a valid number.',
                'parts_used.*.cost.min' => 'Part cost cannot be negative.',
                
                'labor_hours.numeric' => 'Labor hours must be a valid number.',
                'labor_hours.min' => 'Labor hours cannot be negative.',
                'labor_hours.max' => 'Labor hours cannot exceed :max.',
                
                'labor_cost.numeric' => 'Labor cost must be a valid number.',
                'labor_cost.min' => 'Labor cost cannot be negative.',
                'labor_cost.max' => 'Labor cost cannot exceed :max.',
                
                'parts_cost.numeric' => 'Parts cost must be a valid number.',
                'parts_cost.min' => 'Parts cost cannot be negative.',
                'parts_cost.max' => 'Parts cost cannot exceed :max.',
                
                'total_cost.numeric' => 'Total cost must be a valid number.',
                'total_cost.min' => 'Total cost cannot be negative.',
                'total_cost.max' => 'Total cost cannot exceed :max.',
                
                'priority.required' => 'Priority is required.',
                'priority.in' => 'Selected priority is invalid.',
                
                'downtime_hours.numeric' => 'Downtime hours must be a valid number.',
                'downtime_hours.min' => 'Downtime hours cannot be negative.',
                'downtime_hours.max' => 'Downtime hours cannot exceed :max.',
                
                'next_maintenance_date.date' => 'Next maintenance date must be a valid date.',
                'next_maintenance_date.after' => 'Next maintenance date must be after completion date.',
                
                'warranty_claim_number.max' => 'Warranty claim number cannot exceed :max characters.',
                
                'before_condition.in' => 'Selected before condition is invalid.',
                'after_condition.in' => 'Selected after condition is invalid.',
                
                'issues_found.max' => 'Issues found cannot exceed :max characters.',
                'recommendations.max' => 'Recommendations cannot exceed :max characters.',
                'safety_notes.max' => 'Safety notes cannot exceed :max characters.',
                'completion_notes.max' => 'Completion notes cannot exceed :max characters.',
                
                'maintenance_photos.array' => 'Maintenance photos must be a valid list.',
                'maintenance_photos.*.image' => 'Each maintenance photo must be an image.',
                'maintenance_photos.*.mimes' => 'Each maintenance photo must be a JPEG, PNG, or JPG file.',
                'maintenance_photos.*.max' => 'Each maintenance photo cannot exceed 2MB.',
                
                'maintenance_documents.array' => 'Maintenance documents must be a valid list.',
                'maintenance_documents.*.file' => 'Each maintenance document must be a valid file.',
                'maintenance_documents.*.mimes' => 'Each maintenance document must be a PDF, DOC, or DOCX file.',
                'maintenance_documents.*.max' => 'Each maintenance document cannot exceed 5MB.',
                
                // Audit messages
                'audit_name.required' => 'Audit name is required.',
                'audit_name.max' => 'Audit name cannot exceed :max characters.',
                
                'audit_date.required' => 'Audit date is required.',
                'audit_date.date' => 'Audit date must be a valid date.',
                'audit_date.before_or_equal' => 'Audit date cannot be in the future.',
                
                'auditor_id.required' => 'Auditor is required.',
                'auditor_id.exists' => 'Selected auditor does not exist.',
                
                'audit_type.required' => 'Audit type is required.',
                'audit_type.in' => 'Selected audit type is invalid.',
                
                'scope.required' => 'Audit scope is required.',
                'scope.in' => 'Selected audit scope is invalid.',
                
                'category_ids.array' => 'Category selection must be a valid list.',
                'category_ids.*.exists' => 'One or more selected categories do not exist.',
                
                'location_ids.array' => 'Location selection must be a valid list.',
                'location_ids.*.exists' => 'One or more selected locations do not exist.',
                
                'department_ids.array' => 'Department selection must be a valid list.',
                'department_ids.*.exists' => 'One or more selected departments do not exist.',
                
                'asset_ids.array' => 'Asset selection must be a valid list.',
                'asset_ids.*.exists' => 'One or more selected assets do not exist.',
                
                'audit_criteria.max' => 'Audit criteria cannot exceed :max characters.',
                'findings.max' => 'Findings cannot exceed :max characters.',
                
                'discrepancies.array' => 'Discrepancies must be a valid list.',
                'discrepancies.*.asset_id.required' => 'Asset is required for each discrepancy.',
                'discrepancies.*.asset_id.exists' => 'Selected asset does not exist.',
                'discrepancies.*.type.required' => 'Discrepancy type is required.',
                'discrepancies.*.type.in' => 'Selected discrepancy type is invalid.',
                'discrepancies.*.description.required' => 'Discrepancy description is required.',
                'discrepancies.*.description.max' => 'Discrepancy description cannot exceed :max characters.',
                'discrepancies.*.severity.required' => 'Discrepancy severity is required.',
                'discrepancies.*.severity.in' => 'Selected discrepancy severity is invalid.',
                
                'action_items.array' => 'Action items must be a valid list.',
                'action_items.*.description.required' => 'Action item description is required.',
                'action_items.*.description.max' => 'Action item description cannot exceed :max characters.',
                'action_items.*.assigned_to.required' => 'Action item assignee is required.',
                'action_items.*.assigned_to.exists' => 'Selected action item assignee does not exist.',
                'action_items.*.due_date.required' => 'Action item due date is required.',
                'action_items.*.due_date.date' => 'Action item due date must be a valid date.',
                'action_items.*.due_date.after' => 'Action item due date must be in the future.',
                'action_items.*.priority.required' => 'Action item priority is required.',
                'action_items.*.priority.in' => 'Selected action item priority is invalid.',
                
                'completion_date.date' => 'Completion date must be a valid date.',
                'completion_date.after_or_equal' => 'Completion date must be on or after audit date.',
                
                'audit_report.file' => 'Audit report must be a valid file.',
                'audit_report.mimes' => 'Audit report must be a PDF, DOC, or DOCX file.',
                'audit_report.max' => 'Audit report cannot exceed 10MB.',
                
                // Bulk operation messages
                'asset_ids.required' => 'At least one asset must be selected.',
                'asset_ids.array' => 'Asset selection must be a valid list.',
                'asset_ids.min' => 'At least one asset must be selected.',
                'asset_ids.*.exists' => 'One or more selected assets do not exist.',
                
                'action.required' => 'Action is required.',
                'action.in' => 'Selected action is invalid.',
                
                'effective_date.date' => 'Effective date must be a valid date.',
                'effective_date.after_or_equal' => 'Effective date cannot be in the past.',
                
                // Import messages
                'file.required' => 'File is required.',
                'file.file' => 'Uploaded file is invalid.',
                'file.mimes' => 'File must be a CSV or Excel file.',
                'file.max' => 'File size cannot exceed 10MB.',
                
                'delimiter.in' => 'Selected delimiter is invalid.',
                'encoding.in' => 'Selected encoding is invalid.',
                
                'default_category_id.required' => 'Default category is required.',
                'default_category_id.exists' => 'Selected default category does not exist.',
                
                'default_location_id.exists' => 'Selected default location does not exist.',
                'default_department_id.exists' => 'Selected default department does not exist.',
                
                'default_condition.required' => 'Default condition is required.',
                'default_condition.in' => 'Selected default condition is invalid.',
                
                'default_status.required' => 'Default status is required.',
                'default_status.in' => 'Selected default status is invalid.',
                
                'asset_code_prefix.max' => 'Asset code prefix cannot exceed :max characters.',
            ]
        );
    }
}