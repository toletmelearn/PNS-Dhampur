<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Fee;
use App\Models\Attendance;
use App\Models\Result;

class OptimizedReportService
{
    // Cache duration constants
    const CACHE_DURATION_SHORT = 300; // 5 minutes
    const CACHE_DURATION_MEDIUM = 1800; // 30 minutes
    const CACHE_DURATION_LONG = 3600; // 1 hour
    
    // Chunk size for large datasets
    const CHUNK_SIZE = 1000;

    /**
     * Generate comprehensive report with optimized queries
     * This replaces the inefficient generateComprehensiveReport method
     */
    public function generateComprehensiveReport(array $filters = [])
    {
        $cacheKey = 'comprehensive_report_' . md5(serialize($filters));
        
        return Cache::remember($cacheKey, self::CACHE_DURATION_MEDIUM, function () use ($filters) {
            return [
                'student_summary' => $this->getOptimizedStudentSummary($filters),
                'class_wise_collection' => $this->getOptimizedClassWiseCollection($filters),
                'attendance_analysis' => $this->getOptimizedAttendanceAnalysis($filters),
                'performance_metrics' => $this->getOptimizedPerformanceMetrics($filters),
                'financial_overview' => $this->getOptimizedFinancialOverview($filters),
                'generated_at' => now()->toISOString(),
            ];
        });
    }

    /**
     * Optimized class-wise collection query
     * Replaces the inefficient getClassWiseCollection method
     */
    public function getOptimizedClassWiseCollection(array $filters = [])
    {
        $cacheKey = 'class_wise_collection_' . md5(serialize($filters));
        
        return Cache::remember($cacheKey, self::CACHE_DURATION_SHORT, function () use ($filters) {
            // Use a single optimized query with proper indexing
            $query = DB::table('fees')
                ->select([
                    'class_models.id as class_id',
                    'class_models.name as class_name',
                    DB::raw('COUNT(DISTINCT fees.student_id) as student_count'),
                    DB::raw('SUM(fees.paid_amount) as total_collected'),
                    DB::raw('SUM(fees.amount) as total_amount'),
                    DB::raw('SUM(fees.amount - fees.paid_amount) as pending_amount'),
                    DB::raw('ROUND((SUM(fees.paid_amount) / SUM(fees.amount)) * 100, 2) as collection_percentage')
                ])
                ->join('students', 'fees.student_id', '=', 'students.id')
                ->join('class_models', 'students.class_id', '=', 'class_models.id')
                ->where('students.status', 'active')
                ->groupBy('class_models.id', 'class_models.name')
                ->orderBy('class_models.name');

            // Apply date filters if provided
            if (isset($filters['date_from'])) {
                $query->where('fees.created_at', '>=', $filters['date_from']);
            }
            if (isset($filters['date_to'])) {
                $query->where('fees.created_at', '<=', $filters['date_to']);
            }

            return $query->get();
        });
    }

