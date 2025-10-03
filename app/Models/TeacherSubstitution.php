<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TeacherSubstitution extends Model
{
    use HasFactory;

    protected $fillable = [
        'absence_id',
        'substitute_teacher_id',
        'original_teacher_id',
        'class_id',
        'subject_id',
        'period_number',
        'substitution_date',
        'start_time',
        'end_time',
        'status',
        'assigned_by',
        'assigned_at',
        'confirmed_at',
        'completed_at',
        'notes',
        'preparation_materials',
        'feedback',
        'rating',
        'notification_sent',
        'auto_assigned',
        'reason',
        'priority',
        'is_emergency'
    ];

    protected $casts = [
        'substitution_date' => 'date',
        'assigned_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
        'notification_sent' => 'boolean',
        'auto_assigned' => 'boolean',
        'is_emergency' => 'boolean',
        'rating' => 'integer'
    ];

    // Substitution status
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_DECLINED = 'declined';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_NO_SHOW = 'no_show';

    /**
     * Relationship with TeacherAbsence
     */
    public function absence()
    {
        return $this->belongsTo(TeacherAbsence::class);
    }

    /**
     * Relationship with substitute teacher
     */
    public function substituteTeacher()
    {
        return $this->belongsTo(Teacher::class, 'substitute_teacher_id');
    }

    /**
     * Relationship with original teacher
     */
    public function originalTeacher()
    {
        return $this->belongsTo(Teacher::class, 'original_teacher_id');
    }

    // Legacy relationship for backward compatibility
    public function absentTeacher()
    {
        return $this->originalTeacher();
    }

    /**
     * Relationship with class
     */
    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    /**
     * Relationship with subject
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    /**
     * Relationship with assigned by user
     */
    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('substitution_date', Carbon::today());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('substitution_date', '>=', Carbon::today());
    }

    public function scopeEmergency($query)
    {
        return $query->where('is_emergency', true);
    }

    public function scopeByPriority($query, $priority = 'high')
    {
        return $query->where('priority', $priority);
    }

    // Helper methods
    public function getDurationInMinutes()
    {
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);
        return $end->diffInMinutes($start);
    }

    public function getFormattedTimeRange()
    {
        return Carbon::parse($this->start_time)->format('H:i') . ' - ' . 
               Carbon::parse($this->end_time)->format('H:i');
    }

    public function isOverdue()
    {
        return $this->status === self::STATUS_PENDING && 
               Carbon::parse($this->substitution_date . ' ' . $this->start_time)->isPast();
    }

    public function canBeAssigned()
    {
        return in_array($this->status, [self::STATUS_PENDING]) && 
               !$this->isOverdue();
    }

    public function markAsConfirmed($assignedBy = null)
    {
        $this->update([
            'status' => self::STATUS_CONFIRMED,
            'confirmed_at' => now(),
            'assigned_by' => $assignedBy,
        ]);
    }

    public function markAsCompleted($feedback = null, $rating = null)
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'feedback' => $feedback,
            'rating' => $rating,
        ]);
    }

    public function markAsCancelled()
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);
    }

    public function markAsDeclined()
    {
        $this->update([
            'status' => self::STATUS_DECLINED,
        ]);
    }

    // Static methods for finding substitutes
    public static function findAvailableSubstitutes($date, $startTime, $endTime, $subjectId = null, $classId = null)
    {
        // Get teachers who are not absent on this date
        $absentTeacherIds = TeacherAbsence::where('absence_date', '<=', $date)
            ->where(function($query) use ($date) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', $date);
            })
            ->where('status', 'approved')
            ->pluck('teacher_id');

        // Get teachers who don't have conflicting substitutions
        $busyTeacherIds = self::where('substitution_date', $date)
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->whereIn('status', [self::STATUS_CONFIRMED, self::STATUS_PENDING])
            ->pluck('substitute_teacher_id');

        $availableTeachers = Teacher::where('is_active', true)
            ->whereNotIn('id', $absentTeacherIds)
            ->whereNotIn('id', $busyTeacherIds)
            ->get();

        // Filter by subject expertise if specified
        if ($subjectId) {
            $availableTeachers = $availableTeachers->filter(function ($teacher) use ($subjectId) {
                return $teacher->subjects()->where('subject_id', $subjectId)->exists();
            });
        }

        // Check daily substitution limits
        $availableTeachers = $availableTeachers->filter(function ($teacher) use ($date) {
            $todaySubstitutions = self::where('substitute_teacher_id', $teacher->id)
                                     ->where('substitution_date', $date)
                                     ->whereIn('status', [self::STATUS_CONFIRMED, self::STATUS_COMPLETED])
                                     ->count();
            
            return $todaySubstitutions < 3; // Max 3 substitutions per day
        });

        return $availableTeachers;
    }

    public static function autoAssignSubstitute($substitutionId)
    {
        $substitution = self::find($substitutionId);
        
        if (!$substitution || !$substitution->canBeAssigned()) {
            return false;
        }

        $availableTeachers = self::findAvailableSubstitutes(
            $substitution->substitution_date,
            $substitution->start_time,
            $substitution->end_time,
            $substitution->subject_id,
            $substitution->class_id
        );

        if ($availableTeachers->isEmpty()) {
            return false;
        }

        // Prioritize teachers with subject expertise
        $bestMatch = $availableTeachers->sortByDesc(function ($teacher) use ($substitution) {
            $score = 0;
            
            // Subject expertise
            if ($substitution->subject_id && $teacher->subjects()->where('subject_id', $substitution->subject_id)->exists()) {
                $score += 10;
            }
            
            // Class familiarity
            if ($substitution->class_id && $teacher->classes()->where('class_id', $substitution->class_id)->exists()) {
                $score += 5;
            }
            
            // Fewer substitutions today (prefer less busy teachers)
            $todaySubstitutions = self::where('substitute_teacher_id', $teacher->id)
                                     ->where('substitution_date', $substitution->substitution_date)
                                     ->whereIn('status', [self::STATUS_CONFIRMED, self::STATUS_COMPLETED])
                                     ->count();
            $score += (3 - $todaySubstitutions);
            
            return $score;
        })->first();

        if ($bestMatch) {
            $substitution->update([
                'substitute_teacher_id' => $bestMatch->id,
                'status' => self::STATUS_PENDING,
                'assigned_at' => now(),
                'auto_assigned' => true,
            ]);

            return $bestMatch;
        }

        return false;
    }

    public static function getSubstitutionStats($teacherId = null, $startDate = null, $endDate = null)
    {
        $query = self::query();

        if ($teacherId) {
            $query->where('substitute_teacher_id', $teacherId);
        }

        if ($startDate) {
            $query->where('substitution_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('substitution_date', '<=', $endDate);
        }

        return [
            'total' => $query->count(),
            'completed' => $query->where('status', self::STATUS_COMPLETED)->count(),
            'pending' => $query->where('status', self::STATUS_PENDING)->count(),
            'confirmed' => $query->where('status', self::STATUS_CONFIRMED)->count(),
            'declined' => $query->where('status', self::STATUS_DECLINED)->count(),
            'no_show' => $query->where('status', self::STATUS_NO_SHOW)->count(),
            'average_rating' => $query->whereNotNull('rating')->avg('rating'),
            'emergency_count' => $query->where('is_emergency', true)->count(),
        ];
    }

    public static function getTeacherPerformance($teacherId, $months = 6)
    {
        $startDate = Carbon::now()->subMonths($months);
        
        $substitutions = self::where('substitute_teacher_id', $teacherId)
            ->where('substitution_date', '>=', $startDate)
            ->get();

        return [
            'total_substitutions' => $substitutions->count(),
            'completed_substitutions' => $substitutions->where('status', self::STATUS_COMPLETED)->count(),
            'declined_substitutions' => $substitutions->where('status', self::STATUS_DECLINED)->count(),
            'no_show_count' => $substitutions->where('status', self::STATUS_NO_SHOW)->count(),
            'average_rating' => $substitutions->whereNotNull('rating')->avg('rating'),
            'reliability_score' => $substitutions->count() > 0 ? 
                ($substitutions->where('status', self::STATUS_COMPLETED)->count() / $substitutions->count()) * 100 : 0,
            'response_time' => $substitutions->whereNotNull('confirmed_at')->avg(function($sub) {
                return Carbon::parse($sub->assigned_at)->diffInMinutes(Carbon::parse($sub->confirmed_at));
            }),
        ];
    }

    public function hasConflict($date, $startTime, $endTime)
    {
        return self::where('substitute_teacher_id', $this->substitute_teacher_id)
            ->where('substitution_date', $date)
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->whereIn('status', [self::STATUS_CONFIRMED, self::STATUS_PENDING])
            ->where('id', '!=', $this->id)
            ->exists();
    }
}