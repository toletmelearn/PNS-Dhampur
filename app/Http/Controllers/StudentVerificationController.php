<?php

namespace App\Http\Controllers;

use App\Models\StudentVerification;
use App\Models\Student;
use App\Services\DocumentVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;

class StudentVerificationController extends Controller
{
    protected $verificationService;

    public function __construct(DocumentVerificationService $verificationService)
    {
        $this->verificationService = $verificationService;
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
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'document_type' => ['required', Rule::in(array_keys(StudentVerification::DOCUMENT_TYPES))],
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png,gif|max:5120', // 5MB max
        ]);

        try {
            $verification = $this->verificationService->processDocument(
                $request->file('document'),
                $request->document_type,
                $request->student_id,
                Auth::id()
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
}