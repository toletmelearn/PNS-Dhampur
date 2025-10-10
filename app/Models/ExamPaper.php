<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExamPaper extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'paper_code',
        'subject_id',
        'class_id',
        'exam_id',
        'teacher_id',
        'duration_minutes',
        'total_marks',
        'instructions',
        'paper_type',
        'difficulty_level',
        'submission_deadline',
        'status',
        'published_at',
        'published_by',
        'submitted_at',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejected_by',
        'rejection_reason'
    ];

    protected $casts = [
        'submission_deadline' => 'datetime',
        'published_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime'
    ];

    // Relationships
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class)->orderBy('order_number');
    }

    public function publishedBy()
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeByTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeBySubject($query, $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopeByClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeByExam($query, $examId)
    {
        return $query->where('exam_id', $examId);
    }

    // Accessors - Return safe CSS classes instead of HTML
    public function getStatusBadgeClassAttribute()
    {
        $classes = [
            'draft' => 'badge-secondary',
            'submitted' => 'badge-info',
            'published' => 'badge-success',
            'approved' => 'badge-primary',
            'rejected' => 'badge-danger'
        ];

        return $classes[$this->status] ?? 'badge-secondary';
    }

    public function getStatusBadgeTextAttribute()
    {
        $texts = [
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'published' => 'Published',
            'approved' => 'Approved',
            'rejected' => 'Rejected'
        ];

        return $texts[$this->status] ?? 'Unknown';
    }

    public function getDifficultyBadgeClassAttribute()
    {
        $classes = [
            'easy' => 'badge-success',
            'medium' => 'badge-warning',
            'hard' => 'badge-danger'
        ];

        return $classes[$this->difficulty_level] ?? 'badge-secondary';
    }

    public function getDifficultyBadgeTextAttribute()
    {
        $texts = [
            'easy' => 'Easy',
            'medium' => 'Medium',
            'hard' => 'Hard'
        ];

        return $texts[$this->difficulty_level] ?? 'Unknown';
    }

    public function getPaperTypeBadgeClassAttribute()
    {
        $classes = [
            'objective' => 'badge-info',
            'subjective' => 'badge-primary',
            'mixed' => 'badge-secondary'
        ];

        return $classes[$this->paper_type] ?? 'badge-secondary';
    }

    public function getPaperTypeBadgeTextAttribute()
    {
        $texts = [
            'objective' => 'Objective',
            'subjective' => 'Subjective',
            'mixed' => 'Mixed'
        ];

        return $texts[$this->paper_type] ?? 'Unknown';
    }

    public function getFormattedDurationAttribute()
    {
        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;
        
        if ($hours > 0) {
            return $hours . 'h ' . ($minutes > 0 ? $minutes . 'm' : '');
        }
        
        return $minutes . 'm';
    }

    public function getQuestionsCountAttribute()
    {
        return $this->questions()->count();
    }

    public function getCalculatedTotalMarksAttribute()
    {
        return $this->questions()->sum('marks');
    }

    public function getIsEditableAttribute()
    {
        return in_array($this->status, ['draft', 'rejected']);
    }

    public function getIsPublishableAttribute()
    {
        return $this->status === 'draft' && 
               $this->questions()->count() > 0 && 
               $this->calculated_total_marks === $this->total_marks;
    }

    public function getIsSubmittableAttribute()
    {
        return $this->status === 'draft' && 
               $this->questions()->count() > 0;
    }

    public function getIsApprovableAttribute()
    {
        return $this->status === 'submitted';
    }

    public function getIsRejectableAttribute()
    {
        return $this->status === 'submitted';
    }

    public function getIsDeletableAttribute()
    {
        return in_array($this->status, ['draft', 'rejected']);
    }

    public function getIsOverdueAttribute()
    {
        return $this->submission_deadline && 
               $this->submission_deadline->isPast() && 
               in_array($this->status, ['draft']);
    }

    // Methods
    public function canBeEditedBy($user)
    {
        return $user->hasRole('admin') || 
               ($this->teacher_id === $user->id && $this->is_editable);
    }

    public function canBeViewedBy($user)
    {
        return $user->hasRole('admin') || 
               $this->teacher_id === $user->id;
    }

    public function canBePublishedBy($user)
    {
        return ($user->hasRole('admin') || $this->teacher_id === $user->id) && 
               $this->is_publishable;
    }

    public function canBeSubmittedBy($user)
    {
        return ($user->hasRole('admin') || $this->teacher_id === $user->id) && 
               $this->is_submittable;
    }

    public function canBeApprovedBy($user)
    {
        return $user->hasRole('admin') && $this->is_approvable;
    }

    public function canBeRejectedBy($user)
    {
        return $user->hasRole('admin') && $this->is_rejectable;
    }

    public function canBeDeletedBy($user)
    {
        return ($user->hasRole('admin') || $this->teacher_id === $user->id) && 
               $this->is_deletable;
    }

    public function validateQuestions()
    {
        $errors = [];
        
        if ($this->questions()->count() === 0) {
            $errors[] = 'Exam paper must have at least one question.';
        }
        
        $totalMarks = $this->questions()->sum('marks');
        if ($totalMarks !== $this->total_marks) {
            $errors[] = "Total marks of questions ({$totalMarks}) does not match paper total marks ({$this->total_marks}).";
        }
        
        // Check for questions without proper answers for objective types
        $objectiveQuestions = $this->questions()->whereIn('question_type', ['mcq', 'true_false'])->get();
        foreach ($objectiveQuestions as $question) {
            if (empty($question->correct_answer)) {
                $errors[] = "Question #{$question->order_number} ({$question->question_type}) must have a correct answer.";
            }
            
            if ($question->question_type === 'mcq' && empty($question->options)) {
                $errors[] = "Question #{$question->order_number} (MCQ) must have options.";
            }
        }
        
        return $errors;
    }

    public function getQuestionsByType()
    {
        return $this->questions()
            ->selectRaw('question_type, COUNT(*) as count, SUM(marks) as total_marks')
            ->groupBy('question_type')
            ->get()
            ->keyBy('question_type');
    }

    public function getStatistics()
    {
        $questionsByType = $this->getQuestionsByType();
        
        return [
            'total_questions' => $this->questions_count,
            'total_marks' => $this->total_marks,
            'calculated_marks' => $this->calculated_total_marks,
            'duration' => $this->formatted_duration,
            'questions_by_type' => $questionsByType,
            'average_marks_per_question' => $this->questions_count > 0 ? round($this->total_marks / $this->questions_count, 2) : 0,
            'marks_per_minute' => $this->duration_minutes > 0 ? round($this->total_marks / $this->duration_minutes, 2) : 0
        ];
    }
}