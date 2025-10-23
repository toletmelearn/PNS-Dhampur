<?php

namespace App\Modules\ParentPortal\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ParentStudentRelationship;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Result;
use App\Models\Fee;

class ParentPortalController extends Controller
{
    /**
     * Parent dashboard showing children and quick progress metrics
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Get children linked to the parent
        $children = ParentStudentRelationship::with(['student' => function ($q) {
            $q->select('id', 'name', 'class_id', 'roll_number');
        }])
            ->where('parent_id', $user->id)
            ->get()
            ->pluck('student');

        // Aggregate progress metrics
        $progress = [];
        foreach ($children as $student) {
            $attendanceCount = Attendance::where('student_id', $student->id)
                ->whereDate('date', '>=', now()->startOfMonth())
                ->count();
            $absentCount = Attendance::where('student_id', $student->id)
                ->whereDate('date', '>=', now()->startOfMonth())
                ->where('status', 'absent')
                ->count();

            $latestResults = Result::where('student_id', $student->id)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get(['subject', 'marks_obtained', 'total_marks', 'grade']);

            $dueFees = Fee::where('student_id', $student->id)
                ->where('status', 'pending')
                ->sum('amount');

            $progress[$student->id] = [
                'attendance_this_month' => $attendanceCount,
                'absent_this_month' => $absentCount,
                'latest_results' => $latestResults,
                'due_fees' => $dueFees,
            ];
        }

        return view('parent_portal.dashboard', compact('children', 'progress'));
    }

    /**
     * Detailed child progress view
     */
    public function childProgress(int $id)
    {
        $user = Auth::user();

        // Ensure the parent has access to this student
        $allowed = ParentStudentRelationship::where('parent_id', $user->id)
            ->where('student_id', $id)
            ->exists();
        abort_unless($allowed, 403);

        $student = Student::with('class')->findOrFail($id);
        $attendance = Attendance::where('student_id', $id)
            ->orderBy('date', 'desc')
            ->take(30)
            ->get(['date', 'status']);
        $results = Result::where('student_id', $id)
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get(['subject', 'marks_obtained', 'total_marks', 'grade']);
        $fees = Fee::where('student_id', $id)
            ->orderBy('due_date', 'desc')
            ->get(['amount', 'status', 'due_date']);

        return response()->json([
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'class' => optional($student->class)->name,
            ],
            'attendance' => $attendance,
            'results' => $results,
            'fees' => $fees,
        ]);
    }
}
