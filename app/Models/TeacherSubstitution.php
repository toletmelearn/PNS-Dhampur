<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TeacherSubstitution extends Model
{
    use HasFactory;

    protected $fillable = [
        'absent_teacher_id',
        'substitute_teacher_id',
        'class_id',
        'date',
        'start_time',
        'end_time',
        'subject',
        'status',
        'reason',
        'notes',
        'priority',
        'is_emergency',
        'requested_at',
        'assigned_at',
        'completed_at',
        'requested_by',
        'assigned_by',
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_emergency' => 'boolean',
        'requested_at' => 'datetime',
        'assigned_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Relationships
    public function absentTeacher()
    {
        return $this->belongsTo(Teacher::class, 'absent_teacher_id');
    }

    public function substituteTeacher()
    {
        return $this->belongsTo(Teacher::class, 'substitute_teacher_id');
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAssigned($query)
    {
        return $query->where('status', 'assigned');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('date', Carbon::today());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('date', '>=', Carbon::today());
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
        return $this->status === 'pending' && 
               Carbon::parse($this->date . ' ' . $this->start_time)->isPast();
    }

    public function canBeAssigned()
    {
        return in_array($this->status, ['pending']) && 
               !$this->isOverdue();
    }

    public function markAsAssigned($substituteTeacherId, $assignedBy = null)
    {
        $this->update([
            'substitute_teacher_id' => $substituteTeacherId,
            'status' => 'assigned',
            'assigned_at' => now(),
            'assigned_by' => $assignedBy,
        ]);
    }

    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function markAsCancelled()
    {
        $this->update([
            'status' => 'cancelled',
        ]);
    }

    // Static methods for finding substitutes
    public static function findAvailableSubstitutes($date, $startTime, $endTime, $subject = null)
    {
        $availableTeachers = Teacher::whereHas('availability', function ($query) use ($date, $startTime, $endTime) {
            $query->where('date', $date)
                  ->where('status', 'available')
                  ->where('can_substitute', true)
                  ->where('start_time', '<=', $startTime)
                  ->where('end_time', '>=', $endTime);
        })->get();

        // Filter by subject expertise if specified
        if ($subject) {
            $availableTeachers = $availableTeachers->filter(function ($teacher) use ($subject) {
                $availability = $teacher->availability()->where('date', request('date'))->first();
                return $availability && str_contains(strtolower($availability->subject_expertise), strtolower($subject));
            });
        }

        // Check if teachers haven't exceeded their daily substitution limit
        $availableTeachers = $availableTeachers->filter(function ($teacher) use ($date) {
            $todaySubstitutions = self::where('substitute_teacher_id', $teacher->id)
                                     ->where('date', $date)
                                     ->whereIn('status', ['assigned', 'completed'])
                                     ->count();
            
            $availability = $teacher->availability()->where('date', $date)->first();
            $maxSubstitutions = $availability ? $availability->max_substitutions_per_day : 3;
            
            return $todaySubstitutions < $maxSubstitutions;
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
            $substitution->date,
            $substitution->start_time,
            $substitution->end_time,
            $substitution->subject
        );

        if ($availableTeachers->isEmpty()) {
            return false;
        }

        // Prioritize by experience and subject match
        $bestSubstitute = $availableTeachers->sortByDesc(function ($teacher) use ($substitution) {
            $score = $teacher->experience_years * 10;
            
            // Bonus for subject match
            if ($substitution->subject) {
                $availability = $teacher->availability()->where('date', $substitution->date)->first();
                if ($availability && str_contains(strtolower($availability->subject_expertise), strtolower($substitution->subject))) {
                    $score += 50;
                }
            }
            
            return $score;
        })->first();

        $substitution->markAsAssigned($bestSubstitute->id);
        
        return true;
    }
}