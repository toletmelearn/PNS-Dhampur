<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\User;
use App\Models\Role;
use App\Models\ClassModel;
use App\Models\Subject;
use App\Services\UserFriendlyErrorService;
use App\Rules\PasswordComplexity;
use App\Rules\PasswordHistory;
use App\Http\Traits\EmailValidationTrait;
use App\Http\Traits\FileUploadValidationTrait;
use App\Http\Traits\TeacherValidationTrait;
use App\Traits\HandlesApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Helpers\SecurityHelper;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TeacherController extends Controller
{
    use HandlesApiResponses, EmailValidationTrait, FileUploadValidationTrait, TeacherValidationTrait;
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of teachers (Web Interface)
     */
    public function index(Request $request)
    {
        $query = Teacher::with([
            'user:id,name,email,phone,status',
            'classes:id,name,section',
            'salaries' => function($query) {
                $query->latest()->limit(3);
            }
        ])
        ->withCount(['classes', 'salaries'])
        ->withSum('salaries', 'amount')
        ->withAvg('salaries', 'amount');

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', SecurityHelper::buildLikePattern($search))
                  ->orWhere('email', 'like', SecurityHelper::buildLikePattern($search));
            })->orWhere('qualification', 'like', SecurityHelper::buildLikePattern($search));
        }

        if ($request->filled('qualification')) {
            $query->where('qualification', 'like', SecurityHelper::buildLikePattern($request->qualification));
        }

        if ($request->filled('experience_min')) {
            $query->where('experience_years', '>=', $request->experience_min);
        }

        if ($request->filled('experience_max')) {
            $query->where('experience_years', '<=', $request->experience_max);
        }

        if ($request->filled('salary_min')) {
            $query->where('salary', '>=', $request->salary_min);
        }

        if ($request->filled('salary_max')) {
            $query->where('salary', '<=', $request->salary_max);
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        $allowedSorts = ['created_at', 'experience_years', 'salary', 'qualification'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $teachers = $query->paginate(15);

        // Calculate statistics efficiently in a single query
        $statsQuery = Teacher::selectRaw('
            COUNT(*) as total_teachers,
            COUNT(CASE WHEN users.status = "active" THEN 1 END) as active_teachers,
            AVG(experience_years) as average_experience,
            AVG(salary) as average_salary
        ')->join('users', 'teachers.user_id', '=', 'users.id')->first();

        $stats = [
            'total_teachers' => $statsQuery->total_teachers ?? 0,
            'active_teachers' => $statsQuery->active_teachers ?? 0,
            'average_experience' => round($statsQuery->average_experience ?? 0, 1),
            'average_salary' => round($statsQuery->average_salary ?? 0, 2),
        ];

        // Get filter options efficiently
        $qualifications = Teacher::distinct()->pluck('qualification')->filter();
        $classes = ClassModel::select('id', 'name', 'section')->get();
        $subjects = Subject::select('id', 'name')->get();

        if ($request->expectsJson()) {
            return response()->json([
                'teachers' => $teachers,
                'stats' => $stats,
                'qualifications' => $qualifications,
                'pagination' => [
                    'current_page' => $teachers->currentPage(),
                    'last_page' => $teachers->lastPage(),
                    'per_page' => $teachers->perPage(),
                    'total' => $teachers->total(),
                    'from' => $teachers->firstItem(),
                    'to' => $teachers->lastItem(),
                ]
            ]);
        }

        return view('teachers.index', compact('teachers', 'stats', 'qualifications', 'classes', 'subjects'));
    }

    /**
     * Show the form for creating a new teacher
     */
    public function create()
    {
        // Use pagination for classes and subjects to avoid memory issues
        $classes = ClassModel::paginate(50);
        $subjects = Subject::paginate(50);
        
        return view('teachers.create', compact('classes', 'subjects'));
    }

    /**
     * Store a newly created teacher
     */
    public function store(Request $request)
    {
        $validated = $request->validate(
            $this->getTeacherCreateValidationRules(),
            $this->getTeacherValidationMessages()
        );

        try {
            DB::beginTransaction();

            // Create user account
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'status' => 'active',
                'password' => 'temp', // Temporary password
            ]);

            // Use the new updatePassword method which handles history and expiration
            $user->updatePassword($validated['password']);

            // Assign teacher role
            $user->assignRole('teacher');

            // Handle document uploads with security measures
            $documents = [];
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $key => $file) {
                    // Generate secure filename to prevent directory traversal and overwrite attacks
                    $extension = strtolower($file->getClientOriginalExtension());
                    $secureFilename = 'teacher_' . $user->id . '_' . time() . '_' . Str::random(10) . '.' . $extension;
                    
                    // Store with secure filename
                    $path = $file->storeAs('teachers/documents', $secureFilename, 'public');
                    $documents[$key] = [
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'uploaded_at' => now()
                    ];
                }
            }

            // Create teacher record
            $teacher = Teacher::create([
                'user_id' => $user->id,
                'qualification' => $validated['qualification'],
                'experience_years' => $validated['experience_years'],
                'salary' => $validated['salary'],
                'joining_date' => $validated['joining_date'],
                'documents' => $documents,
            ]);

            // Attach subjects if provided
            if (!empty($validated['subjects'])) {
                $teacher->subjects()->attach($validated['subjects']);
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Teacher created successfully',
                    'teacher' => $teacher->load('user', 'classes')
                ], 201);
            }

            return redirect()->route('teachers.index')
                           ->with('success', 'Teacher created successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson()) {
                return response()->json(
                    UserFriendlyErrorService::jsonErrorResponse($e, 'teacher_create'),
                    500
                );
            }

            return back()->withInput()
                        ->withErrors(['error' => UserFriendlyErrorService::getErrorMessage($e, 'teacher_create')]);
        }
    }

    /**
     * Display the specified teacher
     */
    public function show($id)
    {
        $teacher = Teacher::with(['user', 'classes', 'salaries', 'availability'])
                         ->findOrFail($id);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'teacher' => $teacher
            ]);
        }

        return view('teachers.show', compact('teacher'));
    }

    /**
     * Show the form for editing the specified teacher
     */
    public function edit($id)
    {
        $teacher = Teacher::with(['user', 'classes'])->findOrFail($id);
        // Use pagination for classes and subjects to avoid memory issues
        $classes = ClassModel::paginate(50);
        $subjects = Subject::paginate(50);
        
        return view('teachers.edit', compact('teacher', 'classes', 'subjects'));
    }

    /**
     * Update the specified teacher
     */
    public function update(Request $request, $id)
    {
        $teacher = Teacher::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            ...$this->getUpdateEmailValidationRules($teacher->user_id),
            'phone' => 'nullable|string|max:20',
            'qualification' => 'required|string|max:500',
            'experience_years' => 'required|integer|min:0|max:50',
            'salary' => 'required|numeric|min:0',
            'joining_date' => 'required|date',
            'address' => 'nullable|string|max:1000',
            'emergency_contact' => 'nullable|string|max:20',
            'blood_group' => 'nullable|string|max:5',
            ...$this->getDocumentFileValidationRules('documents.*'),
            'subjects' => 'nullable|array',
            'subjects.*' => 'exists:subjects,id',
            'status' => 'required|in:active,inactive,suspended',
        ], array_merge(
            $this->getEmailValidationMessages(),
            $this->getFileUploadValidationMessages()
        ));

        try {
            DB::beginTransaction();

            // Update user account
            $teacher->user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'status' => $validated['status'] ?? 'active',
            ]);

            // Handle document uploads with security measures
            $documents = $teacher->documents ?? [];
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $key => $file) {
                    // Delete old document if exists
                    if (isset($documents[$key])) {
                        // Handle both old string format and new array format
                        $oldPath = is_array($documents[$key]) ? $documents[$key]['path'] : $documents[$key];
                        Storage::disk('public')->delete($oldPath);
                    }
                    
                    // Generate secure filename to prevent directory traversal and overwrite attacks
                    $extension = strtolower($file->getClientOriginalExtension());
                    $secureFilename = 'teacher_' . $teacher->id . '_' . time() . '_' . Str::random(10) . '.' . $extension;
                    
                    // Store with secure filename
                    $path = $file->storeAs('teachers/documents', $secureFilename, 'public');
                    $documents[$key] = [
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'uploaded_at' => now()
                    ];
                }
            }

            // Update teacher record
            $teacher->update([
                'qualification' => $validated['qualification'],
                'experience_years' => $validated['experience_years'],
                'salary' => $validated['salary'],
                'joining_date' => $validated['joining_date'],
                'documents' => $documents,
            ]);

            // Update subjects if provided
            if (isset($validated['subjects'])) {
                $teacher->subjects()->sync($validated['subjects']);
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Teacher updated successfully',
                    'teacher' => $teacher->load('user', 'classes')
                ]);
            }

            return redirect()->route('teachers.index')
                           ->with('success', 'Teacher updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson()) {
                return response()->json(
                    UserFriendlyErrorService::jsonErrorResponse($e, 'teacher_update'),
                    500
                );
            }

            return back()->withInput()
                        ->withErrors(['error' => UserFriendlyErrorService::getErrorMessage($e, 'teacher_update')]);
        }
    }

    /**
     * Remove the specified teacher
     */
    public function destroy($id)
    {
        $teacher = Teacher::findOrFail($id);

        try {
            DB::beginTransaction();

            // Delete associated documents
            if ($teacher->documents) {
                foreach ($teacher->documents as $document) {
                    Storage::disk('public')->delete($document);
                }
            }

            // Soft delete or deactivate instead of hard delete to maintain data integrity
            $teacher->user->update(['status' => 'inactive']);
            
            // Or if you want to actually delete:
            // $teacher->user->delete();
            // $teacher->delete();

            DB::commit();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Teacher deactivated successfully'
                ]);
            }

            return redirect()->route('teachers.index')
                           ->with('success', 'Teacher deactivated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if (request()->expectsJson()) {
                return response()->json(
                    UserFriendlyErrorService::jsonErrorResponse($e, 'teacher_delete'),
                    500
                );
            }

            return back()->withErrors(['error' => UserFriendlyErrorService::getErrorMessage($e, 'teacher_delete')]);
        }
    }

    /**
     * Get teachers for AJAX requests
     */
    public function getTeachers(Request $request)
    {
        $query = Teacher::with('user');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', SecurityHelper::buildLikePattern($search));
            });
        }

        if ($request->filled('subject_id')) {
            $query->whereHas('subjects', function($q) use ($request) {
                $q->where('subjects.id', $request->subject_id);
            });
        }

        $teachers = $query->limit(20)->get();

        return response()->json([
            'success' => true,
            'teachers' => $teachers
        ]);
    }

    /**
     * Export teachers data
     */
    public function export(Request $request)
    {
        // Implementation for exporting teachers data (CSV, PDF, etc.)
        // This would be implemented based on requirements
        
        return response()->json([
            'success' => true,
            'message' => 'Export functionality to be implemented'
        ]);
    }
}
