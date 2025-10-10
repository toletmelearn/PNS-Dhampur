<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\ClassModel;
use App\Models\Subject;
use App\Models\Attendance;
use App\Models\Result;
use App\Models\Fee;
use App\Models\Exam;

class CacheOptimizationService
{
    // Cache key prefixes
    const STUDENT_PREFIX = 'student:';
    const TEACHER_PREFIX = 'teacher:';
    const CLASS_PREFIX = 'class:';
    const ATTENDANCE_PREFIX = 'attendance:';
    const RESULTS_PREFIX = 'results:';
    const FEES_PREFIX = 'fees:';
    const DASHBOARD_PREFIX = 'dashboard:';
    const REPORTS_PREFIX = 'reports:';
    
    // Cache durations (in minutes)
    const SHORT_CACHE = 15;      // 15 minutes
    const MEDIUM_CACHE = 60;     // 1 hour
    const LONG_CACHE = 1440;     // 24 hours
    const EXTENDED_CACHE = 10080; // 1 week

    /**
     * Get or cache student dashboard data
     */
    public function getStudentDashboard($studentId)
    {
        $cacheKey = self::DASHBOARD_PREFIX . "student:{$studentId}";
        
        return Cache::remember($cacheKey, self::SHORT_CACHE, function () use ($studentId) {
            $student = Student::with(['class', 'user'])->find($studentId);
            
            if (!$student) {
                return null;
            }

            // Get upcoming assignments with eager loading
            $upcomingAssignments = $student->assignments()
                ->with(['subject', 'teacher.user'])
                ->where('due_date', '>=', Carbon::now())
                ->orderBy('due_date', 'asc')
                ->limit(5)
                ->get();

            // Get recent results
            $recentResults = Result::where('student_id', $studentId)
                ->with(['exam', 'subject'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            // Get attendance summary
            $attendanceSummary = $this->getStudentAttendanceSummary($studentId);

            return [
                'student' => $student,
                'upcoming_assignments' => $upcomingAssignments,
                'recent_results' => $recentResults,
                'attendance_summary' => $attendanceSummary,
                'cached_at' => Carbon::now()
            ];
        });
    }

    /**
     * Get or cache teacher dashboard data
     */
    public function getTeacherDashboard($teacherId)
    {
        $cacheKey = self::DASHBOARD_PREFIX . "teacher:{$teacherId}";
        
        return Cache::remember($cacheKey, self::SHORT_CACHE, function () use ($teacherId) {
            $teacher = Teacher::with(['user', 'subjects', 'classes'])->find($teacherId);
            
            if (!$teacher) {
                return null;
            }

            // Get classes with student counts
            $classes = $teacher->classes()->withCount('students')->get();

            // Get recent assignments
            $recentAssignments = $teacher->assignments()
                ->with(['subject', 'class'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            // Get pending submissions count
            $pendingSubmissions = DB::table('assignment_submissions')
                ->join('assignments', 'assignment_submissions.assignment_id', '=', 'assignments.id')
                ->where('assignments.teacher_id', $teacherId)
                ->where('assignment_submissions.status', 'submitted')
                ->count();

            return [
                'teacher' => $teacher,
                'classes' => $classes,
                'recent_assignments' => $recentAssignments,
                'pending_submissions' => $pendingSubmissions,
                'cached_at' => Carbon::now()
            ];
        });
    }

    /**
     * Get or cache class performance data
     */
    public function getClassPerformance($classId)
    {
        $cacheKey = self::CLASS_PREFIX . "performance:{$classId}";
        
        return Cache::remember($cacheKey, self::MEDIUM_CACHE, function () use ($classId) {
            $class = ClassModel::with(['students', 'teacher.user'])->find($classId);
            
            if (!$class) {
                return null;
            }

            // Get average attendance rate
            $attendanceRate = DB::table('attendances')
                ->join('students', 'attendances.student_id', '=', 'students.id')
                ->where('students.class_id', $classId)
                ->where('attendances.date', '>=', Carbon::now()->subMonth())
                ->selectRaw('
                    COUNT(*) as total_records,
                    SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_count
                ')
                ->first();

            $attendancePercentage = $attendanceRate->total_records > 0 
                ? ($attendanceRate->present_count / $attendanceRate->total_records) * 100 
                : 0;

            // Get average performance
            $averagePerformance = Result::join('students', 'results.student_id', '=', 'students.id')
                ->where('students.class_id', $classId)
                ->avg('results.marks_obtained');

            // Get subject-wise performance
            $subjectPerformance = Result::join('students', 'results.student_id', '=', 'students.id')
                ->where('students.class_id', $classId)
                ->select('results.subject', DB::raw('AVG(results.marks_obtained) as average'))
                ->groupBy('results.subject')
                ->get();

            return [
                'class' => $class,
                'attendance_rate' => round($attendancePercentage, 2),
                'average_performance' => round($averagePerformance ?? 0, 2),
                'subject_performance' => $subjectPerformance,
                'cached_at' => Carbon::now()
            ];
        });
    }

    /**
     * Get or cache student attendance summary
     */
    public function getStudentAttendanceSummary($studentId)
    {
        $cacheKey = self::ATTENDANCE_PREFIX . "summary:{$studentId}";
        
        return Cache::remember($cacheKey, self::MEDIUM_CACHE, function () use ($studentId) {
            $currentMonth = Carbon::now()->startOfMonth();
            
            $attendance = Attendance::where('student_id', $studentId)
                ->where('date', '>=', $currentMonth)
                ->selectRaw('
                    COUNT(*) as total_days,
                    SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_days,
                    SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent_days,
                    SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late_days
                ')
                ->first();

            $attendanceRate = $attendance->total_days > 0 
                ? ($attendance->present_days / $attendance->total_days) * 100 
                : 0;

            return [
                'total_days' => $attendance->total_days,
                'present_days' => $attendance->present_days,
                'absent_days' => $attendance->absent_days,
                'late_days' => $attendance->late_days,
                'attendance_rate' => round($attendanceRate, 2),
                'cached_at' => Carbon::now()
            ];
        });
    }

    /**
     * Get or cache financial reports data
     */
    public function getFinancialReports()
    {
        $cacheKey = self::REPORTS_PREFIX . 'financial:monthly';
        
        return Cache::remember($cacheKey, self::LONG_CACHE, function () {
            $currentMonth = Carbon::now()->startOfMonth();
            $previousMonth = Carbon::now()->subMonth()->startOfMonth();
            
            // Current month collections
            $currentMonthCollection = Fee::where('paid_date', '>=', $currentMonth)
                ->sum('paid_amount');

            // Previous month collections
            $previousMonthCollection = Fee::whereBetween('paid_date', [
                $previousMonth, 
                $previousMonth->copy()->endOfMonth()
            ])->sum('paid_amount');

            // Outstanding fees
            $outstandingFees = Fee::whereNull('paid_date')
                ->orWhere('paid_amount', '<', DB::raw('total_amount'))
                ->sum(DB::raw('total_amount - COALESCE(paid_amount, 0)'));

            // Class-wise collection
            $classWiseCollection = Fee::join('students', 'fees.student_id', '=', 'students.id')
                ->join('classes', 'students.class_id', '=', 'classes.id')
                ->select('classes.name as class_name', DB::raw('SUM(fees.paid_amount) as total_collected'))
                ->whereNotNull('fees.paid_date')
                ->groupBy('classes.id', 'classes.name')
                ->get();

            return [
                'current_month_collection' => $currentMonthCollection,
                'previous_month_collection' => $previousMonthCollection,
                'outstanding_fees' => $outstandingFees,
                'class_wise_collection' => $classWiseCollection,
                'growth_percentage' => $previousMonthCollection > 0 
                    ? (($currentMonthCollection - $previousMonthCollection) / $previousMonthCollection) * 100 
                    : 0,
                'cached_at' => Carbon::now()
            ];
        });
    }

    /**
     * Get or cache academic performance reports
     */
    public function getAcademicReports()
    {
        $cacheKey = self::REPORTS_PREFIX . 'academic:performance';
        
        return Cache::remember($cacheKey, self::LONG_CACHE, function () {
            // Overall statistics
            $totalStudents = Student::where('status', 'active')->count();
            $totalExams = Exam::count();
            $averagePerformance = Result::avg('marks_obtained');

            // Subject-wise performance
            $subjectPerformance = Result::select('subject', DB::raw('AVG(marks_obtained) as average'))
                ->groupBy('subject')
                ->orderBy('average', 'desc')
                ->get();

            // Class-wise performance
            $classPerformance = Result::join('students', 'results.student_id', '=', 'students.id')
                ->join('classes', 'students.class_id', '=', 'classes.id')
                ->select('classes.name as class_name', DB::raw('AVG(results.marks_obtained) as average'))
                ->groupBy('classes.id', 'classes.name')
                ->orderBy('average', 'desc')
                ->get();

            // Top performers
            $topPerformers = Result::select('student_id', DB::raw('AVG(marks_obtained) as average'))
                ->with('student.class')
                ->groupBy('student_id')
                ->orderBy('average', 'desc')
                ->limit(10)
                ->get();

            return [
                'total_students' => $totalStudents,
                'total_exams' => $totalExams,
                'average_performance' => round($averagePerformance ?? 0, 2),
                'subject_performance' => $subjectPerformance,
                'class_performance' => $classPerformance,
                'top_performers' => $topPerformers,
                'cached_at' => Carbon::now()
            ];
        });
    }

    /**
     * Clear specific cache entries
     */
    public function clearCache($type, $id = null)
    {
        switch ($type) {
            case 'student':
                if ($id) {
                    Cache::forget(self::STUDENT_PREFIX . $id);
                    Cache::forget(self::DASHBOARD_PREFIX . "student:{$id}");
                    Cache::forget(self::ATTENDANCE_PREFIX . "summary:{$id}");
                } else {
                    $this->clearCacheByPrefix(self::STUDENT_PREFIX);
                    $this->clearCacheByPrefix(self::DASHBOARD_PREFIX . 'student:');
                    $this->clearCacheByPrefix(self::ATTENDANCE_PREFIX . 'summary:');
                }
                break;

            case 'teacher':
                if ($id) {
                    Cache::forget(self::TEACHER_PREFIX . $id);
                    Cache::forget(self::DASHBOARD_PREFIX . "teacher:{$id}");
                } else {
                    $this->clearCacheByPrefix(self::TEACHER_PREFIX);
                    $this->clearCacheByPrefix(self::DASHBOARD_PREFIX . 'teacher:');
                }
                break;

            case 'class':
                if ($id) {
                    Cache::forget(self::CLASS_PREFIX . "performance:{$id}");
                } else {
                    $this->clearCacheByPrefix(self::CLASS_PREFIX);
                }
                break;

            case 'reports':
                $this->clearCacheByPrefix(self::REPORTS_PREFIX);
                break;

            case 'all':
                Cache::flush();
                break;
        }

        Log::info("Cache cleared for type: {$type}" . ($id ? ", ID: {$id}" : ''));
    }

    /**
     * Clear cache entries by prefix
     */
    private function clearCacheByPrefix($prefix)
    {
        // This is a simplified implementation
        // In production, you might want to use Redis SCAN or similar
        $keys = Cache::getRedis()->keys($prefix . '*');
        if (!empty($keys)) {
            Cache::getRedis()->del($keys);
        }
    }

    /**
     * Get cache statistics
     */
    public function getCacheStatistics()
    {
        try {
            $redis = Cache::getRedis();
            $info = $redis->info('memory');
            
            return [
                'memory_usage' => $info['used_memory_human'] ?? 'N/A',
                'memory_peak' => $info['used_memory_peak_human'] ?? 'N/A',
                'total_keys' => $redis->dbsize(),
                'cache_hits' => $info['keyspace_hits'] ?? 0,
                'cache_misses' => $info['keyspace_misses'] ?? 0,
                'hit_rate' => $this->calculateHitRate($info['keyspace_hits'] ?? 0, $info['keyspace_misses'] ?? 0),
                'uptime' => $info['uptime_in_seconds'] ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get cache statistics: ' . $e->getMessage());
            return [
                'error' => 'Unable to retrieve cache statistics',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Calculate cache hit rate
     */
    private function calculateHitRate($hits, $misses)
    {
        $total = $hits + $misses;
        return $total > 0 ? round(($hits / $total) * 100, 2) : 0;
    }

    /**
     * Warm up frequently accessed cache
     */
    public function warmUpCache()
    {
        Log::info('Starting cache warm-up process');

        try {
            // Warm up academic reports
            $this->getAcademicReports();
            
            // Warm up financial reports
            $this->getFinancialReports();
            
            // Warm up class performance for active classes
            $activeClasses = ClassModel::where('status', 'active')->pluck('id');
            foreach ($activeClasses as $classId) {
                $this->getClassPerformance($classId);
            }

            Log::info('Cache warm-up completed successfully');
            return true;
        } catch (\Exception $e) {
            Log::error('Cache warm-up failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Schedule cache refresh for specific data types
     */
    public function scheduleRefresh($type, $id = null)
    {
        // Clear existing cache
        $this->clearCache($type, $id);
        
        // Pre-load fresh data
        switch ($type) {
            case 'student':
                if ($id) {
                    $this->getStudentDashboard($id);
                    $this->getStudentAttendanceSummary($id);
                }
                break;
                
            case 'teacher':
                if ($id) {
                    $this->getTeacherDashboard($id);
                }
                break;
                
            case 'class':
                if ($id) {
                    $this->getClassPerformance($id);
                }
                break;
                
            case 'reports':
                $this->getAcademicReports();
                $this->getFinancialReports();
                break;
        }
        
        Log::info("Cache refreshed for type: {$type}" . ($id ? ", ID: {$id}" : ''));
    }
}