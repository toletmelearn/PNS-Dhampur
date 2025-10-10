<?php

namespace App\Http\Controllers;

use App\Models\TeacherDocument;
use App\Models\Teacher;
use App\Services\DocumentExpiryAlertService;
use App\Rules\SafeFileValidation;
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

    // Allowed file types with their MIME types
    private $allowedTypes = [
        'pdf'  => ['application/pdf'],
        'doc'  => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'jpg'  => ['image/jpeg', 'image/jpg'],
        'jpeg' => ['image/jpeg', 'image/jpg'],
        'png'  => ['image/png'],
    ];

    // Maximum file sizes (in KB)
    private $maxSizes = [
        'pdf'  => 5120,  // 5MB
        'doc'  => 5120,  // 5MB
        'docx' => 5120,  // 5MB
        'jpg'  => 2048,  // 2MB
        'jpeg' => 2048,  // 2MB
        'png'  => 2048,  // 2MB
    ];

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
     * Store uploaded documents with enhanced security
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $teacher = Teacher::where('user_id', $user->id)->first();
        
        if (!$teacher) {
            return response()->json(['error' => 'Teacher profile not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'documents' => 'required|array|max:5', // Reduced from 10 to 5 for security
            'documents.*' => [
                'required',
                'file',
                'mimes:pdf,doc,docx,jpg,jpeg,png',
                'max:5120', // 5MB
                new SafeFileValidation(),
                function ($attribute, $value, $fail) {
                    // Enhanced security checks
                    if (!$this->isFileSafe($value)) {
                        $fail('The file appears to be unsafe or malicious.');
                    }
                    
                    // Check file signature
                    if (!$this->validateFileSignature($value)) {
                        $fail('The file type does not match its content.');
                    }
                }
            ],
            'document_types' => 'required|array',
            'document_types.*' => 'required|in:' . implode(',', array_keys(TeacherDocument::DOCUMENT_TYPES)),
            'expiry_dates' => 'array',
            'expiry_dates.*' => 'nullable|date|after:today|before:+10 years',
        ], [
            'documents.max' => 'Maximum 5 documents allowed per upload.',
            'documents.*.max' => 'Each document must not exceed 5MB.',
            'expiry_dates.*.before' => 'Expiry date cannot be more than 10 years in the future.'
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

                // Enhanced malicious file detection
                if ($this->isFileMalicious($file)) {
                    $errors[] = "File '{$file->getClientOriginalName()}' appears to be malicious and was blocked.";
                    continue;
                }

                // Store file securely with enhanced security
                $filePath = $this->storeFileSecurely($file, $teacher->id, $documentType);

                // Create document record
                $document = TeacherDocument::create([
                    'teacher_id' => $teacher->id,
                    'document_type' => $documentType,
                    'original_name' => $file->getClientOriginalName(),
                    'file_path' => $filePath,
                    'file_extension' => $file->getClientOriginalExtension(),
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'expiry_date' => $expiryDate,
                    'uploaded_by' => $user->id,
                    'metadata' => [
                        'upload_ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'file_hash' => hash_file('sha256', $file->getPathname()),
                        'security_scan' => 'passed',
                        'file_signature_valid' => true
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

        if (!Storage::disk('private')->exists($document->file_path)) {
            return redirect()->back()->with('error', 'File not found.');
        }

        return Storage::disk('private')->download($document->file_path, $document->original_name);
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

        // Delete the physical file
        if (Storage::disk('private')->exists($document->file_path)) {
            Storage::disk('private')->delete($document->file_path);
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

        // Security check for bulk uploads
        $securityCheck = $this->validateBulkUploadSecurity($request->file('documents', []));
        if (!$securityCheck['valid']) {
            return response()->json(['error' => $securityCheck['error']], 422);
        }

        // Use enhanced validation from trait
        $validationRules = [
            'document_types' => 'required|array',
            'document_types.*' => 'required|in:' . implode(',', array_keys(TeacherDocument::DOCUMENT_TYPES)),
            'expiry_dates' => 'nullable|array',
            'expiry_dates.*' => 'nullable|date|after:today|before:+10 years'
        ];
        
        // Add enhanced file validation rules from trait
        $fileRules = $this->getMultipleFilesValidationRules(['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'], 5120, 5);
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

                // Enhanced malicious file detection
                if ($this->isFileMalicious($file)) {
                    $errors[] = "File '{$file->getClientOriginalName()}' appears to be malicious and was blocked.";
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

                // Store file securely with enhanced security
                $filePath = $this->storeFileSecurely($file, $teacher->id, $documentType);

                // Create document record
                $document = TeacherDocument::create([
                    'teacher_id' => $teacher->id,
                    'document_type' => $documentType,
                    'original_name' => $file->getClientOriginalName(),
                    'file_path' => $filePath,
                    'file_extension' => $file->getClientOriginalExtension(),
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'expiry_date' => $expiryDate,
                    'uploaded_by' => $user->id,
                    'metadata' => [
                        'upload_ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'file_hash' => hash_file('sha256', $file->getPathname()),
                        'bulk_upload' => true,
                        'batch_index' => $index,
                        'security_scan' => 'passed',
                        'file_signature_valid' => true
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
     * Enhanced malicious file detection
     */
    private function isFileMalicious($file)
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();
        
        // Check for dangerous extensions even if disguised
        $dangerousExtensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'phar', 'html', 'htm', 'js', 'exe', 'bat', 'cmd', 'sh', 'bash', 'py', 'pl'];
        if (in_array($extension, $dangerousExtensions)) {
            return true;
        }
        
        // Check for double extensions
        $originalName = $file->getClientOriginalName();
        if (preg_match('/\.(php|phtml|php3|php4|php5|phar|exe|bat|cmd|sh|bash|py|pl)/i', $originalName)) {
            return true;
        }
        
        // Check MIME type vs extension mismatch
        $validCombinations = [
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png']
        ];
        
        if (isset($validCombinations[$extension]) && !in_array($mimeType, $validCombinations[$extension])) {
            return true;
        }
        
        // Check file content for suspicious patterns
        return $this->containsSuspiciousContent($file);
    }

    /**
     * Check file content for malicious patterns
     */
    private function containsSuspiciousContent($file)
    {
        $suspiciousPatterns = [
            '/<\?php/i',
            '/<script/i',
            '/eval\(/i',
            '/base64_decode/i',
            '/gzinflate/i',
            '/system\(/i',
            '/exec\(/i',
            '/shell_exec/i',
            '/passthru/i',
            '/assert\(/i',
            '/preg_replace\(.*\/e/i'
        ];

        try {
            $content = file_get_contents($file->getPathname());
            // Only check first 8KB for performance
            $sample = substr($content, 0, 8192);
            
            foreach ($suspiciousPatterns as $pattern) {
                if (preg_match($pattern, $sample)) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            // If we can't read the file, treat it as suspicious
            return true;
        }
        
        return false;
    }

    /**
     * Enhanced secure file storage
     */
    private function storeFileSecurely($file, $teacherId, $documentType)
    {
        // Generate ultra-secure filename
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = strtolower($file->getClientOriginalExtension());
        
        // Sanitize filename aggressively
        $sanitizedName = preg_replace('/[^a-zA-Z0-9\-_]/', '', Str::slug($originalName));
        $timestamp = time();
        $randomString = Str::random(32); // Increased randomness
        $filename = "teacher_{$teacherId}_{$documentType}_{$timestamp}_{$randomString}.{$extension}";
        
        // Store with private visibility
        $path = $file->storeAs('teacher-documents', $filename, 'private');
        
        if (!$path) {
            throw new \Exception('Failed to store file securely.');
        }
        
        // Set proper file permissions (Unix systems)
        $fullPath = Storage::disk('private')->path($path);
        if (file_exists($fullPath)) {
            chmod($fullPath, 0644); // Read-only for group/others
        }
        
        return $path;
    }

    /**
     * Validate file signature (magic bytes)
     */
    private function validateFileSignature($file)
    {
        $signatures = [
            'pdf' => "%PDF-",
            'jpg' => "\xFF\xD8\xFF",
            'jpeg' => "\xFF\xD8\xFF",
            'png' => "\x89PNG\r\n\x1a\n",
            'doc' => "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1", // MS Office older
            'docx' => "PK\x03\x04", // ZIP-based formats
        ];
        
        $extension = strtolower($file->getClientOriginalExtension());
        $content = file_get_contents($file->getPathname(), false, null, 0, 8);
        
        if (isset($signatures[$extension])) {
            return strpos($content, $signatures[$extension]) === 0;
        }
        
        return true; // If we don't have signature for this type, allow it
    }

    /**
     * Security check for bulk uploads
     */
    private function validateBulkUploadSecurity($files)
    {
        $totalSize = 0;
        $fileCount = count($files);
        
        foreach ($files as $file) {
            $totalSize += $file->getSize();
            
            // Check individual file security
            if (!$this->isFileSafe($file)) {
                return ['valid' => false, 'error' => 'One or more files appear to be unsafe.'];
            }
        }
        
        // Limit total upload size to 25MB
        if ($totalSize > 25 * 1024 * 1024) {
            return ['valid' => false, 'error' => 'Total upload size exceeds 25MB limit.'];
        }
        
        // Limit number of files
        if ($fileCount > 5) {
            return ['valid' => false, 'error' => 'Maximum 5 files allowed in bulk upload.'];
        }
        
        return ['valid' => true];
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

    /**
     * Enhanced file safety check
     */
    private function isFileSafe($file)
    {
        return !$this->isFileMalicious($file) && $this->validateFileSignature($file);
    }
}