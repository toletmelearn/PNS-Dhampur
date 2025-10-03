<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClassDataAuditRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && (
            auth()->user()->hasAnyRole(['admin', 'principal', 'class_teacher']) ||
            auth()->user()->can('manage-class-data-audit')
        );
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'entity_type' => 'required|string|in:student,class,teacher,subject,exam',
            'entity_id' => 'required|integer|min:1',
            'action' => 'required|string|in:create,update,delete,restore,bulk_update',
            'old_values' => 'nullable|array',
            'new_values' => 'nullable|array',
            'changes_summary' => 'nullable|string|max:1000',
            'metadata' => 'nullable|array',
            'risk_level' => 'nullable|string|in:low,medium,high,critical',
            'requires_approval' => 'nullable|boolean',
            'batch_id' => 'nullable|string|max:100',
            'parent_audit_id' => 'nullable|integer|exists:class_data_audits,id',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            
            // Version creation fields
            'create_version' => 'nullable|boolean',
            'version_type' => 'nullable|string|in:automatic,manual,milestone,backup',
            
            // Approval fields
            'approval_type' => 'nullable|string|in:standard,emergency,bulk,automatic',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'priority' => 'nullable|string|in:low,normal,high,urgent',
            'deadline' => 'nullable|date|after:now',
            'request_reason' => 'nullable|string|max:500',
        ];

        // Add conditional validation based on action
        if ($this->input('action') === 'update') {
            $rules['old_values'] = 'required|array';
            $rules['new_values'] = 'required|array';
        }

        if ($this->input('requires_approval')) {
            $rules['approval_type'] = 'required|string|in:standard,emergency,bulk,automatic';
            $rules['assigned_to'] = 'required|integer|exists:users,id';
            $rules['request_reason'] = 'required|string|max:500';
        }

        if ($this->input('risk_level') === 'critical') {
            $rules['requires_approval'] = 'required|boolean';
            $rules['metadata.justification'] = 'required|string|max:1000';
        }

        return $rules;
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'entity_type.required' => 'Entity type is required.',
            'entity_type.in' => 'Entity type must be one of: student, class, teacher, subject, exam.',
            'entity_id.required' => 'Entity ID is required.',
            'entity_id.integer' => 'Entity ID must be a valid integer.',
            'entity_id.min' => 'Entity ID must be greater than 0.',
            'action.required' => 'Action is required.',
            'action.in' => 'Action must be one of: create, update, delete, restore, bulk_update.',
            'old_values.required' => 'Old values are required for update actions.',
            'new_values.required' => 'New values are required for update actions.',
            'changes_summary.max' => 'Changes summary cannot exceed 1000 characters.',
            'risk_level.in' => 'Risk level must be one of: low, medium, high, critical.',
            'batch_id.max' => 'Batch ID cannot exceed 100 characters.',
            'parent_audit_id.exists' => 'Parent audit ID must reference a valid audit record.',
            'tags.*.max' => 'Each tag cannot exceed 50 characters.',
            'version_type.in' => 'Version type must be one of: automatic, manual, milestone, backup.',
            'approval_type.required' => 'Approval type is required when approval is needed.',
            'approval_type.in' => 'Approval type must be one of: standard, emergency, bulk, automatic.',
            'assigned_to.required' => 'Assigned user is required when approval is needed.',
            'assigned_to.exists' => 'Assigned user must be a valid user.',
            'priority.in' => 'Priority must be one of: low, normal, high, urgent.',
            'deadline.after' => 'Deadline must be in the future.',
            'request_reason.required' => 'Request reason is required when approval is needed.',
            'request_reason.max' => 'Request reason cannot exceed 500 characters.',
            'metadata.justification.required' => 'Justification is required for critical risk level changes.',
            'metadata.justification.max' => 'Justification cannot exceed 1000 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'entity_type' => 'entity type',
            'entity_id' => 'entity ID',
            'old_values' => 'old values',
            'new_values' => 'new values',
            'changes_summary' => 'changes summary',
            'risk_level' => 'risk level',
            'requires_approval' => 'requires approval',
            'batch_id' => 'batch ID',
            'parent_audit_id' => 'parent audit ID',
            'version_type' => 'version type',
            'approval_type' => 'approval type',
            'assigned_to' => 'assigned user',
            'request_reason' => 'request reason',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Custom validation logic
            $this->validateEntityExists($validator);
            $this->validateDataChanges($validator);
            $this->validateApprovalWorkflow($validator);
        });
    }

    /**
     * Validate that the entity exists in the database.
     */
    protected function validateEntityExists($validator): void
    {
        $entityType = $this->input('entity_type');
        $entityId = $this->input('entity_id');

        if (!$entityType || !$entityId) {
            return;
        }

        $modelMap = [
            'student' => \App\Models\Student::class,
            'class' => \App\Models\ClassModel::class,
            'teacher' => \App\Models\Teacher::class,
            'subject' => \App\Models\Subject::class,
            'exam' => \App\Models\Exam::class,
        ];

        if (isset($modelMap[$entityType])) {
            $model = $modelMap[$entityType];
            if (!$model::find($entityId)) {
                $validator->errors()->add('entity_id', "The selected {$entityType} does not exist.");
            }
        }
    }

    /**
     * Validate data changes for update actions.
     */
    protected function validateDataChanges($validator): void
    {
        if ($this->input('action') === 'update') {
            $oldValues = $this->input('old_values', []);
            $newValues = $this->input('new_values', []);

            if (empty($oldValues) && empty($newValues)) {
                $validator->errors()->add('new_values', 'At least old values or new values must be provided for update actions.');
            }

            // Validate that there are actual changes
            if (!empty($oldValues) && !empty($newValues) && $oldValues === $newValues) {
                $validator->errors()->add('new_values', 'New values must be different from old values.');
            }
        }
    }

    /**
     * Validate approval workflow requirements.
     */
    protected function validateApprovalWorkflow($validator): void
    {
        $requiresApproval = $this->input('requires_approval');
        $riskLevel = $this->input('risk_level');

        // High and critical risk changes must require approval
        if (in_array($riskLevel, ['high', 'critical']) && !$requiresApproval) {
            $validator->errors()->add('requires_approval', 'High and critical risk changes must require approval.');
        }

        // Emergency approvals have special requirements
        if ($this->input('approval_type') === 'emergency') {
            if (!$this->input('metadata.emergency_justification')) {
                $validator->errors()->add('metadata.emergency_justification', 'Emergency justification is required for emergency approvals.');
            }
        }
    }
}