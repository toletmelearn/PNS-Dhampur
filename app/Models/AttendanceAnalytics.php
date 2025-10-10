<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AttendanceAnalytics extends Model
{
    use HasFactory;

    protected $table = 'attendance_analytics';

    protected $fillable = [
        'teacher_id',
        'month',
        'year',
        'total_working_days',
        'present_days',
        'absent_days',
        'late_arrivals',
        'early_departures',
        'total_working_hours',
        'average_daily_hours',
        'punctuality_score',
        'attendance_percentage',
        'leave_pattern_analysis',
        'performance_metrics',
        'calculated_at'
    ];

    protected $casts = [
        'calculated_at' => 'datetime',
        'leave_pattern_analysis' => 'array',
        'performance_metrics' => 'array'
    ];

    // Relationships
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    // Scopes
    public function scopeForTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeForMonth($query, $year, $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    public function scopeForYear($query, $year)
    {
        return $query->where('year', $year);
    }

    // Accessors
    public function getPunctualityGradeAttribute()
    {
        if ($this->punctuality_score >= 90) return 'A+';
        if ($this->punctuality_score >= 80) return 'A';
        if ($this->punctuality_score >= 70) return 'B';
        if ($this->punctuality_score >= 60) return 'C';
        return 'D';
    }

    public function getAttendanceGradeAttribute()
    {
        if ($this->attendance_percentage >= 95) return 'Excellent';
        if ($this->attendance_percentage >= 90) return 'Very Good';
        if ($this->attendance_percentage >= 85) return 'Good';
        if ($this->attendance_percentage >= 75) return 'Average';
        return 'Poor';
    }

    // Static methods for analytics calculation
    public static function calculateMonthlyAnalytics($teacherId, $year, $month)
    {
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        
        // Get all attendance records for the month
        $attendances = BiometricAttendance::where('teacher_id', $teacherId)
            ->forDateRange($startDate, $endDate)
            ->get();
        
        // Calculate working days (excluding weekends)
        $totalWorkingDays = 0;
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            if (!$currentDate->isWeekend()) {
                $totalWorkingDays++;
            }
            $currentDate->addDay();
        }
        
        $presentDays = $attendances->where('status', 'present')->count();
        $absentDays = $totalWorkingDays - $presentDays;
        $lateArrivals = $attendances->where('is_late', true)->count();
        $earlyDepartures = $attendances->where('is_early_departure', true)->count();
        $totalWorkingHours = $attendances->sum('working_hours');
        $averageDailyHours = $presentDays > 0 ? round($totalWorkingHours / $presentDays, 2) : 0;
        
        // Calculate punctuality score (0-100)
        $punctualityScore = static::calculatePunctualityScore($presentDays, $lateArrivals, $earlyDepartures);
        
        // Calculate attendance percentage
        $attendancePercentage = $totalWorkingDays > 0 ? round(($presentDays / $totalWorkingDays) * 100, 2) : 0;
        
        // Analyze leave patterns
        $leavePatternAnalysis = static::analyzeLeavePatterns($attendances, $startDate, $endDate);
        
        // Calculate performance metrics
        $performanceMetrics = static::calculatePerformanceMetrics($attendances, $totalWorkingDays);
        
        // Create or update analytics record
        return static::updateOrCreate(
            [
                'teacher_id' => $teacherId,
                'year' => $year,
                'month' => $month
            ],
            [
                'total_working_days' => $totalWorkingDays,
                'present_days' => $presentDays,
                'absent_days' => $absentDays,
                'late_arrivals' => $lateArrivals,
                'early_departures' => $earlyDepartures,
                'total_working_hours' => $totalWorkingHours,
                'average_daily_hours' => $averageDailyHours,
                'punctuality_score' => $punctualityScore,
                'attendance_percentage' => $attendancePercentage,
                'leave_pattern_analysis' => $leavePatternAnalysis,
                'performance_metrics' => $performanceMetrics,
                'calculated_at' => now()
            ]
        );
    }

    protected static function calculatePunctualityScore($presentDays, $lateArrivals, $earlyDepartures)
    {
        if ($presentDays == 0) return 0;
        
        $baseScore = 100;
        
        // Deduct points for late arrivals (5 points per late arrival)
        $lateDeduction = ($lateArrivals / $presentDays) * 30;
        
        // Deduct points for early departures (3 points per early departure)
        $earlyDeduction = ($earlyDepartures / $presentDays) * 20;
        
        $finalScore = $baseScore - $lateDeduction - $earlyDeduction;
        
        return max(0, round($finalScore, 2));
    }

    protected static function analyzeLeavePatterns($attendances, $startDate, $endDate)
    {
        $absentDates = [];
        $currentDate = $startDate->copy();
        
        while ($currentDate <= $endDate) {
            if (!$currentDate->isWeekend()) {
                $attendance = $attendances->where('date', $currentDate->format('Y-m-d'))->first();
                if (!$attendance || $attendance->status === 'absent') {
                    $absentDates[] = $currentDate->format('Y-m-d');
                }
            }
            $currentDate->addDay();
        }
        
        // Analyze patterns
        $patterns = [
            'total_absent_days' => count($absentDates),
            'consecutive_absences' => static::findConsecutiveAbsences($absentDates),
            'monday_absences' => static::countDayOfWeekAbsences($absentDates, 1), // Monday
            'friday_absences' => static::countDayOfWeekAbsences($absentDates, 5), // Friday
            'weekend_adjacent_absences' => static::countWeekendAdjacentAbsences($absentDates)
        ];
        
        return $patterns;
    }

    protected static function findConsecutiveAbsences($absentDates)
    {
        if (empty($absentDates)) return [];
        
        sort($absentDates);
        $consecutive = [];
        $current = [$absentDates[0]];
        
        for ($i = 1; $i < count($absentDates); $i++) {
            $prevDate = Carbon::parse($absentDates[$i - 1]);
            $currDate = Carbon::parse($absentDates[$i]);
            
            if ($prevDate->addDay()->format('Y-m-d') === $currDate->format('Y-m-d')) {
                $current[] = $absentDates[$i];
            } else {
                if (count($current) > 1) {
                    $consecutive[] = $current;
                }
                $current = [$absentDates[$i]];
            }
        }
        
        if (count($current) > 1) {
            $consecutive[] = $current;
        }
        
        return $consecutive;
    }

    protected static function countDayOfWeekAbsences($absentDates, $dayOfWeek)
    {
        $count = 0;
        foreach ($absentDates as $date) {
            if (Carbon::parse($date)->dayOfWeek === $dayOfWeek) {
                $count++;
            }
        }
        return $count;
    }

    protected static function countWeekendAdjacentAbsences($absentDates)
    {
        $count = 0;
        foreach ($absentDates as $date) {
            $carbonDate = Carbon::parse($date);
            if ($carbonDate->dayOfWeek === 1 || $carbonDate->dayOfWeek === 5) { // Monday or Friday
                $count++;
            }
        }
        return $count;
    }

    protected static function calculatePerformanceMetrics($attendances, $totalWorkingDays)
    {
        $presentAttendances = $attendances->where('status', 'present');
        
        return [
            'consistency_score' => static::calculateConsistencyScore($attendances, $totalWorkingDays),
            'average_arrival_time' => static::calculateAverageArrivalTime($presentAttendances),
            'average_departure_time' => static::calculateAverageDepartureTime($presentAttendances),
            'overtime_hours' => $presentAttendances->where('working_hours', '>', 8)->sum('working_hours') - ($presentAttendances->where('working_hours', '>', 8)->count() * 8),
            'undertime_instances' => $presentAttendances->where('working_hours', '<', 8)->count(),
            'perfect_attendance_days' => $presentAttendances->where('is_late', false)->where('is_early_departure', false)->count()
        ];
    }

    protected static function calculateConsistencyScore($attendances, $totalWorkingDays)
    {
        if ($totalWorkingDays == 0) return 0;
        
        $presentDays = $attendances->where('status', 'present')->count();
        $irregularities = $attendances->where('is_late', true)->count() + 
                         $attendances->where('is_early_departure', true)->count();
        
        $baseScore = ($presentDays / $totalWorkingDays) * 100;
        $penaltyScore = ($irregularities / max(1, $presentDays)) * 20;
        
        return max(0, round($baseScore - $penaltyScore, 2));
    }

    protected static function calculateAverageArrivalTime($attendances)
    {
        $checkInTimes = $attendances->whereNotNull('check_in_time')->pluck('check_in_time');
        
        if ($checkInTimes->isEmpty()) return null;
        
        $totalMinutes = 0;
        foreach ($checkInTimes as $time) {
            $carbon = Carbon::parse($time);
            $totalMinutes += $carbon->hour * 60 + $carbon->minute;
        }
        
        $averageMinutes = $totalMinutes / $checkInTimes->count();
        $hours = floor($averageMinutes / 60);
        $minutes = $averageMinutes % 60;
        
        return sprintf('%02d:%02d:00', $hours, $minutes);
    }

    protected static function calculateAverageDepartureTime($attendances)
    {
        $checkOutTimes = $attendances->whereNotNull('check_out_time')->pluck('check_out_time');
        
        if ($checkOutTimes->isEmpty()) return null;
        
        $totalMinutes = 0;
        foreach ($checkOutTimes as $time) {
            $carbon = Carbon::parse($time);
            $totalMinutes += $carbon->hour * 60 + $carbon->minute;
        }
        
        $averageMinutes = $totalMinutes / $checkOutTimes->count();
        $hours = floor($averageMinutes / 60);
        $minutes = $averageMinutes % 60;
        
        return sprintf('%02d:%02d:00', $hours, $minutes);
    }

    // Bulk calculation for all teachers
    public static function calculateAllTeachersAnalytics($year, $month)
    {
        $teachers = Teacher::with(['user', 'subjects', 'classes'])->get();
        $results = [];
        
        foreach ($teachers as $teacher) {
            $analytics = static::calculateMonthlyAnalytics($teacher->id, $year, $month);
            $results[] = $analytics;
        }
        
        return $results;
    }

    // Get top performers
    public static function getTopPerformers($year, $month, $limit = 10)
    {
        return static::with('teacher')
            ->forMonth($year, $month)
            ->orderByDesc('punctuality_score')
            ->orderByDesc('attendance_percentage')
            ->limit($limit)
            ->get();
    }

    // Get analytics summary for dashboard
    public static function getDashboardSummary($year, $month)
    {
        $analytics = static::forMonth($year, $month)->get();
        
        return [
            'total_teachers' => $analytics->count(),
            'average_attendance' => $analytics->avg('attendance_percentage'),
            'average_punctuality' => $analytics->avg('punctuality_score'),
            'total_late_arrivals' => $analytics->sum('late_arrivals'),
            'total_early_departures' => $analytics->sum('early_departures'),
            'excellent_performers' => $analytics->where('punctuality_score', '>=', 90)->count(),
            'poor_performers' => $analytics->where('attendance_percentage', '<', 75)->count()
        ];
    }
}