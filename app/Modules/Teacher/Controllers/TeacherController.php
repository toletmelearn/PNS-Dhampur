<?php

namespace App\Modules\Teacher\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Teacher\Services\TeacherService;
use App\Modules\Teacher\Requests\StoreTeacherRequest;
use App\Modules\Teacher\Requests\UpdateTeacherRequest;
use App\Modules\Teacher\Resources\TeacherResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TeacherController extends Controller
{
    protected TeacherService $teacherService;

    public function __construct(TeacherService $teacherService)
    {
        $this->teacherService = $teacherService;
        
        // Apply middleware
        $this->middleware(['auth', 'verified']);
        $this->middleware('role:admin,principal')->except(['show', 'profile', 'updateProfile']);
        $this->middleware('role:admin,principal,teacher')->only(['show', 'profile', 'updateProfile']);
        
        // Rate limiting for sensitive operations
        $this->middleware('throttle:60,1')->only(['store', 'update', 'destroy', 'bulkAction']);
    }

    /**
     * Display a listing of teachers
     */
    public function index(Request $request): View|JsonResponse
    {
        try {
            $filters = $request->only([
                'search', 'department', 'subject', 'status', 'employment_type',
                'qualification', 'experience_min', 'experience_max', 'sort_by', 'sort_order'
            ]);

            $teachers = $this->teacherService->getAllTeachers($filters, $request->get('per_page', 15));

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => TeacherResource::collection($teachers),
                    'meta' => [
                        'current_page' => $teachers->currentPage(),
                        'last_page' => $teachers->lastPage(),
                        'per_page' => $teachers->perPage(),
                        'total' => $teachers->total(),
                    ]
                ]);
            }

            return view('modules.teacher.index', compact('teachers', 'filters'));
        } catch (\Exception $e) {
            Log::error('Error fetching teachers: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Error fetching teachers'], 500);
            }
            
            return redirect()->back()->with('error', 'Error fetching teachers');
        }
    }

    /**
     * Show the form for creating a new teacher
     */
    public function create(): View
    {
        $departments = $this->teacherService->getDepartments();
        $subjects = $this->teacherService->getSubjects();
        $qualifications = $this->teacherService->getQualifications();
        
        return view('modules.teacher.create', compact('departments', 'subjects', 'qualifications'));
    }

    /**
     * Store a newly created teacher
     */
    public function store(StoreTeacherRequest $request): RedirectResponse|JsonResponse
    {
        try {
            DB::beginTransaction();
            
            $teacher = $this->teacherService->createTeacher($request->validated());
            
            DB::commit();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Teacher created successfully',
                    'data' => new TeacherResource($teacher)
                ], 201);
            }
            
            return redirect()->route('teachers.show', $teacher->id)
                           ->with('success', 'Teacher created successfully');
                           
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating teacher: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Error creating teacher'], 500);
            }
            
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Error creating teacher');
        }
    }

    /**
     * Display the specified teacher
     */
    public function show(Request $request, $id): View|JsonResponse
    {
        try {
            $teacher = $this->teacherService->getTeacherById($id);
            
            if (!$teacher) {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'Teacher not found'], 404);
                }
                
                return redirect()->route('teachers.index')->with('error', 'Teacher not found');
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => new TeacherResource($teacher)
                ]);
            }

            return view('modules.teacher.show', compact('teacher'));
        } catch (\Exception $e) {
            Log::error('Error fetching teacher: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Error fetching teacher'], 500);
            }
            
            return redirect()->route('teachers.index')->with('error', 'Error fetching teacher');
        }
    }

    /**
     * Show the form for editing the specified teacher
     */
    public function edit($id): View
    {
        $teacher = $this->teacherService->getTeacherById($id);
        
        if (!$teacher) {
            return redirect()->route('teachers.index')->with('error', 'Teacher not found');
        }
        
        $departments = $this->teacherService->getDepartments();
        $subjects = $this->teacherService->getSubjects();
        $qualifications = $this->teacherService->getQualifications();
        
        return view('modules.teacher.edit', compact('teacher', 'departments', 'subjects', 'qualifications'));
    }

    /**
     * Update the specified teacher
     */
    public function update(UpdateTeacherRequest $request, $id): RedirectResponse|JsonResponse
    {
        try {
            DB::beginTransaction();
            
            $teacher = $this->teacherService->updateTeacher($id, $request->validated());
            
            if (!$teacher) {
                DB::rollBack();
                
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'Teacher not found'], 404);
                }
                
                return redirect()->route('teachers.index')->with('error', 'Teacher not found');
            }
            
            DB::commit();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Teacher updated successfully',
                    'data' => new TeacherResource($teacher)
                ]);
            }
            
            return redirect()->route('teachers.show', $teacher->id)
                           ->with('success', 'Teacher updated successfully');
                           
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating teacher: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Error updating teacher'], 500);
            }
            
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Error updating teacher');
        }
    }

    /**
     * Remove the specified teacher
     */
    public function destroy(Request $request, $id): RedirectResponse|JsonResponse
    {
        try {
            DB::beginTransaction();
            
            $deleted = $this->teacherService->deleteTeacher($id);
            
            if (!$deleted) {
                DB::rollBack();
                
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'Teacher not found'], 404);
                }
                
                return redirect()->route('teachers.index')->with('error', 'Teacher not found');
            }
            
            DB::commit();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Teacher deleted successfully'
                ]);
            }
            
            return redirect()->route('teachers.index')
                           ->with('success', 'Teacher deleted successfully');
                           
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting teacher: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Error deleting teacher'], 500);
            }
            
            return redirect()->back()->with('error', 'Error deleting teacher');
        }
    }

    /**
     * Handle bulk actions on teachers
     */
    public function bulkAction(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'action' => 'required|in:delete,activate,deactivate,export',
            'teacher_ids' => 'required|array|min:1',
            'teacher_ids.*' => 'exists:teachers,id'
        ]);

        try {
            DB::beginTransaction();
            
            $result = $this->teacherService->bulkAction($request->action, $request->teacher_ids);
            
            DB::commit();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => $result['data'] ?? null
                ]);
            }
            
            return redirect()->back()->with('success', $result['message']);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error performing bulk action: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Error performing bulk action'], 500);
            }
            
            return redirect()->back()->with('error', 'Error performing bulk action');
        }
    }

    /**
     * Get teacher's class assignments
     */
    public function classAssignments(Request $request, $id): View|JsonResponse
    {
        try {
            $teacher = $this->teacherService->getTeacherById($id);
            
            if (!$teacher) {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'Teacher not found'], 404);
                }
                
                return redirect()->route('teachers.index')->with('error', 'Teacher not found');
            }

            $assignments = $this->teacherService->getClassAssignments($id);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $assignments
                ]);
            }

            return view('modules.teacher.class-assignments', compact('teacher', 'assignments'));
        } catch (\Exception $e) {
            Log::error('Error fetching class assignments: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Error fetching assignments'], 500);
            }
            
            return redirect()->back()->with('error', 'Error fetching assignments');
        }
    }

    /**
     * Get teacher's timetable
     */
    public function timetable(Request $request, $id): View|JsonResponse
    {
        try {
            $teacher = $this->teacherService->getTeacherById($id);
            
            if (!$teacher) {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'Teacher not found'], 404);
                }
                
                return redirect()->route('teachers.index')->with('error', 'Teacher not found');
            }

            $timetable = $this->teacherService->getTimetable($id);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $timetable
                ]);
            }

            return view('modules.teacher.timetable', compact('teacher', 'timetable'));
        } catch (\Exception $e) {
            Log::error('Error fetching timetable: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Error fetching timetable'], 500);
            }
            
            return redirect()->back()->with('error', 'Error fetching timetable');
        }
    }

    /**
     * Get teacher's attendance records
     */
    public function attendance(Request $request, $id): View|JsonResponse
    {
        try {
            $teacher = $this->teacherService->getTeacherById($id);
            
            if (!$teacher) {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'Teacher not found'], 404);
                }
                
                return redirect()->route('teachers.index')->with('error', 'Teacher not found');
            }

            $filters = $request->only(['start_date', 'end_date', 'status']);
            $attendance = $this->teacherService->getAttendanceRecords($id, $filters);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $attendance
                ]);
            }

            return view('modules.teacher.attendance', compact('teacher', 'attendance', 'filters'));
        } catch (\Exception $e) {
            Log::error('Error fetching attendance: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Error fetching attendance'], 500);
            }
            
            return redirect()->back()->with('error', 'Error fetching attendance');
        }
    }

    /**
     * Get teacher's performance metrics
     */
    public function performance(Request $request, $id): View|JsonResponse
    {
        try {
            $teacher = $this->teacherService->getTeacherById($id);
            
            if (!$teacher) {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'Teacher not found'], 404);
                }
                
                return redirect()->route('teachers.index')->with('error', 'Teacher not found');
            }

            $performance = $this->teacherService->getPerformanceMetrics($id);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $performance
                ]);
            }

            return view('modules.teacher.performance', compact('teacher', 'performance'));
        } catch (\Exception $e) {
            Log::error('Error fetching performance metrics: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Error fetching performance'], 500);
            }
            
            return redirect()->back()->with('error', 'Error fetching performance');
        }
    }

    /**
     * Teacher profile (for authenticated teacher)
     */
    public function profile(Request $request): View|JsonResponse
    {
        try {
            $teacher = $this->teacherService->getTeacherByUserId(Auth::id());
            
            if (!$teacher) {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'Teacher profile not found'], 404);
                }
                
                return redirect()->route('dashboard')->with('error', 'Teacher profile not found');
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => new TeacherResource($teacher)
                ]);
            }

            return view('modules.teacher.profile', compact('teacher'));
        } catch (\Exception $e) {
            Log::error('Error fetching teacher profile: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Error fetching profile'], 500);
            }
            
            return redirect()->route('dashboard')->with('error', 'Error fetching profile');
        }
    }

    /**
     * Update teacher profile
     */
    public function updateProfile(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'phone' => 'nullable|string|max:20',
            'emergency_contact' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'bio' => 'nullable|string|max:1000',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        try {
            DB::beginTransaction();
            
            $teacher = $this->teacherService->updateTeacherProfile(Auth::id(), $request->all());
            
            if (!$teacher) {
                DB::rollBack();
                
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'Teacher profile not found'], 404);
                }
                
                return redirect()->back()->with('error', 'Teacher profile not found');
            }
            
            DB::commit();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Profile updated successfully',
                    'data' => new TeacherResource($teacher)
                ]);
            }
            
            return redirect()->route('teachers.profile')
                           ->with('success', 'Profile updated successfully');
                           
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating teacher profile: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Error updating profile'], 500);
            }
            
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Error updating profile');
        }
    }

    /**
     * Export teachers data
     */
    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'format' => 'required|in:csv,excel,pdf',
            'filters' => 'nullable|array'
        ]);

        try {
            $result = $this->teacherService->exportTeachers($request->format, $request->filters ?? []);
            
            return response()->json([
                'success' => true,
                'message' => 'Export completed successfully',
                'download_url' => $result['download_url']
            ]);
        } catch (\Exception $e) {
            Log::error('Error exporting teachers: ' . $e->getMessage());
            
            return response()->json(['success' => false, 'message' => 'Error exporting data'], 500);
        }
    }

    /**
     * Import teachers data
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls',
            'format' => 'required|in:csv,excel'
        ]);

        try {
            DB::beginTransaction();
            
            $result = $this->teacherService->importTeachers($request->file, $request->format);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Import completed successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error importing teachers: ' . $e->getMessage());
            
            return response()->json(['success' => false, 'message' => 'Error importing data'], 500);
        }
    }

    /**
     * Get statistics for teachers
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $stats = $this->teacherService->getStatistics();
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching statistics: ' . $e->getMessage());
            
            return response()->json(['success' => false, 'message' => 'Error fetching statistics'], 500);
        }
    }
}