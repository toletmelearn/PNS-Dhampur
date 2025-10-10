<?php

namespace App\Http\Controllers;

use App\Models\StudentVerification;
use App\Models\Student;
use App\Services\DocumentVerificationService;
use App\Services\AadhaarVerificationService;
use App\Services\BirthCertificateOCRService;
use App\Services\BulkVerificationService;
use App\Services\MismatchResolutionService;
use App\Services\AuditTrailService;
use App\Http\Traits\FileUploadValidationTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class StudentVerificationController extends Controller
{
    use FileUploadValidationTrait;
    protected $verificationService;
    protected $aadhaarService;
    protected $birthCertificateOCRService;
    protected $bulkVerificationService;
    protected $mismatchResolutionService;
    protected $auditTrailService;

    public function __construct(
        DocumentVerificationService $verificationService, 
        AadhaarVerificationService $aadhaarService,
        BirthCertificateOCRService $birthCertificateOCRService,
        BulkVerificationService $bulkVerificationService,
        MismatchResolutionService $mismatchResolutionService,
        AuditTrailService $auditTrailService
    ) {
        $this->verificationService = $verificationService;
        $this->aadhaarService = $aadhaarService;
        $this->birthCertificateOCRService = $birthCertificateOCRService;
        $this->bulkVerificationService = $bulkVerificationService;
        $this->mismatchResolutionService = $mismatchResolutionService;
        $this->auditTrailService = $auditTrailService;
    }

    /**
     * Display verification dashboard for administrators
     */
    public function index(Request $request)
    {
        $this->authorize('admin-access');

        $query = StudentVerification::with(['student', 'reviewer', 'uploader'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('verification_status', $request->status);
        }

        if ($request->filled('document_type')) {
            $query->where('document_type', $request->document_type);
        }

        if ($request->filled('confidence_level')) {
            switch ($request->confidence_level) {
                case 'high':
                    $query->where('confidence_score', '>=', StudentVerification::HIGH_CONFIDENCE_THRESHOLD);
                    break;
                case 'medium':
                    $query->whereBetween('confidence_score', [
                        StudentVerification::MEDIUM_CONFIDENCE_THRESHOLD,
                        StudentVerification::HIGH_CONFIDENCE_THRESHOLD - 0.01
                    ]);
                    break;
                case 'low':
                    $query->where('confidence_score', '<', StudentVerification::MEDIUM_CONFIDENCE_THRESHOLD);
                    break;
            }
        }

        if ($request->filled('student_name')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->student_name . '%');
            });
        }

        $verifications = $query->paginate(20);
        $stats = $this->verificationService->getVerificationStats();

        return view('admin.student-verifications.index', compact('verifications', 'stats'));
    }

    /**
     * Display verification details for review
     */
    public function show(StudentVerification $verification)
    {
        $this->authorize('admin-access');

        $verification->load(['student', 'reviewer', 'uploader']);
        
        return view('admin.student-verifications.show', compact('verification'));
    }

    /**
     * Upload and process a document for verification
     */
    public function upload(Request $request)
    {
        // Use enhanced validation from trait
        $validationRules = [
            'student_id' => 'required|exists:students,id',
            'document_type' => ['required', Rule::in(array_keys(StudentVerification::DOCUMENT_TYPES))],
        ];
        
        // Add enhanced file validation rules from trait
        $fileRules = $this->getDocumentValidationRules();
        $validationRules['document'] = $fileRules['document'];
        
        $request->validate($validationRules, $this->getFileUploadValidationMessages());

        // Perform enhanced file validation with virus scanning
        if ($request->hasFile('document')) {
            $validationResult = $this->validateFileWithSecurity($request->file('document'));
            if (!$validationResult['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $validationResult['message']
                ], 422);
            }
        }

        try {
            $verification = $this->verificationService->processDocument(
                $request->file('document'),
                $request->document_type,
                $request->student_id,
                Auth::id()
            );

            // Log the verification creation
            $this->auditTrailService->logVerificationCreation(
                $verification,
                Auth::id(),
                [
                    'document_type' => $request->document_type,
                    'file_name' => $request->file('document')->getClientOriginalName(),
                    'file_size' => $request->file('document')->getSize()
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Document uploaded and verification started successfully.',
                'verification_id' => $verification->id,
                'status' => $verification->verification_status,
                'confidence_score' => $verification->confidence_score,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process document: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Verify Aadhaar number with demographic data
     */
    public function verifyAadhaar(Request $request)
    {
        $request->validate([
            'aadhaar_number' => 'required|string|regex:/^\d{4}\s?\d{4}\s?\d{4}$/',
            'student_id' => 'required|exists:students,id',
            'name' => 'nullable|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:Male,Female,Other',
        ]);

        try {
            // Check if Aadhaar service is available
            if (!$this->aadhaarService->isServiceAvailable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aadhaar verification service is currently unavailable. Please try again later.',
                ], 503);
            }

            // Prepare demographic data
            $demographicData = array_filter([
                'name' => $request->name,
                'father_name' => $request->father_name,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
            ]);

            // Perform Aadhaar verification
            $verificationResult = $this->aadhaarService->verifyAadhaar(
                $request->aadhaar_number,
                $demographicData
            );

            if (!$verificationResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aadhaar verification failed: ' . $verificationResult['error'],
                    'error_code' => $verificationResult['error_code'] ?? 'VERIFICATION_FAILED',
                ], 422);
            }

            // Create or update student verification record for Aadhaar
            $student = Student::findOrFail($request->student_id);
            
            $verification = StudentVerification::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'document_type' => 'aadhaar_card',
                ],
                [
                    'verification_status' => $this->determineVerificationStatus($verificationResult),
                    'confidence_score' => $verificationResult['overall_match_score'] ?? 0,
                    'verification_data' => [
                        'aadhaar_verification' => $verificationResult,
                        'input_data' => $demographicData,
                        'verification_timestamp' => Carbon::now()->toISOString(),
                    ],
                    'uploaded_by' => Auth::id(),
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'admin_comments' => null,
                ]
            );

            // Log the verification attempt
            Log::info('Aadhaar verification completed', [
                'student_id' => $student->id,
                'verification_id' => $verification->id,
                'confidence_score' => $verificationResult['overall_match_score'] ?? 0,
                'status' => $verification->verification_status,
                'reference_id' => $verificationResult['reference_id'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Aadhaar verification completed successfully.',
                'verification_id' => $verification->id,
                'status' => $verification->verification_status,
                'confidence_score' => $verificationResult['overall_match_score'] ?? 0,
                'confidence_level' => $verificationResult['confidence_level'] ?? 'UNKNOWN',
                'match_scores' => $verificationResult['match_scores'] ?? [],
                'verified_data' => $verificationResult['verified_data'] ?? [],
                'reference_id' => $verificationResult['reference_id'] ?? null,
            ]);

        } catch (\Exception $e) {
            Log::error('Aadhaar verification exception', [
                'student_id' => $request->student_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during Aadhaar verification. Please try again.',
            ], 500);
        }
    }

    /**
     * Get Aadhaar verification status for a student
     */
    public function getAadhaarStatus(Student $student)
    {
        $verification = StudentVerification::where('student_id', $student->id)
            ->where('document_type', 'aadhaar_card')
            ->latest()
            ->first();

        if (!$verification) {
            return response()->json([
                'success' => true,
                'status' => 'not_verified',
                'message' => 'No Aadhaar verification found for this student.',
            ]);
        }

        return response()->json([
            'success' => true,
            'status' => $verification->verification_status,
            'confidence_score' => $verification->confidence_score,
            'verified_at' => $verification->created_at,
            'reviewed_at' => $verification->reviewed_at,
            'admin_comments' => $verification->admin_comments,
            'verification_data' => $verification->verification_data,
        ]);
    }

    /**
     * Check Aadhaar service availability
     */
    public function checkAadhaarService()
    {
        $stats = $this->aadhaarService->getServiceStats();
        
        return response()->json([
            'success' => true,
            'service_available' => $stats['service_available'],
            'mock_mode' => $stats['mock_mode'],
            'stats' => $stats,
        ]);
    }

    /**
     * Show Aadhaar verification interface
     */
    public function showAadhaarVerification()
    {
        $students = Student::select('id', 'name', 'admission_number', 'father_name', 'date_of_birth', 'gender')
                          ->orderBy('name')
                          ->get();
        
        return view('student-verifications.aadhaar-verify', compact('students'));
    }

    /**
     * Determine verification status based on Aadhaar verification result
     */
    protected function determineVerificationStatus(array $verificationResult): string
    {
        $overallScore = $verificationResult['overall_match_score'] ?? 0;
        
        if ($overallScore >= StudentVerification::HIGH_CONFIDENCE_THRESHOLD) {
            return StudentVerification::STATUS_APPROVED;
        } elseif ($overallScore >= StudentVerification::MEDIUM_CONFIDENCE_THRESHOLD) {
            return StudentVerification::STATUS_MANUAL_REVIEW;
        } else {
            return StudentVerification::STATUS_REJECTED;
        }
    }

    /**
     * Download original document
     */
    public function download(StudentVerification $verification)
    {
        $this->authorize('admin-access');

        if (!$verification->original_file_path || !Storage::exists($verification->original_file_path)) {
            abort(404, 'File not found');
        }

        $filename = 'verification_' . $verification->id . '_' . $verification->document_type . '.' . 
                   pathinfo($verification->original_file_path, PATHINFO_EXTENSION);

        return Storage::download($verification->original_file_path, $filename);
    }

    /**
     * Approve verification manually
     */
    public function approve(Request $request, StudentVerification $verification)
    {
        $this->authorize('admin-access');

        $request->validate([
            'comments' => 'nullable|string|max:1000',
        ]);

        // Log the approval action
        $this->auditTrailService->logApproval(
            $verification,
            Auth::id(),
            $request->comments
        );

        $verification->approveManually(Auth::id(), $request->comments);

        return response()->json([
            'success' => true,
            'message' => 'Document verification approved successfully.',
        ]);
    }

    /**
     * Reject verification manually
     */
    public function reject(Request $request, StudentVerification $verification)
    {
        $this->authorize('admin-access');

        $request->validate([
            'comments' => 'required|string|max:1000',
        ]);

        // Log the rejection action
        $this->auditTrailService->logRejection(
            $verification,
            Auth::id(),
            $request->comments
        );

        $verification->rejectManually(Auth::id(), $request->comments);

        return response()->json([
            'success' => true,
            'message' => 'Document verification rejected successfully.',
        ]);
    }

    /**
     * Bulk approve verifications
     */
    public function bulkApprove(Request $request)
    {
        $this->authorize('admin-access');

        $request->validate([
            'verification_ids' => 'required|array',
            'verification_ids.*' => 'exists:student_verifications,id',
            'comments' => 'nullable|string|max:1000',
        ]);

        $verifications = StudentVerification::whereIn('id', $request->verification_ids)
            ->whereIn('verification_status', [
                StudentVerification::STATUS_MANUAL_REVIEW,
                StudentVerification::STATUS_PENDING
            ])
            ->get();

        $approved = 0;
        foreach ($verifications as $verification) {
            // Log the bulk approval
            $this->auditTrailService->logApproval(
                $verification,
                Auth::id(),
                $request->comments ?? 'Bulk approval'
            );
            
            $verification->approveManually(Auth::id(), $request->comments);
            $approved++;
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully approved {$approved} document(s).",
        ]);
    }

    /**
     * Bulk reject verifications
     */
    public function bulkReject(Request $request)
    {
        $this->authorize('admin-access');

        $request->validate([
            'verification_ids' => 'required|array',
            'verification_ids.*' => 'exists:student_verifications,id',
            'comments' => 'required|string|max:1000',
        ]);

        $verifications = StudentVerification::whereIn('id', $request->verification_ids)
            ->whereIn('verification_status', [
                StudentVerification::STATUS_MANUAL_REVIEW,
                StudentVerification::STATUS_PENDING
            ])
            ->get();

        $rejected = 0;
        foreach ($verifications as $verification) {
            // Log the bulk rejection
            $this->auditTrailService->logRejection(
                $verification,
                Auth::id(),
                $request->comments
            );
            
            $verification->rejectManually(Auth::id(), $request->comments);
            $rejected++;
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully rejected {$rejected} document(s).",
        ]);
    }

    /**
     * Delete verification record
     */
    public function destroy(StudentVerification $verification)
    {
        $this->authorize('admin-access');

        $verification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Verification record deleted successfully.',
        ]);
    }

    /**
     * Get verification status (for AJAX polling)
     */
    public function status(StudentVerification $verification)
    {
        return response()->json([
            'id' => $verification->id,
            'status' => $verification->verification_status,
            'confidence_score' => $verification->confidence_score,
            'confidence_level' => $verification->confidence_level,
            'is_complete' => $verification->is_complete,
            'requires_manual_review' => $verification->requires_manual_review,
            'verification_results' => $verification->verification_results,
            'updated_at' => $verification->updated_at->toISOString(),
        ]);
    }

    /**
     * Reprocess verification
     */
    public function reprocess(StudentVerification $verification)
    {
        $this->authorize('admin-access');

        if (!$verification->original_file_path || !Storage::exists($verification->original_file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Original file not found. Cannot reprocess.',
            ], 404);
        }

        try {
            // Log the reprocess action
            $this->auditTrailService->logDocumentReprocessing(
                $verification,
                Auth::id(),
                'Document reprocessing initiated'
            );

            // Reset verification status
            $verification->update([
                'verification_status' => StudentVerification::STATUS_PENDING,
                'confidence_score' => null,
                'verification_results' => null,
                'verification_log' => null,
                'reviewed_by' => null,
                'reviewer_comments' => null,
                'reviewed_at' => null,
                'verification_started_at' => null,
                'verification_completed_at' => null,
            ]);

            // Create a temporary uploaded file object for reprocessing
            $filePath = storage_path('app/public/' . $verification->original_file_path);
            $tempFile = new \Illuminate\Http\UploadedFile(
                $filePath,
                basename($filePath),
                mime_content_type($filePath),
                null,
                true
            );

            $verification = $this->verificationService->processDocument(
                $tempFile,
                $verification->document_type,
                $verification->student_id,
                Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Document reprocessing started successfully.',
                'verification_id' => $verification->id,
                'status' => $verification->verification_status,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reprocess document: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Process pending verifications in batch
     */
    public function processPending(Request $request)
    {
        $this->authorize('admin-access');

        $limit = $request->input('limit', 10);
        $results = $this->verificationService->processPendingVerifications($limit);

        return response()->json([
            'success' => true,
            'message' => 'Batch processing completed.',
            'results' => $results,
        ]);
    }

    /**
     * Get verification statistics
     */
    public function statistics()
    {
        $this->authorize('admin-access');

        $stats = $this->verificationService->getVerificationStats();
        
        // Additional statistics
        $stats['recent_activity'] = StudentVerification::with(['student', 'uploader'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($verification) {
                return [
                    'id' => $verification->id,
                    'student_name' => $verification->student->name,
                    'document_type' => $verification->document_type_name,
                    'status' => $verification->verification_status,
                    'confidence_score' => $verification->confidence_score,
                    'created_at' => $verification->created_at->diffForHumans(),
                ];
            });

        $stats['document_type_breakdown'] = StudentVerification::selectRaw('document_type, COUNT(*) as count')
            ->groupBy('document_type')
            ->get()
            ->mapWithKeys(function ($item) {
                return [StudentVerification::DOCUMENT_TYPES[$item->document_type] ?? $item->document_type => $item->count];
            });

        return response()->json($stats);
    }

    /**
     * Student verification upload form
     */
    public function create(Request $request)
    {
        $studentId = $request->input('student_id');
        $student = $studentId ? Student::find($studentId) : null;
        
        return view('admin.student-verifications.create', compact('student'));
    }

    /**
     * Compare verification data with student information
     */
    public function compare(StudentVerification $verification)
    {
        $this->authorize('admin-access');

        $verification->load('student');
        
        $comparisonData = [
            'verification_data' => $verification->extracted_data,
            'student_data' => [
                'name' => $verification->student->name,
                'father_name' => $verification->student->father_name,
                'mother_name' => $verification->student->mother_name,
                'dob' => $verification->student->dob?->format('d/m/Y'),
                'aadhaar' => $verification->student->aadhaar,
                'class' => $verification->student->class?->name,
            ],
            'verification_results' => $verification->verification_results,
        ];

        return view('admin.student-verifications.compare', compact('verification', 'comparisonData'));
    }

    /**
     * Process birth certificate OCR
     */
    public function processBirthCertificateOCR(Request $request)
    {
        // Use enhanced validation from trait
        $validationRules = [
            'student_id' => 'required|exists:students,id',
        ];
        
        // Add enhanced file validation rules from trait
        $fileRules = $this->getDocumentValidationRules();
        $validationRules['birth_certificate'] = $fileRules['document'];
        
        $request->validate($validationRules, $this->getFileUploadValidationMessages());

        // Perform enhanced file validation with virus scanning
        if ($request->hasFile('birth_certificate')) {
            $validationResult = $this->validateFileWithSecurity($request->file('birth_certificate'));
            if (!$validationResult['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $validationResult['message'],
                    'error_code' => 'FILE_VALIDATION_FAILED',
                ], 422);
            }
        }

        try {
            $student = Student::findOrFail($request->student_id);
            $file = $request->file('birth_certificate');

            // Check service availability
            if (!$this->birthCertificateOCRService->isServiceAvailable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Birth certificate OCR service is currently unavailable. Please try again later.',
                    'error_code' => 'SERVICE_UNAVAILABLE',
                ], 503);
            }

            // Process the birth certificate
            $ocrResult = $this->birthCertificateOCRService->processDocument($file);

            if (!$ocrResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Birth certificate OCR processing failed: ' . $ocrResult['error'],
                    'error_code' => $ocrResult['error_code'] ?? 'OCR_FAILED',
                ], 422);
            }

            // Create or update student verification record for birth certificate
            $verification = StudentVerification::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'document_type' => 'birth_certificate',
                ],
                [
                    'verification_status' => $this->determineOCRVerificationStatus($ocrResult),
                    'confidence_score' => $ocrResult['confidence_score'] ?? 0,
                    'extracted_data' => $ocrResult['extracted_data'] ?? [],
                    'verification_data' => [
                        'ocr_result' => $ocrResult,
                        'processing_timestamp' => Carbon::now()->toISOString(),
                        'service_mode' => config('services.birth_certificate_ocr.mock_api.enabled') ? 'mock' : 'production',
                    ],
                    'uploaded_by' => Auth::id(),
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'admin_comments' => null,
                ]
            );

            // Log the OCR processing
            Log::info('Birth certificate OCR processing completed', [
                'student_id' => $student->id,
                'verification_id' => $verification->id,
                'confidence_score' => $ocrResult['confidence_score'] ?? 0,
                'status' => $verification->verification_status,
                'reference_id' => $ocrResult['reference_id'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Birth certificate processed successfully.',
                'verification_id' => $verification->id,
                'status' => $verification->verification_status,
                'confidence_score' => $ocrResult['confidence_score'] ?? 0,
                'extracted_data' => $ocrResult['extracted_data'] ?? [],
                'validation_results' => $ocrResult['validation_results'] ?? [],
                'reference_id' => $ocrResult['reference_id'] ?? null,
            ]);

        } catch (\Exception $e) {
            Log::error('Birth certificate OCR processing error', [
                'student_id' => $request->student_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the birth certificate: ' . $e->getMessage(),
                'error_code' => 'PROCESSING_ERROR',
            ], 500);
        }
    }

    /**
     * Get birth certificate OCR status for a student
     */
    public function getBirthCertificateStatus(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        try {
            $student = Student::findOrFail($request->student_id);
            
            $verification = StudentVerification::where('student_id', $student->id)
                ->where('document_type', 'birth_certificate')
                ->latest()
                ->first();

            if (!$verification) {
                return response()->json([
                    'success' => true,
                    'status' => 'not_processed',
                    'message' => 'No birth certificate OCR record found for this student.',
                ]);
            }

            return response()->json([
                'success' => true,
                'status' => $verification->verification_status,
                'confidence_score' => $verification->confidence_score,
                'extracted_data' => $verification->extracted_data,
                'verification_data' => $verification->verification_data,
                'processed_at' => $verification->updated_at->toISOString(),
                'verification_id' => $verification->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Birth certificate status retrieval error', [
                'student_id' => $request->student_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve birth certificate status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check birth certificate OCR service status
     */
    public function checkBirthCertificateService()
    {
        try {
            $isAvailable = $this->birthCertificateOCRService->isServiceAvailable();
            $isMockMode = config('services.birth_certificate_ocr.mock_api.enabled', false);

            return response()->json([
                'success' => true,
                'service_available' => $isAvailable,
                'service_mode' => $isMockMode ? 'mock' : 'production',
                'message' => $isAvailable 
                    ? 'Birth certificate OCR service is available.' 
                    : 'Birth certificate OCR service is currently unavailable.',
                'supported_formats' => config('services.birth_certificate_ocr.supported_formats', ['pdf', 'jpg', 'jpeg', 'png']),
                'max_file_size_mb' => config('services.birth_certificate_ocr.max_file_size', 10),
            ]);

        } catch (\Exception $e) {
            Log::error('Birth certificate service check error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'service_available' => false,
                'message' => 'Failed to check service status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show birth certificate OCR interface
     */
    public function showBirthCertificateOCR()
    {
        $students = Student::select('id', 'name', 'admission_number', 'father_name', 'mother_name', 'date_of_birth')
                          ->orderBy('name')
                          ->get();
        
        return view('student-verifications.birth-certificate-ocr', compact('students'));
    }

    /**
     * Determine verification status based on OCR results
     */
    private function determineOCRVerificationStatus(array $ocrResult): string
    {
        $confidenceScore = $ocrResult['confidence_score'] ?? 0;
        
        if ($confidenceScore >= StudentVerification::HIGH_CONFIDENCE_THRESHOLD) {
            return StudentVerification::STATUS_VERIFIED;
        } elseif ($confidenceScore >= StudentVerification::MEDIUM_CONFIDENCE_THRESHOLD) {
            return StudentVerification::STATUS_MANUAL_REVIEW;
        } else {
            return StudentVerification::STATUS_REJECTED;
        }
    }

    /**
     * Show bulk verification interface
     */
    public function showBulkVerification()
    {
        $this->authorize('admin-access');
        
        $students = Student::select('id', 'name', 'admission_number', 'class', 'section', 'father_name', 'date_of_birth')
                          ->orderBy('class')
                          ->orderBy('section')
                          ->orderBy('name')
                          ->get();
        
        $verificationTypes = $this->bulkVerificationService->getAvailableVerificationTypes();
        
        return view('student-verifications.bulk-verification', compact('students', 'verificationTypes'));
    }

    /**
     * Process bulk verification
     */
    public function processBulkVerification(Request $request)
    {
        $this->authorize('admin-access');
        
        $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
            'verification_types' => 'required|array|min:1',
            'verification_types.*' => 'in:aadhaar,birth_certificate',
            'batch_size' => 'nullable|integer|min:1|max:50',
            'max_retries' => 'nullable|integer|min:1|max:5'
        ]);
        
        // Validate the request
        $validation = $this->bulkVerificationService->validateBulkVerificationRequest(
            $request->student_ids,
            $request->verification_types
        );
        
        if (!$validation['valid']) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validation['errors']
            ], 422);
        }
        
        try {
            $options = [
                'batch_size' => $request->batch_size ?? 10,
                'max_retries' => $request->max_retries ?? 3,
                'delay_between_batches' => 2
            ];
            
            $results = $this->bulkVerificationService->processBulkVerification(
                $request->student_ids,
                $request->verification_types,
                $options
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Bulk verification completed',
                'data' => $results
            ]);
            
        } catch (\Exception $e) {
            Log::error('Bulk verification failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Bulk verification failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get bulk verification progress
     */
    public function getBulkVerificationProgress(Request $request)
    {
        $this->authorize('admin-access');
        
        $request->validate([
            'session_id' => 'required|string'
        ]);
        
        try {
            $progress = $this->bulkVerificationService->getBulkVerificationProgress($request->session_id);
            
            return response()->json([
                'success' => true,
                'data' => $progress
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get progress: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel bulk verification
     */
    public function cancelBulkVerification(Request $request)
    {
        $this->authorize('admin-access');
        
        $request->validate([
            'session_id' => 'required|string'
        ]);
        
        try {
            $result = $this->bulkVerificationService->cancelBulkVerification($request->session_id);
            
            return response()->json([
                'success' => true,
                'message' => $result['message']
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel verification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get bulk verification statistics
     */
    public function getBulkVerificationStats()
    {
        $this->authorize('admin-access');
        
        try {
            $stats = [
                'total_students' => Student::count(),
                'verified_students' => StudentVerification::where('verification_status', 'verified')->distinct('student_id')->count(),
                'pending_verifications' => StudentVerification::where('verification_status', 'pending')->count(),
                'failed_verifications' => StudentVerification::where('verification_status', 'rejected')->count(),
                'recent_bulk_operations' => 0, // This would come from a tracking table
                'available_verification_types' => $this->bulkVerificationService->getAvailableVerificationTypes()
            ];
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Analyze mismatches for a verification
     */
    public function analyzeMismatches(StudentVerification $verification)
    {
        $this->authorize('admin-access');

        try {
            if (!$verification->extracted_data) {
                return response()->json([
                    'success' => false,
                    'message' => 'No extracted data available for analysis.',
                ], 400);
            }

            $analysis = $this->mismatchResolutionService->analyzeMismatches(
                $verification,
                $verification->extracted_data
            );

            return response()->json([
                'success' => true,
                'analysis' => $analysis,
                'verification_id' => $verification->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Mismatch analysis failed', [
                'verification_id' => $verification->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to analyze mismatches.',
            ], 500);
        }
    }

    /**
     * Apply automatic resolution to a verification
     */
    public function applyAutomaticResolution(StudentVerification $verification)
    {
        $this->authorize('admin-access');

        try {
            if (!$verification->extracted_data) {
                return response()->json([
                    'success' => false,
                    'message' => 'No extracted data available for resolution.',
                ], 400);
            }

            // Analyze mismatches first
            $analysis = $this->mismatchResolutionService->analyzeMismatches(
                $verification,
                $verification->extracted_data
            );

            // Apply automatic resolution if possible
            $resolved = $this->mismatchResolutionService->applyAutomaticResolution(
                $verification,
                $analysis
            );

            $verification->refresh();

            return response()->json([
                'success' => true,
                'resolved' => $resolved,
                'verification_status' => $verification->verification_status,
                'confidence_score' => $verification->confidence_score,
                'resolution_method' => $verification->resolution_method,
                'analysis' => $analysis,
            ]);

        } catch (\Exception $e) {
            Log::error('Automatic resolution failed', [
                'verification_id' => $verification->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to apply automatic resolution.',
            ], 500);
        }
    }

    /**
     * Show mismatch resolution interface
     */
    public function showMismatchResolution()
    {
        $this->authorize('admin-access');

        // Get verifications that require manual review or have mismatches
        $verifications = StudentVerification::with(['student'])
            ->whereIn('verification_status', [
                StudentVerification::STATUS_MANUAL_REVIEW,
                StudentVerification::STATUS_MISMATCH,
                StudentVerification::STATUS_PENDING
            ])
            ->whereNotNull('extracted_data')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Get statistics
        $stats = [
            'total_pending' => StudentVerification::whereIn('verification_status', [
                StudentVerification::STATUS_MANUAL_REVIEW,
                StudentVerification::STATUS_MISMATCH,
                StudentVerification::STATUS_PENDING
            ])->count(),
            'auto_resolvable' => 0, // Will be calculated via AJAX
            'manual_review' => StudentVerification::where('verification_status', StudentVerification::STATUS_MANUAL_REVIEW)->count(),
            'high_confidence' => StudentVerification::where('confidence_score', '>=', 0.9)->count(),
        ];

        return view('student-verifications.mismatch-resolution', compact('verifications', 'stats'));
    }

    /**
     * Batch analyze mismatches for multiple verifications
     */
    public function batchAnalyzeMismatches(Request $request)
    {
        $this->authorize('admin-access');

        $request->validate([
            'verification_ids' => 'required|array',
            'verification_ids.*' => 'exists:student_verifications,id',
        ]);

        try {
            $results = [];
            $autoResolvableCount = 0;

            foreach ($request->verification_ids as $verificationId) {
                $verification = StudentVerification::find($verificationId);
                
                if ($verification && $verification->extracted_data) {
                    $analysis = $this->mismatchResolutionService->analyzeMismatches(
                        $verification,
                        $verification->extracted_data
                    );

                    $results[$verificationId] = $analysis;
                    
                    if ($analysis['auto_resolvable']) {
                        $autoResolvableCount++;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'results' => $results,
                'summary' => [
                    'total_analyzed' => count($results),
                    'auto_resolvable' => $autoResolvableCount,
                    'manual_review_required' => count($results) - $autoResolvableCount,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Batch mismatch analysis failed', [
                'verification_ids' => $request->verification_ids,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to analyze mismatches.',
            ], 500);
        }
    }

    /**
     * Batch apply automatic resolution
     */
    public function batchApplyAutomaticResolution(Request $request)
    {
        $this->authorize('admin-access');

        $request->validate([
            'verification_ids' => 'required|array',
            'verification_ids.*' => 'exists:student_verifications,id',
        ]);

        try {
            $results = [];
            $resolvedCount = 0;
            $manualReviewCount = 0;

            foreach ($request->verification_ids as $verificationId) {
                $verification = StudentVerification::find($verificationId);
                
                if ($verification && $verification->extracted_data) {
                    // Analyze mismatches
                    $analysis = $this->mismatchResolutionService->analyzeMismatches(
                        $verification,
                        $verification->extracted_data
                    );

                    // Apply automatic resolution
                    $resolved = $this->mismatchResolutionService->applyAutomaticResolution(
                        $verification,
                        $analysis
                    );

                    $verification->refresh();

                    $results[$verificationId] = [
                        'resolved' => $resolved,
                        'status' => $verification->verification_status,
                        'confidence' => $verification->confidence_score,
                        'analysis' => $analysis,
                    ];

                    if ($resolved) {
                        $resolvedCount++;
                    } else {
                        $manualReviewCount++;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'results' => $results,
                'summary' => [
                    'total_processed' => count($results),
                    'auto_resolved' => $resolvedCount,
                    'manual_review_required' => $manualReviewCount,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Batch automatic resolution failed', [
                'verification_ids' => $request->verification_ids,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to apply automatic resolution.',
            ], 500);
        }
    }

    /**
     * Show verification history for a specific verification
     */
    public function showHistory(StudentVerification $verification)
    {
        $this->authorize('admin-access');

        $auditLogs = $this->auditTrailService->getVerificationAuditTrail($verification->id);
        
        return view('student-verifications.history', compact('verification', 'auditLogs'));
    }

    /**
     * Show verification history for a specific student
     */
    public function showStudentHistory(Student $student)
    {
        $this->authorize('admin-access');

        $auditLogs = $this->auditTrailService->getStudentAuditTrail($student->id);
        $verifications = StudentVerification::where('student_id', $student->id)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('student-verifications.student-history', compact('student', 'auditLogs', 'verifications'));
    }

    /**
     * Get audit trail data for AJAX requests
     */
    public function getAuditTrail(Request $request)
    {
        $this->authorize('admin-access');

        $request->validate([
            'verification_id' => 'nullable|exists:student_verifications,id',
            'student_id' => 'nullable|exists:students,id',
            'limit' => 'nullable|integer|min:1|max:500',
        ]);

        if ($request->verification_id) {
            $auditLogs = $this->auditTrailService->getVerificationAuditTrail(
                $request->verification_id,
                $request->limit ?? 50
            );
        } elseif ($request->student_id) {
            $auditLogs = $this->auditTrailService->getStudentAuditTrail(
                $request->student_id,
                $request->limit ?? 100
            );
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Either verification_id or student_id is required.',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'audit_logs' => $auditLogs,
        ]);
    }

    /**
     * Export audit trail to CSV
     */
    public function exportAuditTrail(Request $request)
    {
        $this->authorize('admin-access');

        $filters = $request->only([
            'date_from',
            'date_to',
            'user_id',
            'verification_id',
            'student_id',
            'action',
        ]);

        try {
            $filePath = $this->auditTrailService->exportAuditTrail($filters);
            
            return response()->download($filePath)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Audit trail export failed', [
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to export audit trail.');
        }
    }

    /**
     * Get audit statistics
     */
    public function getAuditStatistics(Request $request)
    {
        $this->authorize('admin-access');

        $filters = $request->only([
            'date_from',
            'date_to',
            'user_id',
            'verification_id',
            'student_id',
        ]);

        $statistics = $this->auditTrailService->getAuditStatistics($filters);

        return response()->json([
            'success' => true,
            'statistics' => $statistics,
        ]);
    }
}