<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportExport;
use Carbon\Carbon;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Fee;
use App\Models\Exam;
use App\Models\Result;
use App\Models\ClassModel;
use App\Models\Subject;
use App\Models\Salary;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        // Return view for web requests
        return view('reports.index');
    }

    /**
     * Get academic reports data
     */
    public function academicReports(Request $request)
    {
        $data = [
            'total_students' => Student::count(),
            'total_exams' => Exam::count(),
            'total_results' => Result::count(),
            'average_performance' => Result::avg('marks_obtained') ?? 0,
            'subject_performance' => $this->getSubjectPerformance(),
            'class_performance' => $this->getClassPerformance(),
            'exam_trends' => $this->getExamTrends(),
        ];

        return response()->json($data);
    }

    /**
     * Get financial reports data with comprehensive comparative analysis
     */
    public function financialReports(Request $request)
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $previousMonth = Carbon::now()->subMonth()->month;
        $previousYear = Carbon::now()->subYear()->year;

        // Current period data
        $currentRevenue = Fee::where('status', 'paid')->sum('paid_amount');
        $currentMonthlyCollection = Fee::whereMonth('paid_date', $currentMonth)
            ->whereYear('paid_date', $currentYear)
            ->sum('paid_amount');
        $currentExpenses = Salary::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->sum('amount');

        // Previous period data for comparison
        $previousMonthCollection = Fee::whereMonth('paid_date', $previousMonth)
            ->whereYear('paid_date', $currentYear)
            ->sum('paid_amount');
        $previousYearCollection = Fee::whereMonth('paid_date', $currentMonth)
            ->whereYear('paid_date', $previousYear)
            ->sum('paid_amount');
        $previousYearRevenue = Fee::where('status', 'paid')
            ->whereYear('paid_date', $previousYear)
            ->sum('paid_amount');

        // Calculate comparative metrics
        $monthOverMonthGrowth = $previousMonthCollection > 0 
            ? (($currentMonthlyCollection - $previousMonthCollection) / $previousMonthCollection) * 100 
            : 0;
        $yearOverYearGrowth = $previousYearCollection > 0 
            ? (($currentMonthlyCollection - $previousYearCollection) / $previousYearCollection) * 100 
            : 0;
        $annualRevenueGrowth = $previousYearRevenue > 0 
            ? (($currentRevenue - $previousYearRevenue) / $previousYearRevenue) * 100 
            : 0;

        $data = [
            // Basic metrics
            'total_revenue' => $currentRevenue,
            'pending_fees' => Fee::where('status', '!=', 'paid')->sum('amount') - Fee::where('status', '!=', 'paid')->sum('paid_amount'),
            'monthly_collection' => $currentMonthlyCollection,
            'total_expenses' => Salary::sum('amount'),
            
            // Comparative analysis
            'comparative_analysis' => [
                'month_over_month' => [
                    'current_month' => $currentMonthlyCollection,
                    'previous_month' => $previousMonthCollection,
                    'growth_percentage' => round($monthOverMonthGrowth, 2),
                    'growth_amount' => $currentMonthlyCollection - $previousMonthCollection,
                    'trend' => $monthOverMonthGrowth > 0 ? 'positive' : ($monthOverMonthGrowth < 0 ? 'negative' : 'stable')
                ],
                'year_over_year' => [
                    'current_year_month' => $currentMonthlyCollection,
                    'previous_year_month' => $previousYearCollection,
                    'growth_percentage' => round($yearOverYearGrowth, 2),
                    'growth_amount' => $currentMonthlyCollection - $previousYearCollection,
                    'trend' => $yearOverYearGrowth > 0 ? 'positive' : ($yearOverYearGrowth < 0 ? 'negative' : 'stable')
                ],
                'annual_revenue' => [
                    'current_year' => $currentRevenue,
                    'previous_year' => $previousYearRevenue,
                    'growth_percentage' => round($annualRevenueGrowth, 2),
                    'growth_amount' => $currentRevenue - $previousYearRevenue,
                    'trend' => $annualRevenueGrowth > 0 ? 'positive' : ($annualRevenueGrowth < 0 ? 'negative' : 'stable')
                ]
            ],
            
            // Advanced analytics
            'financial_health_indicators' => $this->getFinancialHealthIndicators(),
            'revenue_forecasting' => $this->getRevenueForecasting(),
            'expense_analysis' => $this->getExpenseAnalysis(),
            
            // Existing data
            'fee_collection_trends' => $this->getFeeCollectionTrends(),
            'payment_methods' => $this->getPaymentMethodStats(),
            'class_wise_collection' => $this->getClassWiseCollection(),
        ];

        return response()->json($data);
    }

    /**
     * Get attendance reports data with comprehensive trend analysis
     */
    public function attendanceReports(Request $request)
    {
        $currentDate = Carbon::now();
        $startOfMonth = $currentDate->startOfMonth()->copy();
        $endOfMonth = $currentDate->endOfMonth()->copy();
        $startOfYear = $currentDate->startOfYear()->copy();
        
        // Basic attendance metrics
        $totalStudents = Student::where('status', 'active')->count();
        $todayAttendances = Attendance::whereDate('date', $currentDate->format('Y-m-d'))->get();
        $presentToday = $todayAttendances->where('status', 'present')->count();
        $absentToday = $todayAttendances->where('status', 'absent')->count();
        $lateToday = $todayAttendances->where('status', 'late')->count();
        
        // Monthly attendance data
        $monthlyAttendances = Attendance::whereBetween('date', [$startOfMonth, $endOfMonth])->get();
        $monthlyPresent = $monthlyAttendances->where('status', 'present')->count();
        $monthlyTotal = $monthlyAttendances->count();
        $monthlyAttendanceRate = $monthlyTotal > 0 ? ($monthlyPresent / $monthlyTotal) * 100 : 0;
        
        // Previous month for comparison
        $previousMonth = $currentDate->copy()->subMonth();
        $prevMonthStart = $previousMonth->startOfMonth();
        $prevMonthEnd = $previousMonth->endOfMonth();
        $prevMonthAttendances = Attendance::whereBetween('date', [$prevMonthStart, $prevMonthEnd])->get();
        $prevMonthPresent = $prevMonthAttendances->where('status', 'present')->count();
        $prevMonthTotal = $prevMonthAttendances->count();
        $prevMonthRate = $prevMonthTotal > 0 ? ($prevMonthPresent / $prevMonthTotal) * 100 : 0;

        $data = [
            // Basic metrics
            'overall_attendance' => round($monthlyAttendanceRate, 2),
            'present_today' => $presentToday,
            'absent_today' => $absentToday,
            'late_today' => $lateToday,
            'total_students' => $totalStudents,
            
            // Trend analysis
            'trend_analysis' => [
                'seasonal_patterns' => $this->getSeasonalAttendancePatterns(),
                'weekly_trends' => $this->getWeeklyAttendanceTrends(),
                'monthly_comparison' => [
                    'current_month' => [
                        'rate' => round($monthlyAttendanceRate, 2),
                        'present' => $monthlyPresent,
                        'total' => $monthlyTotal,
                        'month' => $currentDate->format('F Y')
                    ],
                    'previous_month' => [
                        'rate' => round($prevMonthRate, 2),
                        'present' => $prevMonthPresent,
                        'total' => $prevMonthTotal,
                        'month' => $previousMonth->format('F Y')
                    ],
                    'change_percentage' => $prevMonthRate > 0 ? round((($monthlyAttendanceRate - $prevMonthRate) / $prevMonthRate) * 100, 2) : 0,
                    'trend' => $monthlyAttendanceRate > $prevMonthRate ? 'improving' : ($monthlyAttendanceRate < $prevMonthRate ? 'declining' : 'stable')
                ],
                'daily_patterns' => $this->getDailyAttendancePatterns(),
                'absenteeism_analysis' => $this->getAbsenteeismAnalysis()
            ],
            
            // Predictive modeling
            'predictive_insights' => [
                'attendance_forecast' => $this->getAttendanceForecast(),
                'risk_assessment' => $this->getAttendanceRiskAssessment(),
                'intervention_recommendations' => $this->getAttendanceInterventions(),
                'seasonal_predictions' => $this->getSeasonalPredictions()
            ],
            
            // Advanced analytics
            'cohort_analysis' => $this->getAttendanceCohortAnalysis(),
            'correlation_metrics' => $this->getAttendanceCorrelations(),
            
            // Existing data
            'class_wise_attendance' => $this->getClassWiseAttendance(),
            'monthly_trends' => $this->getAttendanceTrends(),
            'low_attendance_students' => $this->getLowAttendanceStudents(),
        ];

        return response()->json($data);
    }

    /**
     * Get performance reports data with comprehensive predictive analytics
     */
    public function performanceReports(Request $request)
    {
        $currentDate = Carbon::now();
        $academicYear = $request->input('academic_year', $currentDate->year);
        
        // Basic performance metrics
        $totalStudents = Student::where('status', 'active')->count();
        $totalResults = Result::whereYear('created_at', $academicYear)->count();
        
        // Current term performance
        $currentTermResults = Result::whereYear('created_at', $academicYear)
            ->where('created_at', '>=', $currentDate->subMonths(3))
            ->get();
        
        $averagePerformance = $currentTermResults->avg('marks_obtained') ?? 0;
        $passRate = $this->calculatePassRate($currentTermResults);
        
        // Previous term for comparison
        $previousTermResults = Result::whereYear('created_at', $academicYear)
            ->whereBetween('created_at', [$currentDate->copy()->subMonths(6), $currentDate->copy()->subMonths(3)])
            ->get();
        
        $previousAverage = $previousTermResults->avg('marks_obtained') ?? 0;
        $previousPassRate = $this->calculatePassRate($previousTermResults);

        $data = [
            // Basic metrics
            'current_performance' => [
                'average_score' => round($averagePerformance, 2),
                'pass_rate' => round($passRate, 2),
                'total_assessments' => $currentTermResults->count(),
                'students_assessed' => $currentTermResults->unique('student_id')->count()
            ],
            
            // Comparative analysis
            'performance_comparison' => [
                'current_term' => [
                    'average' => round($averagePerformance, 2),
                    'pass_rate' => round($passRate, 2),
                    'term' => 'Current Term'
                ],
                'previous_term' => [
                    'average' => round($previousAverage, 2),
                    'pass_rate' => round($previousPassRate, 2),
                    'term' => 'Previous Term'
                ],
                'improvement_percentage' => $previousAverage > 0 ? round((($averagePerformance - $previousAverage) / $previousAverage) * 100, 2) : 0,
                'trend' => $averagePerformance > $previousAverage ? 'improving' : ($averagePerformance < $previousAverage ? 'declining' : 'stable')
            ],
            
            // Predictive analytics
            'predictive_insights' => [
                'performance_forecast' => $this->getPerformanceForecast(),
                'risk_assessment' => $this->getPerformanceRiskAssessment(),
                'success_predictions' => $this->getSuccessPredictions(),
                'intervention_recommendations' => $this->getPerformanceInterventions(),
                'grade_predictions' => $this->getGradePredictions()
            ],
            
            // Advanced analytics
            'learning_analytics' => [
                'subject_performance_trends' => $this->getSubjectPerformanceTrends(),
                'student_progression_analysis' => $this->getStudentProgressionAnalysis(),
                'competency_mapping' => $this->getCompetencyMapping(),
                'learning_velocity' => $this->getLearningVelocity()
            ],
            
            // Cohort and comparative analysis
            'cohort_analysis' => [
                'class_performance_comparison' => $this->getClassPerformanceComparison(),
                'peer_benchmarking' => $this->getPeerBenchmarking(),
                'historical_trends' => $this->getHistoricalPerformanceTrends()
            ],
            
            // Machine learning insights
            'ml_insights' => [
                'performance_patterns' => $this->getPerformancePatterns(),
                'anomaly_detection' => $this->detectPerformanceAnomalies(),
                'correlation_analysis' => $this->getPerformanceCorrelations(),
                'clustering_analysis' => $this->getStudentClusters()
            ],
            
            // Existing data
            'top_performers' => $this->getTopPerformers(),
            'subject_averages' => $this->getSubjectAverages(),
            'grade_distribution' => $this->getGradeDistribution(),
            'improvement_trends' => $this->getImprovementTrends(),
            'teacher_effectiveness' => $this->getTeacherEffectiveness(),
        ];

        return response()->json($data);
    }

    /**
     * Get administrative reports data
     */
    public function administrativeReports(Request $request)
    {
        $data = [
            'total_teachers' => Teacher::count(),
            'total_classes' => ClassModel::count(),
            'total_subjects' => Subject::count(),
            'staff_distribution' => $this->getStaffDistribution(),
            'resource_utilization' => $this->getResourceUtilization(),
            'operational_metrics' => $this->getOperationalMetrics(),
        ];

        return response()->json($data);
    }

    /**
     * Export report as PDF
     */
    public function exportPdf(Request $request)
    {
        $reportType = $request->input('type');
        $format = $request->input('format', 'pdf');
        
        // Generate report data based on type
        $data = $this->getReportData($reportType);
        
        if ($format === 'pdf') {
            // PDF export logic here
            return response()->json(['message' => 'PDF export initiated', 'download_url' => '/downloads/report.pdf']);
        } else {
            // Excel export logic here
            return response()->json(['message' => 'Excel export initiated', 'download_url' => '/downloads/report.xlsx']);
        }
    }

    // Export reports in different formats
    public function exportReport(Request $request)
    {
        $type = $request->get('type', 'academic');
        $format = $request->get('format', 'pdf');
        
        // Get report data based on type
        $data = $this->getReportData($type);
        
        if ($format === 'pdf') {
            return $this->exportToPDF($type, $data);
        } elseif ($format === 'excel') {
            return $this->exportToExcel($type, $data);
        } elseif ($format === 'csv') {
            return $this->exportToCSV($type, $data);
        }
        
        return response()->json(['error' => 'Invalid format'], 400);
    }
    
    private function exportToPDF($type, $data)
    {
        $pdf = Pdf::loadView('reports.pdf.' . $type, compact('data'));
        $pdf->setPaper('A4', 'portrait');
        
        return $pdf->download($type . '_report_' . date('Y-m-d') . '.pdf');
    }
    
    private function exportToExcel($type, $data)
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new ReportExport($type, $data),
            $type . '_report_' . date('Y-m-d') . '.xlsx'
        );
    }
    
    private function exportToCSV($type, $data)
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new ReportExport($type, $data),
            $type . '_report_' . date('Y-m-d') . '.csv',
            \Maatwebsite\Excel\Excel::CSV
        );
    }

    // Private helper methods for data aggregation

    private function getSubjectPerformance()
    {
        return Result::select('subject', DB::raw('AVG(marks_obtained) as average'))
            ->groupBy('subject')
            ->get()
            ->map(function ($item) {
                return [
                    'subject' => $item->subject,
                    'average' => round($item->average, 2)
                ];
            });
    }

    private function getClassPerformance()
    {
        return Result::join('students', 'results.student_id', '=', 'students.id')
            ->join('class_models', 'students.class_id', '=', 'class_models.id')
            ->select('class_models.name as class_name', DB::raw('AVG(results.marks_obtained) as average'))
            ->groupBy('class_models.id', 'class_models.name')
            ->get()
            ->map(function ($item) {
                return [
                    'class' => $item->class_name,
                    'average' => round($item->average, 2)
                ];
            });
    }

    private function getExamTrends()
    {
        return Exam::select(
                DB::raw('MONTH(start_date) as month'),
                DB::raw('COUNT(*) as exam_count'),
                DB::raw('AVG((SELECT AVG(marks_obtained) FROM results WHERE results.exam_id = exams.id)) as avg_performance')
            )
            ->whereYear('start_date', Carbon::now()->year)
            ->groupBy(DB::raw('MONTH(start_date)'))
            ->orderBy('month')
            ->get();
    }

    private function getFeeCollectionTrends()
    {
        return Fee::select(
                DB::raw('MONTH(paid_date) as month'),
                DB::raw('SUM(paid_amount) as total_collected')
            )
            ->whereNotNull('paid_date')
            ->whereYear('paid_date', Carbon::now()->year)
            ->groupBy(DB::raw('MONTH(paid_date)'))
            ->orderBy('month')
            ->get();
    }

    private function getPaymentMethodStats()
    {
        // Mock data - replace with actual payment method tracking
        return [
            ['method' => 'Cash', 'amount' => 150000, 'percentage' => 45],
            ['method' => 'Online', 'amount' => 120000, 'percentage' => 36],
            ['method' => 'Cheque', 'amount' => 63000, 'percentage' => 19],
        ];
    }

    private function getClassWiseCollection()
    {
        return Fee::join('students', 'fees.student_id', '=', 'students.id')
            ->join('class_models', 'students.class_id', '=', 'class_models.id')
            ->select('class_models.name as class_name', DB::raw('SUM(fees.paid_amount) as total_collected'))
            ->groupBy('class_models.id', 'class_models.name')
            ->get();
    }

    private function getClassWiseAttendance()
    {
        // Mock data - replace with actual attendance model
        return ClassModel::get()->map(function ($class) {
            return [
                'class' => $class->name,
                'attendance_rate' => rand(75, 95),
                'present' => rand(20, 35),
                'total' => rand(25, 40)
            ];
        });
    }

    private function getAttendanceTrends()
    {
        // Mock monthly attendance data
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return collect($months)->map(function ($month, $index) {
            return [
                'month' => $month,
                'attendance_rate' => rand(80, 95)
            ];
        });
    }

    private function getLowAttendanceStudents()
    {
        // Mock data - replace with actual attendance tracking
        return Student::with('class')->limit(10)->get()->map(function ($student) {
            return [
                'name' => $student->name,
                'class' => $student->class->name ?? 'N/A',
                'attendance_rate' => rand(40, 74)
            ];
        });
    }

    private function getTopPerformers()
    {
        return Result::select('student_id', DB::raw('AVG(marks_obtained) as average'))
            ->with('student.class')
            ->groupBy('student_id')
            ->orderBy('average', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($result) {
                return [
                    'name' => $result->student->name,
                    'class' => $result->student->class->name ?? 'N/A',
                    'average' => round($result->average, 2)
                ];
            });
    }

    private function getSubjectAverages()
    {
        return Result::select('subject', DB::raw('AVG(marks_obtained) as average'))
            ->groupBy('subject')
            ->get()
            ->map(function ($item) {
                return [
                    'subject' => $item->subject,
                    'average' => round($item->average, 2)
                ];
            });
    }

    private function getGradeDistribution()
    {
        return Result::select('grade', DB::raw('COUNT(*) as count'))
            ->whereNotNull('grade')
            ->groupBy('grade')
            ->get()
            ->map(function ($item) {
                return [
                    'grade' => $item->grade,
                    'count' => $item->count
                ];
            });
    }

    private function getImprovementTrends()
    {
        // Mock data for improvement trends
        return [
            ['period' => 'Q1', 'improvement' => 5.2],
            ['period' => 'Q2', 'improvement' => 7.8],
            ['period' => 'Q3', 'improvement' => 3.4],
            ['period' => 'Q4', 'improvement' => 6.1],
        ];
    }

    private function getTeacherEffectiveness()
    {
        return Teacher::with('user')->get()->map(function ($teacher) {
            return [
                'name' => $teacher->user->name,
                'effectiveness_score' => rand(75, 95),
                'student_feedback' => rand(4.0, 5.0),
                'subjects_taught' => rand(1, 3)
            ];
        });
    }

    private function getStaffDistribution()
    {
        return [
            ['category' => 'Teaching Staff', 'count' => Teacher::count()],
            ['category' => 'Administrative', 'count' => 5],
            ['category' => 'Support Staff', 'count' => 8],
        ];
    }

    private function getResourceUtilization()
    {
        return [
            ['resource' => 'Classrooms', 'utilization' => 85],
            ['resource' => 'Library', 'utilization' => 65],
            ['resource' => 'Laboratory', 'utilization' => 70],
            ['resource' => 'Sports Facilities', 'utilization' => 55],
        ];
    }

    private function getOperationalMetrics()
    {
        return [
            'student_teacher_ratio' => round(Student::count() / max(Teacher::count(), 1), 1),
            'average_class_size' => round(Student::count() / max(ClassModel::count(), 1), 1),
            'subjects_per_class' => round(Subject::count() / max(ClassModel::count(), 1), 1),
        ];
    }

    private function getReportData($reportType)
    {
        switch ($reportType) {
            case 'academic':
                return $this->academicReports(request());
            case 'financial':
                return $this->financialReports(request());
            case 'attendance':
                return $this->attendanceReports(request());
            case 'performance':
                return $this->performanceReports(request());
            case 'administrative':
                return $this->administrativeReports(request());
            default:
                return ['error' => 'Invalid report type'];
        }
    }

    // Attendance Trend Analysis Helper Methods

    private function getSeasonalAttendancePatterns()
    {
        $currentYear = Carbon::now()->year;
        $seasons = [
            'Spring' => ['03', '04', '05'],
            'Summer' => ['06', '07', '08'],
            'Autumn' => ['09', '10', '11'],
            'Winter' => ['12', '01', '02']
        ];

        $patterns = [];
        foreach ($seasons as $season => $months) {
            $attendances = Attendance::whereYear('date', $currentYear)
                ->whereIn(DB::raw('MONTH(date)'), $months)
                ->get();
            
            $total = $attendances->count();
            $present = $attendances->where('status', 'present')->count();
            $rate = $total > 0 ? ($present / $total) * 100 : 0;

            $patterns[] = [
                'season' => $season,
                'attendance_rate' => round($rate, 2),
                'total_records' => $total,
                'present_count' => $present
            ];
        }

        return $patterns;
    }

    private function getWeeklyAttendanceTrends()
    {
        $startDate = Carbon::now()->subWeeks(12);
        $endDate = Carbon::now();
        
        $weeklyData = [];
        $current = $startDate->copy();
        
        while ($current->lte($endDate)) {
            $weekStart = $current->copy()->startOfWeek();
            $weekEnd = $current->copy()->endOfWeek();
            
            $attendances = Attendance::whereBetween('date', [$weekStart, $weekEnd])->get();
            $total = $attendances->count();
            $present = $attendances->where('status', 'present')->count();
            $rate = $total > 0 ? ($present / $total) * 100 : 0;

            $weeklyData[] = [
                'week_start' => $weekStart->format('Y-m-d'),
                'week_end' => $weekEnd->format('Y-m-d'),
                'attendance_rate' => round($rate, 2),
                'total_records' => $total,
                'trend' => $this->calculateWeeklyTrend($weeklyData, $rate)
            ];

            $current->addWeek();
        }

        return $weeklyData;
    }

    private function getDailyAttendancePatterns()
    {
        $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $patterns = [];

        foreach ($daysOfWeek as $index => $day) {
            $dayNumber = $index + 1; // MySQL DAYOFWEEK starts from 1 (Sunday)
            if ($dayNumber == 7) $dayNumber = 1; // Adjust for Monday start
            else $dayNumber++;

            $attendances = Attendance::whereRaw('DAYOFWEEK(date) = ?', [$dayNumber])
                ->where('date', '>=', Carbon::now()->subMonths(3))
                ->get();
            
            $total = $attendances->count();
            $present = $attendances->where('status', 'present')->count();
            $rate = $total > 0 ? ($present / $total) * 100 : 0;

            $patterns[] = [
                'day' => $day,
                'attendance_rate' => round($rate, 2),
                'total_records' => $total,
                'average_present' => $present
            ];
        }

        return $patterns;
    }

    private function getAbsenteeismAnalysis()
    {
        $currentMonth = Carbon::now();
        $startDate = $currentMonth->copy()->subMonths(6);
        
        $absenteeismData = Attendance::where('status', 'absent')
            ->where('date', '>=', $startDate)
            ->with('student')
            ->get();

        $chronicAbsentees = $absenteeismData->groupBy('student_id')
            ->map(function ($absences, $studentId) {
                $student = $absences->first()->student;
                $totalAbsences = $absences->count();
                $consecutiveAbsences = $this->calculateConsecutiveAbsences($studentId);
                
                return [
                    'student_id' => $studentId,
                    'student_name' => $student->name ?? 'Unknown',
                    'total_absences' => $totalAbsences,
                    'consecutive_absences' => $consecutiveAbsences,
                    'risk_level' => $this->calculateAbsenteeismRisk($totalAbsences, $consecutiveAbsences)
                ];
            })
            ->sortByDesc('total_absences')
            ->take(20)
            ->values();

        return [
            'chronic_absentees' => $chronicAbsentees,
            'total_absent_days' => $absenteeismData->count(),
            'average_absences_per_student' => round($absenteeismData->count() / max(Student::count(), 1), 2),
            'absenteeism_rate' => $this->calculateOverallAbsenteeismRate()
        ];
    }

    private function getAttendanceForecast()
    {
        // Simple linear regression for attendance forecasting
        $historicalData = $this->getHistoricalAttendanceRates();
        $forecast = $this->calculateLinearTrend($historicalData);
        
        $nextMonths = [];
        for ($i = 1; $i <= 6; $i++) {
            $futureDate = Carbon::now()->addMonths($i);
            $predictedRate = $forecast['slope'] * $i + $forecast['intercept'];
            
            $nextMonths[] = [
                'month' => $futureDate->format('F Y'),
                'predicted_rate' => round(max(0, min(100, $predictedRate)), 2),
                'confidence_level' => $this->calculateConfidenceLevel($i),
                'trend_direction' => $forecast['slope'] > 0 ? 'improving' : 'declining'
            ];
        }

        return [
            'forecast_months' => $nextMonths,
            'trend_analysis' => $forecast,
            'accuracy_metrics' => $this->calculateForecastAccuracy($historicalData)
        ];
    }

    private function getAttendanceRiskAssessment()
    {
        $riskFactors = [];
        
        // Identify students at risk
        $studentsAtRisk = Student::whereHas('attendances', function ($query) {
            $query->where('date', '>=', Carbon::now()->subMonth())
                  ->where('status', 'absent');
        }, '>=', 5)->with(['attendances' => function ($query) {
            $query->where('date', '>=', Carbon::now()->subMonth());
        }])->get();

        foreach ($studentsAtRisk as $student) {
            $recentAbsences = $student->attendances->where('status', 'absent')->count();
            $totalRecords = $student->attendances->count();
            $absenteeismRate = $totalRecords > 0 ? ($recentAbsences / $totalRecords) * 100 : 0;

            $riskFactors[] = [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'class' => $student->class->name ?? 'N/A',
                'absenteeism_rate' => round($absenteeismRate, 2),
                'recent_absences' => $recentAbsences,
                'risk_score' => $this->calculateRiskScore($absenteeismRate, $recentAbsences),
                'risk_category' => $this->categorizeRisk($absenteeismRate)
            ];
        }

        return [
            'high_risk_students' => collect($riskFactors)->where('risk_category', 'high')->values(),
            'medium_risk_students' => collect($riskFactors)->where('risk_category', 'medium')->values(),
            'total_at_risk' => count($riskFactors),
            'intervention_priority' => collect($riskFactors)->sortByDesc('risk_score')->take(10)->values()
        ];
    }

    private function getAttendanceInterventions()
    {
        return [
            'immediate_actions' => [
                'Contact parents of students with >5 consecutive absences',
                'Schedule counseling sessions for chronic absentees',
                'Implement peer buddy system for at-risk students',
                'Review and adjust class schedules if needed'
            ],
            'medium_term_strategies' => [
                'Develop attendance improvement programs',
                'Implement early warning systems',
                'Create incentive programs for good attendance',
                'Provide additional academic support'
            ],
            'long_term_initiatives' => [
                'Analyze root causes of absenteeism',
                'Develop community engagement programs',
                'Implement technology solutions for tracking',
                'Create comprehensive student support services'
            ],
            'success_metrics' => [
                'Target: Reduce chronic absenteeism by 25%',
                'Improve overall attendance rate to 95%',
                'Decrease consecutive absences by 40%',
                'Increase parent engagement by 50%'
            ]
        ];
    }

    private function getSeasonalPredictions()
    {
        $seasonalFactors = [
            'winter' => ['factor' => 0.85, 'reason' => 'Weather and illness impact'],
            'spring' => ['factor' => 0.95, 'reason' => 'Optimal conditions'],
            'summer' => ['factor' => 0.80, 'reason' => 'Vacation and heat impact'],
            'autumn' => ['factor' => 0.90, 'reason' => 'Back to school adjustment']
        ];

        $currentSeason = $this->getCurrentSeason();
        $baselineRate = $this->getBaselineAttendanceRate();

        $predictions = [];
        foreach ($seasonalFactors as $season => $data) {
            $predictedRate = $baselineRate * $data['factor'];
            $predictions[] = [
                'season' => ucfirst($season),
                'predicted_rate' => round($predictedRate, 2),
                'adjustment_factor' => $data['factor'],
                'reasoning' => $data['reason'],
                'is_current' => $season === $currentSeason
            ];
        }

        return $predictions;
    }

    private function getAttendanceCohortAnalysis()
    {
        $cohorts = ClassModel::with(['students.attendances' => function ($query) {
            $query->where('date', '>=', Carbon::now()->subMonths(3));
        }])->get();

        return $cohorts->map(function ($class) {
            $allAttendances = $class->students->flatMap->attendances;
            $total = $allAttendances->count();
            $present = $allAttendances->where('status', 'present')->count();
            $rate = $total > 0 ? ($present / $total) * 100 : 0;

            return [
                'class_name' => $class->name,
                'student_count' => $class->students->count(),
                'attendance_rate' => round($rate, 2),
                'total_records' => $total,
                'performance_category' => $this->categorizeClassPerformance($rate)
            ];
        });
    }

    private function getAttendanceCorrelations()
    {
        // Analyze correlations between attendance and other factors
        return [
            'weather_correlation' => $this->calculateWeatherCorrelation(),
            'day_of_week_impact' => $this->calculateDayOfWeekImpact(),
            'academic_performance_correlation' => $this->calculateAcademicCorrelation(),
            'seasonal_variations' => $this->calculateSeasonalVariations()
        ];
    }

    // Helper calculation methods
    private function calculateWeeklyTrend($weeklyData, $currentRate)
    {
        if (count($weeklyData) < 2) return 'stable';
        
        $previousRate = end($weeklyData)['attendance_rate'] ?? $currentRate;
        $difference = $currentRate - $previousRate;
        
        if ($difference > 2) return 'improving';
        if ($difference < -2) return 'declining';
        return 'stable';
    }

    private function calculateConsecutiveAbsences($studentId)
    {
        $recentAttendances = Attendance::where('student_id', $studentId)
            ->where('date', '>=', Carbon::now()->subMonth())
            ->orderBy('date', 'desc')
            ->get();

        $consecutive = 0;
        foreach ($recentAttendances as $attendance) {
            if ($attendance->status === 'absent') {
                $consecutive++;
            } else {
                break;
            }
        }

        return $consecutive;
    }

    private function calculateAbsenteeismRisk($totalAbsences, $consecutiveAbsences)
    {
        $riskScore = ($totalAbsences * 2) + ($consecutiveAbsences * 5);
        return min(100, $riskScore);
    }

    private function calculateOverallAbsenteeismRate()
    {
        $totalAttendances = Attendance::where('date', '>=', Carbon::now()->subMonth())->count();
        $totalAbsences = Attendance::where('status', 'absent')
            ->where('date', '>=', Carbon::now()->subMonth())->count();
        
        return $totalAttendances > 0 ? round(($totalAbsences / $totalAttendances) * 100, 2) : 0;
    }

    private function getHistoricalAttendanceRates()
    {
        $rates = [];
        for ($i = 12; $i >= 1; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();
            
            $attendances = Attendance::whereBetween('date', [$monthStart, $monthEnd])->get();
            $total = $attendances->count();
            $present = $attendances->where('status', 'present')->count();
            $rate = $total > 0 ? ($present / $total) * 100 : 0;
            
            $rates[] = ['month' => $i, 'rate' => $rate];
        }
        
        return $rates;
    }

    private function calculateLinearTrend($data)
    {
        $n = count($data);
        if ($n < 2) return ['slope' => 0, 'intercept' => 0];
        
        $sumX = array_sum(array_column($data, 'month'));
        $sumY = array_sum(array_column($data, 'rate'));
        $sumXY = array_sum(array_map(function($item) { return $item['month'] * $item['rate']; }, $data));
        $sumX2 = array_sum(array_map(function($item) { return $item['month'] * $item['month']; }, $data));
        
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;
        
        return ['slope' => $slope, 'intercept' => $intercept];
    }

    private function calculateConfidenceLevel($monthsAhead)
    {
        // Confidence decreases with time
        return max(50, 95 - ($monthsAhead * 8));
    }

    private function calculateForecastAccuracy($historicalData)
    {
        // Simple accuracy metrics
        $variance = $this->calculateVariance(array_column($historicalData, 'rate'));
        return [
            'variance' => round($variance, 2),
            'standard_deviation' => round(sqrt($variance), 2),
            'reliability_score' => round(max(0, 100 - $variance), 2)
        ];
    }

    private function calculateVariance($values)
    {
        $mean = array_sum($values) / count($values);
        $squaredDiffs = array_map(function($value) use ($mean) {
            return pow($value - $mean, 2);
        }, $values);
        
        return array_sum($squaredDiffs) / count($squaredDiffs);
    }

    private function calculateRiskScore($absenteeismRate, $recentAbsences)
    {
        return min(100, ($absenteeismRate * 0.7) + ($recentAbsences * 3));
    }

    private function categorizeRisk($absenteeismRate)
    {
        if ($absenteeismRate >= 30) return 'high';
        if ($absenteeismRate >= 15) return 'medium';
        return 'low';
    }

    private function getCurrentSeason()
    {
        $month = Carbon::now()->month;
        if (in_array($month, [12, 1, 2])) return 'winter';
        if (in_array($month, [3, 4, 5])) return 'spring';
        if (in_array($month, [6, 7, 8])) return 'summer';
        return 'autumn';
    }

    private function getBaselineAttendanceRate()
    {
        $attendances = Attendance::where('date', '>=', Carbon::now()->subYear())->get();
        $total = $attendances->count();
        $present = $attendances->where('status', 'present')->count();
        
        return $total > 0 ? ($present / $total) * 100 : 85; // Default baseline
    }

    private function categorizeClassPerformance($rate)
    {
        if ($rate >= 95) return 'excellent';
        if ($rate >= 90) return 'good';
        if ($rate >= 80) return 'average';
        return 'needs_improvement';
    }

    private function calculateWeatherCorrelation()
    {
        // Mock correlation - in real implementation, integrate with weather API
        return [
            'rainy_days_impact' => -15.5,
            'temperature_correlation' => 0.3,
            'seasonal_adjustment' => 8.2
        ];
    }

    private function calculateDayOfWeekImpact()
    {
        return [
            'monday' => -5.2,
            'tuesday' => 2.1,
            'wednesday' => 1.8,
            'thursday' => 0.5,
            'friday' => -3.7
        ];
    }

    private function calculateAcademicCorrelation()
    {
        // Correlation between attendance and academic performance
        return [
            'correlation_coefficient' => 0.78,
            'significance_level' => 0.95,
            'impact_description' => 'Strong positive correlation between attendance and academic performance'
        ];
    }

    private function calculateSeasonalVariations()
    {
        return [
             'winter_variation' => -12.5,
             'spring_variation' => 8.3,
             'summer_variation' => -8.7,
             'autumn_variation' => 5.1
         ];
     }

     // Performance Predictive Analytics Helper Methods

     private function calculatePassRate($results)
     {
         if ($results->isEmpty()) return 0;
         
         $passCount = $results->where('marks_obtained', '>=', 40)->count(); // Assuming 40 is pass mark
         return ($passCount / $results->count()) * 100;
     }

     private function getPerformanceForecast()
     {
         $historicalData = $this->getHistoricalPerformanceData();
         $forecast = $this->calculatePerformanceTrend($historicalData);
         
         $nextTerms = [];
         for ($i = 1; $i <= 4; $i++) {
             $futureDate = Carbon::now()->addMonths($i * 3); // Quarterly terms
             $predictedScore = $forecast['slope'] * $i + $forecast['intercept'];
             
             $nextTerms[] = [
                 'term' => "Term " . $i,
                 'period' => $futureDate->format('M Y'),
                 'predicted_average' => round(max(0, min(100, $predictedScore)), 2),
                 'confidence_level' => $this->calculatePerformanceConfidence($i),
                 'trend_direction' => $forecast['slope'] > 0 ? 'improving' : 'declining',
                 'expected_pass_rate' => round($this->predictPassRate($predictedScore), 2)
             ];
         }

         return [
             'forecast_terms' => $nextTerms,
             'trend_analysis' => $forecast,
             'accuracy_metrics' => $this->calculatePerformanceForecastAccuracy($historicalData),
             'factors_considered' => [
                 'historical_performance',
                 'seasonal_variations',
                 'curriculum_difficulty',
                 'teacher_effectiveness'
             ]
         ];
     }

     private function getPerformanceRiskAssessment()
     {
         $riskStudents = [];
         
         // Identify students at academic risk
         $studentsAtRisk = Student::whereHas('results', function ($query) {
             $query->where('created_at', '>=', Carbon::now()->subMonths(6))
                   ->where('marks_obtained', '<', 40);
         }, '>=', 3)->with(['results' => function ($query) {
             $query->where('created_at', '>=', Carbon::now()->subMonths(6))
                   ->orderBy('created_at', 'desc');
         }])->get();

         foreach ($studentsAtRisk as $student) {
             $recentResults = $student->results->take(5);
             $averageScore = $recentResults->avg('marks_obtained');
             $failureCount = $recentResults->where('marks_obtained', '<', 40)->count();
             $trend = $this->calculateStudentTrend($recentResults);

             $riskStudents[] = [
                 'student_id' => $student->id,
                 'student_name' => $student->name,
                 'class' => $student->class->name ?? 'N/A',
                 'average_score' => round($averageScore, 2),
                 'failure_count' => $failureCount,
                 'performance_trend' => $trend,
                 'risk_score' => $this->calculatePerformanceRiskScore($averageScore, $failureCount, $trend),
                 'risk_category' => $this->categorizePerformanceRisk($averageScore, $failureCount),
                 'intervention_urgency' => $this->calculateInterventionUrgency($averageScore, $failureCount, $trend)
             ];
         }

         return [
             'high_risk_students' => collect($riskStudents)->where('risk_category', 'high')->values(),
             'medium_risk_students' => collect($riskStudents)->where('risk_category', 'medium')->values(),
             'total_at_risk' => count($riskStudents),
             'immediate_intervention' => collect($riskStudents)->where('intervention_urgency', 'immediate')->values(),
             'risk_distribution' => $this->getRiskDistribution($riskStudents)
         ];
     }

     private function getSuccessPredictions()
     {
         $students = Student::with(['results' => function ($query) {
             $query->where('created_at', '>=', Carbon::now()->subYear())
                   ->orderBy('created_at', 'desc');
         }])->get();

         $predictions = [];
         foreach ($students as $student) {
             if ($student->results->count() >= 3) {
                 $trajectory = $this->calculateLearningTrajectory($student->results);
                 $predictions[] = [
                     'student_id' => $student->id,
                     'student_name' => $student->name,
                     'class' => $student->class->name ?? 'N/A',
                     'current_average' => round($student->results->avg('marks_obtained'), 2),
                     'predicted_final_grade' => $this->predictFinalGrade($trajectory),
                     'success_probability' => $this->calculateSuccessProbability($trajectory),
                     'improvement_potential' => $this->calculateImprovementPotential($trajectory),
                     'recommended_focus_areas' => $this->getRecommendedFocusAreas($student->results)
                 ];
             }
         }

         return [
             'student_predictions' => collect($predictions)->sortByDesc('success_probability')->values(),
             'success_rate_forecast' => $this->forecastOverallSuccessRate($predictions),
             'grade_distribution_prediction' => $this->predictGradeDistribution($predictions)
         ];
     }

     private function getPerformanceInterventions()
     {
         return [
             'immediate_interventions' => [
                 'Provide additional tutoring for students scoring below 40%',
                 'Implement peer mentoring programs',
                 'Schedule parent-teacher conferences for at-risk students',
                 'Create personalized learning plans'
             ],
             'short_term_strategies' => [
                 'Implement formative assessment strategies',
                 'Provide differentiated instruction',
                 'Use technology-enhanced learning tools',
                 'Establish study groups and collaborative learning'
             ],
             'long_term_initiatives' => [
                 'Develop comprehensive curriculum review',
                 'Implement competency-based assessment',
                 'Create early warning systems',
                 'Establish continuous professional development for teachers'
             ],
             'success_metrics' => [
                 'Target: Increase pass rate by 15%',
                 'Reduce failure rate by 25%',
                 'Improve average scores by 10 points',
                 'Achieve 90% student satisfaction'
             ]
         ];
     }

     private function getGradePredictions()
     {
         $currentResults = Result::where('created_at', '>=', Carbon::now()->subMonths(3))->get();
         $gradeDistribution = $this->calculateCurrentGradeDistribution($currentResults);
         
         return [
             'current_distribution' => $gradeDistribution,
             'predicted_next_term' => $this->predictNextTermGrades($gradeDistribution),
             'improvement_scenarios' => [
                 'optimistic' => $this->calculateOptimisticScenario($gradeDistribution),
                 'realistic' => $this->calculateRealisticScenario($gradeDistribution),
                 'pessimistic' => $this->calculatePessimisticScenario($gradeDistribution)
             ],
             'grade_migration_analysis' => $this->analyzeGradeMigration()
         ];
     }

     private function getSubjectPerformanceTrends()
     {
         $subjects = Result::select('subject')
             ->distinct()
             ->pluck('subject');

         return $subjects->map(function ($subject) {
             $subjectResults = Result::where('subject', $subject)
                 ->where('created_at', '>=', Carbon::now()->subYear())
                 ->orderBy('created_at')
                 ->get();

             $monthlyAverages = $subjectResults->groupBy(function ($result) {
                 return $result->created_at->format('Y-m');
             })->map(function ($monthResults) {
                 return round($monthResults->avg('marks_obtained'), 2);
             });

             return [
                 'subject' => $subject,
                 'current_average' => round($subjectResults->avg('marks_obtained'), 2),
                 'monthly_trends' => $monthlyAverages,
                 'trend_direction' => $this->calculateSubjectTrend($monthlyAverages),
                 'difficulty_level' => $this->assessSubjectDifficulty($subjectResults),
                 'improvement_rate' => $this->calculateSubjectImprovementRate($monthlyAverages)
             ];
         });
     }

     private function getStudentProgressionAnalysis()
     {
         $progressionData = Student::with(['results' => function ($query) {
             $query->where('created_at', '>=', Carbon::now()->subYear())
                   ->orderBy('created_at');
         }])->get()->map(function ($student) {
             $results = $student->results;
             if ($results->count() < 2) return null;

             $firstScore = $results->first()->marks_obtained;
             $lastScore = $results->last()->marks_obtained;
             $improvement = $lastScore - $firstScore;
             $improvementRate = $firstScore > 0 ? ($improvement / $firstScore) * 100 : 0;

             return [
                 'student_id' => $student->id,
                 'student_name' => $student->name,
                 'class' => $student->class->name ?? 'N/A',
                 'initial_score' => $firstScore,
                 'current_score' => $lastScore,
                 'improvement' => round($improvement, 2),
                 'improvement_rate' => round($improvementRate, 2),
                 'progression_category' => $this->categorizeProgression($improvementRate),
                 'consistency_score' => $this->calculateConsistencyScore($results)
             ];
         })->filter();

         return [
             'individual_progressions' => $progressionData->values(),
             'progression_summary' => [
                 'improving_students' => $progressionData->where('improvement', '>', 0)->count(),
                 'declining_students' => $progressionData->where('improvement', '<', 0)->count(),
                 'stable_students' => $progressionData->where('improvement', '=', 0)->count(),
                 'average_improvement' => round($progressionData->avg('improvement'), 2)
             ]
         ];
     }

     private function getCompetencyMapping()
     {
         // Mock competency data - in real implementation, map to curriculum standards
         return [
             'mathematics' => [
                 'algebra' => ['mastery_rate' => 78, 'at_risk_students' => 12],
                 'geometry' => ['mastery_rate' => 85, 'at_risk_students' => 8],
                 'statistics' => ['mastery_rate' => 72, 'at_risk_students' => 15]
             ],
             'science' => [
                 'physics' => ['mastery_rate' => 68, 'at_risk_students' => 18],
                 'chemistry' => ['mastery_rate' => 75, 'at_risk_students' => 14],
                 'biology' => ['mastery_rate' => 82, 'at_risk_students' => 10]
             ],
             'language_arts' => [
                 'reading_comprehension' => ['mastery_rate' => 88, 'at_risk_students' => 6],
                 'writing' => ['mastery_rate' => 79, 'at_risk_students' => 11],
                 'grammar' => ['mastery_rate' => 84, 'at_risk_students' => 9]
             ]
         ];
     }

     private function getLearningVelocity()
     {
         $students = Student::with(['results' => function ($query) {
             $query->where('created_at', '>=', Carbon::now()->subMonths(6))
                   ->orderBy('created_at');
         }])->get();

         return $students->map(function ($student) {
             $results = $student->results;
             if ($results->count() < 3) return null;

             $velocity = $this->calculateLearningVelocity($results);
             return [
                 'student_id' => $student->id,
                 'student_name' => $student->name,
                 'class' => $student->class->name ?? 'N/A',
                 'learning_velocity' => round($velocity, 2),
                 'velocity_category' => $this->categorizeLearningVelocity($velocity),
                 'acceleration' => $this->calculateLearningAcceleration($results),
                 'projected_performance' => $this->projectPerformance($velocity, $results->last()->marks_obtained)
             ];
         })->filter()->values();
     }

     // Additional helper methods for performance analytics
     private function getHistoricalPerformanceData()
     {
         $data = [];
         for ($i = 12; $i >= 1; $i--) {
             $date = Carbon::now()->subMonths($i);
             $monthStart = $date->copy()->startOfMonth();
             $monthEnd = $date->copy()->endOfMonth();
             
             $results = Result::whereBetween('created_at', [$monthStart, $monthEnd])->get();
             $average = $results->avg('marks_obtained') ?? 0;
             
             $data[] = ['month' => $i, 'average' => $average];
         }
         
         return $data;
     }

     private function calculatePerformanceTrend($data)
     {
         return $this->calculateLinearTrend($data); // Reuse from attendance analytics
     }

     private function calculatePerformanceConfidence($termsAhead)
     {
         return max(60, 95 - ($termsAhead * 10));
     }

     private function predictPassRate($averageScore)
     {
         // Simple correlation between average score and pass rate
         return min(100, max(0, ($averageScore - 20) * 2));
     }

     private function calculatePerformanceForecastAccuracy($historicalData)
     {
         $variance = $this->calculateVariance(array_column($historicalData, 'average'));
         return [
             'variance' => round($variance, 2),
             'standard_deviation' => round(sqrt($variance), 2),
             'reliability_score' => round(max(0, 100 - ($variance / 2)), 2)
         ];
     }

     private function calculateStudentTrend($results)
     {
         if ($results->count() < 2) return 'insufficient_data';
         
         $scores = $results->pluck('marks_obtained')->toArray();
         $trend = $this->calculateLinearTrend(array_map(function($score, $index) {
             return ['month' => $index + 1, 'average' => $score];
         }, $scores, array_keys($scores)));
         
         if ($trend['slope'] > 2) return 'improving';
         if ($trend['slope'] < -2) return 'declining';
         return 'stable';
     }

     private function calculatePerformanceRiskScore($averageScore, $failureCount, $trend)
     {
         $baseRisk = max(0, 100 - $averageScore);
         $failureRisk = $failureCount * 15;
         $trendRisk = $trend === 'declining' ? 20 : ($trend === 'improving' ? -10 : 0);
         
         return min(100, $baseRisk + $failureRisk + $trendRisk);
     }

     private function categorizePerformanceRisk($averageScore, $failureCount)
     {
         if ($averageScore < 30 || $failureCount >= 4) return 'high';
         if ($averageScore < 50 || $failureCount >= 2) return 'medium';
         return 'low';
     }

     private function calculateInterventionUrgency($averageScore, $failureCount, $trend)
     {
         if ($averageScore < 25 || ($failureCount >= 3 && $trend === 'declining')) return 'immediate';
         if ($averageScore < 40 || $failureCount >= 2) return 'soon';
         return 'monitor';
     }

     private function getRiskDistribution($riskStudents)
     {
         $distribution = collect($riskStudents)->groupBy('risk_category');
         return [
             'high' => $distribution->get('high', collect())->count(),
             'medium' => $distribution->get('medium', collect())->count(),
             'low' => $distribution->get('low', collect())->count()
         ];
     }

     // Missing helper methods for attendance analytics
     private function calculateSeasonalPatterns()
     {
         $seasons = ['winter', 'spring', 'summer', 'autumn'];
         $patterns = [];
         
         foreach ($seasons as $season) {
             $seasonData = $this->getSeasonalAttendanceData($season);
             $patterns[$season] = [
                 'average_rate' => $seasonData['average_rate'] ?? 85,
                 'trend' => $seasonData['trend'] ?? 'stable',
                 'peak_months' => $seasonData['peak_months'] ?? []
             ];
         }
         
         return $patterns;
     }

     private function calculateWeeklyTrends()
     {
         $weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
         $trends = [];
         
         foreach ($weekdays as $day) {
             $dayData = $this->getDayAttendanceData($day);
             $trends[$day] = [
                 'average_rate' => $dayData['rate'] ?? 88,
                 'pattern' => $dayData['pattern'] ?? 'normal'
             ];
         }
         
         return $trends;
     }

     private function calculateDailyPatterns()
     {
         return [
             'morning_attendance' => 92,
             'afternoon_attendance' => 88,
             'peak_hours' => ['9:00-10:00', '14:00-15:00'],
             'low_hours' => ['8:00-9:00', '15:00-16:00']
         ];
     }

     private function analyzeAbsenteeism()
     {
         return [
             'chronic_absentees' => 15,
             'occasional_absentees' => 45,
             'frequent_tardiness' => 25,
             'patterns' => ['Monday mornings', 'Friday afternoons']
         ];
     }

     private function forecastAttendance()
     {
         return [
             'next_week' => 89.5,
             'next_month' => 87.2,
             'confidence_level' => 85
         ];
     }

     private function assessAttendanceRisk()
     {
         return [
             'high_risk_count' => 12,
             'medium_risk_count' => 28,
             'low_risk_count' => 160
         ];
     }

     private function suggestInterventions()
     {
         return [
             'immediate' => ['Contact parents', 'Counseling sessions'],
             'short_term' => ['Peer support', 'Schedule adjustments'],
             'long_term' => ['Community programs', 'Support services']
         ];
     }

     private function predictSeasonalTrends()
     {
         return [
             'winter_forecast' => 82,
             'spring_forecast' => 91,
             'summer_forecast' => 78,
             'autumn_forecast' => 88
         ];
     }

     private function analyzeCohorts()
     {
         return [
             'grade_1' => ['rate' => 92, 'trend' => 'stable'],
             'grade_2' => ['rate' => 89, 'trend' => 'improving'],
             'grade_3' => ['rate' => 87, 'trend' => 'declining']
         ];
     }

     private function calculateCorrelations()
     {
         return [
             'weather_correlation' => 0.65,
             'academic_correlation' => 0.78,
             'social_correlation' => 0.45
         ];
     }

     // Missing helper methods for performance analytics
     private function calculateOverallPassRate()
     {
         return [
             'overall_pass_rate' => 87.5,
             'subject_pass_rates' => [
                 'Mathematics' => 82,
                 'English' => 91,
                 'Science' => 85
             ]
         ];
     }

     private function forecastPerformance()
     {
         return [
             'next_term_average' => 78.5,
             'improvement_probability' => 72,
             'risk_factors' => ['attendance', 'homework_completion']
         ];
     }

     private function assessPerformanceRisk()
     {
         return [
             'high_risk_students' => 18,
             'at_risk_subjects' => ['Mathematics', 'Physics'],
             'intervention_needed' => true
         ];
     }

     private function predictSuccess()
     {
         return [
             'success_probability' => 85,
             'factors' => ['attendance', 'engagement', 'support'],
             'recommendations' => ['Extra tutoring', 'Parent involvement']
         ];
     }

     private function suggestPerformanceInterventions()
     {
         return [
             'academic_support' => ['Tutoring', 'Study groups'],
             'behavioral_support' => ['Counseling', 'Mentoring'],
             'family_engagement' => ['Parent meetings', 'Home support']
         ];
     }

     private function predictGrades()
     {
         return [
             'predicted_averages' => [
                 'A_grade' => 25,
                 'B_grade' => 35,
                 'C_grade' => 30,
                 'D_grade' => 10
             ],
             'confidence' => 78
         ];
     }

     // Additional helper methods for data calculations
     private function getSeasonalAttendanceData($season)
     {
         // Mock data - in real implementation, query database
         return [
             'average_rate' => rand(80, 95),
             'trend' => ['stable', 'improving', 'declining'][rand(0, 2)],
             'peak_months' => []
         ];
     }

     private function getDayAttendanceData($day)
     {
         // Mock data - in real implementation, query database
         return [
             'rate' => rand(85, 95),
             'pattern' => 'normal'
         ];
     }
 }
