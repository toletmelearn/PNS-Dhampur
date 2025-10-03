<?php

namespace App\Http\Controllers;

use App\Models\Syllabus;
use App\Models\Subject;
use App\Models\ClassModel;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SyllabusController extends Controller
{
    /**
     * Display the syllabus management dashboard
     */
    public function index(Request $request)
    {
        $query = Syllabus::with(['subject', 'class', 'teacher', 'createdBy']);

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

        if ($request->filled('academic_year')) {
            $query->forAcademicYear($request->academic_year);
        }

        if ($request->filled('file_type')) {
            $query->byFileType($request->file_type);
        }

        if ($request->filled('visibility')) {
            $query->where('visibility', $request->visibility);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('tags', 'like', "%{$search}%");
            });
        }

        $syllabi = $query->orderBy('created_at', 'desc')->paginate(15);

        // Get filter options
        $classes = ClassModel::orderBy('name')->get();
        $subjects = Subject::orderBy('name')->get();
        $teachers = Teacher::with('user')->orderBy('name')->get();
        $stats = Syllabus::getSyllabusStats();

        return view('syllabus.index', compact(
            'syllabi', 'classes', 'subjects', 'teachers', 'stats'
        ));
    }

    /**
     * Show the form for creating a new syllabus
     */
    public function create()
    {
        $classes = ClassModel::orderBy('name')->get();
        $subjects = Subject::orderBy('name')->get();
        $teachers = Teacher::with('user')->orderBy('name')->get();
        $visibilityOptions = Syllabus::getVisibilityOptions();
        $fileTypeOptions = Syllabus::getFileTypeOptions();

        return view('syllabus.create', compact(
            'classes', 'subjects', 'teachers', 'visibilityOptions', 'fileTypeOptions'
        ));
    }

    /**
     * Store a newly created syllabus
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'subject_id' => 'nullable|exists:subjects,id',
            'class_id' => 'required|exists:class_models,id',
            'teacher_id' => 'required|exists:teachers,id',
            'academic_year' => 'required|string|max:20',
            'semester' => 'nullable|string|max:20',
            'visibility' => 'required|in:public,class_only,private',
            'tags' => 'nullable|string',
            'file' => 'required|file|mimes:pdf,doc,docx,mp4,avi,mov,wmv,jpg,jpeg,png,gif|max:51200', // 50MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->only([
                'title', 'description', 'subject_id', 'class_id', 'teacher_id',
                'academic_year', 'semester', 'visibility'
            ]);

            // Handle file upload
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $fileName = Str::uuid() . '.' . $extension;
                $filePath = $file->storeAs('syllabus', $fileName, 'public');

                $data['file_path'] = $filePath;
                $data['file_type'] = $this->getFileType($extension);
                $data['file_size'] = $file->getSize();
                $data['original_filename'] = $originalName;
            }

            // Process tags
            if ($request->filled('tags')) {
                $data['tags'] = array_map('trim', explode(',', $request->tags));
            }

            $data['is_active'] = true;
            $data['created_by'] = Auth::id();

            $syllabus = Syllabus::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Syllabus created successfully',
                'syllabus' => $syllabus->load(['subject', 'class', 'teacher'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating syllabus: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified syllabus
     */
    public function show($id)
    {
        $syllabus = Syllabus::with(['subject', 'class', 'teacher', 'createdBy', 'updatedBy'])
            ->findOrFail($id);

        // Check if user can view this syllabus
        if (!$syllabus->canBeViewedBy(Auth::user())) {
            abort(403, 'You do not have permission to view this syllabus.');
        }

        // Increment view count
        $syllabus->incrementViewCount();

        return view('syllabus.show', compact('syllabus'));
    }

    /**
     * Show the form for editing the specified syllabus
     */
    public function edit($id)
    {
        $syllabus = Syllabus::findOrFail($id);

        // Check permissions
        if (Auth::id() !== $syllabus->teacher_id && Auth::id() !== $syllabus->created_by) {
            abort(403, 'You do not have permission to edit this syllabus.');
        }

        $classes = ClassModel::orderBy('name')->get();
        $subjects = Subject::orderBy('name')->get();
        $teachers = Teacher::with('user')->orderBy('name')->get();
        $visibilityOptions = Syllabus::getVisibilityOptions();
        $fileTypeOptions = Syllabus::getFileTypeOptions();

        return view('syllabus.edit', compact(
            'syllabus', 'classes', 'subjects', 'teachers', 'visibilityOptions', 'fileTypeOptions'
        ));
    }

    /**
     * Update the specified syllabus
     */
    public function update(Request $request, $id)
    {
        $syllabus = Syllabus::findOrFail($id);

        // Check permissions
        if (Auth::id() !== $syllabus->teacher_id && Auth::id() !== $syllabus->created_by) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update this syllabus.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'subject_id' => 'nullable|exists:subjects,id',
            'class_id' => 'required|exists:class_models,id',
            'teacher_id' => 'required|exists:teachers,id',
            'academic_year' => 'required|string|max:20',
            'semester' => 'nullable|string|max:20',
            'visibility' => 'required|in:public,class_only,private',
            'tags' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,doc,docx,mp4,avi,mov,wmv,jpg,jpeg,png,gif|max:51200',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->only([
                'title', 'description', 'subject_id', 'class_id', 'teacher_id',
                'academic_year', 'semester', 'visibility'
            ]);

            // Handle file upload
            if ($request->hasFile('file')) {
                // Delete old file
                if ($syllabus->file_path && Storage::disk('public')->exists($syllabus->file_path)) {
                    Storage::disk('public')->delete($syllabus->file_path);
                }

                $file = $request->file('file');
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $fileName = Str::uuid() . '.' . $extension;
                $filePath = $file->storeAs('syllabus', $fileName, 'public');

                $data['file_path'] = $filePath;
                $data['file_type'] = $this->getFileType($extension);
                $data['file_size'] = $file->getSize();
                $data['original_filename'] = $originalName;
            }

            // Process tags
            if ($request->filled('tags')) {
                $data['tags'] = array_map('trim', explode(',', $request->tags));
            }

            $data['updated_by'] = Auth::id();

            $syllabus->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Syllabus updated successfully',
                'syllabus' => $syllabus->load(['subject', 'class', 'teacher'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating syllabus: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified syllabus
     */
    public function destroy($id)
    {
        try {
            $syllabus = Syllabus::findOrFail($id);

            // Check permissions
            if (Auth::id() !== $syllabus->teacher_id && Auth::id() !== $syllabus->created_by) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to delete this syllabus.'
                ], 403);
            }

            $syllabus->delete();

            return response()->json([
                'success' => true,
                'message' => 'Syllabus deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting syllabus: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download syllabus file
     */
    public function download($id)
    {
        $syllabus = Syllabus::findOrFail($id);

        // Check if user can view this syllabus
        if (!$syllabus->canBeViewedBy(Auth::user())) {
            abort(403, 'You do not have permission to download this syllabus.');
        }

        if (!$syllabus->file_path || !Storage::disk('public')->exists($syllabus->file_path)) {
            abort(404, 'File not found.');
        }

        // Increment download count
        $syllabus->incrementDownloadCount();

        return Storage::disk('public')->download(
            $syllabus->file_path,
            $syllabus->original_filename ?? 'syllabus.' . pathinfo($syllabus->file_path, PATHINFO_EXTENSION)
        );
    }

    /**
     * Toggle syllabus active status
     */
    public function toggleActive($id)
    {
        try {
            $syllabus = Syllabus::findOrFail($id);

            // Check permissions
            if (Auth::id() !== $syllabus->teacher_id && Auth::id() !== $syllabus->created_by) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to modify this syllabus.'
                ], 403);
            }

            $syllabus->update([
                'is_active' => !$syllabus->is_active,
                'updated_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Syllabus status updated successfully',
                'is_active' => $syllabus->is_active
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating syllabus status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get syllabus statistics
     */
    public function getStatistics(Request $request)
    {
        $stats = Syllabus::getSyllabusStats();
        
        // Add recent activity
        $stats['recent_syllabi'] = Syllabus::getRecentSyllabi(5);
        $stats['popular_syllabi'] = Syllabus::getPopularSyllabi(5);

        return response()->json([
            'success' => true,
            'statistics' => $stats
        ]);
    }

    /**
     * Get syllabi for a specific class
     */
    public function getForClass($classId)
    {
        $syllabi = Syllabus::active()
            ->forClass($classId)
            ->with(['subject', 'teacher'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'syllabi' => $syllabi
        ]);
    }

    /**
     * Get syllabi for a specific subject
     */
    public function getForSubject($subjectId)
    {
        $syllabi = Syllabus::active()
            ->forSubject($subjectId)
            ->with(['class', 'teacher'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'syllabi' => $syllabi
        ]);
    }

    /**
     * Bulk operations
     */
    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:activate,deactivate,delete',
            'syllabus_ids' => 'required|array',
            'syllabus_ids.*' => 'exists:syllabus,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $syllabi = Syllabus::whereIn('id', $request->syllabus_ids)->get();
            $updated = 0;

            foreach ($syllabi as $syllabus) {
                // Check permissions
                if (Auth::id() !== $syllabus->teacher_id && Auth::id() !== $syllabus->created_by) {
                    continue;
                }

                switch ($request->action) {
                    case 'activate':
                        $syllabus->update(['is_active' => true, 'updated_by' => Auth::id()]);
                        $updated++;
                        break;
                    case 'deactivate':
                        $syllabus->update(['is_active' => false, 'updated_by' => Auth::id()]);
                        $updated++;
                        break;
                    case 'delete':
                        $syllabus->delete();
                        $updated++;
                        break;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully {$request->action}d {$updated} syllabi"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error performing bulk action: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method to determine file type
     */
    private function getFileType($extension)
    {
        $extension = strtolower($extension);
        
        if ($extension === 'pdf') {
            return Syllabus::FILE_TYPE_PDF;
        } elseif (in_array($extension, ['doc', 'docx'])) {
            return $extension === 'doc' ? Syllabus::FILE_TYPE_DOC : Syllabus::FILE_TYPE_DOCX;
        } elseif (in_array($extension, ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'])) {
            return Syllabus::FILE_TYPE_VIDEO;
        } elseif (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg'])) {
            return Syllabus::FILE_TYPE_IMAGE;
        } else {
            return Syllabus::FILE_TYPE_OTHER;
        }
    }
}
