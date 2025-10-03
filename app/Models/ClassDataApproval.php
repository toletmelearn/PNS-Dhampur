<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ClassDataApproval extends Model
{
    use HasFactory;

    protected $table = 'class_data_approvals';

    protected $fillable = [
        'audit_id',
        'approval_type',
        'status',
        'requested_by',
        'assigned_to',
        'approved_by',
        'priority',
        'deadline',
        'approval_reason',
        'rejection_reason',
        'conditions',
        'digital_signature',
        'signature_timestamp',
        'signature_certificate',
        'approval_workflow_step',
        'total_workflow_steps',
        'previous_approval_id',
        'next_approval_id',
        'delegation_history',
        'approval_metadata',
        'notification_sent',
        'escalation_level',
        'escalated_at',
        'escalated_to',
        'auto_approval_rule_id',
        'compliance_notes',
        'risk_assessment',
        'approval_duration_minutes'
    ];

    protected $casts = [
        'conditions' => 'array',
        'delegation_history' => 'array',
        'approval_metadata' => 'array',
        'risk_assessment' => 'array',
        'notification_sent' => 'boolean',
        'deadline' => 'datetime',
        'signature_timestamp' => 'datetime',
        'escalated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Approval types
    const TYPE_STANDARD = 'standard';
    const TYPE_EMERGENCY = 'emergency';
    const TYPE_BULK = 'bulk';
    const TYPE_AUTOMATIC = 'automatic';
    const TYPE_CONDITIONAL = 'conditional';
    const TYPE_MULTI_STEP = 'multi_step';

    // Approval statuses
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_DELEGATED = 'delegated';
    const STATUS_ESCALATED = 'escalated';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_CONDITIONAL_APPROVED = 'conditional_approved';

    // Priority levels
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';
    const PRIORITY_CRITICAL = 'critical';

    // Escalation levels
    const ESCALATION_NONE = 0;
    const ESCALATION_SUPERVISOR = 1;
    const ESCALATION_MANAGER = 2;
    const ESCALATION_DIRECTOR = 3;
    const ESCALATION_BOARD = 4;

    /**
     * Relationships
     */
    public function audit()
    {
        return $this->belongsTo(ClassDataAudit::class, 'audit_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function escalatedToUser()
    {
        return $this->belongsTo(User::class, 'escalated_to');
    }

    public function previousApproval()
    {
        return $this->belongsTo(ClassDataApproval::class, 'previous_approval_id');
    }

    public function nextApproval()
    {
        return $this->belongsTo(ClassDataApproval::class, 'next_approval_id');
    }

    public function autoApprovalRule()
    {
        return $this->belongsTo(AutoApprovalRule::class, 'auto_approval_rule_id');
    }

    /**
     * Scopes
     */
    public function scopeForAudit($query, $auditId)
    {
        return $query->where('audit_id', $auditId);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('approval_type', $type);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeRequestedBy($query, $userId)
    {
        return $query->where('requested_by', $userId);
    }

    public function scopeOverdue($query)
    {
        return $query->where('deadline', '<', now())
                    ->whereIn('status', [self::STATUS_PENDING, self::STATUS_DELEGATED]);
    }

    public function scopeEscalated($query)
    {
        return $query->where('escalation_level', '>', self::ESCALATION_NONE);
    }

    public function scopeWithDigitalSignature($query)
    {
        return $query->whereNotNull('digital_signature');
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    /**
     * Accessors & Mutators
     */
    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at->format('M d, Y H:i:s');
    }

    public function getFormattedDeadlineAttribute()
    {
        return $this->deadline ? $this->deadline->format('M d, Y H:i:s') : null;
    }

    public function getApprovalTypeDisplayAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->approval_type));
    }

    public function getStatusDisplayAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getPriorityDisplayAttribute()
    {
        return ucfirst($this->priority);
    }

    public function getTimeToApprovalAttribute()
    {
        if ($this->isApproved() && $this->updated_at) {
            return $this->created_at->diffInMinutes($this->updated_at);
        }
        return null;
    }

    public function getIsOverdueAttribute()
    {
        return $this->deadline && $this->deadline->isPast() && $this->isPending();
    }

    public function getEscalationLevelDisplayAttribute()
    {
        $levels = [
            self::ESCALATION_NONE => 'None',
            self::ESCALATION_SUPERVISOR => 'Supervisor',
            self::ESCALATION_MANAGER => 'Manager',
            self::ESCALATION_DIRECTOR => 'Director',
            self::ESCALATION_BOARD => 'Board'
        ];

        return $levels[$this->escalation_level] ?? 'Unknown';
    }

    /**
     * Helper Methods
     */
    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved()
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected()
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isDelegated()
    {
        return $this->status === self::STATUS_DELEGATED;
    }

    public function isEscalated()
    {
        return $this->status === self::STATUS_ESCALATED;
    }

    public function isExpired()
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    public function isCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isOverdue()
    {
        return $this->is_overdue;
    }

    public function hasDigitalSignature()
    {
        return !is_null($this->digital_signature);
    }

    public function isHighPriority()
    {
        return in_array($this->priority, [self::PRIORITY_HIGH, self::PRIORITY_URGENT, self::PRIORITY_CRITICAL]);
    }

    public function isMultiStep()
    {
        return $this->approval_type === self::TYPE_MULTI_STEP;
    }

    public function isFirstStep()
    {
        return $this->approval_workflow_step === 1;
    }

    public function isLastStep()
    {
        return $this->approval_workflow_step === $this->total_workflow_steps;
    }

    public function canBeApproved()
    {
        return $this->isPending() && !$this->isExpired();
    }

    public function canBeRejected()
    {
        return $this->isPending() && !$this->isExpired();
    }

    public function canBeDelegated()
    {
        return $this->isPending() && !$this->isExpired();
    }

    public function canBeEscalated()
    {
        return $this->isPending() && $this->escalation_level < self::ESCALATION_BOARD;
    }

    public function approve($approverId, $reason = null, $digitalSignature = null)
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approverId,
            'approval_reason' => $reason,
            'digital_signature' => $digitalSignature,
            'signature_timestamp' => $digitalSignature ? now() : null,
            'approval_duration_minutes' => $this->created_at->diffInMinutes(now())
        ]);

        // Update the related audit
        $this->audit->update([
            'approval_status' => ClassDataAudit::APPROVAL_APPROVED,
            'approved_by' => $approverId,
            'approved_at' => now()
        ]);

        return $this;
    }

    public function reject($approverId, $reason)
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'approved_by' => $approverId,
            'rejection_reason' => $reason,
            'approval_duration_minutes' => $this->created_at->diffInMinutes(now())
        ]);

        // Update the related audit
        $this->audit->update([
            'approval_status' => ClassDataAudit::APPROVAL_REJECTED,
            'approved_by' => $approverId,
            'approved_at' => now()
        ]);

        return $this;
    }

    public function delegate($fromUserId, $toUserId, $reason = null)
    {
        $delegationHistory = $this->delegation_history ?? [];
        $delegationHistory[] = [
            'from_user_id' => $fromUserId,
            'to_user_id' => $toUserId,
            'reason' => $reason,
            'delegated_at' => now()->toISOString()
        ];

        $this->update([
            'status' => self::STATUS_DELEGATED,
            'assigned_to' => $toUserId,
            'delegation_history' => $delegationHistory
        ]);

        return $this;
    }

    public function escalate($escalatedBy, $escalatedTo, $reason = null)
    {
        $this->update([
            'status' => self::STATUS_ESCALATED,
            'escalation_level' => $this->escalation_level + 1,
            'escalated_at' => now(),
            'escalated_to' => $escalatedTo,
            'assigned_to' => $escalatedTo,
            'approval_metadata' => array_merge($this->approval_metadata ?? [], [
                'escalation_reason' => $reason,
                'escalated_by' => $escalatedBy,
                'escalation_timestamp' => now()->toISOString()
            ])
        ]);

        return $this;
    }

    public function extendDeadline($newDeadline, $reason = null)
    {
        $this->update([
            'deadline' => $newDeadline,
            'approval_metadata' => array_merge($this->approval_metadata ?? [], [
                'deadline_extended' => true,
                'extension_reason' => $reason,
                'original_deadline' => $this->deadline,
                'extended_at' => now()->toISOString()
            ])
        ]);

        return $this;
    }

    public function addDigitalSignature($signature, $certificate = null)
    {
        $this->update([
            'digital_signature' => $signature,
            'signature_timestamp' => now(),
            'signature_certificate' => $certificate
        ]);

        return $this;
    }

    public function verifyDigitalSignature()
    {
        if (!$this->hasDigitalSignature()) {
            return false;
        }

        // Implement digital signature verification logic here
        // This would typically involve cryptographic verification
        return true; // Placeholder
    }

    /**
     * Static Methods
     */
    public static function createApprovalRequest($auditId, $options = [])
    {
        $approval = new static([
            'audit_id' => $auditId,
            'approval_type' => $options['type'] ?? self::TYPE_STANDARD,
            'status' => self::STATUS_PENDING,
            'requested_by' => $options['requested_by'] ?? auth()->id(),
            'assigned_to' => $options['assigned_to'],
            'priority' => $options['priority'] ?? self::PRIORITY_NORMAL,
            'deadline' => $options['deadline'],
            'approval_reason' => $options['reason'] ?? null,
            'conditions' => $options['conditions'] ?? [],
            'approval_workflow_step' => $options['workflow_step'] ?? 1,
            'total_workflow_steps' => $options['total_steps'] ?? 1,
            'previous_approval_id' => $options['previous_approval_id'] ?? null,
            'approval_metadata' => $options['metadata'] ?? [],
            'escalation_level' => self::ESCALATION_NONE,
            'risk_assessment' => $options['risk_assessment'] ?? []
        ]);

        $approval->save();

        return $approval;
    }

    public static function getApprovalStatistics($days = 30)
    {
        $startDate = Carbon::now()->subDays($days);

        return [
            'total_approvals' => static::where('created_at', '>=', $startDate)->count(),
            'pending' => static::pending()->count(),
            'approved' => static::approved()->where('created_at', '>=', $startDate)->count(),
            'rejected' => static::rejected()->where('created_at', '>=', $startDate)->count(),
            'overdue' => static::overdue()->count(),
            'escalated' => static::escalated()->where('created_at', '>=', $startDate)->count(),
            'average_approval_time' => static::approved()
                ->where('created_at', '>=', $startDate)
                ->whereNotNull('approval_duration_minutes')
                ->avg('approval_duration_minutes'),
            'by_priority' => static::where('created_at', '>=', $startDate)
                ->selectRaw('priority, COUNT(*) as count')
                ->groupBy('priority')
                ->pluck('count', 'priority')
                ->toArray(),
            'by_type' => static::where('created_at', '>=', $startDate)
                ->selectRaw('approval_type, COUNT(*) as count')
                ->groupBy('approval_type')
                ->pluck('count', 'approval_type')
                ->toArray(),
            'with_digital_signature' => static::withDigitalSignature()
                ->where('created_at', '>=', $startDate)
                ->count()
        ];
    }

    public static function getWorkloadByUser($userId = null, $days = 30)
    {
        $query = static::query();
        
        if ($userId) {
            $query->where('assigned_to', $userId);
        }

        $startDate = Carbon::now()->subDays($days);

        return [
            'pending_approvals' => $query->pending()->count(),
            'completed_approvals' => $query->whereIn('status', [self::STATUS_APPROVED, self::STATUS_REJECTED])
                ->where('created_at', '>=', $startDate)
                ->count(),
            'overdue_approvals' => $query->overdue()->count(),
            'high_priority_pending' => $query->pending()
                ->whereIn('priority', [self::PRIORITY_HIGH, self::PRIORITY_URGENT, self::PRIORITY_CRITICAL])
                ->count(),
            'average_response_time' => $query->whereIn('status', [self::STATUS_APPROVED, self::STATUS_REJECTED])
                ->where('created_at', '>=', $startDate)
                ->whereNotNull('approval_duration_minutes')
                ->avg('approval_duration_minutes')
        ];
    }
}