    /**
     * Optimized attendance cohort analysis
     * Replaces the inefficient getAttendanceCohortAnalysis method
     */
    public function getOptimizedAttendanceCohortAnalysis(array $filters = [])
    {
        $cacheKey = 'attendance_cohort_' . md5(serialize($filters));
        
        return Cache::remember($cacheKey, self::CACHE_DURATION_SHORT, function () use ($filters) {
            $dateFrom = $filters['date_from'] ?? Carbon::now()->subMonths(3)->format('Y-m-d');
            $dateTo = $filters['date_to'] ?? Carbon::now()->format('Y-m-d');

            // Single optimized query instead of N+1 queries
            $results = DB::table('class_models')
                ->select([
                    'class_models.id as class_id',
                    'class_models.name as class_name',
                    DB::raw('COUNT(DISTINCT students.id) as student_count'),
                    DB::raw('COUNT(attendances.id) as total_attendance_records'),
                    DB::raw('SUM(CASE WHEN attendances.status = "present" THEN 1 ELSE 0 END) as present_count'),
                    DB::raw('SUM(CASE WHEN attendances.status = "absent" THEN 1 ELSE 0 END) as absent_count'),
                    DB::raw('SUM(CASE WHEN attendances.status = "late" THEN 1 ELSE 0 END) as late_count'),
                    DB::raw('CASE WHEN COUNT(attendances.id) > 0 THEN ROUND((SUM(CASE WHEN attendances.status = "present" THEN 1 ELSE 0 END) * 100.0) / COUNT(attendances.id), 2) ELSE 0 END as attendance_rate')
                ])
                ->leftJoin('students', 'class_models.id', '=', 'students.class_id')
                ->leftJoin('attendances', function ($join) use ($dateFrom, $dateTo) {
                    $join->on('students.id', '=', 'attendances.student_id')
                         ->whereBetween('attendances.date', [$dateFrom, $dateTo]);
                })
                ->where('class_models.is_active', true)
                ->where('students.status', 'active')
                ->groupBy('class_models.id', 'class_models.name')
                ->orderBy('class_models.name')
                ->get();

            return $results->map(function ($result) {
                return [
                    'class_id' => $result->class_id,
                    'class_name' => $result->class_name,
                    'student_count' => $result->student_count,
                    'attendance_rate' => $result->attendance_rate ?? 0,
                    'total_records' => $result->total_attendance_records,
                    'present_count' => $result->present_count,
                    'absent_count' => $result->absent_count,
                    'late_count' => $result->late_count,
                    'performance_category' => $this->categorizeClassPerformance($result->attendance_rate ?? 0)
                ];
            });
        });
    }

