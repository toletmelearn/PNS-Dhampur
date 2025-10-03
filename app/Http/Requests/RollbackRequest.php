<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RollbackRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && (
            auth()->user()->hasAnyRole(['admin', 'principal']) ||
            auth()->user()->can('rollback-class-data-changes')
        );
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'version_id' => 'required|integer|exists:class_data_versions,id',
            'rollback_reason' => 'required|string|min:20|max:1000',
            'create_backup' => 'nullable|boolean',
            'notify_stakeholders' => 'nullable|boolean',
            'priority' => [
                'required',
                Rule::in(['low', 'medium', 'high', 'critical'])
            ],
            'approval_required' => 'nullable|boolean',
            'approval_justification' => 'required_if:approval_required,true|nullable|string|min:20|max:1000',
            'affected_entities' => 'nullable|array',
            'affected_entities.*' => 'string|max:255',
            'rollback_type' => [
                'required',
                Rule::in(['full', 'partial', 'selective'])
            ],
            'selective_fields' => 'required_if:rollback_type,selective|nullable|array',
            'selective_fields.*' => 'string|max:100',
            'scheduled_at' => 'nullable|date|after:now',
            'maintenance_window' => 'nullable|boolean',
            'estimated_downtime' => 'nullable|integer|min:0|max:1440', // minutes
            'rollback_notes' => 'nullable|string|max:2000',
            'verification_checklist' => 'nullable|array',
            'verification_checklist.*' => 'boolean',
            'emergency_contact' => 'nullable|string|max:255',
            'post_rollback_actions' => 'nullable|array',
            'post_rollback_actions.*' => 'string|max:500',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'version_id.required' => 'Version selection is required for rollback.',
            'version_id.exists' => 'The selected version does not exist.',
            'rollback_reason.required' => 'Rollback reason is required.',
            'rollback_reason.min' => 'Rollback reason must be at least 20 characters.',
            'rollback_reason.max' => 'Rollback reason cannot exceed 1000 characters.',
            'priority.required' => 'Priority level is required.',
            'priority.in' => 'Priority must be one of: low, medium, high, critical.',
            'approval_justification.required_if' => 'Approval justification is required when approval is needed.',
            'approval_justification.min' => 'Approval justification must be at least 20 characters.',
            'approval_justification.max' => 'Approval justification cannot exceed 1000 characters.',
            'rollback_type.required' => 'Rollback type is required.',
            'rollback_type.in' => 'Rollback type must be one of: full, partial, selective.',
            'selective_fields.required_if' => 'Selective fields are required when rollback type is selective.',
            'scheduled_at.after' => 'Scheduled rollback time must be in the future.',
            'estimated_downtime.min' => 'Estimated downtime cannot be negative.',
            'estimated_downtime.max' => 'Estimated downtime cannot exceed 24 hours (1440 minutes).',
            'rollback_notes.max' => 'Rollback notes cannot exceed 2000 characters.',
            'affected_entities.*.max' => 'Each affected entity cannot exceed 255 characters.',
            'selective_fields.*.max' => 'Each selective field cannot exceed 100 characters.',
            'emergency_contact.max' => 'Emergency contact cannot exceed 255 characters.',
            'post_rollback_actions.*.max' => 'Each post-rollback action cannot exceed 500 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'version_id' => 'version',
            'rollback_reason' => 'rollback reason',
            'create_backup' => 'backup creation',
            'notify_stakeholders' => 'stakeholder notification',
            'approval_required' => 'approval requirement',
            'approval_justification' => 'approval justification',
            'affected_entities' => 'affected entities',
            'rollback_type' => 'rollback type',
            'selective_fields' => 'selective fields',
            'scheduled_at' => 'scheduled time',
            'maintenance_window' => 'maintenance window',
            'estimated_downtime' => 'estimated downtime',
            'rollback_notes' => 'rollback notes',
            'verification_checklist' => 'verification checklist',
            'emergency_contact' => 'emergency contact',
            'post_rollback_actions' => 'post-rollback actions',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateVersionRollbackability($validator);
            $this->validatePriorityRequirements($validator);
            $this->validateSchedulingConstraints($validator);
            $this->validateSelectiveRollback($validator);
        });
    }

    /**
     * Validate that the version can be rolled back to.
     */
    protected function validateVersionRollbackability($validator): void
    {
        $versionId = $this->input('version_id');
        $version = \App\Models\ClassDataVersion::find($versionId);
        
        if ($version) {
            // Check if version is not the current version
            $currentVersion = \App\Models\ClassDataVersion::where('entity_type', $version->entity_type)
                ->where('entity_id', $version->entity_id)
                ->orderBy('version_number', 'desc')
                ->first();
                
            if ($currentVersion && $currentVersion->id === $version->id) {
                $validator->errors()->add('version_id', 'Cannot rollback to the current version.');
            }

            // Check if version data is intact
            if (empty($version->data_snapshot)) {
                $validator->errors()->add('version_id', 'Selected version has no data snapshot available for rollback.');
            }

            // Check if version is too old (configurable limit)
            $maxRollbackDays = config('audit.max_rollback_days', 90);
            if ($version->created_at->diffInDays(now()) > $maxRollbackDays) {
                $validator->errors()->add('version_id', "Cannot rollback to versions older than {$maxRollbackDays} days.");
            }
        }
    }

    /**
     * Validate priority-based requirements.
     */
    protected function validatePriorityRequirements($validator): void
    {
        $priority = $this->input('priority');
        
        // High and critical priority rollbacks require approval
        if (in_array($priority, ['high', 'critical'])) {
            if (!$this->input('approval_required')) {
                $validator->errors()->add('approval_required', 'High and critical priority rollbacks require approval.');
            }
        }

        // Critical priority rollbacks require emergency contact
        if ($priority === 'critical' && !$this->input('emergency_contact')) {
            $validator->errors()->add('emergency_contact', 'Critical priority rollbacks require an emergency contact.');
        }

        // Critical and high priority rollbacks should have verification checklist
        if (in_array($priority, ['high', 'critical'])) {
            $checklist = $this->input('verification_checklist', []);
            if (empty($checklist)) {
                $validator->errors()->add('verification_checklist', 'High and critical priority rollbacks should include a verification checklist.');
            }
        }
    }

    /**
     * Validate scheduling constraints.
     */
    protected function validateSchedulingConstraints($validator): void
    {
        $scheduledAt = $this->input('scheduled_at');
        $priority = $this->input('priority');
        
        if ($scheduledAt) {
            $scheduledTime = \Carbon\Carbon::parse($scheduledAt);
            
            // Critical rollbacks cannot be scheduled too far in the future
            if ($priority === 'critical' && $scheduledTime->diffInHours(now()) > 24) {
                $validator->errors()->add('scheduled_at', 'Critical rollbacks cannot be scheduled more than 24 hours in advance.');
            }

            // Validate business hours for non-critical rollbacks
            if ($priority !== 'critical' && $this->input('maintenance_window')) {
                $hour = $scheduledTime->hour;
                if ($hour >= 8 && $hour <= 18) { // Business hours
                    $validator->errors()->add('scheduled_at', 'Maintenance window rollbacks should be scheduled outside business hours (8 AM - 6 PM).');
                }
            }
        }

        // Validate estimated downtime for scheduled rollbacks
        if ($scheduledAt && $this->input('estimated_downtime') > 60 && !$this->input('maintenance_window')) {
            $validator->errors()->add('maintenance_window', 'Rollbacks with estimated downtime over 1 hour should be scheduled during maintenance window.');
        }
    }

    /**
     * Validate selective rollback requirements.
     */
    protected function validateSelectiveRollback($validator): void
    {
        $rollbackType = $this->input('rollback_type');
        $selectiveFields = $this->input('selective_fields', []);
        
        if ($rollbackType === 'selective') {
            if (empty($selectiveFields)) {
                $validator->errors()->add('selective_fields', 'Selective rollback requires at least one field to be specified.');
            }

            // Validate that selective fields exist in the version data
            $versionId = $this->input('version_id');
            $version = \App\Models\ClassDataVersion::find($versionId);
            
            if ($version && !empty($version->data_snapshot)) {
                $availableFields = array_keys(json_decode($version->data_snapshot, true) ?: []);
                $invalidFields = array_diff($selectiveFields, $availableFields);
                
                if (!empty($invalidFields)) {
                    $validator->errors()->add('selective_fields', 'Invalid fields selected: ' . implode(', ', $invalidFields));
                }
            }
        }
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Clean and prepare data
        if ($this->has('rollback_reason')) {
            $this->merge([
                'rollback_reason' => trim($this->input('rollback_reason'))
            ]);
        }

        if ($this->has('approval_justification')) {
            $this->merge([
                'approval_justification' => trim($this->input('approval_justification'))
            ]);
        }

        if ($this->has('rollback_notes')) {
            $this->merge([
                'rollback_notes' => trim($this->input('rollback_notes'))
            ]);
        }

        // Set default values
        $this->merge([
            'create_backup' => $this->input('create_backup', true),
            'notify_stakeholders' => $this->input('notify_stakeholders', true),
            'maintenance_window' => $this->input('maintenance_window', false),
        ]);

        // Auto-determine approval requirement based on priority
        $priority = $this->input('priority');
        if (in_array($priority, ['high', 'critical']) && !$this->has('approval_required')) {
            $this->merge(['approval_required' => true]);
        }
    }
}