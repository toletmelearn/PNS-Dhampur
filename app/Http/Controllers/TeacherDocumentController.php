<?php

namespace App\Http\Controllers;

use App\Models\TeacherDocument;
use App\Models\Teacher;
use App\Services\DocumentExpiryAlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Http\Traits\FileUploadValidationTrait;

class TeacherDocumentController extends Controller
{
    use FileUploadValidationTrait;
    protected $documentExpiryAlertService;

    public function __construct(DocumentExpiryAlertService $documentExpiryAlertService)
    {
        $this->documentExpiryAlertService = $documentExpiryAlertService;
        $this->middleware('auth');
        $this->middleware('role:admin,principal')->only(['approve', 'reject', 'bulkApprove', 'adminIndex']);
    }

    /**
     * Display documents for the authenticated teacher
     */
    public function index()
    {
        $user = auth()->user();
        
        if ($user->role === 'teacher') {
            $teacher = Teacher::with(['user', 'subjects', 'classModel'])->where('user_id', $user->id)->first();
            if (!$teacher) {
                return redirect()->back()->with('error', 'Teacher profile not found.');
            }
            
            $documents = $teacher->teacherDocuments()
                               ->orderBy('created_at', 'desc')
                               ->paginate(10);
        } else {
            // Admin/Principal view - show all documents
            $documents = TeacherDocument::with(['teacher.user', 'uploadedBy', 'reviewedBy'])
                                      ->orderBy('created_at', 'desc')
                                      ->paginate(15);
        }

        return view('teacher-documents.index', compact('documents'));
    }

