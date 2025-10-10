<?php

namespace App\Http\Controllers;

use App\Http\Traits\FileUploadValidationTrait;
use App\Models\DocumentVerification;
use App\Models\Student;
use App\Services\UserFriendlyErrorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;

class DocumentVerificationController extends Controller
{
    use FileUploadValidationTrait;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = DocumentVerification::with(['student', 'verifiedBy']);

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('verification_status', $request->status);
        }

        // Filter by document type
        if ($request->has('document_type') && $request->document_type !== 'all') {
            $query->where('document_type', $request->document_type);
        }

        // Filter by student
        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        // Search by student name or document name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('document_name', 'like', "%{$search}%")
                  ->orWhereHas('student', function($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $documents = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('document-verification.index', compact('documents'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $students = Student::with(['classModel', 'user'])->get();
        $documentTypes = [
            'birth_certificate' => 'Birth Certificate',
            'transfer_certificate' => 'Transfer Certificate',
            'caste_certificate' => 'Caste Certificate',
            'income_certificate' => 'Income Certificate',
            'domicile_certificate' => 'Domicile Certificate',
            'passport_photo' => 'Passport Photo',
            'aadhar_card' => 'Aadhar Card',
            'previous_marksheet' => 'Previous Marksheet'
        ];

        $selectedStudent = $request->has('student_id') ? 
            Student::with(['classModel', 'user'])->find($request->student_id) : null;

        return view('document-verification.create', compact('students', 'documentTypes', 'selectedStudent'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'document_type' => 'required|string',
            'document_name' => 'required|string|max:255',
            ...$this->getDocumentFileValidationRules('document_file'),
            'expiry_date' => 'nullable|date|after:today',
            'is_mandatory' => 'boolean',
            'metadata' => 'nullable|array'
        ], $this->getFileUploadValidationMessages());

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Store the file
            $file = $request->file('document_file');
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            
            // Generate secure filename
            $sanitizedName = Str::slug($originalName);
            $fileName = $sanitizedName . '_' . time() . '_' . Str::random(8) . '.' . $extension;
            $filePath = $file->storeAs('documents/verification', $fileName);

            // Generate file hash
            $fileHash = hash_file('sha256', $file->getPathname());

            // Create document verification record
            $document = DocumentVerification::create([
                'student_id' => $request->student_id,
                'document_type' => $request->document_type,
                'document_name' => $request->document_name,
                'file_path' => $filePath,
                'file_hash' => $fileHash,
                'expiry_date' => $request->expiry_date,
                'is_mandatory' => $request->has('is_mandatory'),
                'metadata' => $request->metadata ?? []
            ]);

            return redirect()->route('document-verification.index')
                ->with('success', 'Document uploaded successfully and is pending verification.');

        } catch (\Exception $e) {
            Log::error('Failed to upload document', [
                'student_id' => $request->student_id,
                'document_type' => $request->document_type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', UserFriendlyErrorService::getErrorMessage($e, 'general'))
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(DocumentVerification $documentVerification)
    {
        $documentVerification->load(['student', 'verifiedBy']);
        
        return view('document-verification.show', compact('documentVerification'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DocumentVerification $documentVerification)
    {
        // Use pagination for students to avoid memory issues
        $students = Student::paginate(100);
        $documentTypes = [
            'birth_certificate' => 'Birth Certificate',
            'transfer_certificate' => 'Transfer Certificate',
            'caste_certificate' => 'Caste Certificate',
            'income_certificate' => 'Income Certificate',
            'domicile_certificate' => 'Domicile Certificate',
            'passport_photo' => 'Passport Photo',
            'aadhar_card' => 'Aadhar Card',
            'previous_marksheet' => 'Previous Marksheet'
        ];

        return view('document-verification.edit', compact('documentVerification', 'students', 'documentTypes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DocumentVerification $documentVerification)
    {
        $validator = Validator::make($request->all(), [
            'document_name' => 'required|string|max:255',
            'expiry_date' => 'nullable|date|after:today',
            'is_mandatory' => 'boolean',
            'metadata' => 'nullable|array',
            ...$this->getOptionalDocumentFileValidationRules('document_file')
        ], $this->getFileUploadValidationMessages());

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $updateData = [
                'document_name' => $request->document_name,
                'expiry_date' => $request->expiry_date,
                'is_mandatory' => $request->has('is_mandatory'),
                'metadata' => $request->metadata ?? []
            ];

            // Handle file replacement
            if ($request->hasFile('document_file')) {
                // Delete old file
                if (Storage::exists($documentVerification->file_path)) {
                    Storage::delete($documentVerification->file_path);
                }

                // Store new file
                $file = $request->file('document_file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('documents/verification', $fileName);
                $fileHash = hash_file('sha256', $file->getPathname());

                $updateData['file_path'] = $filePath;
                $updateData['file_hash'] = $fileHash;
                $updateData['verification_status'] = 'pending'; // Reset status on file change
            }

            $documentVerification->update($updateData);

            return redirect()->route('document-verification.index')
                ->with('success', 'Document updated successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to update document', [
                'document_id' => $documentVerification->id,
                'student_id' => $documentVerification->student_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', UserFriendlyErrorService::getErrorMessage($e, 'general'))
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DocumentVerification $documentVerification)
    {
        try {
            // Delete the file
            if (Storage::exists($documentVerification->file_path)) {
                Storage::delete($documentVerification->file_path);
            }

            $documentVerification->delete();

            return redirect()->route('document-verification.index')
                ->with('success', 'Document deleted successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to delete document', [
                'document_id' => $documentVerification->id,
                'student_id' => $documentVerification->student_id,
                'file_path' => $documentVerification->file_path,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', UserFriendlyErrorService::getErrorMessage($e, 'general'));
        }
    }

    /**
     * Verify a document
     */
    public function verify(Request $request, DocumentVerification $documentVerification)
    {
        $validator = Validator::make($request->all(), [
            'verification_notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        try {
            $documentVerification->verify(Auth::user(), $request->verification_notes);

            return redirect()->back()
                ->with('success', 'Document verified successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to verify document', [
                'document_id' => $documentVerification->id,
                'student_id' => $documentVerification->student_id,
                'verifier_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', UserFriendlyErrorService::getErrorMessage($e, 'general'));
        }
    }

    /**
     * Reject a document
     */
    public function reject(Request $request, DocumentVerification $documentVerification)
    {
        $validator = Validator::make($request->all(), [
            'verification_notes' => 'required|string|max:1000'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        try {
            $documentVerification->reject(Auth::user(), $request->verification_notes);

            return redirect()->back()
                ->with('success', 'Document rejected with notes.');

        } catch (\Exception $e) {
            Log::error('Failed to reject document', [
                'document_id' => $documentVerification->id,
                'student_id' => $documentVerification->student_id,
                'rejector_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', UserFriendlyErrorService::getErrorMessage($e, 'general'));
        }
    }

    /**
     * Download document file
     */
    public function download(DocumentVerification $documentVerification)
    {
        if (!Storage::exists($documentVerification->file_path)) {
            return redirect()->back()
                ->with('error', 'Document file not found.');
        }

        // Verify file integrity
        if (!$documentVerification->verifyFileIntegrity()) {
            return redirect()->back()
                ->with('error', 'Document file integrity check failed.');
        }

        return Storage::download($documentVerification->file_path, $documentVerification->document_name);
    }

    /**
     * Get student documents for AJAX
     */
    public function getStudentDocuments(Student $student)
    {
        $documents = $student->documentVerifications()
            ->select('id', 'document_type', 'document_name', 'verification_status', 'created_at')
            ->get();

        return response()->json($documents);
    }

    /**
     * Bulk verification actions
     */
    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:verify,reject',
            'document_ids' => 'required|array',
            'document_ids.*' => 'exists:document_verifications,id',
            'verification_notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        try {
            $documents = DocumentVerification::whereIn('id', $request->document_ids)->get();
            $user = Auth::user();

            foreach ($documents as $document) {
                if ($request->action === 'verify') {
                    $document->verify($user, $request->verification_notes);
                } else {
                    $document->reject($user, $request->verification_notes ?: 'Bulk rejection');
                }
            }

            $message = $request->action === 'verify' ? 'Documents verified successfully.' : 'Documents rejected successfully.';
            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Failed to process bulk action', [
                'action' => $request->action,
                'document_ids' => $request->document_ids,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', UserFriendlyErrorService::getErrorMessage($e, 'general'));
        }
    }
}
