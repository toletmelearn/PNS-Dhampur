<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeacherAssignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'teacher_id',
        'school_id',
        'class_name',
        'section',
        'subject',
        'academic_year',
        'is_class_teacher',
        'assigned_by',
        'assigned_at',
        'effective_from',
        'effective_until',
        'workload_hours',
        'notes',
        'is_active'
    ];

    protected $casts = [
        'is_class_teacher' => 'boolean',
        'assigned_at' => 'datetime',
        'effective_from' => 'date',
        'effective_until' => 'date',
        'workload_hours' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    protected $dates = [
        'assigned_at',
        'effective_from',
        'effective_until',
        'deleted_at'
    ];

    /**
     * Get the teacher for this assignment
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get the school for this assignment
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the user who assigned this
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Get full class name with section
     */
    public function getFullClassNameAttribute(): string
    {
        if ($this->section) {
            return $this->class_name . ' - ' . $this->section;
        }
        
        return $this->class_name;
    }

    /**
     * Get assignment display name
     */
    public function getDisplayNameAttribute(): string
    {
        $parts = [$this->full_class_name];
        
        if ($this->subject) {
            $parts[] = $this->subject;
        }
        
        if ($this->is_class_teacher) {
            $parts[] = '(Class Teacher)';
        }
        
        return implode(' - ', $parts);
    }

    /**
     * Check if assignment is currently active
     */
    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        
        $now = now()->toDateString();
        
        // Check if current date is within effective period
        if ($this->effective_from && $this->effective_from > $now) {
            return false;
        }
        
        if ($this->effective_until && $this->effective_until < $now) {
            return false;
        }
        
        return true;
    }

    /**
     * Check if assignment is for current academic year
     */
    public function isCurrentAcademicYear(): bool
    {
        $currentYear = $this->getCurrentAcademicYear();
        return $this->academic_year === $currentYear;
    }

    /**
     * Get current academic year
     */
    protected function getCurrentAcademicYear(): string
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        
        // Assuming academic year starts in April
        if ($currentMonth >= 4) {
            return $currentYear . '-' . ($currentYear + 1);
        } else {
            return ($currentYear - 1) . '-' . $currentYear;
        }
    }

    /**
     * Check if teacher is class teacher for this assignment
     */
    public function isClassTeacher(): bool
    {
        return $this->is_class_teacher;
    }

    /**
     * Get assignment duration in days
     */
    public function getDurationInDays(): ?int
    {
        if (!$this->effective_from || !$this->effective_until) {
            return null;
        }
        
        return $this->effective_from->diffInDays($this->effective_until);
    }

    /**
     * Get remaining days for assignment
     */
    public function getRemainingDays(): ?int
    {
        if (!$this->effective_until) {
            return null;
        }
        
        $today = now()->toDateString();
        if ($this->effective_until < $today) {
            return 0;
        }
        
        return now()->diffInDays($this->effective_until);
    }

    /**
     * Scope: Active assignments only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Current assignments (within effective period)
     */
    public function scopeCurrent($query)
    {
        $now = now()->toDateString();
        
        return $query->where('is_active', true)
                    ->where(function($q) use ($now) {
                        $q->whereNull('effective_from')
                          ->orWhere('effective_from', '<=', $now);
                    })
                    ->where(function($q) use ($now) {
                        $q->whereNull('effective_until')
                          ->orWhere('effective_until', '>=', $now);
                    });
    }

    /**
     * Scope: Assignments by teacher
     */
    public function scopeByTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    /**
     * Scope: Assignments by school
     */
    public function scopeBySchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    /**
     * Scope: Assignments by class
     */
    public function scopeByClass($query, string $className, string $section = null)
    {
        $query = $query->where('class_name', $className);
        
        if ($section) {
            $query->where('section', $section);
        }
        
        return $query;
    }

    /**
     * Scope: Assignments by subject
     */
    public function scopeBySubject($query, string $subject)
    {
        return $query->where('subject', $subject);
    }

    /**
     * Scope: Class teacher assignments only
     */
    public function scopeClassTeachers($query)
    {
        return $query->where('is_class_teacher', true);
    }

    /**
     * Scope: Subject teacher assignments only
     */
    public function scopeSubjectTeachers($query)
    {
        return $query->where('is_class_teacher', false);
    }

    /**
     * Scope: Assignments by academic year
     */
    public function scopeByAcademicYear($query, string $academicYear)
    {
        return $query->where('academic_year', $academicYear);
    }

    /**
     * Scope: Current academic year assignments
     */
    public function scopeCurrentAcademicYear($query)
    {
        $currentYear = $this->getCurrentAcademicYear();
        return $query->where('academic_year', $currentYear);
    }

    /**
     * Get teacher's total workload hours
     */
    public static function getTeacherWorkload($teacherId, $schoolId = null, $academicYear = null): float
    {
        $query = self::where('teacher_id', $teacherId)
                    ->where('is_active', true);
        
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        
        if ($academicYear) {
            $query->where('academic_year', $academicYear);
        } else {
            $query->currentAcademicYear();
        }
        
        return $query->sum('workload_hours') ?? 0;
    }

    /**
     * Get all classes assigned to a teacher
     */
    public static function getTeacherClasses($teacherId, $schoolId = null, $academicYear = null): array
    {
        $query = self::where('teacher_id', $teacherId)
                    ->where('is_active', true)
                    ->current();
        
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        
        if ($academicYear) {
            $query->where('academic_year', $academicYear);
        } else {
            $query->currentAcademicYear();
        }
        
        return $query->get()
                    ->map(function($assignment) {
                        return [
                            'class_name' => $assignment->class_name,
                            'section' => $assignment->section,
                            'subject' => $assignment->subject,
                            'is_class_teacher' => $assignment->is_class_teacher,
                            'full_class_name' => $assignment->full_class_name,
                            'display_name' => $assignment->display_name,
                        ];
                    })
                    ->toArray();
    }

    /**
     * Get all subjects taught by a teacher
     */
    public static function getTeacherSubjects($teacherId, $schoolId = null, $academicYear = null): array
    {
        $query = self::where('teacher_id', $teacherId)
                    ->where('is_active', true)
                    ->current()
                    ->whereNotNull('subject');
        
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        
        if ($academicYear) {
            $query->where('academic_year', $academicYear);
        } else {
            $query->currentAcademicYear();
        }
        
        return $query->distinct('subject')
                    ->pluck('subject')
                    ->toArray();
    }

    /**
     * Check if teacher can access specific class
     */
    public static function canTeacherAccessClass($teacherId, string $className, string $section = null, $schoolId = null): bool
    {
        $query = self::where('teacher_id', $teacherId)
                    ->where('class_name', $className)
                    ->where('is_active', true)
                    ->current();
        
        if ($section) {
            $query->where('section', $section);
        }
        
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        
        return $query->exists();
    }

    /**
     * Get class teacher for a specific class
     */
    public static function getClassTeacher(string $className, string $section = null, $schoolId = null, $academicYear = null)
    {
        $query = self::where('class_name', $className)
                    ->where('is_class_teacher', true)
                    ->where('is_active', true)
                    ->current();
        
        if ($section) {
            $query->where('section', $section);
        }
        
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        
        if ($academicYear) {
            $query->where('academic_year', $academicYear);
        } else {
            $query->currentAcademicYear();
        }
        
        return $query->with('teacher')->first();
    }

    /**
     * Assign teacher to class/subject
     */
    public static function assignTeacher(array $data): self
    {
        // Set default values
        $data['assigned_at'] = $data['assigned_at'] ?? now();
        $data['effective_from'] = $data['effective_from'] ?? now()->toDateString();
        $data['academic_year'] = $data['academic_year'] ?? (new self())->getCurrentAcademicYear();
        $data['is_active'] = $data['is_active'] ?? true;
        
        return self::create($data);
    }

    /**
     * Deactivate assignment
     */
    public function deactivate(string $reason = null): void
    {
        $this->is_active = false;
        $this->effective_until = now()->toDateString();
        
        if ($reason) {
            $this->notes = ($this->notes ? $this->notes . "\n" : '') . "Deactivated: {$reason}";
        }
        
        $this->save();
    }
}