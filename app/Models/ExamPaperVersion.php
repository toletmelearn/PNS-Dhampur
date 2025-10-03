<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ExamPaperVersion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'exam_paper_id',
        'version_number',
        'title',
        'description',
        'questions',
        'marking_scheme',
        'total_marks',
        'duration_minutes',
        'difficulty_level',
        'instructions',
        'metadata',
        'status',
        'change_summary',
        'created_by',
        'approved_at',
        'approved_by',
        'approval_notes',
        'is_current_version',
        'checksum'
    ];

    protected $casts = [
        'questions' => 'array',
        'marking_scheme' => 'array',
        'instructions' => 'array',
        'metadata' => 'array',
        'approved_at' => 'datetime',
        'is_current_version' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $dates = [
        'approved_at',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_REVIEW = 'review';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_ARCHIVED = 'archived';

    // Difficulty level constants
    const DIFFICULTY_EASY = 'easy';
    const DIFFICULTY_MEDIUM = 'medium';
    const DIFFICULTY_HARD = 'hard';

    /**
     * Relationships
     */
    public function examPaper(): BelongsTo
    {
        return $this->belongsTo(ExamPaper::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(ExamPaperApproval::class);
    }

    public function securityLogs(): HasMany
    {
        return $this->hasMany(ExamPaperSecurityLog::class);
    }

    /**
     * Scopes
     */
    public function scopeCurrentVersion($query)
    {
        return $query->where('is_current_version', true);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePendingApproval($query)
    {
        return $query->where('status', self::STATUS_REVIEW);
    }

    public function scopeByDifficulty($query, $difficulty)
    {
        return $query->where('difficulty_level', $difficulty);
    }

    /**
     * Business Logic Methods
     */
    public function generateChecksum(): string
    {
        $data = [
            'questions' => $this->questions,
            'marking_scheme' => $this->marking_scheme,
            'total_marks' => $this->total_marks,
            'duration_minutes' => $this->duration_minutes,
            'instructions' => $this->instructions
        ];
        
        return hash('sha256', json_encode($data));
    }

    public function verifyIntegrity(): bool
    {
        return $this->checksum === $this->generateChecksum();
    }

    public function submitForApproval(): bool
    {
        if ($this->status !== self::STATUS_DRAFT) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_REVIEW,
            'checksum' => $this->generateChecksum()
        ]);

        // Create approval requests for required approvers
        $this->createApprovalRequests();

        // Log the submission
        $this->logSecurityEvent('submitted', 'Exam paper version submitted for approval');

        return true;
    }

    public function approve(User $approver, string $notes = null): bool
    {
        if ($this->status !== self::STATUS_REVIEW) {
            return false;
        }

        // Check if all required approvals are completed
        $pendingApprovals = $this->approvals()
            ->where('status', ExamPaperApproval::STATUS_PENDING)
            ->where('is_required', true)
            ->count();

        if ($pendingApprovals > 1) {
            // Still pending other approvals
            return true;
        }

        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => $approver->id,
            'approval_notes' => $notes
        ]);

        // Set as current version if approved
        $this->setAsCurrentVersion();

        // Log the approval
        $this->logSecurityEvent('approved', 'Exam paper version approved by ' . $approver->name);

        return true;
    }

    public function reject(User $approver, string $reason): bool
    {
        if ($this->status !== self::STATUS_REVIEW) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_REJECTED,
            'approval_notes' => $reason
        ]);

        // Log the rejection
        $this->logSecurityEvent('rejected', 'Exam paper version rejected by ' . $approver->name . ': ' . $reason);

        return true;
    }

    public function setAsCurrentVersion(): void
    {
        // Remove current version flag from other versions
        self::where('exam_paper_id', $this->exam_paper_id)
            ->where('id', '!=', $this->id)
            ->update(['is_current_version' => false]);

        // Set this version as current
        $this->update(['is_current_version' => true]);

        // Log the change
        $this->logSecurityEvent('set_current', 'Version set as current version');
    }

    public function archive(): bool
    {
        if ($this->is_current_version) {
            return false; // Cannot archive current version
        }

        $this->update(['status' => self::STATUS_ARCHIVED]);

        // Log the archival
        $this->logSecurityEvent('archived', 'Exam paper version archived');

        return true;
    }

    public function createNewVersion(array $changes): self
    {
        $newVersion = self::create([
            'exam_paper_id' => $this->exam_paper_id,
            'version_number' => $this->getNextVersionNumber(),
            'title' => $changes['title'] ?? $this->title,
            'description' => $changes['description'] ?? $this->description,
            'questions' => $changes['questions'] ?? $this->questions,
            'marking_scheme' => $changes['marking_scheme'] ?? $this->marking_scheme,
            'total_marks' => $changes['total_marks'] ?? $this->total_marks,
            'duration_minutes' => $changes['duration_minutes'] ?? $this->duration_minutes,
            'difficulty_level' => $changes['difficulty_level'] ?? $this->difficulty_level,
            'instructions' => $changes['instructions'] ?? $this->instructions,
            'metadata' => $changes['metadata'] ?? $this->metadata,
            'change_summary' => $changes['change_summary'] ?? 'New version created',
            'created_by' => Auth::id(),
            'status' => self::STATUS_DRAFT
        ]);

        // Log the creation
        $newVersion->logSecurityEvent('created', 'New version created from version ' . $this->version_number);

        return $newVersion;
    }

    protected function getNextVersionNumber(): int
    {
        return self::where('exam_paper_id', $this->exam_paper_id)
            ->max('version_number') + 1;
    }

    protected function createApprovalRequests(): void
    {
        $approvalLevels = [
            ['level' => ExamPaperApproval::LEVEL_DEPARTMENT_HEAD, 'priority' => 1, 'required' => true],
            ['level' => ExamPaperApproval::LEVEL_ACADEMIC_COORDINATOR, 'priority' => 2, 'required' => true],
            ['level' => ExamPaperApproval::LEVEL_PRINCIPAL, 'priority' => 3, 'required' => true],
        ];

        foreach ($approvalLevels as $level) {
            // Find appropriate approver based on level
            $approver = $this->findApproverByLevel($level['level']);
            
            if ($approver) {
                ExamPaperApproval::create([
                    'exam_paper_version_id' => $this->id,
                    'approver_id' => $approver->id,
                    'approval_level' => $level['level'],
                    'priority' => $level['priority'],
                    'is_required' => $level['required'],
                    'submitted_at' => now(),
                    'deadline' => now()->addDays(3) // 3 days deadline
                ]);
            }
        }
    }

    protected function findApproverByLevel(string $level): ?User
    {
        // This would be implemented based on your user role system
        // For now, returning a placeholder
        return User::whereHas('roles', function ($query) use ($level) {
            $query->where('name', $level);
        })->first();
    }

    public function logSecurityEvent(string $action, string $description, array $metadata = []): void
    {
        ExamPaperSecurityLog::create([
            'exam_paper_id' => $this->exam_paper_id,
            'exam_paper_version_id' => $this->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'resource_type' => 'exam_paper_version',
            'resource_id' => $this->id,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'metadata' => array_merge($metadata, [
                'version_number' => $this->version_number,
                'status' => $this->status
            ])
        ]);
    }

    /**
     * Utility Methods
     */
    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'badge-secondary',
            self::STATUS_REVIEW => 'badge-warning',
            self::STATUS_APPROVED => 'badge-success',
            self::STATUS_REJECTED => 'badge-danger',
            self::STATUS_ARCHIVED => 'badge-dark',
            default => 'badge-light'
        };
    }

    public function getDifficultyBadgeClass(): string
    {
        return match($this->difficulty_level) {
            self::DIFFICULTY_EASY => 'badge-success',
            self::DIFFICULTY_MEDIUM => 'badge-warning',
            self::DIFFICULTY_HARD => 'badge-danger',
            default => 'badge-light'
        };
    }

    public function getFormattedDuration(): string
    {
        $hours = intval($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;
        
        if ($hours > 0) {
            return $hours . 'h ' . ($minutes > 0 ? $minutes . 'm' : '');
        }
        
        return $minutes . 'm';
    }

    public function canEdit(): bool
    {
        return $this->status === self::STATUS_DRAFT && 
               ($this->created_by === Auth::id() || Auth::user()->hasRole('admin'));
    }

    public function canApprove(): bool
    {
        return $this->status === self::STATUS_REVIEW &&
               $this->approvals()->where('approver_id', Auth::id())
                   ->where('status', ExamPaperApproval::STATUS_PENDING)
                   ->exists();
    }

    public function getApprovalProgress(): array
    {
        $total = $this->approvals()->where('is_required', true)->count();
        $completed = $this->approvals()
            ->where('is_required', true)
            ->whereIn('status', [ExamPaperApproval::STATUS_APPROVED, ExamPaperApproval::STATUS_REJECTED])
            ->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'percentage' => $total > 0 ? round(($completed / $total) * 100, 2) : 0
        ];
    }
}