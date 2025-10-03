<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ExamPaperApproval extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'exam_paper_version_id',
        'approver_id',
        'approval_level',
        'status',
        'comments',
        'feedback',
        'priority',
        'submitted_at',
        'reviewed_at',
        'deadline',
        'is_required',
        'can_delegate',
        'delegated_to',
        'delegated_at',
        'approval_criteria',
        'score',
        'digital_signature',
        'metadata'
    ];

    protected $casts = [
        'feedback' => 'array',
        'approval_criteria' => 'array',
        'metadata' => 'array',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'deadline' => 'datetime',
        'delegated_at' => 'datetime',
        'is_required' => 'boolean',
        'can_delegate' => 'boolean',
        'score' => 'decimal:2'
    ];

    protected $dates = [
        'submitted_at',
        'reviewed_at',
        'deadline',
        'delegated_at',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_DELEGATED = 'delegated';

    // Approval level constants
    const LEVEL_DEPARTMENT_HEAD = 'department_head';
    const LEVEL_PRINCIPAL = 'principal';
    const LEVEL_ACADEMIC_COORDINATOR = 'academic_coordinator';
    const LEVEL_EXTERNAL_REVIEWER = 'external_reviewer';

    /**
     * Relationships
     */
    public function examPaperVersion(): BelongsTo
    {
        return $this->belongsTo(ExamPaperVersion::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function delegatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegated_to');
    }

    /**
     * Scopes
     */
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

    public function scopeOverdue($query)
    {
        return $query->where('deadline', '<', now())
                    ->where('status', self::STATUS_PENDING);
    }

    public function scopeByApprover($query, $approverId)
    {
        return $query->where('approver_id', $approverId);
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('approval_level', $level);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeByPriority($query, $priority = null)
    {
        if ($priority) {
            return $query->where('priority', $priority);
        }
        return $query->orderBy('priority');
    }

    /**
     * Business Logic Methods
     */
    public function approve(string $comments = null, array $feedback = [], float $score = null): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_APPROVED,
            'comments' => $comments,
            'feedback' => $feedback,
            'score' => $score,
            'reviewed_at' => now(),
            'digital_signature' => $this->generateDigitalSignature('approved')
        ]);

        // Log the approval
        $this->logApprovalAction('approved', $comments);

        // Check if this completes the approval process
        $this->checkApprovalCompletion();

        return true;
    }

    public function reject(string $reason, array $feedback = []): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_REJECTED,
            'comments' => $reason,
            'feedback' => $feedback,
            'reviewed_at' => now(),
            'digital_signature' => $this->generateDigitalSignature('rejected')
        ]);

        // Log the rejection
        $this->logApprovalAction('rejected', $reason);

        // Reject the entire version if this is a required approval
        if ($this->is_required) {
            $this->examPaperVersion->reject(Auth::user(), $reason);
        }

        return true;
    }

    public function delegate(User $delegateTo, string $reason = null): bool
    {
        if (!$this->can_delegate || $this->status !== self::STATUS_PENDING) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_DELEGATED,
            'delegated_to' => $delegateTo->id,
            'delegated_at' => now(),
            'comments' => $reason
        ]);

        // Create new approval for delegated user
        self::create([
            'exam_paper_version_id' => $this->exam_paper_version_id,
            'approver_id' => $delegateTo->id,
            'approval_level' => $this->approval_level,
            'priority' => $this->priority,
            'is_required' => $this->is_required,
            'can_delegate' => false, // Delegated approvals cannot be further delegated
            'submitted_at' => now(),
            'deadline' => $this->deadline,
            'approval_criteria' => $this->approval_criteria,
            'metadata' => array_merge($this->metadata ?? [], [
                'delegated_from' => $this->approver_id,
                'delegation_reason' => $reason
            ])
        ]);

        // Log the delegation
        $this->logApprovalAction('delegated', "Delegated to {$delegateTo->name}: {$reason}");

        return true;
    }

    public function extendDeadline(Carbon $newDeadline, string $reason = null): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $oldDeadline = $this->deadline;
        $this->update([
            'deadline' => $newDeadline,
            'metadata' => array_merge($this->metadata ?? [], [
                'deadline_extended' => [
                    'old_deadline' => $oldDeadline,
                    'new_deadline' => $newDeadline,
                    'reason' => $reason,
                    'extended_by' => Auth::id(),
                    'extended_at' => now()
                ]
            ])
        ]);

        // Log the deadline extension
        $this->logApprovalAction('deadline_extended', 
            "Deadline extended from {$oldDeadline} to {$newDeadline}: {$reason}");

        return true;
    }

    protected function generateDigitalSignature(string $action): string
    {
        $data = [
            'approval_id' => $this->id,
            'approver_id' => Auth::id(),
            'action' => $action,
            'timestamp' => now()->toISOString(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ];

        return hash('sha256', json_encode($data) . config('app.key'));
    }

    protected function verifyDigitalSignature(string $signature, string $action): bool
    {
        $expectedSignature = $this->generateDigitalSignature($action);
        return hash_equals($expectedSignature, $signature);
    }

    protected function checkApprovalCompletion(): void
    {
        $version = $this->examPaperVersion;
        
        // Check if all required approvals are completed
        $pendingRequired = $version->approvals()
            ->where('is_required', true)
            ->where('status', self::STATUS_PENDING)
            ->count();

        if ($pendingRequired === 0) {
            // All required approvals completed, check if any were rejected
            $rejectedRequired = $version->approvals()
                ->where('is_required', true)
                ->where('status', self::STATUS_REJECTED)
                ->count();

            if ($rejectedRequired === 0) {
                // All required approvals are approved
                $version->approve(Auth::user(), 'All required approvals completed');
            }
        }
    }

    protected function logApprovalAction(string $action, string $description): void
    {
        ExamPaperSecurityLog::create([
            'exam_paper_id' => $this->examPaperVersion->exam_paper_id,
            'exam_paper_version_id' => $this->exam_paper_version_id,
            'user_id' => Auth::id(),
            'action' => $action,
            'resource_type' => 'exam_paper_approval',
            'resource_id' => $this->id,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'metadata' => [
                'approval_level' => $this->approval_level,
                'priority' => $this->priority,
                'is_required' => $this->is_required
            ]
        ]);
    }

    /**
     * Utility Methods
     */
    public function isOverdue(): bool
    {
        return $this->deadline && 
               $this->deadline->isPast() && 
               $this->status === self::STATUS_PENDING;
    }

    public function getDaysUntilDeadline(): int
    {
        if (!$this->deadline) {
            return 0;
        }

        return max(0, now()->diffInDays($this->deadline, false));
    }

    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => $this->isOverdue() ? 'badge-danger' : 'badge-warning',
            self::STATUS_APPROVED => 'badge-success',
            self::STATUS_REJECTED => 'badge-danger',
            self::STATUS_DELEGATED => 'badge-info',
            default => 'badge-light'
        };
    }

    public function getLevelBadgeClass(): string
    {
        return match($this->approval_level) {
            self::LEVEL_DEPARTMENT_HEAD => 'badge-primary',
            self::LEVEL_ACADEMIC_COORDINATOR => 'badge-info',
            self::LEVEL_PRINCIPAL => 'badge-warning',
            self::LEVEL_EXTERNAL_REVIEWER => 'badge-secondary',
            default => 'badge-light'
        };
    }

    public function getPriorityBadgeClass(): string
    {
        return match($this->priority) {
            1 => 'badge-danger',  // High priority
            2 => 'badge-warning', // Medium priority
            3 => 'badge-info',    // Low priority
            default => 'badge-light'
        };
    }

    public function canApprove(): bool
    {
        return $this->status === self::STATUS_PENDING &&
               ($this->approver_id === Auth::id() || 
                ($this->delegated_to && $this->delegated_to === Auth::id()));
    }

    public function canDelegate(): bool
    {
        return $this->can_delegate &&
               $this->status === self::STATUS_PENDING &&
               $this->approver_id === Auth::id();
    }

    public function getFormattedLevel(): string
    {
        return match($this->approval_level) {
            self::LEVEL_DEPARTMENT_HEAD => 'Department Head',
            self::LEVEL_ACADEMIC_COORDINATOR => 'Academic Coordinator',
            self::LEVEL_PRINCIPAL => 'Principal',
            self::LEVEL_EXTERNAL_REVIEWER => 'External Reviewer',
            default => ucfirst(str_replace('_', ' ', $this->approval_level))
        };
    }

    public function getApprovalSummary(): array
    {
        return [
            'level' => $this->getFormattedLevel(),
            'approver' => $this->approver->name,
            'status' => ucfirst($this->status),
            'submitted_at' => $this->submitted_at->format('M d, Y H:i'),
            'deadline' => $this->deadline ? $this->deadline->format('M d, Y H:i') : null,
            'reviewed_at' => $this->reviewed_at ? $this->reviewed_at->format('M d, Y H:i') : null,
            'is_overdue' => $this->isOverdue(),
            'days_until_deadline' => $this->getDaysUntilDeadline(),
            'score' => $this->score,
            'comments' => $this->comments,
            'feedback' => $this->feedback
        ];
    }

    /**
     * Static Methods
     */
    public static function getPendingApprovalsForUser(User $user)
    {
        return self::where(function ($query) use ($user) {
            $query->where('approver_id', $user->id)
                  ->orWhere('delegated_to', $user->id);
        })
        ->where('status', self::STATUS_PENDING)
        ->with(['examPaperVersion.examPaper', 'examPaperVersion.creator'])
        ->orderBy('priority')
        ->orderBy('deadline');
    }

    public static function getApprovalStatistics(): array
    {
        return [
            'total_pending' => self::where('status', self::STATUS_PENDING)->count(),
            'total_approved' => self::where('status', self::STATUS_APPROVED)->count(),
            'total_rejected' => self::where('status', self::STATUS_REJECTED)->count(),
            'total_overdue' => self::overdue()->count(),
            'avg_approval_time' => self::where('status', self::STATUS_APPROVED)
                ->whereNotNull('reviewed_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, submitted_at, reviewed_at)) as avg_hours')
                ->value('avg_hours')
        ];
    }
}