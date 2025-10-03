<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Syllabus;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\ClassModel;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LearningApiController extends Controller
{
    /**
     * Get classes data for dropdowns
     */
    public function getClasses()
    {
        $classes = ClassModel::select('id', 'name', 'section')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $classes
        ]);
    }

    /**
     * Get subjects data for dropdowns
     */
    public function getSubjects(Request $request)
    {
        $query = Subject::select('id', 'name', 'code');

        if ($request->filled('class_id')) {
            $query->whereHas('classes', function ($q) use ($request) {
                $q->where('class_models.id', $request->class_id);
            });
        }

        $subjects = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $subjects
        ]);
    }

    /**
     * Get students for a specific class
     */
    public function getClassStudents($classId)
    {
        $students = Student::where('class_id', $classId)
            ->select('id', 'name', 'admission_no', 'roll_no')
            ->orderBy('roll_no')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $students
        ]);
    }

    /**
     * Search assignments
     */
    public function searchAssignments(Request $request)
    {
        $query = Assignment::with(['subject', 'class', 'teacher']);

        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('instructions', 'like', "%{$search}%");
            });
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->filled('status')) {
            switch ($request->status) {
                case 'published':
                    $query->published();
                    break;
                case 'draft':
                    $query->draft();
                    break;
                case 'overdue':
                    $query->overdue();
                    break;
                case 'upcoming':
                    $query->upcoming();
                    break;
            }
        }

        $assignments = $query->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $assignments
        ]);
    }

    /**
     * Filter assignments with advanced options
     */
    public function filterAssignments(Request $request)
    {
        $query = Assignment::with(['subject', 'class', 'teacher']);

        // Apply filters
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }

        if ($request->filled('status')) {
            switch ($request->status) {
                case 'published':
                    $query->published();
                    break;
                case 'draft':
                    $query->draft();
                    break;
                case 'overdue':
                    $query->overdue();
                    break;
                case 'upcoming':
                    $query->upcoming();
                    break;
            }
        }

        if ($request->filled('date_from')) {
            $query->where('due_datetime', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('due_datetime', '<=', $request->date_to);
        }

        $assignments = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $assignments
        ]);
    }

    /**
     * Get assignment submissions count
     */
    public function getSubmissionsCount($assignmentId)
    {
        $assignment = Assignment::findOrFail($assignmentId);
        
        $totalStudents = Student::where('class_id', $assignment->class_id)->count();
        $submittedCount = AssignmentSubmission::where('assignment_id', $assignmentId)
            ->where('status', 'submitted')
            ->count();
        $gradedCount = AssignmentSubmission::where('assignment_id', $assignmentId)
            ->where('status', 'graded')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_students' => $totalStudents,
                'submitted_count' => $submittedCount,
                'pending_count' => $totalStudents - $submittedCount,
                'graded_count' => $gradedCount,
                'ungraded_count' => $submittedCount - $gradedCount,
                'submission_rate' => $totalStudents > 0 ? round(($submittedCount / $totalStudents) * 100, 2) : 0,
                'grading_rate' => $submittedCount > 0 ? round(($gradedCount / $submittedCount) * 100, 2) : 0
            ]
        ]);
    }

    /**
     * Search syllabi
     */
    public function searchSyllabi(Request $request)
    {
        $query = Syllabus::with(['subject', 'class', 'teacher']);

        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('tags', 'like', "%{$search}%");
            });
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->filled('visibility')) {
            $query->where('visibility', $request->visibility);
        }

        $syllabi = $query->active()
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $syllabi
        ]);
    }

    /**
     * Filter syllabi with advanced options
     */
    public function filterSyllabi(Request $request)
    {
        $query = Syllabus::with(['subject', 'class', 'teacher']);

        // Apply filters
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        if ($request->filled('academic_year')) {
            $query->where('academic_year', $request->academic_year);
        }

        if ($request->filled('semester')) {
            $query->where('semester', $request->semester);
        }

        if ($request->filled('file_type')) {
            $query->where('file_type', $request->file_type);
        }

        if ($request->filled('visibility')) {
            $query->where('visibility', $request->visibility);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $syllabi = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $syllabi
        ]);
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats()
    {
        $user = Auth::user();
        $stats = [];

        if ($user->hasAnyRole(['admin', 'principal'])) {
            // Admin/Principal dashboard stats
            $stats = [
                'total_assignments' => Assignment::count(),
                'published_assignments' => Assignment::published()->count(),
                'draft_assignments' => Assignment::draft()->count(),
                'overdue_assignments' => Assignment::overdue()->count(),
                'total_syllabi' => Syllabus::count(),
                'active_syllabi' => Syllabus::active()->count(),
                'total_submissions' => AssignmentSubmission::count(),
                'graded_submissions' => AssignmentSubmission::graded()->count(),
                'pending_grading' => AssignmentSubmission::submitted()->whereNull('final_marks')->count(),
                'total_students' => Student::count(),
                'active_students' => Student::where('status', 'active')->count(),
                'total_teachers' => Teacher::count(),
                'active_teachers' => Teacher::where('status', 'active')->count()
            ];
        } elseif ($user->hasRole('teacher')) {
            // Teacher dashboard stats
            $teacher = Teacher::where('user_id', $user->id)->first();
            if ($teacher) {
                $stats = [
                    'my_assignments' => Assignment::where('teacher_id', $teacher->id)->count(),
                    'published_assignments' => Assignment::where('teacher_id', $teacher->id)->published()->count(),
                    'draft_assignments' => Assignment::where('teacher_id', $teacher->id)->draft()->count(),
                    'overdue_assignments' => Assignment::where('teacher_id', $teacher->id)->overdue()->count(),
                    'my_syllabi' => Syllabus::where('teacher_id', $teacher->id)->count(),
                    'active_syllabi' => Syllabus::where('teacher_id', $teacher->id)->active()->count(),
                    'total_submissions' => AssignmentSubmission::whereHas('assignment', function ($q) use ($teacher) {
                        $q->where('teacher_id', $teacher->id);
                    })->count(),
                    'pending_grading' => AssignmentSubmission::whereHas('assignment', function ($q) use ($teacher) {
                        $q->where('teacher_id', $teacher->id);
                    })->submitted()->whereNull('final_marks')->count()
                ];
            }
        } elseif ($user->hasRole('student')) {
            // Student dashboard stats
            $student = Student::where('user_id', $user->id)->first();
            if ($student) {
                $totalAssignments = Assignment::published()->forClass($student->class_id)->count();
                $submittedCount = AssignmentSubmission::where('student_id', $student->id)->submitted()->count();
                $gradedCount = AssignmentSubmission::where('student_id', $student->id)->graded()->count();

                $stats = [
                    'total_assignments' => $totalAssignments,
                    'submitted_assignments' => $submittedCount,
                    'pending_assignments' => $totalAssignments - $submittedCount,
                    'graded_assignments' => $gradedCount,
                    'overdue_assignments' => Assignment::published()->forClass($student->class_id)->overdue()->count(),
                    'average_grade' => AssignmentSubmission::where('student_id', $student->id)
                        ->graded()
                        ->avg('final_marks') ?? 0,
                    'available_syllabi' => Syllabus::active()
                        ->where(function ($query) use ($student) {
                            $query->forClass($student->class_id)
                                  ->orWhere('visibility', 'public');
                        })->count()
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get assignment-specific statistics
     */
    public function getAssignmentStats($assignmentId)
    {
        $assignment = Assignment::with(['subject', 'class', 'teacher'])->findOrFail($assignmentId);
        
        $totalStudents = Student::where('class_id', $assignment->class_id)->count();
        $submissions = AssignmentSubmission::where('assignment_id', $assignmentId)->get();
        
        $submittedCount = $submissions->where('status', 'submitted')->count();
        $gradedCount = $submissions->where('status', 'graded')->count();
        $averageGrade = $submissions->where('status', 'graded')->avg('final_marks') ?? 0;
        
        // Grade distribution
        $gradeDistribution = $submissions->where('status', 'graded')
            ->groupBy(function ($submission) {
                $percentage = ($submission->final_marks / $submission->assignment->total_marks) * 100;
                if ($percentage >= 90) return 'A+';
                if ($percentage >= 80) return 'A';
                if ($percentage >= 70) return 'B';
                if ($percentage >= 60) return 'C';
                if ($percentage >= 50) return 'D';
                return 'F';
            })
            ->map(function ($group) {
                return $group->count();
            });

        return response()->json([
            'success' => true,
            'data' => [
                'assignment' => $assignment,
                'total_students' => $totalStudents,
                'submitted_count' => $submittedCount,
                'pending_count' => $totalStudents - $submittedCount,
                'graded_count' => $gradedCount,
                'ungraded_count' => $submittedCount - $gradedCount,
                'average_grade' => round($averageGrade, 2),
                'submission_rate' => $totalStudents > 0 ? round(($submittedCount / $totalStudents) * 100, 2) : 0,
                'grading_rate' => $submittedCount > 0 ? round(($gradedCount / $submittedCount) * 100, 2) : 0,
                'grade_distribution' => $gradeDistribution,
                'is_overdue' => $assignment->due_datetime < now(),
                'days_until_due' => $assignment->due_datetime > now() ? 
                    now()->diffInDays($assignment->due_datetime) : 0
            ]
        ]);
    }

    /**
     * Get student progress data
     */
    public function getStudentProgress($studentId)
    {
        $student = Student::with(['class', 'user'])->findOrFail($studentId);
        
        $assignments = Assignment::published()->forClass($student->class_id)->get();
        $submissions = AssignmentSubmission::where('student_id', $studentId)->get();
        
        $totalAssignments = $assignments->count();
        $submittedCount = $submissions->where('status', 'submitted')->count();
        $gradedCount = $submissions->where('status', 'graded')->count();
        $averageGrade = $submissions->where('status', 'graded')->avg('final_marks') ?? 0;
        
        // Subject-wise performance
        $subjectPerformance = $submissions->where('status', 'graded')
            ->groupBy('assignment.subject_id')
            ->map(function ($group) {
                return [
                    'subject_name' => $group->first()->assignment->subject->name ?? 'Unknown',
                    'total_assignments' => $group->count(),
                    'average_grade' => round($group->avg('final_marks'), 2),
                    'total_marks' => $group->sum('final_marks'),
                    'possible_marks' => $group->sum('assignment.total_marks')
                ];
            });

        // Monthly progress
        $monthlyProgress = $submissions->where('status', 'graded')
            ->groupBy(function ($submission) {
                return Carbon::parse($submission->submitted_at)->format('Y-m');
            })
            ->map(function ($group, $month) {
                return [
                    'month' => $month,
                    'submissions' => $group->count(),
                    'average_grade' => round($group->avg('final_marks'), 2)
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'student' => $student,
                'total_assignments' => $totalAssignments,
                'submitted_count' => $submittedCount,
                'pending_count' => $totalAssignments - $submittedCount,
                'graded_count' => $gradedCount,
                'average_grade' => round($averageGrade, 2),
                'completion_rate' => $totalAssignments > 0 ? round(($submittedCount / $totalAssignments) * 100, 2) : 0,
                'subject_performance' => $subjectPerformance,
                'monthly_progress' => $monthlyProgress,
                'recent_submissions' => $submissions->sortByDesc('submitted_at')->take(5)->values()
            ]
        ]);
    }
}