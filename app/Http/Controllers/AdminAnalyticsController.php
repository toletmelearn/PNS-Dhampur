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
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminAnalyticsController extends Controller
{
    /**
     * Analytics dashboard
     */
    public function dashboard()
    {
        // Overall statistics
        $stats = [
            'total_assignments' => Assignment::count(),
            'published_assignments' => Assignment::published()->count(),
            'total_submissions' => AssignmentSubmission::count(),
            'graded_submissions' => AssignmentSubmission::graded()->count(),
            'total_syllabi' => Syllabus::count(),
            'active_syllabi' => Syllabus::active()->count(),
            'total_students' => Student::count(),
            'total_teachers' => Teacher::count(),
        ];

        // Recent activity
        $recentAssignments = Assignment::with(['subject', 'class', 'teacher'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $recentSubmissions = AssignmentSubmission::with(['assignment.subject', 'student.user'])
            ->orderBy('submitted_at', 'desc')
            ->limit(10)
            ->get();

        // Performance metrics
        $performanceMetrics = $this->getPerformanceMetrics();

        return view('admin.analytics.dashboard', compact(
            'stats', 'recentAssignments', 'recentSubmissions', 'performanceMetrics'
        ));
    }

    /**
     * Assignment analytics
     */
    public function assignmentAnalytics(Request $request)
    {
        // Assignment distribution by subject
        $assignmentsBySubject = Assignment::join('subjects', 'assignments.subject_id', '=', 'subjects.id')
            ->selectRaw('subjects.name as subject_name, COUNT(*) as total_assignments')
            ->groupBy('subjects.id', 'subjects.name')
            ->orderBy('total_assignments', 'desc')
            ->get();

        // Assignment distribution by class
        $assignmentsByClass = Assignment::join('class_models', 'assignments.class_id', '=', 'class_models.id')
            ->selectRaw('class_models.name as class_name, COUNT(*) as total_assignments')
            ->groupBy('class_models.id', 'class_models.name')
            ->orderBy('total_assignments', 'desc')
            ->get();

        // Assignment distribution by type
        $assignmentsByType = Assignment::selectRaw('type, COUNT(*) as total_assignments')
            ->groupBy('type')
            ->orderBy('total_assignments', 'desc')
            ->get();

        // Assignment distribution by difficulty
        $assignmentsByDifficulty = Assignment::selectRaw('difficulty, COUNT(*) as total_assignments')
            ->groupBy('difficulty')
            ->orderBy('total_assignments', 'desc')
            ->get();

        // Monthly assignment creation trend
        $monthlyTrend = Assignment::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as total_assignments')
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        // Top performing assignments (by submission rate)
        $topAssignments = Assignment::with(['subject', 'class'])
            ->withCount('submissions')
            ->having('submissions_count', '>', 0)
            ->orderBy('submissions_count', 'desc')
            ->limit(10)
            ->get();

        return view('admin.analytics.assignments', compact(
            'assignmentsBySubject', 'assignmentsByClass', 'assignmentsByType',
            'assignmentsByDifficulty', 'monthlyTrend', 'topAssignments'
        ));
    }

    /**
     * Student performance analytics
     */
    public function studentPerformance(Request $request)
    {
        // Overall student performance
        $studentPerformance = Student::with(['user', 'class'])
            ->leftJoin('assignment_submissions', 'students.id', '=', 'assignment_submissions.student_id')
            ->selectRaw('
                students.id,
                students.user_id,
                students.class_id,
                COUNT(assignment_submissions.id) as total_submissions,
                COUNT(CASE WHEN assignment_submissions.status = "graded" THEN 1 END) as graded_submissions,
                AVG(CASE WHEN assignment_submissions.status = "graded" THEN assignment_submissions.final_marks END) as average_grade,
                COUNT(CASE WHEN assignment_submissions.is_late = 1 THEN 1 END) as late_submissions
            ')
            ->groupBy('students.id', 'students.user_id', 'students.class_id')
            ->orderBy('average_grade', 'desc')
            ->paginate(20);

        // Class-wise performance
        $classPerformance = ClassModel::with('students')
            ->leftJoin('students', 'class_models.id', '=', 'students.class_id')
            ->leftJoin('assignment_submissions', 'students.id', '=', 'assignment_submissions.student_id')
            ->selectRaw('
                class_models.id,
                class_models.name as class_name,
                COUNT(DISTINCT students.id) as total_students,
                COUNT(assignment_submissions.id) as total_submissions,
                AVG(CASE WHEN assignment_submissions.status = "graded" THEN assignment_submissions.final_marks END) as average_grade,
                COUNT(CASE WHEN assignment_submissions.is_late = 1 THEN 1 END) as late_submissions
            ')
            ->groupBy('class_models.id', 'class_models.name')
            ->orderBy('average_grade', 'desc')
            ->get();

        // Subject-wise performance
        $subjectPerformance = Subject::leftJoin('assignments', 'subjects.id', '=', 'assignments.subject_id')
            ->leftJoin('assignment_submissions', 'assignments.id', '=', 'assignment_submissions.assignment_id')
            ->selectRaw('
                subjects.id,
                subjects.name as subject_name,
                COUNT(DISTINCT assignments.id) as total_assignments,
                COUNT(assignment_submissions.id) as total_submissions,
                AVG(CASE WHEN assignment_submissions.status = "graded" THEN assignment_submissions.final_marks END) as average_grade,
                COUNT(CASE WHEN assignment_submissions.is_late = 1 THEN 1 END) as late_submissions
            ')
            ->groupBy('subjects.id', 'subjects.name')
            ->orderBy('average_grade', 'desc')
            ->get();

        return view('admin.analytics.student-performance', compact(
            'studentPerformance', 'classPerformance', 'subjectPerformance'
        ));
    }

    /**
     * Teacher performance analytics
     */
    public function teacherPerformance(Request $request)
    {
        // Teacher assignment statistics
        $teacherStats = Teacher::with(['user'])
            ->leftJoin('assignments', 'teachers.id', '=', 'assignments.teacher_id')
            ->leftJoin('assignment_submissions', 'assignments.id', '=', 'assignment_submissions.assignment_id')
            ->selectRaw('
                teachers.id,
                teachers.user_id,
                teachers.name,
                COUNT(DISTINCT assignments.id) as total_assignments,
                COUNT(assignment_submissions.id) as total_submissions,
                COUNT(CASE WHEN assignment_submissions.status = "graded" THEN 1 END) as graded_submissions,
                AVG(CASE WHEN assignment_submissions.status = "graded" THEN assignment_submissions.final_marks END) as average_grade,
                COUNT(CASE WHEN assignments.is_published = 1 THEN 1 END) as published_assignments
            ')
            ->groupBy('teachers.id', 'teachers.user_id', 'teachers.name')
            ->orderBy('total_assignments', 'desc')
            ->paginate(20);

        // Grading efficiency (time to grade)
        $gradingEfficiency = Teacher::with(['user'])
            ->leftJoin('assignments', 'teachers.id', '=', 'assignments.teacher_id')
            ->leftJoin('assignment_submissions', 'assignments.id', '=', 'assignment_submissions.assignment_id')
            ->selectRaw('
                teachers.id,
                teachers.name,
                COUNT(CASE WHEN assignment_submissions.status = "graded" THEN 1 END) as graded_count,
                AVG(TIMESTAMPDIFF(HOUR, assignment_submissions.submitted_at, assignment_submissions.graded_at)) as avg_grading_time_hours
            ')
            ->whereNotNull('assignment_submissions.graded_at')
            ->groupBy('teachers.id', 'teachers.name')
            ->having('graded_count', '>', 0)
            ->orderBy('avg_grading_time_hours', 'asc')
            ->get();

        return view('admin.analytics.teacher-performance', compact(
            'teacherStats', 'gradingEfficiency'
        ));
    }

    /**
     * Syllabus analytics
     */
    public function syllabusAnalytics(Request $request)
    {
        // Syllabus distribution by subject
        $syllabusBySubject = Syllabus::join('subjects', 'syllabus.subject_id', '=', 'subjects.id')
            ->selectRaw('subjects.name as subject_name, COUNT(*) as total_syllabi')
            ->groupBy('subjects.id', 'subjects.name')
            ->orderBy('total_syllabi', 'desc')
            ->get();

        // Syllabus distribution by class
        $syllabusByClass = Syllabus::join('class_models', 'syllabus.class_id', '=', 'class_models.id')
            ->selectRaw('class_models.name as class_name, COUNT(*) as total_syllabi')
            ->groupBy('class_models.id', 'class_models.name')
            ->orderBy('total_syllabi', 'desc')
            ->get();

        // Most viewed syllabi
        $mostViewed = Syllabus::with(['subject', 'class', 'teacher'])
            ->orderBy('view_count', 'desc')
            ->limit(10)
            ->get();

        // Most downloaded syllabi
        $mostDownloaded = Syllabus::with(['subject', 'class', 'teacher'])
            ->orderBy('download_count', 'desc')
            ->limit(10)
            ->get();

        // File type distribution
        $fileTypeDistribution = Syllabus::selectRaw('file_type, COUNT(*) as total_files')
            ->groupBy('file_type')
            ->orderBy('total_files', 'desc')
            ->get();

        return view('admin.analytics.syllabus', compact(
            'syllabusBySubject', 'syllabusByClass', 'mostViewed',
            'mostDownloaded', 'fileTypeDistribution'
        ));
    }

    /**
     * Assignment calendar
     */
    public function assignmentCalendar(Request $request)
    {
        $startDate = $request->get('start', now()->startOfMonth());
        $endDate = $request->get('end', now()->endOfMonth());

        $assignments = Assignment::with(['subject', 'class', 'teacher'])
            ->whereBetween('due_datetime', [$startDate, $endDate])
            ->orderBy('due_datetime', 'asc')
            ->get();

        // Group assignments by date
        $calendarData = $assignments->groupBy(function ($assignment) {
            return $assignment->due_datetime->format('Y-m-d');
        });

        return view('admin.analytics.calendar', compact('calendarData', 'startDate', 'endDate'));
    }

    /**
     * Export analytics data
     */
    public function exportData(Request $request)
    {
        $type = $request->get('type', 'assignments');
        $format = $request->get('format', 'csv');

        switch ($type) {
            case 'assignments':
                return $this->exportAssignments($format);
            case 'submissions':
                return $this->exportSubmissions($format);
            case 'student_performance':
                return $this->exportStudentPerformance($format);
            case 'teacher_performance':
                return $this->exportTeacherPerformance($format);
            default:
                abort(400, 'Invalid export type');
        }
    }

    /**
     * Get completion analytics
     */
    public function getCompletionAnalytics(Request $request)
    {
        $period = $request->get('period', 'month'); // week, month, quarter, year

        $dateFormat = match ($period) {
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            'quarter' => '%Y-%q',
            'year' => '%Y',
            default => '%Y-%m'
        };

        $completionData = AssignmentSubmission::selectRaw("
            DATE_FORMAT(submitted_at, '{$dateFormat}') as period,
            COUNT(*) as total_submissions,
            COUNT(CASE WHEN status = 'graded' THEN 1 END) as graded_submissions,
            AVG(CASE WHEN status = 'graded' THEN final_marks END) as average_grade
        ")
            ->where('submitted_at', '>=', now()->subMonths(12))
            ->groupBy('period')
            ->orderBy('period', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $completionData
        ]);
    }

    /**
     * Get real-time statistics
     */
    public function getRealTimeStats()
    {
        $stats = [
            'today_submissions' => AssignmentSubmission::whereDate('submitted_at', today())->count(),
            'pending_grading' => AssignmentSubmission::submitted()->whereNull('graded_at')->count(),
            'overdue_assignments' => Assignment::published()->overdue()->count(),
            'upcoming_deadlines' => Assignment::published()
                ->where('due_datetime', '>', now())
                ->where('due_datetime', '<=', now()->addDays(3))
                ->count(),
            'active_students' => Student::whereHas('submissions', function ($query) {
                $query->where('submitted_at', '>=', now()->subDays(7));
            })->count(),
            'recent_syllabi' => Syllabus::where('created_at', '>=', now()->subDays(7))->count(),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics()
    {
        return [
            'submission_rate' => $this->getSubmissionRate(),
            'grading_efficiency' => $this->getGradingEfficiency(),
            'student_engagement' => $this->getStudentEngagement(),
            'content_utilization' => $this->getContentUtilization(),
        ];
    }

    /**
     * Calculate submission rate
     */
    private function getSubmissionRate()
    {
        $totalAssignments = Assignment::published()->count();
        $totalSubmissions = AssignmentSubmission::submitted()->count();
        
        return $totalAssignments > 0 ? round(($totalSubmissions / $totalAssignments) * 100, 2) : 0;
    }

    /**
     * Calculate grading efficiency
     */
    private function getGradingEfficiency()
    {
        $totalSubmissions = AssignmentSubmission::submitted()->count();
        $gradedSubmissions = AssignmentSubmission::graded()->count();
        
        return $totalSubmissions > 0 ? round(($gradedSubmissions / $totalSubmissions) * 100, 2) : 0;
    }

    /**
     * Calculate student engagement
     */
    private function getStudentEngagement()
    {
        $totalStudents = Student::count();
        $activeStudents = Student::whereHas('submissions', function ($query) {
            $query->where('submitted_at', '>=', now()->subDays(30));
        })->count();
        
        return $totalStudents > 0 ? round(($activeStudents / $totalStudents) * 100, 2) : 0;
    }

    /**
     * Calculate content utilization
     */
    private function getContentUtilization()
    {
        $totalSyllabi = Syllabus::active()->count();
        $viewedSyllabi = Syllabus::active()->where('view_count', '>', 0)->count();
        
        return $totalSyllabi > 0 ? round(($viewedSyllabi / $totalSyllabi) * 100, 2) : 0;
    }

    /**
     * Export assignments data
     */
    private function exportAssignments($format)
    {
        $assignments = Assignment::with(['subject', 'class', 'teacher'])
            ->get()
            ->map(function ($assignment) {
                return [
                    'ID' => $assignment->id,
                    'Title' => $assignment->title,
                    'Subject' => $assignment->subject->name,
                    'Class' => $assignment->class->name,
                    'Teacher' => $assignment->teacher->name,
                    'Type' => $assignment->type,
                    'Total Marks' => $assignment->total_marks,
                    'Due Date' => $assignment->due_datetime->format('Y-m-d H:i'),
                    'Published' => $assignment->is_published ? 'Yes' : 'No',
                    'Submissions' => $assignment->submissions_count ?? 0,
                    'Created At' => $assignment->created_at->format('Y-m-d H:i'),
                ];
            });

        return $this->generateExport($assignments, 'assignments', $format);
    }

    /**
     * Export submissions data
     */
    private function exportSubmissions($format)
    {
        $submissions = AssignmentSubmission::with(['assignment.subject', 'student.user'])
            ->get()
            ->map(function ($submission) {
                return [
                    'ID' => $submission->id,
                    'Assignment' => $submission->assignment->title,
                    'Subject' => $submission->assignment->subject->name,
                    'Student' => $submission->student->user->name,
                    'Status' => $submission->status,
                    'Marks Obtained' => $submission->marks_obtained,
                    'Final Marks' => $submission->final_marks,
                    'Is Late' => $submission->is_late ? 'Yes' : 'No',
                    'Submitted At' => $submission->submitted_at?->format('Y-m-d H:i'),
                    'Graded At' => $submission->graded_at?->format('Y-m-d H:i'),
                ];
            });

        return $this->generateExport($submissions, 'submissions', $format);
    }

    /**
     * Export student performance data
     */
    private function exportStudentPerformance($format)
    {
        $performance = Student::with(['user', 'class'])
            ->leftJoin('assignment_submissions', 'students.id', '=', 'assignment_submissions.student_id')
            ->selectRaw('
                students.id,
                students.user_id,
                students.class_id,
                COUNT(assignment_submissions.id) as total_submissions,
                COUNT(CASE WHEN assignment_submissions.status = "graded" THEN 1 END) as graded_submissions,
                AVG(CASE WHEN assignment_submissions.status = "graded" THEN assignment_submissions.final_marks END) as average_grade
            ')
            ->groupBy('students.id', 'students.user_id', 'students.class_id')
            ->get()
            ->map(function ($student) {
                return [
                    'Student ID' => $student->id,
                    'Name' => $student->user->name,
                    'Class' => $student->class->name,
                    'Total Submissions' => $student->total_submissions,
                    'Graded Submissions' => $student->graded_submissions,
                    'Average Grade' => round($student->average_grade ?? 0, 2),
                ];
            });

        return $this->generateExport($performance, 'student_performance', $format);
    }

    /**
     * Export teacher performance data
     */
    private function exportTeacherPerformance($format)
    {
        $performance = Teacher::with(['user'])
            ->leftJoin('assignments', 'teachers.id', '=', 'assignments.teacher_id')
            ->leftJoin('assignment_submissions', 'assignments.id', '=', 'assignment_submissions.assignment_id')
            ->selectRaw('
                teachers.id,
                teachers.name,
                COUNT(DISTINCT assignments.id) as total_assignments,
                COUNT(assignment_submissions.id) as total_submissions,
                COUNT(CASE WHEN assignment_submissions.status = "graded" THEN 1 END) as graded_submissions
            ')
            ->groupBy('teachers.id', 'teachers.name')
            ->get()
            ->map(function ($teacher) {
                return [
                    'Teacher ID' => $teacher->id,
                    'Name' => $teacher->name,
                    'Total Assignments' => $teacher->total_assignments,
                    'Total Submissions' => $teacher->total_submissions,
                    'Graded Submissions' => $teacher->graded_submissions,
                    'Grading Rate' => $teacher->total_submissions > 0 ? 
                        round(($teacher->graded_submissions / $teacher->total_submissions) * 100, 2) . '%' : '0%',
                ];
            });

        return $this->generateExport($performance, 'teacher_performance', $format);
    }

    /**
     * Generate export file
     */
    private function generateExport($data, $filename, $format)
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "{$filename}_{$timestamp}";

        if ($format === 'json') {
            return response()->json($data)
                ->header('Content-Disposition', "attachment; filename=\"{$filename}.json\"");
        }

        // Default to CSV
        $csv = fopen('php://temp', 'w+');
        
        if ($data->isNotEmpty()) {
            // Write headers
            fputcsv($csv, array_keys($data->first()));
            
            // Write data
            foreach ($data as $row) {
                fputcsv($csv, array_values($row));
            }
        }

        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        return response($content)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}.csv\"");
    }
}