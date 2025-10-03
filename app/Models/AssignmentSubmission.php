<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AssignmentSubmission extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'assignment_id',
        'student_id',
        'submission_text',
        'attachment_path',
        'attachment_type',
        'attachment_size',
        'original_filename',
        'submitted_at',
        'status',
        'marks_obtained',
        'feedback',
        'teacher_comments',
        'graded_at',
        'graded_by',
        'is_late',
        'late_days',
        'penalty_applied',
        'final_marks',
        'submission_count',
        'last_modified_at'
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'last_modified_at' => 'datetime',
        'is_late' => 'boolean',
        'marks_obtained' => 'decimal:2',
        'penalty_applied' => 'decimal:2',
        'final_marks' => 'decimal:2',
        'late_days' => 'integer',
        'submission_count' => 'integer',
        'attachment_size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_PENDING = 'pending';
    const STATUS_GRADED = 'graded';
    const STATUS_RETURNED = 'returned';
    const STATUS_RESUBMITTED = 'resubmitted';

    /**
     * Relationships
     */
    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function gradedBy()
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    /**
     * Scopes
     */
    public function scopeSubmitted($query)
    {
        return $query->where('status', self::STATUS_SUBMITTED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeGraded($query)
    {
        return $query->where('status', self::STATUS_GRADED);
    }

    public function scopeLate($query)
    {
        return $query->where('is_late', true);
    }

    public function scopeOnTime($query)
    {
        return $query->where('is_late', false);
    }

    public function scopeForAssignment($query, $assignmentId)
    {
        return $query->where('assignment_id', $assignmentId);
    }

    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeRecentlySubmitted($query, $days = 7)
    {
        return $query->where('submitted_at', '>=', now()->subDays($days));
    }

    public function scopeRecentlyGraded($query, $days = 7)
    {
        return $query->where('graded_at', '>=', now()->subDays($days));
    }

    /**
     * Helper Methods
     */
    public function getAttachmentUrl()
    {
        if ($this->attachment_path) {
            return Storage::url($this->attachment_path);
        }
        return null;
    }

    public function getAttachmentSizeFormatted()
    {
        if (!$this->attachment_size) return '0 B';
        
        $bytes = $this->attachment_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function isSubmitted()
    {
        return in_array($this->status, [
            self::STATUS_SUBMITTED,
            self::STATUS_PENDING,
            self::STATUS_GRADED,
            self::STATUS_RETURNED,
            self::STATUS_RESUBMITTED
        ]);
    }

    public function isGraded()
    {
        return $this->status === self::STATUS_GRADED;
    }

    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isDraft()
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function getGradePercentage()
    {
        if ($this->assignment->total_marks > 0 && $this->final_marks !== null) {
            return ($this->final_marks / $this->assignment->total_marks) * 100;
        }
        return null;
    }

    public function getGradeLetter()
    {
        $percentage = $this->getGradePercentage();
        
        if ($percentage === null) return null;
        
        if ($percentage >= 90) return 'A+';
        if ($percentage >= 80) return 'A';
        if ($percentage >= 70) return 'B+';
        if ($percentage >= 60) return 'B';
        if ($percentage >= 50) return 'C+';
        if ($percentage >= 40) return 'C';
        if ($percentage >= 30) return 'D';
        return 'F';
    }

    public function getSubmissionDelay()
    {
        if (!$this->submitted_at || !$this->assignment->due_date) {
            return null;
        }

        $dueDateTime = $this->assignment->due_time ?? $this->assignment->due_date->endOfDay();
        
        if ($this->submitted_at <= $dueDateTime) {
            return 0; // On time
        }

        return $this->submitted_at->diffInDays($dueDateTime);
    }

    public function calculateLatePenalty()
    {
        if (!$this->is_late || !$this->assignment->late_penalty_per_day) {
            return 0;
        }

        $lateDays = min($this->late_days, $this->assignment->max_late_days ?? $this->late_days);
        return $lateDays * $this->assignment->late_penalty_per_day;
    }

    public function calculateFinalMarks()
    {
        if ($this->marks_obtained === null) {
            return null;
        }

        $penalty = $this->calculateLatePenalty();
        return max(0, $this->marks_obtained - $penalty);
    }

    public function markAsSubmitted()
    {
        $this->update([
            'status' => self::STATUS_SUBMITTED,
            'submitted_at' => now(),
            'is_late' => $this->getSubmissionDelay() > 0,
            'late_days' => max(0, $this->getSubmissionDelay()),
            'submission_count' => $this->submission_count + 1,
            'last_modified_at' => now()
        ]);
    }

    public function markAsGraded($marks, $feedback = null, $teacherComments = null, $gradedBy = null)
    {
        $penalty = $this->calculateLatePenalty();
        $finalMarks = max(0, $marks - $penalty);

        $this->update([
            'status' => self::STATUS_GRADED,
            'marks_obtained' => $marks,
            'penalty_applied' => $penalty,
            'final_marks' => $finalMarks,
            'feedback' => $feedback,
            'teacher_comments' => $teacherComments,
            'graded_at' => now(),
            'graded_by' => $gradedBy ?? auth()->id()
        ]);
    }

    public function returnForRevision($feedback = null)
    {
        $this->update([
            'status' => self::STATUS_RETURNED,
            'feedback' => $feedback,
            'last_modified_at' => now()
        ]);
    }

    /**
     * Static Methods
     */
    public static function getStatusOptions()
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SUBMITTED => 'Submitted',
            self::STATUS_PENDING => 'Pending Review',
            self::STATUS_GRADED => 'Graded',
            self::STATUS_RETURNED => 'Returned for Revision',
            self::STATUS_RESUBMITTED => 'Resubmitted'
        ];
    }

    public static function getSubmissionStats($assignmentId = null, $studentId = null)
    {
        $query = self::query();
        
        if ($assignmentId) {
            $query->forAssignment($assignmentId);
        }
        
        if ($studentId) {
            $query->forStudent($studentId);
        }
        
        return [
            'total' => $query->count(),
            'submitted' => $query->submitted()->count(),
            'pending' => $query->pending()->count(),
            'graded' => $query->graded()->count(),
            'late' => $query->late()->count(),
            'on_time' => $query->onTime()->count(),
            'average_grade' => $query->graded()->avg('final_marks'),
            'highest_grade' => $query->graded()->max('final_marks'),
            'lowest_grade' => $query->graded()->min('final_marks'),
            'recently_submitted' => $query->recentlySubmitted()->count(),
            'recently_graded' => $query->recentlyGraded()->count()
        ];
    }

    public static function getStudentSubmissionHistory($studentId, $limit = 10)
    {
        return self::forStudent($studentId)
            ->with(['assignment', 'assignment.subject', 'assignment.class'])
            ->orderBy('submitted_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function getPendingGrading($teacherId = null, $limit = 20)
    {
        $query = self::pending()
            ->with(['assignment', 'student', 'assignment.subject', 'assignment.class']);
        
        if ($teacherId) {
            $query->whereHas('assignment', function ($q) use ($teacherId) {
                $q->where('teacher_id', $teacherId);
            });
        }
        
        return $query->orderBy('submitted_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($submission) {
            $submission->submission_count = 1;
            $submission->last_modified_at = now();
        });

        static::updating(function ($submission) {
            $submission->last_modified_at = now();
            
            // Send notification when assignment is graded
            if ($submission->isDirty('status') && $submission->status === self::STATUS_GRADED) {
                try {
                    $notificationService = app(\App\Services\NotificationService::class);
                    $notificationService->sendAssignmentGradedNotification($submission);
                } catch (\Exception $e) {
                    \Log::error('Failed to send assignment graded notification: ' . $e->getMessage());
                }
            }
        });

        static::deleting(function ($submission) {
            // Delete associated attachment when submission is deleted
            if ($submission->attachment_path && Storage::exists($submission->attachment_path)) {
                Storage::delete($submission->attachment_path);
            }
        });
    }
}