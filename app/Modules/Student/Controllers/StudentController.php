<?php

namespace App\Modules\Student\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Student\Services\StudentService;
use App\Modules\Student\Requests\StoreStudentRequest;
use App\Modules\Student\Requests\UpdateStudentRequest;
use App\Modules\Student\Resources\StudentResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class StudentController extends Controller
{
    protected StudentService $studentService;

    public function __construct(StudentService $studentService)
    {
        $this->studentService = $studentService;
        $this->middleware('auth');
        $this->middleware('role:admin,teacher')->except(['show']);
    }

    /**
     * Display a listing of students
     */
    public function index(Request $request): View|JsonResponse
    {
        $filters = $request->only(['class_id', 'section', 'status', 'search']);
        $students = $this->studentService->getStudents($filters, $request->get('per_page', 15));

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => StudentResource::collection($students),
                'meta' => [
                    'total' => $students->total(),
                    'per_page' => $students->perPage(),
                    'current_page' => $students->currentPage(),
                ]
            ]);
        }

        return view('modules.student.index', compact('students', 'filters'));
    }

    /**
     * Show the form for creating a new student
     */
    public function create(): View
    {
        $classes = $this->studentService->getAvailableClasses();
        return view('modules.student.create', compact('classes'));
    }

    /**
     * Store a newly created student
     */
    public function store(StoreStudentRequest $request): RedirectResponse|JsonResponse
    {
        try {
            $student = $this->studentService->createStudent($request->validated());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Student created successfully',
                    'data' => new StudentResource($student)
                ], 201);
            }

            return redirect()->route('students.index')
                ->with('success', 'Student created successfully');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create student',
                    'error' => $e->getMessage()
                ], 422);
            }

            return back()->withInput()
                ->with('error', 'Failed to create student: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified student
     */
    public function show(int $id): View|JsonResponse
    {
        $student = $this->studentService->getStudentById($id);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => new StudentResource($student)
            ]);
        }

        return view('modules.student.show', compact('student'));
    }

    /**
     * Show the form for editing the specified student
     */
    public function edit(int $id): View
    {
        $student = $this->studentService->getStudentById($id);
        $classes = $this->studentService->getAvailableClasses();
        
        return view('modules.student.edit', compact('student', 'classes'));
    }

    /**
     * Update the specified student
     */
    public function update(UpdateStudentRequest $request, int $id): RedirectResponse|JsonResponse
    {
        try {
            $student = $this->studentService->updateStudent($id, $request->validated());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Student updated successfully',
                    'data' => new StudentResource($student)
                ]);
            }

            return redirect()->route('students.show', $id)
                ->with('success', 'Student updated successfully');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update student',
                    'error' => $e->getMessage()
                ], 422);
            }

            return back()->withInput()
                ->with('error', 'Failed to update student: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified student
     */
    public function destroy(int $id): RedirectResponse|JsonResponse
    {
        try {
            $this->studentService->deleteStudent($id);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Student deleted successfully'
                ]);
            }

            return redirect()->route('students.index')
                ->with('success', 'Student deleted successfully');
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete student',
                    'error' => $e->getMessage()
                ], 422);
            }

            return back()->with('error', 'Failed to delete student: ' . $e->getMessage());
        }
    }

    /**
     * Get student academic records
     */
    public function academicRecords(int $id): View|JsonResponse
    {
        $student = $this->studentService->getStudentById($id);
        $academicRecords = $this->studentService->getAcademicRecords($id);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'student' => new StudentResource($student),
                    'academic_records' => $academicRecords
                ]
            ]);
        }

        return view('modules.student.academic-records', compact('student', 'academicRecords'));
    }

    /**
     * Bulk operations on students
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,delete,promote',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'integer|exists:students,id',
            'target_class_id' => 'required_if:action,promote|integer|exists:classes,id'
        ]);

        try {
            $result = $this->studentService->bulkAction(
                $request->input('action'),
                $request->input('student_ids'),
                $request->only(['target_class_id'])
            );

            return response()->json([
                'success' => true,
                'message' => "Bulk {$request->input('action')} completed successfully",
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk operation failed',
                'error' => $e->getMessage()
            ], 422);
        }
    }
}