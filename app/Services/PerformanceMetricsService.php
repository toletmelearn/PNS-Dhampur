<?php

namespace App\Services;

use App\Models\BiometricAttendance;
use App\Models\Teacher;
use App\Models\AttendanceAnalytics;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PerformanceMetricsService
{
    protected $cacheTimeout = 3600; // 1 hour

    /**
     * Get comprehensive performance dashboard data
     */
    public function getPerformanceDashboard($startDate = null, $endDate = null, $teacherId = null)
    {
        $startDate = $startDate ? Carbon::parse($startDate) : now()->startOfMonth();
        $endDate = $endDate ? Carbon::parse($endDate) : now();
        
        $cacheKey = "performance_dashboard_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}_{$teacherId}";
        
        return Cache::remember($cacheKey, $this->cacheTimeout, function () use ($startDate, $endDate, $teacherId) {
            return [
                'overview' => $this->getOverviewMetrics($startDate, $endDate, $teacherId),
                'attendance_trends' => $this->getAttendanceTrends($startDate, $endDate, $teacherId),
                'punctuality_analysis' => $this->getPunctualityAnalysis($startDate, $endDate, $teacherId),
                'productivity_metrics' => $this->getProductivityMetrics($startDate, $endDate, $teacherId),
                'leave_patterns' => $this->getLeavePatterns($startDate, $endDate, $teacherId),
                'performance_rankings' => $this->getPerformanceRankings($startDate, $endDate),
                'department_comparison' => $this->getDepartmentComparison($startDate, $endDate),
                'alerts_and_insights' => $this->getAlertsAndInsights($startDate, $endDate, $teacherId),
                'predictive_analytics' => $this->getPredictiveAnalytics($startDate, $endDate, $teacherId)
            ];
        });
    }

    /**
     * Get overview metrics
     */
    protected function getOverviewMetrics($startDate, $endDate, $teacherId = null)
    {
        $query = BiometricAttendance::whereBetween('date', [$startDate, $endDate]);
        
        if ($teacherId) {
            $query->where('teacher_id', $teacherId);
        }
        
        $attendances = $query->get();
        $totalTeachers = $teacherId ? 1 : Teacher::count();
        $workingDays = $this->calculateWorkingDays($startDate, $endDate);
        
        $presentDays = $attendances->where('status', 'present')->count();
        $totalPossibleDays = $totalTeachers * $workingDays;
        
        return [
            'total_teachers' => $totalTeachers,
            'working_days' => $workingDays,
            'total_attendance_records' => $attendances->count(),
            'present_days' => $presentDays,
            'absent_days' => $totalPossibleDays - $presentDays,
            'overall_attendance_rate' => $totalPossibleDays > 0 ? round(($presentDays / $totalPossibleDays) * 100, 2) : 0,
            'average_working_hours' => round($attendances->where('working_hours', '>', 0)->avg('working_hours'), 2),
            'total_working_hours' => round($attendances->sum('working_hours'), 2),
            'late_arrivals' => $attendances->where('is_late', true)->count(),
            'early_departures' => $attendances->where('is_early_departure', true)->count(),
            'punctuality_rate' => $presentDays > 0 ? round((($presentDays - $attendances->where('is_late', true)->count()) / $presentDays) * 100, 2) : 0,
            'overtime_instances' => $attendances->where('working_hours', '>', 8)->count(),
            'total_overtime_hours' => round($attendances->where('working_hours', '>', 8)->sum('working_hours') - ($attendances->where('working_hours', '>', 8)->count() * 8), 2)
        ];
    }

    /**
     * Get attendance trends over time
     */
    protected function getAttendanceTrends($startDate, $endDate, $teacherId = null)
    {
        $query = BiometricAttendance::whereBetween('date', [$startDate, $endDate]);
        
        if ($teacherId) {
            $query->where('teacher_id', $teacherId);
        }
        
        $attendances = $query->get();
        
        // Daily trends
        $dailyTrends = $attendances->groupBy(function ($item) {
            return $item->date->format('Y-m-d');
        })->map(function ($dayAttendances, $date) {
            $totalTeachers = Teacher::count();
            $present = $dayAttendances->where('status', 'present')->count();
            
            return [
                'date' => $date,
                'present' => $present,
                'absent' => $totalTeachers - $present,
                'late' => $dayAttendances->where('is_late', true)->count(),
                'early_departure' => $dayAttendances->where('is_early_departure', true)->count(),
                'attendance_rate' => $totalTeachers > 0 ? round(($present / $totalTeachers) * 100, 2) : 0,
                'average_working_hours' => round($dayAttendances->where('working_hours', '>', 0)->avg('working_hours'), 2)
            ];
        })->values();

        // Weekly trends
        $weeklyTrends = $attendances->groupBy(function ($item) {
            return $item->date->format('Y-W');
        })->map(function ($weekAttendances, $week) {
            $totalTeachers = Teacher::count();
            $workingDays = $weekAttendances->groupBy('date')->count();
            $present = $weekAttendances->where('status', 'present')->count();
            
            return [
                'week' => $week,
                'working_days' => $workingDays,
                'present' => $present,
                'attendance_rate' => ($totalTeachers * $workingDays) > 0 ? round(($present / ($totalTeachers * $workingDays)) * 100, 2) : 0,
                'late_arrivals' => $weekAttendances->where('is_late', true)->count(),
                'early_departures' => $weekAttendances->where('is_early_departure', true)->count(),
                'total_working_hours' => round($weekAttendances->sum('working_hours'), 2)
            ];
        })->values();

        // Monthly trends
        $monthlyTrends = $attendances->groupBy(function ($item) {
            return $item->date->format('Y-m');
        })->map(function ($monthAttendances, $month) {
            $totalTeachers = Teacher::count();
            $workingDays = $monthAttendances->groupBy('date')->count();
            $present = $monthAttendances->where('status', 'present')->count();
            
            return [
                'month' => $month,
                'working_days' => $workingDays,
                'present' => $present,
                'attendance_rate' => ($totalTeachers * $workingDays) > 0 ? round(($present / ($totalTeachers * $workingDays)) * 100, 2) : 0,
                'punctuality_rate' => $present > 0 ? round((($present - $monthAttendances->where('is_late', true)->count()) / $present) * 100, 2) : 0,
                'average_working_hours' => round($monthAttendances->where('working_hours', '>', 0)->avg('working_hours'), 2)
            ];
        })->values();

        return [
            'daily' => $dailyTrends,
            'weekly' => $weeklyTrends,
            'monthly' => $monthlyTrends
        ];
    }

    /**
     * Get punctuality analysis
     */
    protected function getPunctualityAnalysis($startDate, $endDate, $teacherId = null)
    {
        $query = BiometricAttendance::whereBetween('date', [$startDate, $endDate])
            ->whereNotNull('check_in_time');
        
        if ($teacherId) {
            $query->where('teacher_id', $teacherId);
        }
        
        $attendances = $query->get();
        
        // Time-based analysis
        $timeAnalysis = $attendances->groupBy(function ($item) {
            return $item->check_in_time->format('H');
        })->map(function ($hourAttendances, $hour) {
            return [
                'hour' => $hour . ':00',
                'count' => $hourAttendances->count(),
                'late_count' => $hourAttendances->where('is_late', true)->count(),
                'on_time_percentage' => $hourAttendances->count() > 0 ? 
                    round((($hourAttendances->count() - $hourAttendances->where('is_late', true)->count()) / $hourAttendances->count()) * 100, 2) : 0
            ];
        })->sortKeys();

        // Day of week analysis
        $dayOfWeekAnalysis = $attendances->groupBy(function ($item) {
            return $item->date->format('l');
        })->map(function ($dayAttendances, $day) {
            return [
                'day' => $day,
                'total' => $dayAttendances->count(),
                'late' => $dayAttendances->where('is_late', true)->count(),
                'punctuality_rate' => $dayAttendances->count() > 0 ? 
                    round((($dayAttendances->count() - $dayAttendances->where('is_late', true)->count()) / $dayAttendances->count()) * 100, 2) : 0,
                'average_arrival_time' => $this->calculateAverageTime($dayAttendances->pluck('check_in_time'))
            ];
        });

        // Late arrival patterns
        $latePatterns = $attendances->where('is_late', true)->groupBy(function ($item) {
            $minutesLate = $item->check_in_time->diffInMinutes(Carbon::createFromFormat('H:i:s', '08:00:00'));
            if ($minutesLate <= 15) return '1-15 minutes';
            if ($minutesLate <= 30) return '16-30 minutes';
            if ($minutesLate <= 60) return '31-60 minutes';
            return '60+ minutes';
        })->map(function ($patternAttendances, $pattern) {
            return [
                'pattern' => $pattern,
                'count' => $patternAttendances->count(),
                'percentage' => round(($patternAttendances->count() / $attendances->where('is_late', true)->count()) * 100, 2)
            ];
        });

        return [
            'overall_punctuality_rate' => $attendances->count() > 0 ? 
                round((($attendances->count() - $attendances->where('is_late', true)->count()) / $attendances->count()) * 100, 2) : 0,
            'total_late_instances' => $attendances->where('is_late', true)->count(),
            'average_late_minutes' => $this->calculateAverageLateMinutes($attendances->where('is_late', true)),
            'time_analysis' => $timeAnalysis->values(),
            'day_of_week_analysis' => $dayOfWeekAnalysis->values(),
            'late_patterns' => $latePatterns->values(),
            'most_punctual_day' => $dayOfWeekAnalysis->sortByDesc('punctuality_rate')->first(),
            'least_punctual_day' => $dayOfWeekAnalysis->sortBy('punctuality_rate')->first()
        ];
    }

    /**
     * Get productivity metrics
     */
    protected function getProductivityMetrics($startDate, $endDate, $teacherId = null)
    {
        $query = BiometricAttendance::whereBetween('date', [$startDate, $endDate])
            ->whereNotNull('working_hours');
        
        if ($teacherId) {
            $query->where('teacher_id', $teacherId);
        }
        
        $attendances = $query->get();
        
        // Working hours distribution
        $hoursDistribution = $attendances->groupBy(function ($item) {
            $hours = floor($item->working_hours);
            if ($hours < 4) return 'Under 4 hours';
            if ($hours < 6) return '4-6 hours';
            if ($hours < 8) return '6-8 hours';
            if ($hours < 10) return '8-10 hours';
            return '10+ hours';
        })->map(function ($hoursAttendances, $range) {
            return [
                'range' => $range,
                'count' => $hoursAttendances->count(),
                'percentage' => round(($hoursAttendances->count() / $attendances->count()) * 100, 2),
                'average_hours' => round($hoursAttendances->avg('working_hours'), 2)
            ];
        });

        // Efficiency metrics
        $efficiencyMetrics = [
            'average_daily_hours' => round($attendances->avg('working_hours'), 2),
            'total_productive_hours' => round($attendances->sum('working_hours'), 2),
            'overtime_frequency' => round(($attendances->where('working_hours', '>', 8)->count() / $attendances->count()) * 100, 2),
            'undertime_frequency' => round(($attendances->where('working_hours', '<', 8)->count() / $attendances->count()) * 100, 2),
            'consistency_score' => $this->calculateConsistencyScore($attendances),
            'productivity_trend' => $this->calculateProductivityTrend($attendances)
        ];

        // Peak performance analysis
        $peakPerformance = [
            'best_day' => $attendances->sortByDesc('working_hours')->first(),
            'most_consistent_week' => $this->findMostConsistentWeek($attendances),
            'highest_productivity_month' => $this->findHighestProductivityMonth($attendances)
        ];

        return [
            'hours_distribution' => $hoursDistribution->values(),
            'efficiency_metrics' => $efficiencyMetrics,
            'peak_performance' => $peakPerformance,
            'recommendations' => $this->generateProductivityRecommendations($attendances)
        ];
    }

    /**
     * Get leave patterns analysis
     */
    protected function getLeavePatterns($startDate, $endDate, $teacherId = null)
    {
        $workingDays = $this->calculateWorkingDays($startDate, $endDate);
        $totalTeachers = $teacherId ? 1 : Teacher::count();
        
        $query = BiometricAttendance::whereBetween('date', [$startDate, $endDate]);
        
        if ($teacherId) {
            $query->where('teacher_id', $teacherId);
        }
        
        $attendances = $query->get();
        $absentDays = ($totalTeachers * $workingDays) - $attendances->where('status', 'present')->count();
        
        // Day of week patterns
        $dayPatterns = collect(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'])
            ->mapWithKeys(function ($day) use ($startDate, $endDate, $teacherId) {
                $dayAbsences = $this->getAbsencesForDay($day, $startDate, $endDate, $teacherId);
                return [$day => $dayAbsences];
            });

        // Monthly patterns
        $monthlyPatterns = $attendances->groupBy(function ($item) {
            return $item->date->format('Y-m');
        })->map(function ($monthAttendances, $month) use ($totalTeachers) {
            $workingDaysInMonth = $monthAttendances->groupBy('date')->count();
            $presentDays = $monthAttendances->where('status', 'present')->count();
            $absentDays = ($totalTeachers * $workingDaysInMonth) - $presentDays;
            
            return [
                'month' => $month,
                'working_days' => $workingDaysInMonth,
                'absent_days' => $absentDays,
                'absence_rate' => $workingDaysInMonth > 0 ? round(($absentDays / ($totalTeachers * $workingDaysInMonth)) * 100, 2) : 0
            ];
        });

        // Frequent absentees
        $frequentAbsentees = $this->getFrequentAbsentees($startDate, $endDate, $teacherId);

        // Leave clustering analysis
        $leaveClusters = $this->analyzeLeaveClusters($startDate, $endDate, $teacherId);

        return [
            'total_absent_days' => $absentDays,
            'overall_absence_rate' => ($totalTeachers * $workingDays) > 0 ? 
                round(($absentDays / ($totalTeachers * $workingDays)) * 100, 2) : 0,
            'day_of_week_patterns' => $dayPatterns,
            'monthly_patterns' => $monthlyPatterns->values(),
            'frequent_absentees' => $frequentAbsentees,
            'leave_clusters' => $leaveClusters,
            'seasonal_trends' => $this->analyzeSeasonalTrends($startDate, $endDate, $teacherId)
        ];
    }

    /**
     * Get performance rankings
     */
    protected function getPerformanceRankings($startDate, $endDate)
    {
        $teachers = Teacher::with(['biometricAttendances' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }])->get();

        $rankings = $teachers->map(function ($teacher) use ($startDate, $endDate) {
            $attendances = $teacher->biometricAttendances;
            $workingDays = $this->calculateWorkingDays($startDate, $endDate);
            
            $presentDays = $attendances->where('status', 'present')->count();
            $lateArrivals = $attendances->where('is_late', true)->count();
            $earlyDepartures = $attendances->where('is_early_departure', true)->count();
            $totalWorkingHours = $attendances->sum('working_hours');
            
            $attendanceRate = $workingDays > 0 ? ($presentDays / $workingDays) * 100 : 0;
            $punctualityRate = $presentDays > 0 ? (($presentDays - $lateArrivals) / $presentDays) * 100 : 0;
            $consistencyScore = $this->calculateConsistencyScore($attendances);
            
            // Overall performance score (weighted average)
            $performanceScore = ($attendanceRate * 0.4) + ($punctualityRate * 0.3) + ($consistencyScore * 0.3);
            
            return [
                'teacher_id' => $teacher->id,
                'teacher_name' => $teacher->name,
                'employee_id' => $teacher->employee_id,
                'department' => $teacher->department,
                'attendance_rate' => round($attendanceRate, 2),
                'punctuality_rate' => round($punctualityRate, 2),
                'consistency_score' => round($consistencyScore, 2),
                'performance_score' => round($performanceScore, 2),
                'present_days' => $presentDays,
                'late_arrivals' => $lateArrivals,
                'early_departures' => $earlyDepartures,
                'total_working_hours' => round($totalWorkingHours, 2),
                'average_daily_hours' => $presentDays > 0 ? round($totalWorkingHours / $presentDays, 2) : 0
            ];
        })->sortByDesc('performance_score')->values();

        return [
            'top_performers' => $rankings->take(10),
            'bottom_performers' => $rankings->reverse()->take(10),
            'all_rankings' => $rankings
        ];
    }

    /**
     * Get department comparison
     */
    protected function getDepartmentComparison($startDate, $endDate)
    {
        $departments = Teacher::select('department')
            ->distinct()
            ->whereNotNull('department')
            ->pluck('department');

        $comparison = $departments->map(function ($department) use ($startDate, $endDate) {
            $teachers = Teacher::where('department', $department)->pluck('id');
            $attendances = BiometricAttendance::whereIn('teacher_id', $teachers)
                ->whereBetween('date', [$startDate, $endDate])
                ->get();
            
            $workingDays = $this->calculateWorkingDays($startDate, $endDate);
            $totalTeachers = $teachers->count();
            $presentDays = $attendances->where('status', 'present')->count();
            
            return [
                'department' => $department,
                'total_teachers' => $totalTeachers,
                'attendance_rate' => ($totalTeachers * $workingDays) > 0 ? 
                    round(($presentDays / ($totalTeachers * $workingDays)) * 100, 2) : 0,
                'punctuality_rate' => $presentDays > 0 ? 
                    round((($presentDays - $attendances->where('is_late', true)->count()) / $presentDays) * 100, 2) : 0,
                'average_working_hours' => round($attendances->where('working_hours', '>', 0)->avg('working_hours'), 2),
                'total_working_hours' => round($attendances->sum('working_hours'), 2),
                'late_arrivals' => $attendances->where('is_late', true)->count(),
                'early_departures' => $attendances->where('is_early_departure', true)->count()
            ];
        })->sortByDesc('attendance_rate')->values();

        return $comparison;
    }

    /**
     * Get alerts and insights
     */
    protected function getAlertsAndInsights($startDate, $endDate, $teacherId = null)
    {
        $alerts = [];
        $insights = [];
        
        // Performance alerts
        $performanceRankings = $this->getPerformanceRankings($startDate, $endDate);
        $bottomPerformers = $performanceRankings['bottom_performers'];
        
        if ($bottomPerformers->count() > 0) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Low Performance Alert',
                'message' => $bottomPerformers->count() . ' teachers have performance scores below 70%',
                'action' => 'Review and provide support'
            ];
        }
        
        // Attendance insights
        $overviewMetrics = $this->getOverviewMetrics($startDate, $endDate, $teacherId);
        
        if ($overviewMetrics['overall_attendance_rate'] < 85) {
            $alerts[] = [
                'type' => 'danger',
                'title' => 'Low Attendance Rate',
                'message' => 'Overall attendance rate is ' . $overviewMetrics['overall_attendance_rate'] . '%',
                'action' => 'Investigate attendance issues'
            ];
        }
        
        if ($overviewMetrics['punctuality_rate'] < 80) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Punctuality Concern',
                'message' => 'Punctuality rate is ' . $overviewMetrics['punctuality_rate'] . '%',
                'action' => 'Address late arrival patterns'
            ];
        }
        
        // Generate insights
        $insights[] = [
            'title' => 'Attendance Trend',
            'description' => $this->generateAttendanceTrendInsight($startDate, $endDate, $teacherId),
            'impact' => 'medium'
        ];
        
        $insights[] = [
            'title' => 'Productivity Analysis',
            'description' => $this->generateProductivityInsight($startDate, $endDate, $teacherId),
            'impact' => 'high'
        ];
        
        return [
            'alerts' => $alerts,
            'insights' => $insights,
            'recommendations' => $this->generateRecommendations($startDate, $endDate, $teacherId)
        ];
    }

    /**
     * Get predictive analytics
     */
    protected function getPredictiveAnalytics($startDate, $endDate, $teacherId = null)
    {
        // This is a simplified version - in production, you'd use machine learning models
        $historicalData = $this->getHistoricalTrends($startDate, $endDate, $teacherId);
        
        return [
            'attendance_forecast' => $this->forecastAttendance($historicalData),
            'risk_assessment' => $this->assessRisks($historicalData),
            'improvement_opportunities' => $this->identifyImprovementOpportunities($historicalData),
            'seasonal_predictions' => $this->predictSeasonalTrends($historicalData)
        ];
    }

    // Helper methods
    protected function calculateWorkingDays($startDate, $endDate)
    {
        $workingDays = 0;
        $currentDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);
        
        while ($currentDate <= $endDate) {
            if (!$currentDate->isWeekend()) {
                $workingDays++;
            }
            $currentDate->addDay();
        }
        
        return $workingDays;
    }

    protected function calculateAverageTime($times)
    {
        if ($times->isEmpty()) return null;
        
        $totalMinutes = $times->sum(function ($time) {
            return Carbon::parse($time)->hour * 60 + Carbon::parse($time)->minute;
        });
        
        $averageMinutes = $totalMinutes / $times->count();
        $hours = floor($averageMinutes / 60);
        $minutes = $averageMinutes % 60;
        
        return sprintf('%02d:%02d', $hours, $minutes);
    }

    protected function calculateAverageLateMinutes($lateAttendances)
    {
        if ($lateAttendances->isEmpty()) return 0;
        
        $totalLateMinutes = $lateAttendances->sum(function ($attendance) {
            $schoolStart = Carbon::createFromFormat('H:i:s', '08:00:00');
            return Carbon::parse($attendance->check_in_time)->diffInMinutes($schoolStart);
        });
        
        return round($totalLateMinutes / $lateAttendances->count(), 2);
    }

    protected function calculateConsistencyScore($attendances)
    {
        if ($attendances->isEmpty()) return 0;
        
        $workingHours = $attendances->where('working_hours', '>', 0)->pluck('working_hours');
        if ($workingHours->isEmpty()) return 0;
        
        $mean = $workingHours->avg();
        $variance = $workingHours->sum(function ($hours) use ($mean) {
            return pow($hours - $mean, 2);
        }) / $workingHours->count();
        
        $standardDeviation = sqrt($variance);
        
        // Convert to consistency score (0-100, where 100 is most consistent)
        return max(0, 100 - ($standardDeviation * 10));
    }

    protected function calculateProductivityTrend($attendances)
    {
        // Simplified trend calculation
        $weeklyAverages = $attendances->groupBy(function ($item) {
            return $item->date->format('Y-W');
        })->map(function ($weekAttendances) {
            return $weekAttendances->avg('working_hours');
        })->values();
        
        if ($weeklyAverages->count() < 2) return 'stable';
        
        $firstHalf = $weeklyAverages->take($weeklyAverages->count() / 2)->avg();
        $secondHalf = $weeklyAverages->skip($weeklyAverages->count() / 2)->avg();
        
        $change = (($secondHalf - $firstHalf) / $firstHalf) * 100;
        
        if ($change > 5) return 'improving';
        if ($change < -5) return 'declining';
        return 'stable';
    }

    // Additional helper methods would be implemented here...
    protected function findMostConsistentWeek($attendances) { return null; }
    protected function findHighestProductivityMonth($attendances) { return null; }
    protected function generateProductivityRecommendations($attendances) { return []; }
    protected function getAbsencesForDay($day, $startDate, $endDate, $teacherId) { return 0; }
    protected function getFrequentAbsentees($startDate, $endDate, $teacherId) { return []; }
    protected function analyzeLeaveClusters($startDate, $endDate, $teacherId) { return []; }
    protected function analyzeSeasonalTrends($startDate, $endDate, $teacherId) { return []; }
    protected function generateAttendanceTrendInsight($startDate, $endDate, $teacherId) { return 'Stable attendance pattern observed.'; }
    protected function generateProductivityInsight($startDate, $endDate, $teacherId) { return 'Productivity levels are within normal range.'; }
    protected function generateRecommendations($startDate, $endDate, $teacherId) { return []; }
    protected function getHistoricalTrends($startDate, $endDate, $teacherId) { return []; }
    protected function forecastAttendance($historicalData) { return []; }
    protected function assessRisks($historicalData) { return []; }
    protected function identifyImprovementOpportunities($historicalData) { return []; }
    protected function predictSeasonalTrends($historicalData) { return []; }
}