    /**
     * Optimized student summary with chunking for large datasets
     */
    public function getOptimizedStudentSummary(array $filters = [])
    {
        $cacheKey = 'student_summary_' . md5(serialize($filters));
        
        return Cache::remember($cacheKey, self::CACHE_DURATION_MEDIUM, function () use ($filters) {
            $results = collect();
            
            // Use chunking to handle large datasets efficiently
            DB::table('students')
                ->select([
                    'students.id',
                    'students.name',
                    'students.admission_no',
                    'class_models.name as class_name',
                    DB::raw('COALESCE(fee_summary.total_fees, 0) as total_fees'),
                    DB::raw('COALESCE(fee_summary.paid_fees, 0) as paid_fees'),
                    DB::raw('COALESCE(attendance_summary.attendance_rate, 0) as attendance_rate'),
                    DB::raw('COALESCE(performance_summary.average_marks, 0) as average_marks')
                ])
                ->leftJoin('class_models', 'students.class_id', '=', 'class_models.id')
                ->leftJoin(DB::raw('(
                    SELECT 
                        student_id,
                        SUM(amount) as total_fees,
                        SUM(paid_amount) as paid_fees
                    FROM fees 
                    GROUP BY student_id
                ) as fee_summary'), 'students.id', '=', 'fee_summary.student_id')
                ->leftJoin(DB::raw('(
                    SELECT 
                        student_id,
                        ROUND((SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as attendance_rate
                    FROM attendances 
                    WHERE date >= date("now", "-3 months")
                    GROUP BY student_id
                ) as attendance_summary'), 'students.id', '=', 'attendance_summary.student_id')
                ->leftJoin(DB::raw('(
                    SELECT 
                        student_id,
                        ROUND(AVG(marks_obtained), 2) as average_marks
                    FROM results 
                    WHERE created_at >= date("now", "-6 months")
                    GROUP BY student_id
                ) as performance_summary'), 'students.id', '=', 'performance_summary.student_id')
                ->where('students.status', 'active')
                ->orderBy('students.name')
                ->chunk(self::CHUNK_SIZE, function ($chunk) use ($results) {
                    $results->push(...$chunk);
                });

            return $results;
        });
    }

    /**
     * Optimized attendance analysis
     */
    public function getOptimizedAttendanceAnalysis(array $filters = [])
    {
        $dateFrom = $filters['date_from'] ?? Carbon::now()->subMonths(1)->format('Y-m-d');
        $dateTo = $filters['date_to'] ?? Carbon::now()->format('Y-m-d');
        
        $cacheKey = "attendance_analysis_{$dateFrom}_{$dateTo}";
        
        return Cache::remember($cacheKey, self::CACHE_DURATION_SHORT, function () use ($dateFrom, $dateTo) {
            return DB::table('attendances')
                ->select([
                    DB::raw('DATE(date) as attendance_date'),
                    DB::raw('COUNT(*) as total_records'),
                    DB::raw('SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_count'),
                    DB::raw('SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent_count'),
                    DB::raw('SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late_count'),
                    DB::raw('ROUND((SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as daily_attendance_rate')
                ])
                ->whereBetween('date', [$dateFrom, $dateTo])
                ->groupBy(DB::raw('DATE(date)'))
                ->orderBy('attendance_date')
                ->get();
        });
    }

    /**
     * Optimized performance metrics
     */
    public function getOptimizedPerformanceMetrics(array $filters = [])
    {
        $cacheKey = 'performance_metrics_' . md5(serialize($filters));
        
        return Cache::remember($cacheKey, self::CACHE_DURATION_MEDIUM, function () use ($filters) {
            return DB::table('results')
                ->select([
                    'subject',
                    DB::raw('COUNT(*) as total_assessments'),
                    DB::raw('ROUND(AVG(marks_obtained), 2) as average_marks'),
                    DB::raw('MIN(marks_obtained) as min_marks'),
                    DB::raw('MAX(marks_obtained) as max_marks'),
                    DB::raw('COUNT(DISTINCT student_id) as students_assessed'),
                    DB::raw('SUM(CASE WHEN marks_obtained >= 40 THEN 1 ELSE 0 END) as passed_count'),
                    DB::raw('ROUND((SUM(CASE WHEN marks_obtained >= 40 THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as pass_rate')
                ])
                ->where('created_at', '>=', Carbon::now()->subMonths(6))
                ->groupBy('subject')
                ->orderBy('subject')
                ->get();
        });
    }

    /**
     * Optimized financial overview
     */
    public function getOptimizedFinancialOverview(array $filters = [])
    {
        $cacheKey = 'financial_overview_' . md5(serialize($filters));
        
        return Cache::remember($cacheKey, self::CACHE_DURATION_SHORT, function () use ($filters) {
            return DB::table('fees')
                ->select([
                    DB::raw('COUNT(DISTINCT student_id) as total_students'),
                    DB::raw('SUM(amount) as total_fees_amount'),
                    DB::raw('SUM(paid_amount) as total_paid_amount'),
                    DB::raw('SUM(amount - paid_amount) as total_pending_amount'),
                    DB::raw('CASE WHEN SUM(amount) > 0 THEN ROUND((CAST(SUM(paid_amount) AS REAL) / CAST(SUM(amount) AS REAL)) * 100, 2) ELSE 0 END as overall_collection_rate'),
                    DB::raw('COUNT(CASE WHEN status = "paid" THEN 1 END) as fully_paid_count'),
                    DB::raw('COUNT(CASE WHEN status = "partial" THEN 1 END) as partially_paid_count'),
                    DB::raw('COUNT(CASE WHEN status = "pending" THEN 1 END) as pending_count')
                ])
                ->first();
        });
    }

    /**
     * Clear all report caches
     */
    public function clearReportCaches()
    {
        $patterns = [
            'comprehensive_report_*',
            'class_wise_collection_*',
            'attendance_cohort_*',
            'student_summary_*',
            'attendance_analysis_*',
            'performance_metrics_*',
            'financial_overview_*'
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }

    /**
     * Helper method to categorize class performance
     */
    private function categorizeClassPerformance($rate)
    {
        if ($rate >= 90) return 'Excellent';
        if ($rate >= 80) return 'Good';
        if ($rate >= 70) return 'Average';
        if ($rate >= 60) return 'Below Average';
        return 'Poor';
    }
}