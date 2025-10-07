<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\ClassModel;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Helpers\SecurityHelper;

class SubjectController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Subject::with(['class', 'teacher']);

        // Filter by class if provided
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        // Filter by teacher if provided
        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search by name or code
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', SecurityHelper::buildLikePattern($search))
                  ->orWhere('code', 'like', SecurityHelper::buildLikePattern($search))
                  ->orWhere('description', 'like', SecurityHelper::buildLikePattern($search));
            });
        }

        $subjects = $query->orderBy('name')->paginate(15);

        return response()->json($subjects);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $classes = ClassModel::orderBy('name')->get();
        $teachers = Teacher::with('user')->get();
        
        return view('subjects.create', compact('classes', 'teachers'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:subjects,code',
            'description' => 'nullable|string',
            'class_id' => 'required|exists:class_models,id',
            'teacher_id' => 'nullable|exists:teachers,id',
            'is_active' => 'boolean'
        ]);

        // Set default value for is_active if not provided
        $data['is_active'] = $data['is_active'] ?? true;

        $subject = Subject::create($data);

        return response()->json([
            'message' => 'Subject created successfully',
            'subject' => $subject->load(['class', 'teacher'])
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $subject = Subject::with(['class', 'teacher', 'srRegisters', 'examPapers'])->findOrFail($id);
        return response()->json($subject);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $subject = Subject::with(['class', 'teacher'])->findOrFail($id);
        $classes = ClassModel::orderBy('name')->get();
        $teachers = Teacher::with('user')->get();
        
        return view('subjects.edit', compact('subject', 'classes', 'teachers'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $subject = Subject::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => ['sometimes', 'required', 'string', 'max:10', Rule::unique('subjects')->ignore($subject->id)],
            'description' => 'sometimes|nullable|string',
            'class_id' => 'sometimes|required|exists:class_models,id',
            'teacher_id' => 'sometimes|nullable|exists:teachers,id',
            'is_active' => 'sometimes|boolean'
        ]);

        $subject->update($data);

        return response()->json([
            'message' => 'Subject updated successfully',
            'subject' => $subject->load(['class', 'teacher'])
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $subject = Subject::findOrFail($id);
        
        // Check if subject has related records
        if ($subject->srRegisters()->count() > 0 || $subject->examPapers()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete subject as it has related records. Consider deactivating it instead.'
            ], 422);
        }

        $subject->delete();

        return response()->json([
            'message' => 'Subject deleted successfully'
        ]);
    }

    /**
     * Get subjects for a specific class
     *
     * @param  int  $classId
     * @return \Illuminate\Http\Response
     */
    public function getByClass($classId)
    {
        $subjects = Subject::with('teacher')
            ->where('class_id', $classId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json($subjects);
    }

    /**
     * Toggle subject active status
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function toggleStatus($id)
    {
        $subject = Subject::findOrFail($id);
        $subject->update(['is_active' => !$subject->is_active]);

        return response()->json([
            'message' => 'Subject status updated successfully',
            'subject' => $subject
        ]);
    }
}
