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
     * Get financial reports data
     */
    public function financialReports(Request $request)
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $data = [
            'total_revenue' => Fee::where('status', 'paid')->sum('paid_amount'),
            'pending_fees' => Fee::where('status', '!=', 'paid')->sum('amount') - Fee::where('status', '!=', 'paid')->sum('paid_amount'),
            'monthly_collection' => Fee::whereMonth('paid_date', $currentMonth)->whereYear('paid_date', $currentYear)->sum('paid_amount'),
            'total_expenses' => Salary::sum('amount'),
            'fee_collection_trends' => $this->getFeeCollectionTrends(),
            'payment_methods' => $this->getPaymentMethodStats(),
            'class_wise_collection' => $this->getClassWiseCollection(),
        ];

        return response()->json($data);
    }

    /**
     * Get attendance reports data
     */
    public function attendanceReports(Request $request)
    {
        // Mock data - replace with actual attendance model when available
        $data = [
            'overall_attendance' => 85.5,
            'present_today' => 245,
            'absent_today' => 35,
            'total_students' => Student::count(),
            'class_wise_attendance' => $this->getClassWiseAttendance(),
            'monthly_trends' => $this->getAttendanceTrends(),
            'low_attendance_students' => $this->getLowAttendanceStudents(),
        ];

        return response()->json($data);
    }

    /**
     * Get performance reports data
     */
    public function performanceReports(Request $request)
    {
        $data = [
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
}