    /**
     * Admin view for document management
     */
    public function adminIndex(Request $request)
    {
        $query = TeacherDocument::with(['teacher.user', 'uploadedBy', 'reviewedBy']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by document type
        if ($request->filled('document_type')) {
            $query->where('document_type', $request->document_type);
        }

        // Filter by expiring documents
        if ($request->filled('expiring')) {
            $query->expiringWithin(30);
        }

        // Search by teacher name
        if ($request->filled('search')) {
            $query->whereHas('teacher.user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }

        $documents = $query->orderBy('created_at', 'desc')->paginate(20);
        
        // Get statistics
        $stats = [
            'pending' => TeacherDocument::pending()->count(),
            'verified' => TeacherDocument::verified()->count(),
            'rejected' => TeacherDocument::rejected()->count(),
            'expiring' => TeacherDocument::expiringWithin(30)->count(),
        ];

        return view('teacher-documents.admin', compact('documents', 'stats'));
    }

    /**
     * Show the form for uploading documents
     */
    public function create()
    {
        $user = auth()->user();
        $teacher = Teacher::where('user_id', $user->id)->first();
        
        if (!$teacher) {
            return redirect()->back()->with('error', 'Teacher profile not found.');
        }

        $documentTypes = TeacherDocument::DOCUMENT_TYPES;
        
        return view('teacher-documents.create', compact('documentTypes', 'teacher'));
    }

    /**
     * Store uploaded documents
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $teacher = Teacher::where('user_id', $user->id)->first();
        
        if (!$teacher) {
            return response()->json(['error' => 'Teacher profile not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'documents' => 'required|array|max:10',
            'documents.*' => [
                'required',
                'file',
                'mimes:pdf,doc,docx,jpg,jpeg,png',
                'max:10240', // 10MB
                function ($attribute, $value, $fail) {
                    // Additional file validation
                    $allowedMimeTypes = [
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'image/jpeg',
                        'image/jpg',
                        'image/png'
                    ];
                    
                    if (!in_array($value->getMimeType(), $allowedMimeTypes)) {
                        $fail('The file must be a valid PDF, DOC, DOCX, JPG, JPEG, or PNG file.');
                    }
                    
                    // Check file signature (magic bytes) for additional security
                    $fileContent = file_get_contents($value->getPathname());
                    $fileSignature = bin2hex(substr($fileContent, 0, 4));
                    
                    $validSignatures = [
                        '25504446', // PDF
                        'd0cf11e0', // DOC/DOCX (OLE2)
                        '504b0304', // DOCX (ZIP-based)
                        'ffd8ffe0', // JPEG
                        'ffd8ffe1', // JPEG
                        'ffd8ffe2', // JPEG
                        'ffd8ffe3', // JPEG
                        'ffd8ffe8', // JPEG
                        'ffd8ffdb', // JPEG
                        '89504e47', // PNG
                    ];
                    
                    if (!in_array($fileSignature, $validSignatures)) {
                        $fail('The file appears to be corrupted or is not a valid document/image file.');
                    }
                }
            ],
            'document_types' => 'required|array',
            'document_types.*' => 'required|in:' . implode(',', array_keys(TeacherDocument::DOCUMENT_TYPES)),
            'expiry_dates' => 'array',
            'expiry_dates.*' => 'nullable|date|after:today',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $uploadedDocuments = [];
        $errors = [];

        foreach ($request->file('documents') as $index => $file) {
            try {
                $documentType = $request->document_types[$index];
                $expiryDate = $request->expiry_dates[$index] ?? null;

                // Check if document type already exists for this teacher
                $existingDoc = TeacherDocument::where('teacher_id', $teacher->id)
                                            ->where('document_type', $documentType)
                                            ->where('status', '!=', 'rejected')
                                            ->first();

                if ($existingDoc) {
                    $errors[] = "Document type '{$documentType}' already exists.";
                    continue;
                }

                // Additional file size validation (double-check)
                if ($file->getSize() > 10485760) { // 10MB in bytes
                    $errors[] = "File '{$file->getClientOriginalName()}' exceeds maximum size of 10MB.";
                    continue;
                }

                // Validate file extension matches MIME type
                $extension = strtolower($file->getClientOriginalExtension());
                $mimeType = $file->getMimeType();
                
                $validCombinations = [
                    'pdf' => ['application/pdf'],
                    'doc' => ['application/msword'],
                    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
                    'jpg' => ['image/jpeg'],
                    'jpeg' => ['image/jpeg'],
                    'png' => ['image/png']
                ];
                
                if (!isset($validCombinations[$extension]) || !in_array($mimeType, $validCombinations[$extension])) {
                    $errors[] = "File '{$file->getClientOriginalName()}' has an invalid file type or extension.";
                    continue;
                }

                // Generate unique filename
                $originalName = $file->getClientOriginalName();
                $filename = $teacher->id . '_' . $documentType . '_' . time() . '_' . Str::random(10) . '.' . $extension;
                
                // Store file
                $path = $file->storeAs('teacher-documents', $filename, 'public');

                // Create document record
                $document = TeacherDocument::create([
                    'teacher_id' => $teacher->id,
                    'document_type' => $documentType,
                    'original_name' => $originalName,
                    'file_path' => $path,
                    'file_extension' => $extension,
                    'file_size' => $file->getSize(),
                    'mime_type' => $mimeType,
                    'expiry_date' => $expiryDate,
                    'uploaded_by' => $user->id,
                    'metadata' => [
                        'upload_ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'file_hash' => hash_file('sha256', $file->getPathname()),
                    ]
                ]);

                $uploadedDocuments[] = $document;

            } catch (\Exception $e) {
                $errors[] = "Failed to upload {$file->getClientOriginalName()}: " . $e->getMessage();
            }
        }

        if (empty($uploadedDocuments) && !empty($errors)) {
            return response()->json(['error' => 'No documents were uploaded.', 'details' => $errors], 400);
        }

        $response = [
            'success' => true,
            'message' => count($uploadedDocuments) . ' document(s) uploaded successfully.',
            'documents' => $uploadedDocuments
        ];

        if (!empty($errors)) {
            $response['warnings'] = $errors;
        }

        return response()->json($response);
    }

    /**
     * Display the specified document
     */
    public function show(TeacherDocument $document)
    {
        $user = auth()->user();
        
        // Check permissions
        if ($user->role === 'teacher') {
            $teacher = Teacher::with(['user', 'subjects', 'classModel'])->where('user_id', $user->id)->first();
            if (!$teacher || $document->teacher_id !== $teacher->id) {
                abort(403, 'Unauthorized access to document.');
            }
        }

        return view('teacher-documents.show', compact('document'));
    }

    /**
     * Download document file
     */
    public function download(TeacherDocument $document)
    {
        $user = auth()->user();
        
        // Check permissions
        if ($user->role === 'teacher') {
            $teacher = Teacher::with(['user', 'subjects', 'classModel'])->where('user_id', $user->id)->first();
            if (!$teacher || $document->teacher_id !== $teacher->id) {
                abort(403, 'Unauthorized access to document.');
            }
        }

        if (!Storage::disk('public')->exists($document->file_path)) {
            return redirect()->back()->with('error', 'File not found.');
        }

        return Storage::disk('public')->download($document->file_path, $document->original_name);
    }

    /**
     * Approve a document (Admin/Principal only)
     */
    public function approve(Request $request, TeacherDocument $document)
    {
        $this->authorize('admin-access');

        $request->validate([
            'admin_comments' => 'nullable|string|max:1000'
        ]);

        $document->update([
            'status' => TeacherDocument::STATUS_VERIFIED,
            'admin_comments' => $request->admin_comments,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now()
        ]);

        // Log the approval
        \Log::info('Document approved', [
            'document_id' => $document->id,
            'teacher_id' => $document->teacher_id,
            'approved_by' => auth()->id(),
            'comments' => $request->admin_comments
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Document approved successfully.',
                'document' => $document->fresh()
            ]);
        }

        return redirect()->back()->with('success', 'Document approved successfully.');
    }

    /**
     * Reject a document (Admin/Principal only)
     */
    public function reject(Request $request, TeacherDocument $document)
    {
        $this->authorize('admin-access');

        $request->validate([
            'admin_comments' => 'required|string|max:1000'
        ]);

        $document->update([
            'status' => TeacherDocument::STATUS_REJECTED,
            'admin_comments' => $request->admin_comments,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now()
        ]);

        // Log the rejection
        \Log::info('Document rejected', [
            'document_id' => $document->id,
            'teacher_id' => $document->teacher_id,
            'rejected_by' => auth()->id(),
            'comments' => $request->admin_comments
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Document rejected successfully.',
                'document' => $document->fresh()
            ]);
        }

        return redirect()->back()->with('success', 'Document rejected successfully.');
    }

    /**
     * Bulk approve documents (Admin/Principal only)
     */
    public function bulkApprove(Request $request)
    {
        $this->authorize('admin-access');

        $request->validate([
            'document_ids' => 'required|array',
            'document_ids.*' => 'exists:teacher_documents,id',
            'admin_comments' => 'nullable|string|max:1000'
        ]);

        $documents = TeacherDocument::whereIn('id', $request->document_ids)
                                   ->where('status', TeacherDocument::STATUS_PENDING)
                                   ->get();

        $approvedCount = 0;
        foreach ($documents as $document) {
            $document->update([
                'status' => TeacherDocument::STATUS_VERIFIED,
                'admin_comments' => $request->admin_comments,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now()
            ]);
            $approvedCount++;
        }

        // Log bulk approval
        \Log::info('Bulk document approval', [
            'document_ids' => $request->document_ids,
            'approved_count' => $approvedCount,
            'approved_by' => auth()->id()
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "{$approvedCount} document(s) approved successfully.",
                'approved_count' => $approvedCount
            ]);
        }

        return redirect()->back()->with('success', "{$approvedCount} document(s) approved successfully.");
    }

