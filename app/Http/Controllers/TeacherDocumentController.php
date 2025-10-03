<?php

namespace App\Http\Controllers;

use App\Models\TeacherDocument;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TeacherDocumentController extends Controller
{
    public function __construct()
    {
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
            $teacher = Teacher::where('user_id', $user->id)->first();
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
            'documents.*' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120', // 5MB
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

                // Generate unique filename
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
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
                    'mime_type' => $file->getMimeType(),
                    'expiry_date' => $expiryDate,
                    'uploaded_by' => $user->id,
                    'metadata' => [
                        'upload_ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
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
            $teacher = Teacher::where('user_id', $user->id)->first();
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
            $teacher = Teacher::where('user_id', $user->id)->first();
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
        $request->validate([
            'comments' => 'nullable|string|max:1000'
        ]);

        $document->markAsVerified(auth()->id(), $request->comments);

        return response()->json([
            'success' => true,
            'message' => 'Document approved successfully.'
        ]);
    }

    /**
     * Reject a document (Admin/Principal only)
     */
    public function reject(Request $request, TeacherDocument $document)
    {
        $request->validate([
            'comments' => 'required|string|max:1000'
        ]);

        $document->markAsRejected(auth()->id(), $request->comments);

        return response()->json([
            'success' => true,
            'message' => 'Document rejected successfully.'
        ]);
    }

    /**
     * Bulk approve documents
     */
    public function bulkApprove(Request $request)
    {
        $request->validate([
            'document_ids' => 'required|array',
            'document_ids.*' => 'exists:teacher_documents,id',
            'comments' => 'nullable|string|max:1000'
        ]);

        $documents = TeacherDocument::whereIn('id', $request->document_ids)
                                  ->where('status', 'pending')
                                  ->get();

        foreach ($documents as $document) {
            $document->markAsVerified(auth()->id(), $request->comments);
        }

        return response()->json([
            'success' => true,
            'message' => count($documents) . ' document(s) approved successfully.'
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
            $teacher = Teacher::where('user_id', $user->id)->first();
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
}