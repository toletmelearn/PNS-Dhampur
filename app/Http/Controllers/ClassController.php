<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\Teacher;
use App\Models\Subject;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClassController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = ClassModel::with(['classTeacher', 'subjects', 'students']);

        // Filter by class teacher if provided
        if ($request->filled('class_teacher_id')) {
            $query->where('class_teacher_id', $request->class_teacher_id);
        }

        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search by name or section
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('section', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $classes = $query->orderBy('name')->orderBy('section')->paginate(15);

        return response()->json($classes);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $teachers = Teacher::with('user')->get();
        
        return view('classes.create', compact('teachers'));
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
            'section' => 'nullable|string|max:10',
            'description' => 'nullable|string',
            'class_teacher_id' => 'nullable|exists:teachers,id',
            'capacity' => 'nullable|integer|min:1|max:200',
            'is_active' => 'boolean'
        ]);

        // Ensure unique combination of name and section
        $existingClass = ClassModel::where('name', $data['name'])
            ->where('section', $data['section'] ?? null)
            ->first();

        if ($existingClass) {
            return response()->json([
                'message' => 'A class with this name and section already exists.',
                'errors' => [
                    'name' => ['This class name and section combination already exists.']
                ]
            ], 422);
        }

        // Set default values
        $data['is_active'] = $data['is_active'] ?? true;
        $data['capacity'] = $data['capacity'] ?? 50;

        $class = ClassModel::create($data);

        return response()->json([
            'message' => 'Class created successfully',
            'class' => $class->load(['classTeacher'])
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
        $class = ClassModel::with([
            'classTeacher.user',
            'subjects.teacher.user',
            'students.user',
            'classTeacherPermissions'
        ])->findOrFail($id);

        // Add student count and subject count
        $class->student_count = $class->students->count();
        $class->subject_count = $class->subjects->count();

        return response()->json($class);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $class = ClassModel::with(['classTeacher'])->findOrFail($id);
        $teachers = Teacher::with('user')->get();
        
        return view('classes.edit', compact('class', 'teachers'));
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
        $class = ClassModel::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'section' => 'sometimes|nullable|string|max:10',
            'description' => 'sometimes|nullable|string',
            'class_teacher_id' => 'sometimes|nullable|exists:teachers,id',
            'capacity' => 'sometimes|nullable|integer|min:1|max:200',
            'is_active' => 'sometimes|boolean'
        ]);

        // Check for unique combination if name or section is being updated
        if (isset($data['name']) || isset($data['section'])) {
            $name = $data['name'] ?? $class->name;
            $section = $data['section'] ?? $class->section;

            $existingClass = ClassModel::where('name', $name)
                ->where('section', $section)
                ->where('id', '!=', $class->id)
                ->first();

            if ($existingClass) {
                return response()->json([
                    'message' => 'A class with this name and section already exists.',
                    'errors' => [
                        'name' => ['This class name and section combination already exists.']
                    ]
                ], 422);
            }
        }

        $class->update($data);

        return response()->json([
            'message' => 'Class updated successfully',
            'class' => $class->load(['classTeacher'])
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
        $class = ClassModel::findOrFail($id);
        
        // Check if class has related records
        if ($class->students()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete class as it has enrolled students. Consider deactivating it instead.'
            ], 422);
        }

        if ($class->subjects()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete class as it has assigned subjects. Consider deactivating it instead.'
            ], 422);
        }

        $class->delete();

        return response()->json([
            'message' => 'Class deleted successfully'
        ]);
    }

    /**
     * Get class statistics
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getStatistics($id)
    {
        $class = ClassModel::findOrFail($id);

        $statistics = [
            'total_students' => $class->students()->count(),
            'total_subjects' => $class->subjects()->count(),
            'capacity' => $class->capacity,
            'available_spots' => max(0, $class->capacity - $class->students()->count()),
            'class_teacher' => $class->classTeacher ? $class->classTeacher->user->name : 'Not assigned',
            'active_subjects' => $class->subjects()->where('is_active', true)->count(),
            'inactive_subjects' => $class->subjects()->where('is_active', false)->count()
        ];

        return response()->json($statistics);
    }

    /**
     * Toggle class active status
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function toggleStatus($id)
    {
        $class = ClassModel::findOrFail($id);
        $class->update(['is_active' => !$class->is_active]);

        return response()->json([
            'message' => 'Class status updated successfully',
            'class' => $class
        ]);
    }

    /**
     * Assign class teacher
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function assignTeacher(Request $request, $id)
    {
        $class = ClassModel::findOrFail($id);

        $data = $request->validate([
            'class_teacher_id' => 'required|exists:teachers,id'
        ]);

        $class->update($data);

        return response()->json([
            'message' => 'Class teacher assigned successfully',
            'class' => $class->load(['classTeacher.user'])
        ]);
    }

    /**
     * Get students in a class
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getStudents($id)
    {
        $class = ClassModel::findOrFail($id);
        $students = $class->students()->with('user')->orderBy('roll_number')->get();

        return response()->json($students);
    }

    /**
     * Get subjects for a class
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getSubjects($id)
    {
        $class = ClassModel::findOrFail($id);
        $subjects = $class->subjects()->with('teacher.user')->where('is_active', true)->orderBy('name')->get();

        return response()->json($subjects);
    }
}
