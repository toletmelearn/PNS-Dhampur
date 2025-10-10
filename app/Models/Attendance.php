<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'class_id',
        'date',
        'status',
        'marked_by',
        'check_in_time',
        'check_out_time',
        'late_minutes',
        'early_departure_minutes',
        'remarks',
        'academic_year',
        'month',
        'week_number',
        'is_holiday',
        'attendance_type'
    ];

    protected $casts = [
        'date' => 'date',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'late_minutes' => 'integer',
        'early_departure_minutes' => 'integer',
        'is_holiday' => 'boolean',
        'week_number' => 'integer'
    ];

    // Status constants
    const STATUS_PRESENT = 'present';
    const STATUS_ABSENT = 'absent';
    const STATUS_LATE = 'late';
    const STATUS_EXCUSED = 'excused';
    const STATUS_SICK = 'sick';
    const STATUS_HOLIDAY = 'holiday';

    // Attendance type constants
    const TYPE_REGULAR = 'regular';
    const TYPE_MAKEUP = 'makeup';
    const TYPE_SPECIAL = 'special';

    /**
     * Relationships
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function classModel()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function markedBy()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    /**
     * Scopes
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopeForClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeForMonth($query, $month, $year = null)
    {
        $year = $year ?? date('Y');
        return $query->whereMonth('date', $month)->whereYear('date', $year);
    }

    public function scopeForAcademicYear($query, $academicYear)
    {
        return $query->where('academic_year', $academicYear);
    }

    public function scopePresent($query)
    {
        return $query->whereIn('status', [self::STATUS_PRESENT, self::STATUS_LATE]);
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', self::STATUS_ABSENT);
    }

    public function scopeLate($query)
    {
        return $query->where('status', self::STATUS_LATE);
    }

    /**
     * Accessors
     */
    public function getIsLateAttribute()
    {
        return $this->status === self::STATUS_LATE || $this->late_minutes > 0;
    }

    public function getIsPresentAttribute()
    {
        return in_array($this->status, [self::STATUS_PRESENT, self::STATUS_LATE]);
    }

    public function getStatusBadgeAttribute()
    {
        // Return safe status text only - HTML should be handled in views
        $statuses = [
            self::STATUS_PRESENT => 'Present',
            self::STATUS_ABSENT => 'Absent',
            self::STATUS_LATE => 'Late',
            self::STATUS_EXCUSED => 'Excused',
            self::STATUS_SICK => 'Sick',
            self::STATUS_HOLIDAY => 'Holiday',
        ];

        return $statuses[$this->status] ?? 'Unknown';
    }

    public function getStatusBadgeClassAttribute()
    {
        // Return CSS class for safe styling
        $classes = [
            self::STATUS_PRESENT => 'bg-success',
            self::STATUS_ABSENT => 'bg-danger',
            self::STATUS_LATE => 'bg-warning',
            self::STATUS_EXCUSED => 'bg-info',
            self::STATUS_SICK => 'bg-secondary',
            self::STATUS_HOLIDAY => 'bg-primary',
        ];

        return $classes[$this->status] ?? 'bg-light';
    }

    public function getFormattedDateAttribute()
    {
        return $this->date->format('M d, Y');
    }

    public function getFormattedCheckInTimeAttribute()
    {
        return $this->check_in_time ? $this->check_in_time->format('H:i A') : null;
    }

    public function getFormattedCheckOutTimeAttribute()
    {
        return $this->check_out_time ? $this->check_out_time->format('H:i A') : null;
    }

    /**
     * Static methods for analytics
     */
    public static function getAttendancePercentage($studentId, $startDate = null, $endDate = null)
    {
        $query = self::where('student_id', $studentId);
        
        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }

        $total = $query->count();
        $present = $query->present()->count();

        return $total > 0 ? round(($present / $total) * 100, 2) : 0;
    }

    public static function getClassAttendanceStats($classId, $date = null)
    {
        $date = $date ?? now()->format('Y-m-d');
        
        $attendances = self::where('class_id', $classId)
            ->whereDate('date', $date)
            ->get();

        $totalStudents = Student::where('class_id', $classId)
            ->where('status', 'active')
            ->count();

        return [
            'total_students' => $totalStudents,
            'present' => $attendances->where('status', self::STATUS_PRESENT)->count(),
            'absent' => $attendances->where('status', self::STATUS_ABSENT)->count(),
            'late' => $attendances->where('status', self::STATUS_LATE)->count(),
            'excused' => $attendances->where('status', self::STATUS_EXCUSED)->count(),
            'attendance_percentage' => $totalStudents > 0 ? 
                round((($attendances->present()->count()) / $totalStudents) * 100, 2) : 0
        ];
    }

    public static function getMonthlyAttendanceReport($classId = null, $month = null, $year = null)
    {
        $month = $month ?? date('n');
        $year = $year ?? date('Y');
        
        $query = self::whereMonth('date', $month)->whereYear('date', $year);
        
        if ($classId) {
            $query->where('class_id', $classId);
        }

        $attendances = $query->with(['student', 'classModel'])->get();
        
        return $attendances->groupBy('date')->map(function ($dayAttendances) {
            return [
                'date' => $dayAttendances->first()->date->format('Y-m-d'),
                'total' => $dayAttendances->count(),
                'present' => $dayAttendances->present()->count(),
                'absent' => $dayAttendances->where('status', self::STATUS_ABSENT)->count(),
                'late' => $dayAttendances->where('status', self::STATUS_LATE)->count(),
                'percentage' => $dayAttendances->count() > 0 ? 
                    round(($dayAttendances->present()->count() / $dayAttendances->count()) * 100, 2) : 0
            ];
        });
    }

    /**
     * Boot method to automatically set academic year and other fields
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($attendance) {
            if (!$attendance->academic_year) {
                $attendance->academic_year = self::getCurrentAcademicYear();
            }
            
            if (!$attendance->month) {
                $attendance->month = Carbon::parse($attendance->date)->month;
            }
            
            if (!$attendance->week_number) {
                $attendance->week_number = Carbon::parse($attendance->date)->weekOfYear;
            }
            
            if (!$attendance->attendance_type) {
                $attendance->attendance_type = self::TYPE_REGULAR;
            }
        });
    }

    /**
     * Get current academic year
     */
    public static function getCurrentAcademicYear()
    {
        $currentMonth = date('n');
        $currentYear = date('Y');
        
        // Academic year starts from April (month 4)
        if ($currentMonth >= 4) {
            return $currentYear . '-' . ($currentYear + 1);
        } else {
            return ($currentYear - 1) . '-' . $currentYear;
        }
    }
}
