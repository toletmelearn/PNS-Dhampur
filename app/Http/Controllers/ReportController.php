<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\ClassModel;
use App\Models\Attendance;
use App\Models\Fee;
use App\Models\Exam;

class ReportController extends Controller
{
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
        return view('reports.index');
    }

    /**
     * Generate student report.
     */
    public function studentReport(Request $request)
    {
        $students = Student::with(['class', 'attendances', 'fees', 'examResults'])
            ->when($request->class_id, function ($query, $classId) {
                return $query->where('class_id', $classId);
            })
            ->get();

        return view('reports.students', compact('students'));
    }

    /**
     * Generate attendance report.
     */
    public function attendanceReport(Request $request)
    {
        $attendances = Attendance::with(['student', 'class'])
            ->when($request->date_from, function ($query, $dateFrom) {
                return $query->whereDate('date', '>=', $dateFrom);
            })
            ->when($request->date_to, function ($query, $dateTo) {
                return $query->whereDate('date', '<=', $dateTo);
            })
            ->get();

        return view('reports.attendance', compact('attendances'));
    }

    /**
     * Generate fee report.
     */
    public function feeReport(Request $request)
    {
        $fees = Fee::with(['student', 'class'])
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->get();

        return view('reports.fees', compact('fees'));
    }

    /**
     * Generate exam report.
     */
    public function examReport(Request $request)
    {
        $exams = Exam::with(['class', 'results'])
            ->when($request->class_id, function ($query, $classId) {
                return $query->where('class_id', $classId);
            })
            ->get();

        return view('reports.exams', compact('exams'));
    }
}