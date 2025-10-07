<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\ClassModel;
use App\Models\Attendance;
use App\Models\Fee;
use App\Models\Exam;
use App\Http\Traits\DateRangeValidationTrait;

class ReportController extends Controller
{
    use DateRangeValidationTrait;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the reports dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        // Cache the dashboard data for 5 minutes to reduce database load
        $dashboardData = Cache::remember('reports_dashboard', 300, function () {
            return [
                'total_students' => Student::count(),
                'total_classes' => ClassModel::count(),
                'total_teachers' => Teacher::count(),
                'total_fees_collected' => Fee::whereNotNull('paid_date')->sum('paid_amount'),
                'recent_attendances' => Attendance::with(['student:id,name', 'class:id,name'])
                    ->latest()
                    ->limit(10)
                    ->get(),
                'recent_fee_payments' => Fee::with(['student:id,name'])
                    ->whereNotNull('paid_date')
                    ->latest('paid_date')
                    ->limit(10)
                    ->get(),
            ];
        });

        return view('reports.index', $dashboardData);
    }

    /**
     * Generate student report.
     */
    public function studentReport(Request $request)
    {
        // Create cache key based on request parameters
        $cacheKey = 'student_report_' . md5(serialize($request->all()));
        
        $data = Cache::remember($cacheKey, 600, function () use ($request) {
            $query = Student::with(['class', 'attendances', 'fees', 'examResults'])
                ->when($request->class_id, function ($query, $classId) {
                    return $query->where('class_id', $classId);
                });

            // Use pagination for memory efficiency
            return $query->paginate(50);
        });

        return view('reports.students', ['students' => $data]);
    }

    /**
     * Generate attendance report.
     */
    public function attendanceReport(Request $request)
    {
        $request->validate([
            ...$this->getFilterDateRangeValidationRules(),
            'class_id' => 'nullable|exists:classes,id'
        ], $this->getDateRangeValidationMessages());

        // Create cache key based on request parameters
        $cacheKey = 'attendance_report_' . md5(serialize($request->all()));
        
        $data = Cache::remember($cacheKey, 600, function () use ($request) {
            $query = Attendance::with(['student', 'class'])
                ->when($request->date_from, function ($query, $dateFrom) {
                    return $query->whereDate('date', '>=', $dateFrom);
                })
                ->when($request->date_to, function ($query, $dateTo) {
                    return $query->whereDate('date', '<=', $dateTo);
                });

            // Use pagination for memory efficiency
            return $query->paginate(100);
        });

        return view('reports.attendance', ['attendances' => $data]);
    }

    /**
     * Generate fee report.
     */
    public function feeReport(Request $request)
    {
        // Create cache key based on request parameters
        $cacheKey = 'fee_report_' . md5(serialize($request->all()));
        
        $data = Cache::remember($cacheKey, 600, function () use ($request) {
            $query = Fee::with(['student', 'class'])
                ->when($request->status, function ($query, $status) {
                    return $query->where('status', $status);
                });

            // Use pagination for memory efficiency
            return $query->paginate(50);
        });

        return view('reports.fees', ['fees' => $data]);
    }

    /**
     * Generate exam report.
     */
    public function examReport(Request $request)
    {
        $query = Exam::with(['class', 'results'])
            ->when($request->class_id, function ($query, $classId) {
                return $query->where('class_id', $classId);
            });

        // Use pagination for memory efficiency
        $exams = $query->paginate(25);

        return view('reports.exams', compact('exams'));
    }

    /**
     * Generate financial report with memory-efficient processing
     */
    public function generateFinancialReport(Request $request)
    {
        $data = [
            'total_revenue' => 0,
            'pending_fees' => 0,
            'monthly_collection' => 0,
            'fee_breakdown' => [],
            'student_summary' => []
        ];

        // Process fees in chunks to avoid memory issues
        Fee::with(['student.user', 'student.classModel'])
            ->chunk(100, function ($fees) use (&$data) {
                foreach ($fees as $fee) {
                    $data['total_revenue'] += $fee->paid_amount;
                    if ($fee->status !== 'paid') {
                        $data['pending_fees'] += ($fee->amount - $fee->paid_amount);
                    }
                    
                    // Monthly collection for current month
                    if ($fee->paid_date && $fee->paid_date->isCurrentMonth()) {
                        $data['monthly_collection'] += $fee->paid_amount;
                    }
                }
            });

        // Process students in chunks for summary data
        Student::with(['fees', 'classModel'])
            ->chunk(50, function ($students) use (&$data) {
                foreach ($students as $student) {
                    $totalFees = $student->fees->sum('amount');
                    $paidFees = $student->fees->sum('paid_amount');
                    
                    $data['student_summary'][] = [
                        'id' => $student->id,
                        'name' => $student->name,
                        'class' => $student->classModel->name ?? 'N/A',
                        'total_fees' => $totalFees,
                        'paid_fees' => $paidFees,
                        'pending_fees' => $totalFees - $paidFees,
                        'payment_status' => $totalFees == $paidFees ? 'Complete' : 'Pending'
                    ];
                }
            });

        return response()->json($data);
    }
}