<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Syllabus;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class StudentPortalController extends Controller
{
    /**
     * Student portal dashboard
     */
    public function dashboard()
    {
        $student = Student::with(['class', 'user'])->where('user_id', Auth::id())->first();
        
        if (!$student) {
            abort(403, 'Student profile not found.');
        }

        // Get student's class assignments
        $upcomingAssignments = Assignment::published()
            ->forClass($student->class_id)
            ->upcoming()
            ->with(['subject', 'teacher'])
            ->orderBy('due_datetime', 'asc')
            ->limit(5)
            ->get();

        $overdueAssignments = Assignment::published()
            ->forClass($student->class_id)
            ->overdue()
            ->with(['subject', 'teacher'])
            ->orderBy('due_datetime', 'desc')
            ->limit(5)
            ->get();

        // Get recent submissions
        $recentSubmissions = AssignmentSubmission::where('student_id', $student->id)
            ->with(['assignment.subject', 'assignment.teacher'])
            ->orderBy('submitted_at', 'desc')
            ->limit(5)
            ->get();

        // Get statistics
        $stats = [
            'total_assignments' => Assignment::published()->forClass($student->class_id)->count(),
            'submitted' => AssignmentSubmission::where('student_id', $student->id)->submitted()->count(),
            'pending' => Assignment::published()->forClass($student->class_id)->count() - 
                        AssignmentSubmission::where('student_id', $student->id)->submitted()->count(),
            'graded' => AssignmentSubmission::where('student_id', $student->id)->graded()->count(),
            'average_grade' => AssignmentSubmission::where('student_id', $student->id)
                ->graded()
                ->avg('final_marks') ?? 0,
        ];

        // Get available syllabi
        $syllabi = Syllabus::active()
            ->where(function ($query) use ($student) {
                $query->forClass($student->class_id)
                      ->orWhere('visibility', 'public');
            })
            ->with(['subject', 'teacher'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('student.dashboard', compact(
            'student', 'upcomingAssignments', 'overdueAssignments', 
            'recentSubmissions', 'stats', 'syllabi'
        ));
    }

    /**
     * List all assignments for the student
     */
    public function assignments(Request $request)
    {
        $student = Student::with(['class', 'user'])->where('user_id', Auth::id())->first();
        
        if (!$student) {
            abort(403, 'Student profile not found.');
        }

        $query = Assignment::published()
            ->forClass($student->class_id)
            ->with(['subject', 'teacher', 'syllabus']);

        // Apply filters
        if ($request->filled('subject_id')) {
            $query->forSubject($request->subject_id);
        }

        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        if ($request->filled('status')) {
            if ($request->status === 'upcoming') {
                $query->upcoming();
            } elseif ($request->status === 'overdue') {
                $query->overdue();
            } elseif ($request->status === 'submitted') {
                $submittedAssignmentIds = AssignmentSubmission::where('student_id', $student->id)
                    ->submitted()
                    ->pluck('assignment_id');
                $query->whereIn('id', $submittedAssignmentIds);
            } elseif ($request->status === 'pending') {
                $submittedAssignmentIds = AssignmentSubmission::where('student_id', $student->id)
                    ->submitted()
                    ->pluck('assignment_id');
                $query->whereNotIn('id', $submittedAssignmentIds);
            }
        }

        if ($request->filled('difficulty')) {
            $query->byDifficulty($request->difficulty);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('tags', 'like', "%{$search}%");
            });
        }

        $assignments = $query->orderBy('due_datetime', 'asc')->paginate(12);

        // Add submission status to each assignment
        $assignmentIds = $assignments->pluck('id');
        $submissions = AssignmentSubmission::where('student_id', $student->id)
            ->whereIn('assignment_id', $assignmentIds)
            ->get()
            ->keyBy('assignment_id');

        foreach ($assignments as $assignment) {
            $assignment->student_submission = $submissions->get($assignment->id);
        }

        // Get filter options
        $subjects = Subject::whereHas('assignments', function ($query) use ($student) {
            $query->published()->forClass($student->class_id);
        })->orderBy('name')->get();

        return view('student.assignments', compact('assignments', 'subjects', 'student'));
    }

    /**
     * Show assignment details
     */
    public function showAssignment($id)
    {
        $student = Student::with(['class', 'user'])->where('user_id', Auth::id())->first();
        
        if (!$student) {
            abort(403, 'Student profile not found.');
        }

        $assignment = Assignment::published()
            ->forClass($student->class_id)
            ->with(['subject', 'teacher', 'syllabus'])
            ->findOrFail($id);

        // Get student's submission if exists
        $submission = AssignmentSubmission::where('assignment_id', $id)
            ->where('student_id', $student->id)
            ->first();

        return view('student.assignment-detail', compact('assignment', 'submission', 'student'));
    }

    /**
     * Submit assignment
     */
    public function submitAssignment(Request $request, $id)
    {
        $student = Student::with(['class', 'user'])->where('user_id', Auth::id())->first();
        
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student profile not found.'
            ], 403);
        }

        $assignment = Assignment::published()
            ->forClass($student->class_id)
            ->findOrFail($id);

        // Check if assignment is still accepting submissions
        if ($assignment->isOverdue() && !$assignment->allow_late_submission) {
            return response()->json([
                'success' => false,
                'message' => 'This assignment is overdue and no longer accepting submissions.'
            ], 400);
        }

        // Check if student has already submitted
        $existingSubmission = AssignmentSubmission::where('assignment_id', $id)
            ->where('student_id', $student->id)
            ->first();

        if ($existingSubmission && $existingSubmission->status !== AssignmentSubmission::STATUS_DRAFT) {
            return response()->json([
                'success' => false,
                'message' => 'You have already submitted this assignment.'
            ], 400);
        }

        // Validate based on submission type
        $rules = [];
        $maxAssignmentSize = config('fileupload.max_file_sizes.assignment');
        $assignmentMimes = config('fileupload.allowed_file_types.assignment.mimes');
        
        if ($assignment->submission_type === 'text' || $assignment->submission_type === 'both') {
            $rules['submission_text'] = 'required|string';
        }
        if ($assignment->submission_type === 'file' || $assignment->submission_type === 'both') {
            $rules['attachment'] = "required|file|mimes:{$assignmentMimes}|max:{$maxAssignmentSize}"; // Updated with config values
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = [
                'assignment_id' => $id,
                'student_id' => $student->id,
                'submission_text' => $request->submission_text,
                'submitted_at' => now(),
                'status' => AssignmentSubmission::STATUS_SUBMITTED,
                'is_late' => $assignment->isOverdue(),
            ];

            // Handle file upload
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $fileName = Str::uuid() . '.' . $extension;
                $filePath = $file->storeAs('submissions', $fileName, 'public');

                $data['attachment_path'] = $filePath;
                $data['attachment_size'] = $file->getSize();
                $data['original_attachment_name'] = $originalName;
            }

            if ($existingSubmission) {
                // Update existing draft
                $existingSubmission->update($data);
                $submission = $existingSubmission;
            } else {
                // Create new submission
                $submission = AssignmentSubmission::create($data);
            }

            return response()->json([
                'success' => true,
                'message' => 'Assignment submitted successfully',
                'submission' => $submission
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error submitting assignment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save assignment as draft
     */
    public function saveDraft(Request $request, $id)
    {
        $student = Student::with(['class', 'user'])->where('user_id', Auth::id())->first();
        
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student profile not found.'
            ], 403);
        }

        $assignment = Assignment::published()
            ->forClass($student->class_id)
            ->findOrFail($id);

        // Validate file upload if present
        if ($request->hasFile('attachment')) {
            $validator = Validator::make($request->all(), [
                'attachment' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
        }

        try {
            $data = [
                'assignment_id' => $id,
                'student_id' => $student->id,
                'submission_text' => $request->submission_text,
                'status' => AssignmentSubmission::STATUS_DRAFT,
            ];

            // Handle file upload
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $fileName = Str::uuid() . '.' . $extension;
                $filePath = $file->storeAs('submissions', $fileName, 'public');

                $data['attachment_path'] = $filePath;
                $data['attachment_size'] = $file->getSize();
                $data['original_attachment_name'] = $originalName;
            }

            $submission = AssignmentSubmission::updateOrCreate(
                ['assignment_id' => $id, 'student_id' => $student->id],
                $data
            );

            return response()->json([
                'success' => true,
                'message' => 'Draft saved successfully',
                'submission' => $submission
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error saving draft: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download submission attachment
     */
    public function downloadSubmissionAttachment($submissionId)
    {
        $student = Student::with(['class', 'user'])->where('user_id', Auth::id())->first();
        
        if (!$student) {
            abort(403, 'Student profile not found.');
        }

        $submission = AssignmentSubmission::where('student_id', $student->id)
            ->findOrFail($submissionId);

        if (!$submission->attachment_path || !Storage::disk('public')->exists($submission->attachment_path)) {
            abort(404, 'Attachment not found.');
        }

        return Storage::disk('public')->download(
            $submission->attachment_path,
            $submission->original_attachment_name ?? 'submission.' . pathinfo($submission->attachment_path, PATHINFO_EXTENSION)
        );
    }

    /**
     * List available syllabi
     */
    public function syllabi(Request $request)
    {
        $student = Student::with(['class', 'user'])->where('user_id', Auth::id())->first();
        
        if (!$student) {
            abort(403, 'Student profile not found.');
        }

        $query = Syllabus::active()
            ->where(function ($q) use ($student) {
                $q->forClass($student->class_id)
                  ->orWhere('visibility', 'public');
            })
            ->with(['subject', 'teacher', 'class']);

        // Apply filters
        if ($request->filled('subject_id')) {
            $query->forSubject($request->subject_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('tags', 'like', "%{$search}%");
            });
        }

        $syllabi = $query->orderBy('created_at', 'desc')->paginate(12);

        // Get filter options
        $subjects = Subject::whereHas('syllabi', function ($query) use ($student) {
            $query->active()->where(function ($q) use ($student) {
                $q->forClass($student->class_id)
                  ->orWhere('visibility', 'public');
            });
        })->orderBy('name')->get();

        return view('student.syllabi', compact('syllabi', 'subjects', 'student'));
    }

    /**
     * View syllabus details
     */
    public function showSyllabus($id)
    {
        $student = Student::with(['class', 'user'])->where('user_id', Auth::id())->first();
        
        if (!$student) {
            abort(403, 'Student profile not found.');
        }

        $syllabus = Syllabus::active()
            ->where(function ($query) use ($student) {
                $query->forClass($student->class_id)
                      ->orWhere('visibility', 'public');
            })
            ->with(['subject', 'teacher', 'class'])
            ->findOrFail($id);

        // Check if student can view this syllabus
        if (!$syllabus->canBeViewedBy(Auth::user())) {
            abort(403, 'You do not have permission to view this syllabus.');
        }

        // Increment view count
        $syllabus->incrementViewCount();

        return view('student.syllabus-detail', compact('syllabus', 'student'));
    }

    /**
     * Download syllabus file
     */
    public function downloadSyllabus($id)
    {
        $student = Student::with(['class', 'user'])->where('user_id', Auth::id())->first();
        
        if (!$student) {
            abort(403, 'Student profile not found.');
        }

        $syllabus = Syllabus::active()
            ->where(function ($query) use ($student) {
                $query->forClass($student->class_id)
                      ->orWhere('visibility', 'public');
            })
            ->findOrFail($id);

        // Check if student can view this syllabus
        if (!$syllabus->canBeViewedBy(Auth::user())) {
            abort(403, 'You do not have permission to download this syllabus.');
        }

        if (!$syllabus->file_path || !Storage::disk('public')->exists($syllabus->file_path)) {
            abort(404, 'Syllabus file not found.');
        }

        // Increment download count
        $syllabus->incrementDownloadCount();

        return Storage::disk('public')->download(
            $syllabus->file_path,
            $syllabus->original_filename ?? 'syllabus.' . pathinfo($syllabus->file_path, PATHINFO_EXTENSION)
        );
    }

    /**
     * Get upcoming deadlines
     */
    public function getUpcomingDeadlines()
    {
        $student = Student::with(['class', 'user'])->where('user_id', Auth::id())->first();
        
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student profile not found.'
            ], 403);
        }

        $upcomingAssignments = Assignment::published()
            ->forClass($student->class_id)
            ->where('due_datetime', '>', now())
            ->where('due_datetime', '<=', now()->addDays(7))
            ->with(['subject', 'teacher'])
            ->orderBy('due_datetime', 'asc')
            ->get();

        // Check which assignments are not yet submitted
        $submittedAssignmentIds = AssignmentSubmission::where('student_id', $student->id)
            ->submitted()
            ->pluck('assignment_id');

        $pendingAssignments = $upcomingAssignments->whereNotIn('id', $submittedAssignmentIds);

        return response()->json([
            'success' => true,
            'upcoming_deadlines' => $pendingAssignments->values()
        ]);
    }

    /**
     * Get student progress summary
     */
    public function getProgressSummary()
    {
        $student = Student::with(['class', 'user'])->where('user_id', Auth::id())->first();
        
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student profile not found.'
            ], 403);
        }

        $totalAssignments = Assignment::published()->forClass($student->class_id)->count();
        $submittedCount = AssignmentSubmission::where('student_id', $student->id)->submitted()->count();
        $gradedCount = AssignmentSubmission::where('student_id', $student->id)->graded()->count();
        $averageGrade = AssignmentSubmission::where('student_id', $student->id)
            ->graded()
            ->avg('final_marks') ?? 0;

        // Subject-wise performance
        $subjectPerformance = AssignmentSubmission::where('student_id', $student->id)
            ->graded()
            ->join('assignments', 'assignment_submissions.assignment_id', '=', 'assignments.id')
            ->join('subjects', 'assignments.subject_id', '=', 'subjects.id')
            ->selectRaw('subjects.name as subject_name, AVG(assignment_submissions.final_marks) as average_grade, COUNT(*) as total_graded')
            ->groupBy('subjects.id', 'subjects.name')
            ->get();

        return response()->json([
            'success' => true,
            'progress' => [
                'total_assignments' => $totalAssignments,
                'submitted_count' => $submittedCount,
                'pending_count' => $totalAssignments - $submittedCount,
                'graded_count' => $gradedCount,
                'average_grade' => round($averageGrade, 2),
                'completion_rate' => $totalAssignments > 0 ? round(($submittedCount / $totalAssignments) * 100, 2) : 0,
                'subject_performance' => $subjectPerformance
            ]
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markNotificationRead($notificationId)
    {
        // This would integrate with Laravel's notification system
        // For now, return success
        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read'
        ]);
    }
}