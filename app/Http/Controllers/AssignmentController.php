<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Subject;
use App\Models\ClassModel;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Syllabus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AssignmentController extends Controller
{
    /**
     * Display the assignment management dashboard
     */
    public function index(Request $request)
    {
        $query = Assignment::with(['subject', 'class', 'teacher', 'syllabus', 'createdBy']);

        // Apply filters
        if ($request->filled('class_id')) {
            $query->forClass($request->class_id);
        }

        if ($request->filled('subject_id')) {
            $query->forSubject($request->subject_id);
        }

        if ($request->filled('teacher_id')) {
            $query->forTeacher($request->teacher_id);
        }

        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        if ($request->filled('status')) {
            if ($request->status === 'published') {
                $query->published();
            } elseif ($request->status === 'draft') {
                $query->draft();
            } elseif ($request->status === 'overdue') {
                $query->overdue();
            } elseif ($request->status === 'upcoming') {
                $query->upcoming();
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
                  ->orWhere('instructions', 'like', "%{$search}%")
                  ->orWhere('tags', 'like', "%{$search}%");
            });
        }

        $assignments = $query->orderBy('created_at', 'desc')->paginate(15);

        // Get filter options
        $classes = ClassModel::orderBy('name')->get();
        $subjects = Subject::orderBy('name')->get();
        $teachers = Teacher::with('user')->orderBy('name')->get();
        $stats = Assignment::getAssignmentStats();

        return view('assignments.index', compact(
            'assignments', 'classes', 'subjects', 'teachers', 'stats'
        ));
    }

    /**
     * Show the form for creating a new assignment
     */
    public function create()
    {
        $classes = ClassModel::orderBy('name')->get();
        $subjects = Subject::orderBy('name')->get();
        $teachers = Teacher::with('user')->orderBy('name')->get();
        $syllabi = Syllabus::active()->orderBy('title')->get();
        $typeOptions = Assignment::getTypeOptions();
        $submissionTypeOptions = Assignment::getSubmissionTypeOptions();
        $visibilityOptions = Assignment::getVisibilityOptions();
        $difficultyOptions = Assignment::getDifficultyOptions();

        return view('assignments.create', compact(
            'classes', 'subjects', 'teachers', 'syllabi', 'typeOptions',
            'submissionTypeOptions', 'visibilityOptions', 'difficultyOptions'
        ));
    }

    /**
     * Store a newly created assignment
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'subject_id' => 'required|exists:subjects,id',
            'class_id' => 'required|exists:class_models,id',
            'teacher_id' => 'required|exists:teachers,id',
            'syllabus_id' => 'nullable|exists:syllabus,id',
            'type' => 'required|in:homework,project,quiz,exam,lab,presentation',
            'total_marks' => 'required|numeric|min:0',
            'due_date' => 'required|date|after:now',
            'due_time' => 'nullable|date_format:H:i',
            'submission_type' => 'required|in:file,text,both',
            'allow_late_submission' => 'boolean',
            'late_penalty_per_day' => 'nullable|numeric|min:0|max:100',
            'max_late_days' => 'nullable|integer|min:0',
            'is_published' => 'boolean',
            'visibility' => 'required|in:public,class_only,private',
            'estimated_duration' => 'nullable|integer|min:1',
            'difficulty' => 'required|in:easy,medium,hard',
            'tags' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,gif,zip,rar|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->only([
                'title', 'description', 'instructions', 'subject_id', 'class_id',
                'teacher_id', 'syllabus_id', 'type', 'total_marks', 'due_date',
                'due_time', 'submission_type', 'allow_late_submission',
                'late_penalty_per_day', 'max_late_days', 'is_published',
                'visibility', 'estimated_duration', 'difficulty'
            ]);

            // Combine due date and time
            if ($request->filled('due_time')) {
                $data['due_datetime'] = Carbon::parse($request->due_date . ' ' . $request->due_time);
            } else {
                $data['due_datetime'] = Carbon::parse($request->due_date . ' 23:59:59');
            }

            // Handle file upload
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $fileName = Str::uuid() . '.' . $extension;
                $filePath = $file->storeAs('assignments', $fileName, 'public');

                $data['attachment_path'] = $filePath;
                $data['attachment_size'] = $file->getSize();
                $data['original_attachment_name'] = $originalName;
            }

            // Process tags
            if ($request->filled('tags')) {
                $data['tags'] = array_map('trim', explode(',', $request->tags));
            }

            $data['created_by'] = Auth::id();

            $assignment = Assignment::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Assignment created successfully',
                'assignment' => $assignment->load(['subject', 'class', 'teacher', 'syllabus'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating assignment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified assignment
     */
    public function show($id)
    {
        $assignment = Assignment::with([
            'subject', 'class', 'teacher', 'syllabus', 'createdBy', 'updatedBy',
            'submissions.student.user'
        ])->findOrFail($id);

        // Check if user can view this assignment
        if (!$assignment->canBeViewedBy(Auth::user())) {
            abort(403, 'You do not have permission to view this assignment.');
        }

        // Get submission statistics
        $submissionStats = [
            'total_students' => $assignment->class->students()->count(),
            'submitted' => $assignment->submissions()->submitted()->count(),
            'pending' => $assignment->submissions()->pending()->count(),
            'graded' => $assignment->submissions()->graded()->count(),
            'late' => $assignment->submissions()->late()->count(),
        ];

        return view('assignments.show', compact('assignment', 'submissionStats'));
    }

    /**
     * Show the form for editing the specified assignment
     */
    public function edit($id)
    {
        $assignment = Assignment::findOrFail($id);

        // Check permissions
        if (Auth::id() !== $assignment->teacher_id && Auth::id() !== $assignment->created_by) {
            abort(403, 'You do not have permission to edit this assignment.');
        }

        $classes = ClassModel::orderBy('name')->get();
        $subjects = Subject::orderBy('name')->get();
        $teachers = Teacher::with('user')->orderBy('name')->get();
        $syllabi = Syllabus::active()->orderBy('title')->get();
        $typeOptions = Assignment::getTypeOptions();
        $submissionTypeOptions = Assignment::getSubmissionTypeOptions();
        $visibilityOptions = Assignment::getVisibilityOptions();
        $difficultyOptions = Assignment::getDifficultyOptions();

        return view('assignments.edit', compact(
            'assignment', 'classes', 'subjects', 'teachers', 'syllabi',
            'typeOptions', 'submissionTypeOptions', 'visibilityOptions', 'difficultyOptions'
        ));
    }

    /**
     * Update the specified assignment
     */
    public function update(Request $request, $id)
    {
        $assignment = Assignment::findOrFail($id);

        // Check permissions
        if (Auth::id() !== $assignment->teacher_id && Auth::id() !== $assignment->created_by) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update this assignment.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'subject_id' => 'required|exists:subjects,id',
            'class_id' => 'required|exists:class_models,id',
            'teacher_id' => 'required|exists:teachers,id',
            'syllabus_id' => 'nullable|exists:syllabus,id',
            'type' => 'required|in:homework,project,quiz,exam,lab,presentation',
            'total_marks' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'due_time' => 'nullable|date_format:H:i',
            'submission_type' => 'required|in:file,text,both',
            'allow_late_submission' => 'boolean',
            'late_penalty_per_day' => 'nullable|numeric|min:0|max:100',
            'max_late_days' => 'nullable|integer|min:0',
            'is_published' => 'boolean',
            'visibility' => 'required|in:public,class_only,private',
            'estimated_duration' => 'nullable|integer|min:1',
            'difficulty' => 'required|in:easy,medium,hard',
            'tags' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,gif,zip,rar|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->only([
                'title', 'description', 'instructions', 'subject_id', 'class_id',
                'teacher_id', 'syllabus_id', 'type', 'total_marks', 'due_date',
                'due_time', 'submission_type', 'allow_late_submission',
                'late_penalty_per_day', 'max_late_days', 'is_published',
                'visibility', 'estimated_duration', 'difficulty'
            ]);

            // Combine due date and time
            if ($request->filled('due_time')) {
                $data['due_datetime'] = Carbon::parse($request->due_date . ' ' . $request->due_time);
            } else {
                $data['due_datetime'] = Carbon::parse($request->due_date . ' 23:59:59');
            }

            // Handle file upload
            if ($request->hasFile('attachment')) {
                // Delete old file
                if ($assignment->attachment_path && Storage::disk('public')->exists($assignment->attachment_path)) {
                    Storage::disk('public')->delete($assignment->attachment_path);
                }

                $file = $request->file('attachment');
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $fileName = Str::uuid() . '.' . $extension;
                $filePath = $file->storeAs('assignments', $fileName, 'public');

                $data['attachment_path'] = $filePath;
                $data['attachment_size'] = $file->getSize();
                $data['original_attachment_name'] = $originalName;
            }

            // Process tags
            if ($request->filled('tags')) {
                $data['tags'] = array_map('trim', explode(',', $request->tags));
            }

            $data['updated_by'] = Auth::id();

            $assignment->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Assignment updated successfully',
                'assignment' => $assignment->load(['subject', 'class', 'teacher', 'syllabus'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating assignment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified assignment
     */
    public function destroy($id)
    {
        try {
            $assignment = Assignment::findOrFail($id);

            // Check permissions
            if (Auth::id() !== $assignment->teacher_id && Auth::id() !== $assignment->created_by) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to delete this assignment.'
                ], 403);
            }

            $assignment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Assignment deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting assignment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download assignment attachment
     */
    public function downloadAttachment($id)
    {
        $assignment = Assignment::findOrFail($id);

        // Check if user can view this assignment
        if (!$assignment->canBeViewedBy(Auth::user())) {
            abort(403, 'You do not have permission to download this attachment.');
        }

        if (!$assignment->attachment_path || !Storage::disk('public')->exists($assignment->attachment_path)) {
            abort(404, 'Attachment not found.');
        }

        return Storage::disk('public')->download(
            $assignment->attachment_path,
            $assignment->original_attachment_name ?? 'assignment.' . pathinfo($assignment->attachment_path, PATHINFO_EXTENSION)
        );
    }

    /**
     * Toggle assignment published status
     */
    public function togglePublished($id)
    {
        try {
            $assignment = Assignment::findOrFail($id);

            // Check permissions
            if (Auth::id() !== $assignment->teacher_id && Auth::id() !== $assignment->created_by) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to modify this assignment.'
                ], 403);
            }

            $assignment->update([
                'is_published' => !$assignment->is_published,
                'updated_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Assignment status updated successfully',
                'is_published' => $assignment->is_published
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating assignment status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get assignment statistics
     */
    public function getStatistics(Request $request)
    {
        $stats = Assignment::getAssignmentStats();
        
        // Add recent activity
        $stats['upcoming_assignments'] = Assignment::getUpcomingAssignments(5);
        $stats['overdue_assignments'] = Assignment::getOverdueAssignments(5);

        return response()->json([
            'success' => true,
            'statistics' => $stats
        ]);
    }

    /**
     * Get assignments for a specific class
     */
    public function getForClass($classId)
    {
        $assignments = Assignment::published()
            ->forClass($classId)
            ->with(['subject', 'teacher'])
            ->orderBy('due_datetime', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'assignments' => $assignments
        ]);
    }

    /**
     * Get assignments for a specific subject
     */
    public function getForSubject($subjectId)
    {
        $assignments = Assignment::published()
            ->forSubject($subjectId)
            ->with(['class', 'teacher'])
            ->orderBy('due_datetime', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'assignments' => $assignments
        ]);
    }

    /**
     * Get submissions for an assignment
     */
    public function getSubmissions($id, Request $request)
    {
        $assignment = Assignment::findOrFail($id);

        // Check permissions
        if (Auth::id() !== $assignment->teacher_id && Auth::id() !== $assignment->created_by) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view submissions.'
            ], 403);
        }

        $query = $assignment->submissions()->with(['student.user']);

        // Apply filters
        if ($request->filled('status')) {
            if ($request->status === 'submitted') {
                $query->submitted();
            } elseif ($request->status === 'pending') {
                $query->pending();
            } elseif ($request->status === 'graded') {
                $query->graded();
            } elseif ($request->status === 'late') {
                $query->late();
            }
        }

        $submissions = $query->orderBy('submitted_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'submissions' => $submissions
        ]);
    }

    /**
     * Grade a submission
     */
    public function gradeSubmission(Request $request, $assignmentId, $submissionId)
    {
        $assignment = Assignment::findOrFail($assignmentId);
        $submission = AssignmentSubmission::findOrFail($submissionId);

        // Check permissions
        if (Auth::id() !== $assignment->teacher_id && Auth::id() !== $assignment->created_by) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to grade this submission.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'marks_obtained' => 'required|numeric|min:0|max:' . $assignment->total_marks,
            'feedback' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $submission->update([
                'marks_obtained' => $request->marks_obtained,
                'feedback' => $request->feedback,
                'status' => AssignmentSubmission::STATUS_GRADED,
                'graded_at' => now(),
                'graded_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Submission graded successfully',
                'submission' => $submission->load(['student.user'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error grading submission: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk operations
     */
    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:publish,unpublish,delete',
            'assignment_ids' => 'required|array',
            'assignment_ids.*' => 'exists:assignments,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $assignments = Assignment::whereIn('id', $request->assignment_ids)->get();
            $updated = 0;

            foreach ($assignments as $assignment) {
                // Check permissions
                if (Auth::id() !== $assignment->teacher_id && Auth::id() !== $assignment->created_by) {
                    continue;
                }

                switch ($request->action) {
                    case 'publish':
                        $assignment->update(['is_published' => true, 'updated_by' => Auth::id()]);
                        $updated++;
                        break;
                    case 'unpublish':
                        $assignment->update(['is_published' => false, 'updated_by' => Auth::id()]);
                        $updated++;
                        break;
                    case 'delete':
                        $assignment->delete();
                        $updated++;
                        break;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully {$request->action}ed {$updated} assignments"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error performing bulk action: ' . $e->getMessage()
            ], 500);
        }
    }
}