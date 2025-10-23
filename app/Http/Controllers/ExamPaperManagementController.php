<?php

namespace App\Http\Controllers;

use App\Models\ExamPaper;
use App\Models\PaperTemplate;
use App\Models\PaperSubmission;
use App\Models\PaperApproval;
use App\Models\PaperVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExamPaperManagementController extends Controller
{
    // Admin: upload a template
    public function uploadTemplate(Request $request)
    {
        $this->authorizeAdmin();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'required|file|mimes:doc,docx,pdf,odt|max:5120'
        ]);

        $path = $request->file('file')->store('paper_templates');
        $template = PaperTemplate::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'file_path' => $path,
            'mime_type' => $request->file('file')->getClientMimeType(),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
            'is_active' => true,
        ]);

        return response()->json(['message' => 'Template uploaded', 'template' => $template], 201);
    }

    // Teacher: submit paper (text or file)
    public function submitPaper(Request $request, ExamPaper $paper)
    {
        $this->authorizeTeacherOwnsPaper($paper);
        $validated = $request->validate([
            'content_text' => 'nullable|string',
            'file' => 'nullable|file|mimes:doc,docx,pdf,odt|max:5120',
            'notes' => 'nullable|string'
        ]);

        if (empty($validated['content_text']) && !$request->hasFile('file')) {
            return response()->json(['error' => 'Provide text content or upload a file'], 422);
        }

        $path = null; $mime = null;
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('exam_papers/submissions');
            $mime = $request->file('file')->getClientMimeType();
        }

        $submission = PaperSubmission::create([
            'exam_paper_id' => $paper->id,
            'submitted_by' => Auth::id(),
            'content_text' => $validated['content_text'] ?? null,
            'file_path' => $path,
            'mime_type' => $mime,
            'status' => 'pending',
            'notes' => $validated['notes'] ?? null,
        ]);

        // Create a new version snapshot
        $latestVersionNumber = PaperVersion::where('exam_paper_id', $paper->id)->max('version_number') ?? 0;
        PaperVersion::create([
            'exam_paper_id' => $paper->id,
            'version_number' => $latestVersionNumber + 1,
            'content_text' => $validated['content_text'] ?? null,
            'file_path' => $path,
            'mime_type' => $mime,
            'created_by' => Auth::id(),
        ]);

        $paper->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        return response()->json(['message' => 'Paper submitted', 'submission' => $submission], 201);
    }

    // Admin: approve or reject submission
    public function approveSubmission(Request $request, PaperSubmission $submission)
    {
        $this->authorizeAdmin();
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'remarks' => 'nullable|string'
        ]);

        DB::transaction(function () use ($validated, $submission) {
            PaperApproval::create([
                'submission_id' => $submission->id,
                'approved_by' => Auth::id(),
                'status' => $validated['status'],
                'remarks' => $validated['remarks'] ?? null,
            ]);

            $submission->update(['status' => $validated['status']]);

            $paper = $submission->paper;
            if ($validated['status'] === 'approved') {
                $paper->update([
                    'status' => 'approved',
                    'approved_at' => now(),
                    'approved_by' => Auth::id(),
                ]);
            } else {
                $paper->update([
                    'status' => 'rejected',
                    'rejected_at' => now(),
                    'rejected_by' => Auth::id(),
                    'rejection_reason' => $validated['remarks'] ?? null,
                ]);
            }
        });

        return response()->json(['message' => 'Decision recorded'], 200);
    }

    // Versions listing
    public function listVersions(ExamPaper $paper)
    {
        $this->authorizePaperAccess($paper);
        $versions = PaperVersion::where('exam_paper_id', $paper->id)
            ->orderByDesc('version_number')
            ->get();
        return response()->json(['versions' => $versions]);
    }

    // Create a new version manually (teacher)
    public function createVersion(Request $request, ExamPaper $paper)
    {
        $this->authorizeTeacherOwnsPaper($paper);
        $validated = $request->validate([
            'content_text' => 'nullable|string',
            'file' => 'nullable|file|mimes:doc,docx,pdf,odt|max:5120'
        ]);
        if (empty($validated['content_text']) && !$request->hasFile('file')) {
            return response()->json(['error' => 'Provide text content or upload a file'], 422);
        }
        $path = null; $mime = null;
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('exam_papers/versions');
            $mime = $request->file('file')->getClientMimeType();
        }
        $latest = PaperVersion::where('exam_paper_id', $paper->id)->max('version_number') ?? 0;
        $version = PaperVersion::create([
            'exam_paper_id' => $paper->id,
            'version_number' => $latest + 1,
            'content_text' => $validated['content_text'] ?? null,
            'file_path' => $path,
            'mime_type' => $mime,
            'created_by' => Auth::id(),
        ]);
        return response()->json(['message' => 'Version created', 'version' => $version], 201);
    }

    // Secure single download
    public function downloadPaper(ExamPaper $paper): StreamedResponse
    {
        $this->authorizePaperAccess($paper);
        $latestVersion = PaperVersion::where('exam_paper_id', $paper->id)
            ->orderByDesc('version_number')
            ->first();
        if (!$latestVersion || !$latestVersion->file_path) {
            abort(404, 'No file available for download');
        }
        $downloadName = $paper->paper_code . '.' . $this->mapMimeToExtension((string) $latestVersion->mime_type);
        return Storage::download($latestVersion->file_path, $downloadName);
    }

    // Secure bulk download (ZIP)
    public function bulkDownload(Request $request)
    {
        $this->authorizeAdmin();
        $validated = $request->validate([
            'paper_ids' => 'required|array|min:1',
            'paper_ids.*' => 'integer|exists:exam_papers,id'
        ]);
        $papers = ExamPaper::whereIn('id', $validated['paper_ids'])->get();
        $zipFileName = 'exam_papers_' . now()->format('Ymd_His') . '.zip';
        $zipPath = storage_path('app/' . $zipFileName);

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE) !== true) {
            abort(500, 'Could not create ZIP file');
        }

        foreach ($papers as $paper) {
            $latestVersion = PaperVersion::where('exam_paper_id', $paper->id)
                ->orderByDesc('version_number')
                ->first();
            if ($latestVersion && $latestVersion->file_path && Storage::exists($latestVersion->file_path)) {
                $ext = $this->mapMimeToExtension((string) $latestVersion->mime_type);
                $zip->addFile(storage_path('app/' . $latestVersion->file_path), $paper->paper_code . '.' . $ext);
            }
        }
        $zip->close();

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    // Helper authorization methods
    private function authorizeAdmin(): void
    {
        $user = Auth::user();
        if (!$user || !method_exists($user, 'hasRole') || !$user->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }
    }

    private function authorizeTeacherOwnsPaper(ExamPaper $paper): void
    {
        $user = Auth::user();
        if (!$user || (!method_exists($user, 'hasRole') || (!$user->hasRole('teacher') && !$user->hasRole('admin'))) || ($user->hasRole('teacher') && (int)$paper->teacher_id !== (int)$user->id)) {
            abort(403, 'Unauthorized');
        }
    }

    private function authorizePaperAccess(ExamPaper $paper): void
    {
        $user = Auth::user();
        if (!$user) abort(403);
        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) return;
        if (method_exists($user, 'hasRole') && $user->hasRole('teacher') && (int)$paper->teacher_id === (int)$user->id) return;
        abort(403, 'Unauthorized');
    }

    private function mapMimeToExtension(string $mime): string
    {
        switch ($mime) {
            case 'application/pdf':
                return 'pdf';
            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                return 'docx';
            case 'application/msword':
                return 'doc';
            case 'application/vnd.oasis.opendocument.text':
                return 'odt';
            default:
                return 'dat';
        }
    }
}