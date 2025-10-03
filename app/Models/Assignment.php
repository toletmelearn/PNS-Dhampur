<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class Assignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'instructions',
        'subject_id',
        'class_id',
        'teacher_id',
        'syllabus_id',
        'assignment_type',
        'total_marks',
        'due_date',
        'due_time',
        'submission_type',
        'allow_late_submission',
        'late_penalty_per_day',
        'max_late_days',
        'is_published',
        'is_active',
        'visibility',
        'attachment_path',
        'attachment_type',
        'attachment_size',
        'original_filename',
        'estimated_duration',
        'difficulty_level',
        'tags',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'due_date' => 'date',
        'due_time' => 'datetime',
        'is_published' => 'boolean',
        'is_active' => 'boolean',
        'allow_late_submission' => 'boolean',
        'total_marks' => 'integer',
        'late_penalty_per_day' => 'decimal:2',
        'max_late_days' => 'integer',
        'attachment_size' => 'integer',
        'estimated_duration' => 'integer',
        'tags' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Assignment type constants
    const TYPE_HOMEWORK = 'homework';
    const TYPE_PROJECT = 'project';
    const TYPE_QUIZ = 'quiz';
    const TYPE_EXAM = 'exam';
    const TYPE_PRESENTATION = 'presentation';
    const TYPE_RESEARCH = 'research';
    const TYPE_PRACTICAL = 'practical';

    // Submission type constants
    const SUBMISSION_ONLINE = 'online';
    const SUBMISSION_OFFLINE = 'offline';
    const SUBMISSION_BOTH = 'both';

    // Visibility constants
    const VISIBILITY_PUBLIC = 'public';
    const VISIBILITY_CLASS_ONLY = 'class_only';
    const VISIBILITY_PRIVATE = 'private';

    // Difficulty level constants
    const DIFFICULTY_EASY = 'easy';
    const DIFFICULTY_MEDIUM = 'medium';
    const DIFFICULTY_HARD = 'hard';

    /**
     * Relationships
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function syllabus()
    {
        return $this->belongsTo(Syllabus::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function submissions()
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function reminders()
    {
        return $this->hasMany(AssignmentReminder::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeForClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeForSubject($query, $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopeForTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('assignment_type', $type);
    }

    public function scopeDueToday($query)
    {
        return $query->whereDate('due_date', today());
    }

    public function scopeDueTomorrow($query)
    {
        return $query->whereDate('due_date', today()->addDay());
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', today());
    }

    public function scopeUpcoming($query, $days = 7)
    {
        return $query->whereBetween('due_date', [today(), today()->addDays($days)]);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
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

    public function isOverdue()
    {
        return $this->due_date < today();
    }

    public function isDueToday()
    {
        return $this->due_date->isToday();
    }

    public function isDueTomorrow()
    {
        return $this->due_date->isTomorrow();
    }

    public function getDaysUntilDue()
    {
        return today()->diffInDays($this->due_date, false);
    }

    public function getTimeUntilDue()
    {
        if ($this->due_time) {
            return now()->diffForHumans($this->due_time);
        }
        return $this->due_date->diffForHumans();
    }

    public function getSubmissionCount()
    {
        return $this->submissions()->count();
    }

    public function getPendingSubmissionCount()
    {
        return $this->submissions()->where('status', 'pending')->count();
    }

    public function getGradedSubmissionCount()
    {
        return $this->submissions()->where('status', 'graded')->count();
    }

    public function getAverageGrade()
    {
        return $this->submissions()
            ->where('status', 'graded')
            ->whereNotNull('marks_obtained')
            ->avg('marks_obtained');
    }

    public function getSubmissionRate()
    {
        $totalStudents = $this->class->students()->count();
        $submissionCount = $this->getSubmissionCount();
        
        return $totalStudents > 0 ? ($submissionCount / $totalStudents) * 100 : 0;
    }

    public function canBeViewedBy($user)
    {
        if (!$this->is_published) {
            return $user->id === $this->teacher_id || $user->id === $this->created_by;
        }

        if ($this->visibility === self::VISIBILITY_PUBLIC) {
            return true;
        }

        if ($this->visibility === self::VISIBILITY_PRIVATE) {
            return $user->id === $this->teacher_id || $user->id === $this->created_by;
        }

        if ($this->visibility === self::VISIBILITY_CLASS_ONLY) {
            if ($user->role === 'student') {
                return $user->student && $user->student->class_id === $this->class_id;
            }
            return $user->id === $this->teacher_id || $user->id === $this->created_by;
        }

        return false;
    }

    public function hasSubmissionFrom($studentId)
    {
        return $this->submissions()->where('student_id', $studentId)->exists();
    }

    public function getSubmissionFrom($studentId)
    {
        return $this->submissions()->where('student_id', $studentId)->first();
    }

    /**
     * Static Methods
     */
    public static function getTypeOptions()
    {
        return [
            self::TYPE_HOMEWORK => 'Homework',
            self::TYPE_PROJECT => 'Project',
            self::TYPE_QUIZ => 'Quiz',
            self::TYPE_EXAM => 'Exam',
            self::TYPE_PRESENTATION => 'Presentation',
            self::TYPE_RESEARCH => 'Research',
            self::TYPE_PRACTICAL => 'Practical'
        ];
    }

    public static function getSubmissionTypeOptions()
    {
        return [
            self::SUBMISSION_ONLINE => 'Online',
            self::SUBMISSION_OFFLINE => 'Offline',
            self::SUBMISSION_BOTH => 'Both'
        ];
    }

    public static function getVisibilityOptions()
    {
        return [
            self::VISIBILITY_PUBLIC => 'Public',
            self::VISIBILITY_CLASS_ONLY => 'Class Only',
            self::VISIBILITY_PRIVATE => 'Private'
        ];
    }

    public static function getDifficultyOptions()
    {
        return [
            self::DIFFICULTY_EASY => 'Easy',
            self::DIFFICULTY_MEDIUM => 'Medium',
            self::DIFFICULTY_HARD => 'Hard'
        ];
    }

    public static function getUpcomingAssignments($classId = null, $limit = 10)
    {
        $query = self::published()->active()->upcoming();
        
        if ($classId) {
            $query->forClass($classId);
        }
        
        return $query->orderBy('due_date')
            ->limit($limit)
            ->get();
    }

    public static function getOverdueAssignments($classId = null, $limit = 10)
    {
        $query = self::published()->active()->overdue();
        
        if ($classId) {
            $query->forClass($classId);
        }
        
        return $query->orderBy('due_date', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function getAssignmentStats($teacherId = null)
    {
        $query = self::query();
        
        if ($teacherId) {
            $query->forTeacher($teacherId);
        }
        
        return [
            'total' => $query->count(),
            'published' => $query->published()->count(),
            'active' => $query->active()->count(),
            'overdue' => $query->overdue()->count(),
            'due_today' => $query->dueToday()->count(),
            'due_tomorrow' => $query->dueTomorrow()->count(),
            'by_type' => $query->selectRaw('assignment_type, COUNT(*) as count')
                ->groupBy('assignment_type')
                ->pluck('count', 'assignment_type'),
            'by_subject' => $query->with('subject')
                ->selectRaw('subject_id, COUNT(*) as count')
                ->groupBy('subject_id')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->subject->name ?? 'Unknown' => $item->count];
                }),
            'recent_assignments' => $query->recent(7)->count()
        ];
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($assignment) {
            // Delete associated attachment when assignment is deleted
            if ($assignment->attachment_path && Storage::exists($assignment->attachment_path)) {
                Storage::delete($assignment->attachment_path);
            }
        });

        // Send notification when assignment is created
        static::created(function ($assignment) {
            if ($assignment->is_published) {
                app(\App\Services\NotificationService::class)
                    ->createAssignmentCreatedNotification($assignment->id);
            }
        });

        // Send notification when assignment is updated (published)
        static::updated(function ($assignment) {
            if ($assignment->wasChanged('is_published') && $assignment->is_published) {
                app(\App\Services\NotificationService::class)
                    ->createAssignmentCreatedNotification($assignment->id);
            }
        });
    }
}