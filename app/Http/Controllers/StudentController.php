<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentRequest;
use App\Http\Traits\FileUploadValidationTrait;
use App\Http\Traits\StudentValidationTrait;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\StudentVerification;
use App\Models\User;
use App\Models\Role;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\SavedSearch;
use App\Services\AadhaarVerificationService;
use App\Services\BirthCertificateOCRService;
use App\Services\UserFriendlyErrorService;
use App\Services\StudentSearchService;
use App\Traits\HandlesApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    use HandlesApiResponses, FileUploadValidationTrait, StudentValidationTrait;

    protected $studentSearchService;

    public function __construct(StudentSearchService $searchService)
    {
        $this->studentSearchService = $searchService;
    }

    // GET /api/students or /students - Enhanced with comprehensive search
    /**
     * Display a listing of students with comprehensive search and filters
     */
    public function index(Request $request)
    {
        try {
            // Use the search service to get filtered students with eager loading
            $query = $this->studentSearchService->search($request);
            
            // Apply sorting
            $query = $this->studentSearchService->applySorting($query, $request);
            
            // Paginate results
            $students = $query->paginate(25);
            
            // Calculate attendance percentages and derived fields to prevent N+1 queries
            $students->getCollection()->transform(function ($student) {
                $student->attendance_percentage = $student->total_attendance_count > 0
                    ? round(($student->present_count / $student->total_attendance_count) * 100, 2)
                    : 0;
                
                // Calculate fee balance
                $student->fee_balance = ($student->total_fees_sum ?? 0) - ($student->paid_fees_sum ?? 0);
                
                return $student;
            });
            
            // Get filter options and search statistics
            $filterOptions = $this->studentSearchService->getFilterOptions();
            $searchStats = $this->studentSearchService->getSearchStats($query->getQuery());
            
            return view('students.index', compact('students', 'filterOptions', 'searchStats'));
            
        } catch (\Exception $e) {
            Log::error('Student index error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                           ->with('error', 'An error occurred while loading students. Please try again.');
        }
    }

    /**
     * Advanced search with export functionality
     */
    public function advancedSearch(Request $request)
    {
        $request->validate([
            'export_format' => 'nullable|in:csv,excel,pdf',
            'export_fields' => 'nullable|array',
            'export_fields.*' => 'string|in:admission_no,name,father_name,mother_name,dob,gender,aadhaar,class,status,contact_number,email,address'
        ]);

        $results = $this->studentSearchService->search($request);
        
        if ($request->filled('export_format')) {
            return $this->studentSearchService->exportResults(
                $results, 
                $request->export_format, 
                $request->export_fields ?? []
            );
        }

        $filterOptions = $this->studentSearchService->getFilterOptions();
        $searchStats = $this->studentSearchService->getSearchStatistics($request);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'students' => $results,
                'filter_options' => $filterOptions,
                'search_stats' => $searchStats
            ]);
        }

        return view('students.advanced-search', compact('results', 'filterOptions', 'searchStats'));
    }

    /**
     * Save current search as a saved search
     */
    public function saveSearch(Request $request)
    {
        $request->validate($this->getStudentSearchValidationRules());

        try {
            $savedSearch = SavedSearch::createFromRequest(
                $request, 
                Auth::id(), 
                $request->name, 
                $request->description
            );

            if ($request->is_public) {
                $savedSearch->update(['is_public' => true]);
            }

            if ($request->is_default) {
                $savedSearch->setAsDefault();
            }

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Search saved successfully',
                    'saved_search' => $savedSearch
                ]);
            }

            return back()->with('success', 'Search "' . $savedSearch->name . '" saved successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to save search', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request' => $request->all()
            ]);

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to save search'
                ], 500);
            }

            return back()->with('error', 'Failed to save search. Please try again.');
        }
    }

    /**
     * Get user's saved searches
     */
    public function getSavedSearches(Request $request)
    {
        $searches = SavedSearch::forUser(Auth::id())
                              ->byType('student')
                              ->orderBy('last_used_at', 'desc')
                              ->get();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json($searches);
        }

        return view('students.saved-searches', compact('searches'));
    }

    /**
     * Delete a saved search
     */
    public function deleteSavedSearch(Request $request, SavedSearch $savedSearch)
    {
        // Check if user owns the search or is admin
        if ($savedSearch->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            return back()->with('error', 'You are not authorized to delete this search.');
        }

        $searchName = $savedSearch->name;
        $savedSearch->delete();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Search deleted successfully']);
        }

        return back()->with('success', 'Search "' . $searchName . '" deleted successfully.');
    }

    /**
     * Get search suggestions based on partial input
     */
    /**
 * Get search suggestions based on partial input - SECURE VERSION
 */
