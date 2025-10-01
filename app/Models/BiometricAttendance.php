<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BiometricAttendance extends Model
{
    use HasFactory;

    protected $table = 'biometric_attendances';

    protected $fillable = [
        'teacher_id',
        'date',
        'check_in_time',
        'check_out_time',
        'status',
        'working_hours',
        'is_late',
        'is_early_departure',
        'biometric_data',
        'device_id',
        'check_in_location',
        'check_out_location',
        'absence_reason',
        'marked_by',
        'notes'
    ];

    protected $casts = [
        'date' => 'date',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'working_hours' => 'decimal:2',
        'is_late' => 'boolean',
        'is_early_departure' => 'boolean'
    ];

    // Relationships
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function markedBy()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    // Scopes
    public function scopePresent($query)
    {
        return $query->where('status', 'present');
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }

    public function scopeLateArrivals($query)
    {
        return $query->where('is_late', true);
    }

    public function scopeEarlyDepartures($query)
    {
        return $query->where('is_early_departure', true);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopeForMonth($query, $year, $month)
    {
        return $query->whereYear('date', $year)->whereMonth('date', $month);
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    // Accessors
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'present' => '<span class="badge bg-success">Present</span>',
            'absent' => '<span class="badge bg-danger">Absent</span>',
            'late' => '<span class="badge bg-warning">Late</span>',
            'early_departure' => '<span class="badge bg-info">Early Departure</span>'
        ];

        return $badges[$this->status] ?? '<span class="badge bg-secondary">Unknown</span>';
    }

    public function getFormattedCheckInTimeAttribute()
    {
        return $this->check_in_time ? $this->check_in_time->format('H:i:s') : null;
    }

    public function getFormattedCheckOutTimeAttribute()
    {
        return $this->check_out_time ? $this->check_out_time->format('H:i:s') : null;
    }

    public function getFormattedWorkingHoursAttribute()
    {
        if (!$this->working_hours) {
            return '0h 0m';
        }

        $hours = floor($this->working_hours);
        $minutes = round(($this->working_hours - $hours) * 60);

        return "{$hours}h {$minutes}m";
    }

    public function getIsCurrentlyPresentAttribute()
    {
        return $this->status === 'present' && 
               $this->check_in_time && 
               !$this->check_out_time &&
               $this->date->isToday();
    }

    // Mutators
    public function setDateAttribute($value)
    {
        $this->attributes['date'] = Carbon::parse($value)->format('Y-m-d');
    }

    // Methods
    public function calculateWorkingHours()
    {
        if (!$this->check_in_time || !$this->check_out_time) {
            return 0;
        }

        $checkIn = Carbon::parse($this->check_in_time);
        $checkOut = Carbon::parse($this->check_out_time);

        return round($checkIn->diffInMinutes($checkOut) / 60, 2);
    }

    public function isLateArrival($schoolStartTime = '08:00:00')
    {
        if (!$this->check_in_time) {
            return false;
        }

        $checkInTime = Carbon::parse($this->check_in_time);
        $startTime = Carbon::createFromFormat('H:i:s', $schoolStartTime);

        return $checkInTime->format('H:i:s') > $startTime->format('H:i:s');
    }

    public function isEarlyDeparture($schoolEndTime = '16:00:00', $minimumHours = 8)
    {
        if (!$this->check_out_time || !$this->working_hours) {
            return false;
        }

        // Check if departed before school end time or worked less than minimum hours
        $checkOutTime = Carbon::parse($this->check_out_time);
        $endTime = Carbon::createFromFormat('H:i:s', $schoolEndTime);

        return $checkOutTime->format('H:i:s') < $endTime->format('H:i:s') || 
               $this->working_hours < $minimumHours;
    }

    public function markAsPresent($checkInTime = null)
    {
        $this->update([
            'status' => 'present',
            'check_in_time' => $checkInTime ?? now(),
            'is_late' => $this->isLateArrival()
        ]);
    }

    public function markAsAbsent($reason = null, $markedBy = null)
    {
        $this->update([
            'status' => 'absent',
            'absence_reason' => $reason,
            'marked_by' => $markedBy ?? auth()->id()
        ]);
    }

    public function processCheckOut($checkOutTime = null)
    {
        $checkOutTime = $checkOutTime ?? now();
        
        $this->update([
            'check_out_time' => $checkOutTime,
            'working_hours' => $this->calculateWorkingHours(),
            'is_early_departure' => $this->isEarlyDeparture()
        ]);
    }

    // Static methods
    public static function getTodayAttendance()
    {
        return static::forDate(now()->format('Y-m-d'))->with('teacher')->get();
    }

    public static function getMonthlyAttendance($year = null, $month = null)
    {
        $year = $year ?? now()->year;
        $month = $month ?? now()->month;
        
        return static::forMonth($year, $month)->with('teacher')->get();
    }

    public static function getAttendanceStats($startDate, $endDate)
    {
        $attendances = static::forDateRange($startDate, $endDate)->get();
        
        return [
            'total_records' => $attendances->count(),
            'present_count' => $attendances->where('status', 'present')->count(),
            'absent_count' => $attendances->where('status', 'absent')->count(),
            'late_arrivals' => $attendances->where('is_late', true)->count(),
            'early_departures' => $attendances->where('is_early_departure', true)->count(),
            'average_working_hours' => $attendances->where('working_hours', '>', 0)->avg('working_hours'),
            'total_working_hours' => $attendances->sum('working_hours')
        ];
    }

    public static function getTeacherAttendancePercentage($teacherId, $startDate, $endDate)
    {
        $totalDays = static::where('teacher_id', $teacherId)
            ->forDateRange($startDate, $endDate)
            ->count();
            
        $presentDays = static::where('teacher_id', $teacherId)
            ->forDateRange($startDate, $endDate)
            ->present()
            ->count();
            
        return $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0;
    }

    public static function getDailyAttendanceSummary($date = null)
    {
        $date = $date ?? now()->format('Y-m-d');
        $attendances = static::forDate($date)->get();
        $totalTeachers = Teacher::count();
        
        return [
            'date' => $date,
            'total_teachers' => $totalTeachers,
            'present' => $attendances->where('status', 'present')->count(),
            'absent' => $totalTeachers - $attendances->where('status', 'present')->count(),
            'late_arrivals' => $attendances->where('is_late', true)->count(),
            'early_departures' => $attendances->where('is_early_departure', true)->count(),
            'attendance_percentage' => $totalTeachers > 0 ? 
                round(($attendances->where('status', 'present')->count() / $totalTeachers) * 100, 2) : 0
        ];
    }
}