    /**
     * Get expiring documents for alerts
     */
    public function getExpiringDocuments()
    {
        $this->authorize('admin-access');

        $expiringDocuments = TeacherDocument::with(['teacher.user'])
            ->where('status', TeacherDocument::STATUS_VERIFIED)
            ->where('expiry_date', '<=', Carbon::now()->addDays(30))
            ->whereNotNull('expiry_date')
            ->orderBy('expiry_date', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'documents' => $expiringDocuments->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'teacher_name' => $doc->teacher->user->name,
                    'document_type' => $doc->document_type,
                    'expiry_date' => $doc->expiry_date->format('Y-m-d'),
                    'days_until_expiry' => $doc->expiry_date->diffInDays(Carbon::now()),
                    'is_expired' => $doc->expiry_date->isPast()
                ];
            })
        ]);
    }

    /**
     * Delete a document
     */
    public function destroy(TeacherDocument $document)
    {
        $user = auth()->user();
        
        // Check permissions
        if ($user->role === 'teacher') {
            $teacher = Teacher::with(['user', 'subjects', 'classModel'])->where('user_id', $user->id)->first();
            if (!$teacher || $document->teacher_id !== $teacher->id) {
                abort(403, 'Unauthorized access to document.');
            }
            
            // Teachers can only delete pending documents
            if ($document->status !== 'pending') {
                return response()->json(['error' => 'Cannot delete approved/rejected documents.'], 403);
            }
        }

        $document->delete();

        return response()->json([
            'success' => true,
            'message' => 'Document deleted successfully.'
        ]);
    }

    /**
     * Handle bulk actions (approve/reject) for multiple documents
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'document_ids' => 'required|string',
            'action' => 'required|in:approve,reject',
            'admin_comments' => 'nullable|string|max:1000'
        ]);

        $documentIds = explode(',', $request->document_ids);
        $action = $request->action;
        $comments = $request->admin_comments;

        $documents = TeacherDocument::whereIn('id', $documentIds)
            ->where('status', TeacherDocument::STATUS_PENDING)
            ->get();

        if ($documents->isEmpty()) {
            return redirect()->back()->with('error', 'No pending documents found for bulk action.');
        }

        $status = $action === 'approve' ? TeacherDocument::STATUS_VERIFIED : TeacherDocument::STATUS_REJECTED;
        $successCount = 0;

        foreach ($documents as $document) {
            $document->update([
                'status' => $status,
                'admin_comments' => $comments,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now()
            ]);
            $successCount++;
        }

        $actionText = $action === 'approve' ? 'approved' : 'rejected';
        return redirect()->back()->with('success', "{$successCount} documents have been {$actionText} successfully.");
    }

    public function bulkUpload()
    {
        return view('teacher-documents.bulk-upload');
    }

    public function storeBulkUpload(Request $request)
    {
        $user = auth()->user();
        $teacher = Teacher::where('user_id', $user->id)->first();
        
        if (!$teacher) {
            return response()->json(['error' => 'Teacher profile not found.'], 404);
        }

        // Use enhanced validation from trait
        $validationRules = [
            'document_types' => 'required|array',
            'document_types.*' => 'required|in:' . implode(',', array_keys(TeacherDocument::DOCUMENT_TYPES)),
            'expiry_dates' => 'nullable|array',
            'expiry_dates.*' => 'nullable|date|after:today'
        ];
        
        // Add enhanced file validation rules from trait
        $fileRules = $this->getMultipleFilesValidationRules(['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'], 10240, 10);
        $validationRules = array_merge($validationRules, [
            'documents' => $fileRules['files'],
            'documents.*' => $fileRules['files.*']
        ]);

        $validator = Validator::make($request->all(), $validationRules, $this->getFileUploadValidationMessages());

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $uploadedDocuments = [];
        $errors = [];

        foreach ($request->file('documents') as $index => $file) {
            try {
                // Perform enhanced file validation with virus scanning
                $validationResult = $this->validateFileWithSecurity($file);
                if (!$validationResult['valid']) {
                    $errors[] = "File '{$file->getClientOriginalName()}': " . $validationResult['message'];
                    continue;
                }

                $documentType = $request->document_types[$index];
                $expiryDate = $request->expiry_dates[$index] ?? null;

                // Check if document type already exists for this teacher
                $existingDoc = TeacherDocument::where('teacher_id', $teacher->id)
                                            ->where('document_type', $documentType)
                                            ->where('status', '!=', 'rejected')
                                            ->first();

                if ($existingDoc) {
                    $errors[] = "Document type '{$documentType}' already exists.";
                    continue;
                }
                ];
                
                if (!isset($validCombinations[$extension]) || !in_array($mimeType, $validCombinations[$extension])) {
                    $errors[] = "File '{$file->getClientOriginalName()}' has an invalid file type or extension.";
                    continue;
                }

                // Generate unique filename
                $originalName = $file->getClientOriginalName();
                $filename = $teacher->id . '_' . $documentType . '_' . time() . '_' . $index . '_' . Str::random(10) . '.' . $extension;
                
                // Store file
                $path = $file->storeAs('teacher-documents', $filename, 'public');
                
                // Create document record
                $document = TeacherDocument::create([
                    'teacher_id' => $teacher->id,
                    'document_type' => $documentType,
                    'original_name' => $originalName,
                    'file_path' => $path,
                    'file_extension' => $extension,
                    'file_size' => $file->getSize(),
                    'mime_type' => $mimeType,
                    'expiry_date' => $expiryDate,
                    'uploaded_by' => $user->id,
                    'metadata' => [
                        'upload_ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'file_hash' => hash_file('sha256', $file->getPathname()),
                        'bulk_upload' => true,
                        'batch_index' => $index
                    ]
                ]);

                $uploadedDocuments[] = $document;
            } catch (\Exception $e) {
                $errors[] = "Failed to upload {$file->getClientOriginalName()}: " . $e->getMessage();
            }
        }

        if (empty($uploadedDocuments) && !empty($errors)) {
            return response()->json([
                'success' => false,
                'message' => 'No documents were uploaded.',
                'errors' => $errors
            ], 422);
        }

        $response = [
            'success' => true,
            'message' => 'Successfully uploaded ' . count($uploadedDocuments) . ' document(s).',
            'uploaded_count' => count($uploadedDocuments),
            'documents' => $uploadedDocuments
        ];

        if (!empty($errors)) {
            $response['warnings'] = $errors;
        }

        return response()->json($response);
    }

    /**
     * Check for expiring documents and send alerts
     */
    public function checkExpiringDocuments()
    {
        $expiringDocuments = TeacherDocument::expiringWithin(30)->get();
        
        foreach ($expiringDocuments as $document) {
            // Here you would typically send notifications
            // For now, we'll just mark them as expiring soon
            $document->checkExpiry();
        }

        return response()->json([
            'success' => true,
            'message' => 'Expiry check completed.',
            'expiring_count' => $expiringDocuments->count()
        ]);
    }

    /**
     * Get document expiry alerts dashboard
     */
    public function getExpiryAlerts()
    {
        try {
            $alerts = $this->documentExpiryAlertService->getExpiringDocuments();
            $statistics = $this->documentExpiryAlertService->getAlertStatistics();
            
            return response()->json([
                'success' => true,
                'alerts' => $alerts,
                'statistics' => $statistics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve expiry alerts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process and send document expiry alerts
     */
    public function processExpiryAlerts()
    {
        try {
            $result = $this->documentExpiryAlertService->processAlerts();
            
            return response()->json([
                'success' => true,
                'message' => 'Expiry alerts processed successfully.',
                'processed_count' => $result['processed_count'],
                'notifications_sent' => $result['notifications_sent']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process expiry alerts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show document expiry alerts dashboard
     */
    public function showExpiryAlerts()
    {
        $alerts = $this->documentExpiryAlertService->getExpiringDocuments();
        $statistics = $this->documentExpiryAlertService->getAlertStatistics();
        
        return view('teacher-documents.expiry-alerts', compact('alerts', 'statistics'));
    }

    /**
     * Get expiry alerts for a specific teacher
     */
    public function getTeacherExpiryAlerts($teacherId)
    {
        try {
            $alerts = $this->documentExpiryAlertService->getTeacherExpiringDocuments($teacherId);
            
            return response()->json([
                'success' => true,
                'alerts' => $alerts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve teacher expiry alerts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check document expiry alert service status
     */
    public function checkExpiryAlertService()
    {
        try {
            $status = $this->documentExpiryAlertService->checkServiceAvailability();
            
            return response()->json([
                'success' => true,
                'service_status' => $status
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Service check failed: ' . $e->getMessage()
            ], 500);
        }
    }
}