/**
 * Get search suggestions based on partial input - SECURE VERSION
 */
public function getSearchSuggestions(Request $request)
{
    $request->validate([
        'query' => 'required|string|min:2|max:100',
        'field' => 'required|string|in:name,admission_no,father_name,mother_name,contact_number,email,aadhaar,address'
    ]);

    // Field whitelist for extra security
    $allowedFields = ['name', 'admission_no', 'father_name', 'mother_name', 'contact_number', 'email', 'aadhaar', 'address'];
    $field = in_array($request->field, $allowedFields) ? $request->field : 'name';
    $query = $request->query;

    // Use parameter binding to prevent SQL injection
    $suggestions = Student::select($field)
                         ->where($field, 'LIKE', "%{$query}%")
                         ->whereNotNull($field)
                         ->where($field, '!=', '')
                         ->distinct()
                         ->limit(10)
                         ->pluck($field)
                         ->toArray();

    return response()->json($suggestions);
}
    /**
     * Get filter statistics for dashboard
     */
    public function getFilterStats(Request $request)
    {
        $stats = [
            'total_students' => Student::count(),
            'by_status' => Student::select('status', DB::raw('count(*) as count'))
                                 ->groupBy('status')
                                 ->pluck('count', 'status'),
            'by_class' => Student::join('class_models', 'students.class_id', '=', 'class_models.id')
                                ->select('class_models.name', 'class_models.section', DB::raw('count(*) as count'))
                                ->groupBy('class_models.id', 'class_models.name', 'class_models.section')
                                ->get()
                                ->map(function($item) {
                                    return [
                                        'class' => $item->name . ' - ' . $item->section,
                                        'count' => $item->count
                                    ];
                                }),
            'by_gender' => Student::select('gender', DB::raw('count(*) as count'))
                                 ->whereNotNull('gender')
                                 ->groupBy('gender')
                                 ->pluck('count', 'gender'),
            'verification_status' => Student::select('verification_status', DB::raw('count(*) as count'))
                                          ->groupBy('verification_status')
                                          ->pluck('count', 'verification_status'),
            'recent_admissions' => Student::where('created_at', '>=', now()->subDays(30))->count(),
            'pending_verifications' => Student::where('verification_status', 'pending')->count()
        ];

        return response()->json($stats);
    }

    // POST /api/students
    public function store(StoreStudentRequest $request)
    {
        return $this->handleWithTransaction(function() use ($request) {
            // Get validated data from the request
            $data = $request->validated();

            // Generate admission number if not provided
            if (empty($data['admission_no'])) {
                $data['admission_no'] = $this->generateAdmissionNumber();
            }

            // Combine first and last name
            $data['name'] = trim($data['first_name'] . ' ' . $data['last_name']);
            
            // Map form fields to database fields
            $studentData = [
                'admission_no' => $data['admission_no'],
                'name' => $data['name'],
                'father_name' => $data['father_name'] ?? null,
                'mother_name' => $data['mother_name'] ?? null,
                'dob' => $data['date_of_birth'] ?? null,
                'gender' => $data['gender'] ?? null,
                'aadhaar' => $data['aadhaar'] ?? null,
                'class_id' => $data['class'] ?? null,
                'roll_number' => $data['roll_number'] ?? null,
                'contact_number' => $data['contact_number'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'status' => $data['status'] ?? 'active',
                'meta' => $data['meta'] ?? [],
            ];

            // Handle secure file uploads
            $documents = [];
            $uploadErrors = [];

            if ($request->hasFile('birth_cert')) {
                try {
                    $documents['birth_cert'] = $this->storeFileSecurely($request->file('birth_cert'), 'students/documents');
                } catch (\Exception $e) {
                    $uploadErrors[] = UserFriendlyErrorService::getErrorMessage($e, 'document_upload');
                }
            }

            if ($request->hasFile('aadhaar_file')) {
                try {
                    $documents['aadhaar'] = $this->storeFileSecurely($request->file('aadhaar_file'), 'students/documents');
                } catch (\Exception $e) {
                    $uploadErrors[] = UserFriendlyErrorService::getErrorMessage($e, 'document_upload');
                }
            }

            if ($request->hasFile('other_docs')) {
                $other = [];
                foreach ($request->file('other_docs') as $index => $file) {
                    try {
                        $other[] = $this->storeFileSecurely($file, 'students/documents');
                    } catch (\Exception $e) {
                        $uploadErrors[] = "Document " . ($index + 1) . ": " . UserFriendlyErrorService::getErrorMessage($e, 'document_upload');
                    }
                }
                if (!empty($other)) {
                    $documents['other'] = $other;
                }
            }

            // If there are upload errors, return with errors
            if (!empty($uploadErrors)) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    return $this->jsonErrorResponse(new \Exception('File upload failed'), 'document_upload', 422);
                }
                return back()->withErrors($uploadErrors)->withInput();
            }

            $studentData['documents'] = $documents;
            $studentData['verification_status'] = 'pending';

            // Create student record
            $student = Student::create($studentData);

            // Log the creation
            \Log::info('Student created successfully', [
                'student_id' => $student->id,
                'admission_no' => $student->admission_no,
                'name' => $student->name,
                'created_by' => auth()->id() ?? 'system'
            ]);

            if ($request->expectsJson() || $request->is('api/*')) {
                return $this->jsonSuccessResponse('student_create', $student, 201);
            }
            
            return redirect()->route('students.index')->with('success', 'Student "' . $student->name . '" has been successfully registered with admission number: ' . $student->admission_no);

        }, $request, 'student_create');
    }

    // GET /api/students/{id} or /students/{id}
    public function show(Request $request, Student $student)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json($student);
        }
        
        return view('students.show', compact('student'));
    }

    // PUT /api/students/{id}
    public function update(Request $request, Student $student)
    {
        $data = $request->validate([
            'admission_no' => ['nullable','string', Rule::unique('students','admission_no')->ignore($student->id)],
            'name' => 'sometimes|required|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'dob' => 'nullable|date',
            'aadhaar' => ['nullable','string', Rule::unique('students','aadhaar')->ignore($student->id)],
            'class_id' => 'nullable|integer|exists:class_models,id',
            'status' => ['nullable', Rule::in(['active','left','alumni'])],
            'meta' => 'nullable|array',
        ]);

        // files - optional replace
        $documents = $student->documents ?? [];

        if ($request->hasFile('birth_cert')) {
            if(isset($documents['birth_cert'])) Storage::delete($documents['birth_cert']);
            $documents['birth_cert'] = $this->storeFile($request->file('birth_cert'), 'students/documents');
        }
        if ($request->hasFile('aadhaar_file')) {
            if(isset($documents['aadhaar'])) Storage::delete($documents['aadhaar']);
            $documents['aadhaar'] = $this->storeFile($request->file('aadhaar_file'), 'students/documents');
        }
        if ($request->hasFile('other_docs')) {
            $other = $documents['other'] ?? [];
            foreach ($request->file('other_docs') as $f) {
                $other[] = $this->storeFile($f, 'students/documents');
            }
            $documents['other'] = $other;
        }

        $student->update(array_merge($data, ['documents' => $documents]));

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json($student);
        }
        
        return redirect()->route('students.index')->with('success', 'Student updated successfully');
    }

    // DELETE /api/students/{id}
    public function destroy(Request $request, Student $student)
    {
        if ($student->documents) {
            foreach ($student->documents as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $p) Storage::delete($p);
                } else {
                    Storage::delete($v);
                }
            }
        }
        $student->delete();
        
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Deleted']);
        }
        
        return redirect()->route('students.index')->with('success', 'Student deleted successfully');
    }

    // ... rest of your methods (verify, storeFile, normalize) remain the same ...
    // POST /api/students/{id}/verify
    public function verify(Request $request, Student $student)
    {
        $payload = $request->validate($this->getStudentVerificationValidationRules());

        $verifiedData = $payload['verified_data'];

        $mismatches = [];
        $fieldsToCheck = ['name','father_name','mother_name','dob','aadhaar'];

        foreach ($fieldsToCheck as $f) {
            if (isset($verifiedData[$f])) {
                $left = $this->normalize($student->$f);
                $right = $this->normalize($verifiedData[$f]);
                if ($left !== null && $right !== null && $left !== $right) {
                    $mismatches[$f] = ['student' => $student->$f, 'doc' => $verifiedData[$f]];
                }
            }
        }

        if (count($mismatches) === 0 || ($payload['force'] ?? false)) {
            $student->update([
                'documents_verified_data' => $verifiedData,
                'verification_status' => 'verified'
            ]);
            return response()->json(['status' => 'verified', 'mismatches' => $mismatches]);
        }

        $student->update([
            'documents_verified_data' => $verifiedData,
            'verification_status' => 'mismatch'
        ]);

        return response()->json(['status' => 'mismatch', 'mismatches' => $mismatches], 422);
    }

    // helper - store a file and return path
    protected function storeFile($file, $dir)
    {
        $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '-' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs($dir, $filename, 'public');
        return $path;
    }

    // Enhanced secure file storage method
    protected function storeFileSecurely($file, $dir)
    {
        // Use enhanced file validation with virus scanning
        $validationResult = $this->validateFileWithSecurity($file, 'document');
        
        if (!$validationResult['valid']) {
            throw new \Exception($validationResult['error']);
        }
        
        // Generate secure filename
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $sanitizedName = Str::slug($originalName);
        $timestamp = time();
        $randomString = Str::random(8);
        $filename = "{$sanitizedName}-{$timestamp}-{$randomString}.{$extension}";
        
        // Ensure directory exists
        $fullDir = storage_path("app/public/{$dir}");
        if (!file_exists($fullDir)) {
            mkdir($fullDir, 0755, true);
        }
        
        // Store file
        $path = $file->storeAs($dir, $filename, 'public');
        
        if (!$path) {
            throw new \Exception('Failed to store file.');
        }
        
        return $path;
    }

    // Generate unique admission number
    protected function generateAdmissionNumber()
    {
        $year = date('Y');
        $prefix = 'PNS' . substr($year, -2);
        
        // Get the last admission number for this year
        $lastStudent = Student::where('admission_no', 'LIKE', $prefix . '%')
                             ->orderBy('admission_no', 'desc')
                             ->first();
        
        if ($lastStudent) {
            // Extract the number part and increment
            $lastNumber = intval(substr($lastStudent->admission_no, -4));
            $newNumber = $lastNumber + 1;
        } else {
            // Start from 1 for the first student of the year
            $newNumber = 1;
        }
        
        // Format with leading zeros
        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    protected function normalize($value)
    {
        if ($value === null) return null;
        return mb_strtolower(preg_replace('/\s+/', ' ', trim((string)$value)));
    }

    /**
     * Get classes for dropdown
     */
    public function getClasses()
    {
        $classes = ClassModel::select('id', 'name', 'section')
            ->where('is_active', true)
            ->orderBy('name')
            ->orderBy('section')
            ->get();
        
        return response()->json($classes);
    }

    /**
     * Show the form for creating a new student
     */
    public function create()
    {
        $classes = ClassModel::select('id', 'name', 'section')
            ->where('is_active', true)
            ->orderBy('name')
            ->orderBy('section')
            ->get();
        
        return view('students.create', compact('classes'));
    }

    /**
     * Bulk mark attendance for multiple students
     */
    public function bulkAttendance(Request $request)
    {
        try {
            $request->validate([
                'student_ids' => 'required|array|min:1',
                'student_ids.*' => 'exists:students,id',
                'date' => 'required|date',
                'status' => 'required|in:present,absent,late,excused',
                'remarks' => 'nullable|string|max:500'
            ]);

            $date = $request->date;
            $status = $request->status;
            $remarks = $request->remarks;
            $studentIds = $request->student_ids;

            $attendanceRecords = [];
            $updatedCount = 0;

            // Use database transaction with proper locking to prevent race conditions
            DB::transaction(function () use ($date, $status, $remarks, $studentIds, &$attendanceRecords, &$updatedCount) {
                // Lock the attendance table for the specific date to prevent race conditions
                $existingAttendances = Attendance::whereIn('student_id', $studentIds)
                    ->where('date', $date)
                    ->lockForUpdate() // Database-level row locking
                    ->get()
                    ->keyBy('student_id');

                foreach ($studentIds as $studentId) {
                    if ($existingAttendances->has($studentId)) {
                        // Update existing record
                        $existingAttendances[$studentId]->update([
                            'status' => $status,
                            'remarks' => $remarks,
                            'marked_by' => auth()->id()
                        ]);
                        $updatedCount++;
                    } else {
                        // Create new record
                        $attendanceRecords[] = [
                            'student_id' => $studentId,
                            'date' => $date,
                            'status' => $status,
                            'remarks' => $remarks,
                            'marked_by' => auth()->id(),
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    }
                }

                // Bulk insert new records with duplicate handling
                if (!empty($attendanceRecords)) {
                    try {
                        Attendance::insert($attendanceRecords);
                    } catch (\Illuminate\Database\QueryException $e) {
                        // Handle unique constraint violations gracefully
                        if ($e->getCode() === '23000') { // Integrity constraint violation
                            // If duplicate entries are detected, use updateOrCreate as fallback
                            foreach ($attendanceRecords as $attendance) {
                                Attendance::updateOrCreate(
                                    [
                                        'student_id' => $attendance['student_id'],
                                        'date' => $attendance['date']
                                    ],
                                    [
                                        'status' => $attendance['status'],
                                        'remarks' => $attendance['remarks'],
                                        'marked_by' => $attendance['marked_by']
                                    ]
                                );
                            }
                        } else {
                            throw $e; // Re-throw if it's not a duplicate key error
                        }
                    }
                }
            });

            $totalProcessed = count($attendanceRecords) + $updatedCount;

            \Log::info('Bulk attendance marked', [
                'date' => $date,
                'status' => $status,
                'students_processed' => $totalProcessed,
                'marked_by' => auth()->id()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Attendance marked for {$totalProcessed} students",
                    'processed_count' => $totalProcessed
                ]);
            }

            return back()->with('success', "Attendance marked for {$totalProcessed} students");

        } catch (\Exception $e) {
            \Log::error('Bulk attendance marking failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to mark attendance: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to mark attendance: ' . $e->getMessage());
        }
    }

    /**
     * Bulk fee collection for multiple students
     */
    public function bulkFeeCollection(Request $request)
    {
        try {
            $request->validate([
                'student_ids' => 'required|array|min:1',
                'student_ids.*' => 'exists:students,id',
                'fee_type' => 'required|string|max:100',
                'amount' => 'required|numeric|min:0',
                'payment_method' => 'required|in:cash,online,cheque,dd',
                'payment_date' => 'required|date',
                'remarks' => 'nullable|string|max:500',
                'receipt_prefix' => 'nullable|string|max:10'
            ]);

            $studentIds = $request->student_ids;
            $feeType = $request->fee_type;
            $amount = $request->amount;
            $paymentMethod = $request->payment_method;
            $paymentDate = $request->payment_date;
            $remarks = $request->remarks;
            $receiptPrefix = $request->receipt_prefix ?? 'FEE';

            $feeRecords = [];
            $receiptNumbers = [];

            foreach ($studentIds as $index => $studentId) {
                $receiptNumber = $receiptPrefix . date('Ymd') . str_pad($index + 1, 4, '0', STR_PAD_LEFT);
                
                $feeRecords[] = [
                    'student_id' => $studentId,
                    'fee_type' => $feeType,
                    'amount' => $amount,
                    'payment_method' => $paymentMethod,
                    'payment_date' => $paymentDate,
                    'receipt_number' => $receiptNumber,
                    'status' => 'paid',
                    'remarks' => $remarks,
                    'collected_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                $receiptNumbers[] = $receiptNumber;
            }

            // Bulk insert fee records
            FeePayment::insert($feeRecords);

            $totalAmount = $amount * count($studentIds);

            \Log::info('Bulk fee collection completed', [
                'fee_type' => $feeType,
                'students_count' => count($studentIds),
                'total_amount' => $totalAmount,
                'collected_by' => auth()->id()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Fee collected for " . count($studentIds) . " students",
                    'total_amount' => $totalAmount,
                    'receipt_numbers' => $receiptNumbers
                ]);
            }

            return back()->with('success', "Fee collected for " . count($studentIds) . " students. Total amount: ₹{$totalAmount}");

        } catch (\Exception $e) {
            \Log::error('Bulk fee collection failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->except(['student_ids'])
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to collect fees: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to collect fees: ' . $e->getMessage());
        }
    }

    /**
     * Bulk document upload for multiple students
     */
    public function bulkDocumentUpload(Request $request)
    {
        try {
            // Use enhanced validation rules from trait
            $validationRules = [
                'student_ids' => 'required|array|min:1',
                'student_ids.*' => 'exists:students,id',
                'document_type' => 'required|in:birth_cert,aadhaar,photo,other',
                'documents' => 'required|array|min:1',
                'document_names' => 'nullable|array',
                'document_names.*' => 'nullable|string|max:255'
            ];
            
            // Add file validation rules from trait
            $fileRules = $this->getMultipleFilesValidationRules(['pdf', 'jpg', 'jpeg', 'png'], 5120, 50);
            $validationRules = array_merge($validationRules, [
                'documents.*' => $fileRules['files.*']
            ]);

            $request->validate($validationRules, $this->getFileUploadValidationMessages());

            $studentIds = $request->student_ids;
            $documentType = $request->document_type;
            $documents = $request->file('documents');
            $documentNames = $request->document_names ?? [];

            if (count($documents) !== count($studentIds)) {
                throw new \Exception('Number of documents must match number of students');
            }

            $uploadedFiles = [];
            $updatedStudents = 0;
            $validationErrors = [];

            foreach ($studentIds as $index => $studentId) {
                $student = Student::find($studentId);
                if (!$student) continue;

                $document = $documents[$index];
                
                // Use enhanced file validation with virus scanning
                $validationResult = $this->validateFileWithSecurity($document, 'document');
                
                if (!$validationResult['valid']) {
                    $validationErrors[] = "Student {$student->name}: {$validationResult['error']}";
                    continue;
                }

                $customName = $documentNames[$index] ?? null;

                // Generate file name
                $fileName = $customName ?: ($documentType . '_' . $student->admission_no . '_' . time());
                $fileName .= '.' . $document->getClientOriginalExtension();

                // Store file
                $path = $document->storeAs('student_documents/' . $student->id, $fileName, 'public');

                // Update student documents
                $studentDocuments = $student->documents ?? [];
                
                if ($documentType === 'other') {
                    $studentDocuments['other_docs'][] = $path;
                } else {
                    $studentDocuments[$documentType] = $path;
                }

                $student->update(['documents' => $studentDocuments]);

                $uploadedFiles[] = [
                    'student_id' => $studentId,
                    'student_name' => $student->name,
                    'document_type' => $documentType,
                    'file_path' => $path,
                    'file_name' => $fileName
                ];

                $updatedStudents++;
            }
            
            // If there were validation errors, include them in the response
            if (!empty($validationErrors)) {
                $errorMessage = "Some files failed validation: " . implode(', ', $validationErrors);
                if ($updatedStudents === 0) {
                    throw new \Exception($errorMessage);
                }
                // Log warnings for partial success
                \Log::warning('Bulk document upload had validation errors', [
                    'errors' => $validationErrors,
                    'successful_uploads' => $updatedStudents
                ]);
            }

            \Log::info('Bulk document upload completed', [
                'document_type' => $documentType,
                'students_count' => $updatedStudents,
                'uploaded_by' => auth()->id()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Documents uploaded for {$updatedStudents} students",
                    'uploaded_files' => $uploadedFiles
                ]);
            }

            return back()->with('success', "Documents uploaded for {$updatedStudents} students");

        } catch (\Exception $e) {
            \Log::error('Bulk document upload failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->except(['documents'])
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to upload documents: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to upload documents: ' . $e->getMessage());
        }
    }

    /**
     * Bulk status update for multiple students
     */
    public function bulkStatusUpdate(Request $request)
    {
        try {
            $request->validate([
                'student_ids' => 'required|array|min:1',
                'student_ids.*' => 'exists:students,id',
                'status' => 'required|in:active,inactive,left,alumni,suspended',
                'reason' => 'nullable|string|max:500',
                'effective_date' => 'nullable|date'
            ]);

            $studentIds = $request->student_ids;
            $status = $request->status;
            $reason = $request->reason;
            $effectiveDate = $request->effective_date ?? now()->toDateString();

            // Update students
            $updatedCount = Student::whereIn('id', $studentIds)
                ->update([
                    'status' => $status,
                    'updated_at' => now()
                ]);

            // Log status changes for audit trail
            foreach ($studentIds as $studentId) {
                $student = Student::find($studentId);
                if ($student) {
                    \Log::info('Student status updated', [
                        'student_id' => $studentId,
                        'student_name' => $student->name,
                        'admission_no' => $student->admission_no,
                        'old_status' => $student->getOriginal('status'),
                        'new_status' => $status,
                        'reason' => $reason,
                        'effective_date' => $effectiveDate,
                        'updated_by' => auth()->id()
                    ]);
                }
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Status updated for {$updatedCount} students",
                    'updated_count' => $updatedCount,
                    'new_status' => $status
                ]);
            }

            return back()->with('success', "Status updated to '{$status}' for {$updatedCount} students");

        } catch (\Exception $e) {
            \Log::error('Bulk status update failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update status: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to update status: ' . $e->getMessage());
        }
    }

    /**
     * Get bulk operation form
     */
    public function bulkOperationsForm(Request $request)
    {
        $operation = $request->get('operation', 'attendance');
        $studentIds = $request->get('student_ids', []);

        if (empty($studentIds)) {
            return back()->with('error', 'Please select at least one student');
        }

        $students = Student::whereIn('id', $studentIds)
            ->select('id', 'name', 'admission_no', 'class_id')
            ->with('class:id,name,section')
            ->get();

        $classes = ClassModel::select('id', 'name', 'section')
            ->where('is_active', true)
            ->orderBy('name')
            ->orderBy('section')
            ->get();

        return view('students.bulk_operations', compact('operation', 'students', 'classes'));
    }
}