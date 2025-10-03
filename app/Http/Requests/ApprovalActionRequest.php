<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApprovalActionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && (
            auth()->user()->hasAnyRole(['admin', 'principal', 'class_teacher']) ||
            auth()->user()->hasPermission('approve_audit_changes') ||
            auth()->user()->can('approve-class-data-changes')
        );
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $action = $this->route()->getActionMethod();
        
        $baseRules = [
            'approval_id' => 'required|integer|exists:class_data_approvals,id',
        ];

        switch ($action) {
            case 'approve':
                return array_merge($baseRules, [
                    'approval_reason' => 'nullable|string|max:1000',
                    'conditions' => 'nullable|array',
                    'conditions.*' => 'string|max:255',
                    'digital_signature' => 'nullable|string|max:500',
                    'notify_stakeholders' => 'nullable|boolean',
                    'implementation_notes' => 'nullable|string|max:1000',
                ]);

            case 'reject':
                return array_merge($baseRules, [
                    'rejection_reason' => 'required|string|max:1000|min:10',
                    'alternative_suggestions' => 'nullable|string|max:1000',
                    'notify_requester' => 'nullable|boolean',
                ]);

            case 'delegate':
                return array_merge($baseRules, [
                    'delegate_to' => 'required|integer|exists:users,id|different:' . auth()->id(),
                    'delegation_reason' => 'required|string|max:500|min:10',
                    'delegation_notes' => 'nullable|string|max:1000',
                    'retain_oversight' => 'nullable|boolean',
                    'deadline_extension' => 'nullable|integer|min:1|max:30', // days
                ]);

            case 'bulkApprove':
                return [
                    'approval_ids' => 'required|array|min:1|max:50',
                    'approval_ids.*' => 'integer|exists:class_data_approvals,id',
                    'approval_reason' => 'nullable|string|max:1000',
                    'notify_stakeholders' => 'nullable|boolean',
                ];

            case 'bulkReject':
                return [
                    'approval_ids' => 'required|array|min:1|max:50',
                    'approval_ids.*' => 'integer|exists:class_data_approvals,id',
                    'rejection_reason' => 'required|string|max:1000|min:10',
                    'notify_requesters' => 'nullable|boolean',
                ];

            default:
                return $baseRules;
        }
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'approval_id.required' => 'Approval ID is required.',
            'approval_id.exists' => 'The selected approval does not exist.',
            'approval_reason.max' => 'Approval reason cannot exceed 1000 characters.',
            'rejection_reason.required' => 'Rejection reason is required.',
            'rejection_reason.min' => 'Rejection reason must be at least 10 characters.',
            'rejection_reason.max' => 'Rejection reason cannot exceed 1000 characters.',
            'delegate_to.required' => 'Delegate user is required.',
            'delegate_to.exists' => 'The selected user does not exist.',
            'delegate_to.different' => 'You cannot delegate to yourself.',
            'delegation_reason.required' => 'Delegation reason is required.',
            'delegation_reason.min' => 'Delegation reason must be at least 10 characters.',
            'delegation_reason.max' => 'Delegation reason cannot exceed 500 characters.',
            'delegation_notes.max' => 'Delegation notes cannot exceed 1000 characters.',
            'deadline_extension.min' => 'Deadline extension must be at least 1 day.',
            'deadline_extension.max' => 'Deadline extension cannot exceed 30 days.',
            'approval_ids.required' => 'At least one approval must be selected.',
            'approval_ids.min' => 'At least one approval must be selected.',
            'approval_ids.max' => 'Cannot process more than 50 approvals at once.',
            'approval_ids.*.exists' => 'One or more selected approvals do not exist.',
            'conditions.*.max' => 'Each condition cannot exceed 255 characters.',
            'digital_signature.max' => 'Digital signature cannot exceed 500 characters.',
            'implementation_notes.max' => 'Implementation notes cannot exceed 1000 characters.',
            'alternative_suggestions.max' => 'Alternative suggestions cannot exceed 1000 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'approval_id' => 'approval',
            'approval_reason' => 'approval reason',
            'rejection_reason' => 'rejection reason',
            'delegate_to' => 'delegate user',
            'delegation_reason' => 'delegation reason',
            'delegation_notes' => 'delegation notes',
            'deadline_extension' => 'deadline extension',
            'approval_ids' => 'approvals',
            'digital_signature' => 'digital signature',
            'implementation_notes' => 'implementation notes',
            'alternative_suggestions' => 'alternative suggestions',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateApprovalStatus($validator);
            $this->validateUserPermissions($validator);
            $this->validateBusinessRules($validator);
        });
    }

    /**
     * Validate that the approval is in the correct status.
     */
    protected function validateApprovalStatus($validator): void
    {
        $approvalId = $this->input('approval_id');
        $approvalIds = $this->input('approval_ids', []);
        
        if ($approvalId) {
            $approvalIds = [$approvalId];
        }

        foreach ($approvalIds as $id) {
            $approval = \App\Models\ClassDataApproval::find($id);
            
            if ($approval && $approval->status !== 'pending') {
                $validator->errors()->add(
                    $approvalId ? 'approval_id' : 'approval_ids',
                    "Approval #{$id} is not in pending status and cannot be processed."
                );
            }
        }
    }

    /**
     * Validate user permissions for the specific approval.
     */
    protected function validateUserPermissions($validator): void
    {
        $approvalId = $this->input('approval_id');
        $approvalIds = $this->input('approval_ids', []);
        
        if ($approvalId) {
            $approvalIds = [$approvalId];
        }

        $user = auth()->user();
        
        foreach ($approvalIds as $id) {
            $approval = \App\Models\ClassDataApproval::find($id);
            
            if ($approval) {
                // Check if user is assigned to this approval or has admin rights
                if ($approval->assigned_to !== $user->id && !$user->hasAnyRole(['admin', 'principal'])) {
                    $validator->errors()->add(
                        $approvalId ? 'approval_id' : 'approval_ids',
                        "You do not have permission to process approval #{$id}."
                    );
                }
            }
        }
    }

    /**
     * Validate business rules for approval actions.
     */
    protected function validateBusinessRules($validator): void
    {
        $action = $this->route()->getActionMethod();
        
        if ($action === 'delegate') {
            $delegateTo = $this->input('delegate_to');
            $delegateUser = \App\Models\User::find($delegateTo);
            
            if ($delegateUser && !$delegateUser->hasAnyRole(['admin', 'principal', 'class_teacher'])) {
                $validator->errors()->add('delegate_to', 'The selected user does not have permission to handle approvals.');
            }
        }

        // Validate bulk operation limits
        if (in_array($action, ['bulkApprove', 'bulkReject'])) {
            $approvalIds = $this->input('approval_ids', []);
            
            if (count($approvalIds) > 50) {
                $validator->errors()->add('approval_ids', 'Cannot process more than 50 approvals at once.');
            }
        }
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Clean and prepare data
        if ($this->has('approval_reason')) {
            $this->merge([
                'approval_reason' => trim($this->input('approval_reason'))
            ]);
        }

        if ($this->has('rejection_reason')) {
            $this->merge([
                'rejection_reason' => trim($this->input('rejection_reason'))
            ]);
        }

        if ($this->has('delegation_reason')) {
            $this->merge([
                'delegation_reason' => trim($this->input('delegation_reason'))
            ]);
        }

        // Set default values
        $this->merge([
            'notify_stakeholders' => $this->input('notify_stakeholders', true),
            'notify_requester' => $this->input('notify_requester', true),
            'notify_requesters' => $this->input('notify_requesters', true),
            'retain_oversight' => $this->input('retain_oversight', false),
        ]);
    }
}