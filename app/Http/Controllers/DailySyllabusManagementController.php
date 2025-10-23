<?php

namespace App\Http\Controllers;

use App\Models\DailySyllabus;
use App\Models\SubjectMaterial;
use App\Models\SyllabusProgress;
use App\Models\StudentAccessLog;
use App\Models\MaterialComment;
use App\Traits\HandlesApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DailySyllabusManagementController extends Controller
{
    use HandlesApiResponses;

    /**
     * Upload material linked to a DailySyllabus.
     */
    public function uploadMaterial(Request $request)
    {
        $validation = $this->validateAndHandle($request, [
            'class_id'   => ['required','integer','exists:class_models,id'],
            'subject_id' => ['required','integer','exists:subjects,id'],
            'date'       => ['required','date'],
            'title'      => ['required','string','max:255'],
            'description'=> ['nullable','string'],
            'visibility' => ['nullable','in:public,class_only,private'],
            'tags'       => ['nullable'],
            'file'       => ['required','file','mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png,gif,mp4,mov,avi,mkv']
        ], [], 'daily_syllabus_upload_validation');
        if ($validation) { return $validation; }

        return $this->handleFileUpload($request, function () use ($request) {
            $user = Auth::user();
            if (!$user) {
                abort(401, 'Unauthenticated');
            }

            // Ensure teacher_id is set if available via relation
            $teacherId = optional($user->teacher)->id;

            // Create or fetch DailySyllabus entry
            $syllabus = DailySyllabus::firstOrCreate([
                'class_id' => $request->integer('class_id'),
                'subject_id' => $request->integer('subject_id'),
                'date' => Carbon::parse($request->input('date'))->toDateString(),
                'title' => $request->input('title'),
            ], [
                'description' => $request->input('description'),
                'tags' => $this->normalizeTags($request->input('tags')),
                'visibility' => $request->input('visibility') ?? 'class_only',
                'is_active' => true,
                'teacher_id' => $teacherId,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            // Store file
            $file = $request->file('file');
            $datePath = Carbon::parse($syllabus->date)->format('Y/m/d');
            $basePath = "daily-syllabus/{$datePath}/class-{$syllabus->class_id}/subject-{$syllabus->subject_id}";
            $original = $file->getClientOriginalName();
            $ext = strtolower($file->getClientOriginalExtension());
            $slug = Str::slug(pathinfo($original, PATHINFO_FILENAME));
            $fileName = $slug . '-' . Str::uuid() . '.' . $ext;
            $disk = 'public';
            $storedPath = Storage::disk($disk)->putFileAs($basePath, $file, $fileName);

            // Determine material type
            $type = $this->detectMaterialType($ext, $file->getMimeType());

            // Create material record
            $material = SubjectMaterial::create([
                'daily_syllabus_id' => $syllabus->id,
                'uploaded_by' => $user->id,
                'type' => $type,
                'title' => $request->input('title'),
                'description' => $request->input('description'),
                'file_path' => $storedPath,
                'original_filename' => $original,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'storage_disk' => $disk,
                'visibility' => $request->input('visibility') ?? 'class_only',
                'is_active' => true,
                'download_count' => 0,
                'view_count' => 0,
            ]);

            return [
                'syllabus' => $syllabus->only(['id','class_id','subject_id','date','title','visibility']),
                'material' => $material->only(['id','type','title','file_path','original_filename','mime_type','file_size','visibility'])
            ];
        }, 'daily_syllabus_upload');
    }

    /**
     * List materials with filters.
     */
    public function listMaterials(Request $request)
    {
        try {
            $query = SubjectMaterial::query()
                ->with(['uploader:id,name', 'syllabus:id,class_id,subject_id,date,title'])
                ->where('is_active', true);

            if ($request->filled('class_id')) {
                $query->whereHas('syllabus', function ($q) use ($request) {
                    $q->where('class_id', $request->integer('class_id'));
                });
            }
            if ($request->filled('subject_id')) {
                $query->whereHas('syllabus', function ($q) use ($request) {
                    $q->where('subject_id', $request->integer('subject_id'));
                });
            }
            if ($request->filled('date_start') && $request->filled('date_end')) {
                $start = Carbon::parse($request->input('date_start'))->toDateString();
                $end = Carbon::parse($request->input('date_end'))->toDateString();
                $query->whereHas('syllabus', function ($q) use ($start, $end) {
                    $q->whereBetween('date', [$start, $end]);
                });
            }

            $perPage = min((int)($request->input('per_page', 20)), 100);
            $materials = $query->orderByDesc('id')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $materials->items(),
                'meta' => [
                    'current_page' => $materials->currentPage(),
                    'per_page' => $materials->perPage(),
                    'total' => $materials->total(),
                    'last_page' => $materials->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, $request, 'daily_syllabus_list');
        }
    }

    /**
     * Download a material and log access.
     */
    public function downloadMaterial(Request $request, int $id)
    {
        try {
            $material = SubjectMaterial::findOrFail($id);
            $disk = $material->storage_disk ?: 'public';
            if (!$material->existsOnDisk()) {
                abort(404, 'File not found');
            }

            // Increment and log
            $material->incrementDownloadCount();
            StudentAccessLog::create([
                'user_id' => Auth::id(),
                'material_id' => $material->id,
                'accessed_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_info' => [
                    'accept' => $request->header('Accept'),
                    'content_type' => $request->header('Content-Type'),
                ],
                'success' => true,
            ]);

            $downloadName = $material->original_filename ?: basename($material->file_path);
            return Storage::disk($disk)->download($material->file_path, $downloadName);
        } catch (\Exception $e) {
            return $this->handleException($e, $request, 'daily_syllabus_download');
        }
    }

    /**
     * Update syllabus progress.
     */
    public function updateProgress(Request $request)
    {
        $validation = $this->validateAndHandle($request, [
            'daily_syllabus_id' => ['required','integer','exists:daily_syllabi,id'],
            'class_id' => ['required','integer','exists:class_models,id'],
            'subject_id' => ['required','integer','exists:subjects,id'],
            'date' => ['required','date'],
            'planned_topics' => ['nullable'],
            'completed_topics' => ['nullable'],
            'completion_percentage' => ['required','numeric','min:0','max:100'],
            'status' => ['required','string','in:not_started,in_progress,completed,blocked'],
            'remarks' => ['nullable','string','max:1000'],
        ], [], 'daily_syllabus_progress_validation');
        if ($validation) { return $validation; }

        return $this->handleTransaction(function () use ($request) {
            $progress = SyllabusProgress::updateOrCreate([
                'daily_syllabus_id' => $request->integer('daily_syllabus_id'),
                'date' => Carbon::parse($request->input('date'))->toDateString(),
            ], [
                'class_id' => $request->integer('class_id'),
                'subject_id' => $request->integer('subject_id'),
                'planned_topics' => $this->normalizeTopics($request->input('planned_topics')),
                'completed_topics' => $this->normalizeTopics($request->input('completed_topics')),
                'completion_percentage' => (float)$request->input('completion_percentage'),
                'status' => $request->input('status'),
                'remarks' => $request->input('remarks'),
                'marked_by' => Auth::id(),
            ]);

            return $progress->only(['id','daily_syllabus_id','date','completion_percentage','status']);
        }, $request, 'daily_syllabus_progress_update');
    }

    /**
     * Get progress summary for a class/subject/date range.
     */
    public function getProgressSummary(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'class_id' => ['required','integer','exists:class_models,id'],
                'subject_id' => ['required','integer','exists:subjects,id'],
                'date_start' => ['required','date'],
                'date_end' => ['required','date','after_or_equal:date_start'],
            ]);
            if ($validation->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validation->errors(),
                    'error_code' => 422
                ], 422);
            }

            $start = Carbon::parse($request->input('date_start'))->toDateString();
            $end = Carbon::parse($request->input('date_end'))->toDateString();

            $progress = SyllabusProgress::where('class_id', $request->integer('class_id'))
                ->where('subject_id', $request->integer('subject_id'))
                ->whereBetween('date', [$start, $end])
                ->get();

            $avgCompletion = round((float)$progress->avg('completion_percentage'), 2);
            $statusCounts = $progress->groupBy('status')->map->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'average_completion' => $avgCompletion,
                    'status_counts' => $statusCounts,
                    'days_tracked' => $progress->count(),
                ]
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, $request, 'daily_syllabus_progress_summary');
        }
    }

    /**
     * Add a comment to a material.
     */
    public function addComment(Request $request, int $id = null)
    {
        if ($id) { $request->merge(['material_id' => $id]); }
        $validation = $this->validateAndHandle($request, [
            'material_id' => ['required','integer','exists:subject_materials,id'],
            'comment' => ['required','string','max:2000'],
            'parent_id' => ['nullable','integer','exists:material_comments,id']
        ], [], 'daily_syllabus_comment_validation');
        if ($validation) { return $validation; }

        return $this->handleTransaction(function () use ($request) {
            $comment = MaterialComment::create([
                'material_id' => $request->integer('material_id'),
                'user_id' => Auth::id(),
                'parent_id' => $request->input('parent_id'),
                'comment' => $request->input('comment'),
                'is_resolved' => false,
            ]);

            return $comment->only(['id','material_id','user_id','comment','parent_id','is_resolved']);
        }, $request, 'daily_syllabus_comment_add');
    }

    public function teacherUploadDailyWork(Request $request)
    {
        return $this->uploadMaterial($request);
    }

    public function listDailyForStudent(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                abort(403, 'Only students can access this endpoint');
            }

            // Resolve student record by authenticated user
            $student = \App\Models\Student::with(['class'])
                ->where('user_id', $user->id)
                ->first();
            if (!$student) {
                abort(403, 'Student profile not found');
            }

            $query = SubjectMaterial::query()
                ->with(['uploader:id,name', 'syllabus:id,class_id,subject_id,date,title'])
                ->where('is_active', true)
                ->whereIn('visibility', ['public', 'class_only'])
                ->whereHas('syllabus', function ($q) use ($student) {
                    $q->where('class_id', $student->class_id);
                });

            if ($request->filled('subject_id')) {
                $query->whereHas('syllabus', function ($q) use ($request) {
                    $q->where('subject_id', $request->integer('subject_id'));
                });
            }
            if ($request->filled('date_start') && $request->filled('date_end')) {
                $start = Carbon::parse($request->input('date_start'))->toDateString();
                $end = Carbon::parse($request->input('date_end'))->toDateString();
                $query->whereHas('syllabus', function ($q) use ($start, $end) {
                    $q->whereBetween('date', [$start, $end]);
                });
            }

            $perPage = min((int)($request->input('per_page', 20)), 100);
            $materials = $query->orderByDesc('id')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $materials->items(),
                'meta' => [
                    'current_page' => $materials->currentPage(),
                    'per_page' => $materials->perPage(),
                    'total' => $materials->total(),
                    'last_page' => $materials->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, $request, 'daily_syllabus_student_list');
        }
    }

    public function updateSyllabusProgress(Request $request)
    {
        return $this->updateProgress($request);
    }

    public function progressSummary(Request $request)
    {
        return $this->getProgressSummary($request);
    }

    public function listComments(Request $request, int $id)
    {
        try {
            $material = SubjectMaterial::findOrFail($id);

            $perPage = min((int)($request->input('per_page', 20)), 100);
            $comments = MaterialComment::where('material_id', $material->id)
                ->with(['user:id,name'])
                ->orderBy('id', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $comments->items(),
                'meta' => [
                    'current_page' => $comments->currentPage(),
                    'per_page' => $comments->perPage(),
                    'total' => $comments->total(),
                    'last_page' => $comments->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, $request, 'daily_syllabus_comment_list');
        }
    }

    private function detectMaterialType(string $ext, ?string $mime): string
    {
        $ext = strtolower($ext);
        if (in_array($ext, ['pdf'])) return 'pdf';
        if (in_array($ext, ['doc','docx','ppt','pptx','xls','xlsx'])) return 'doc';
        if (in_array($ext, ['jpg','jpeg','png','gif'])) return 'image';
        if (in_array($ext, ['mp4','mov','avi','mkv'])) return 'video';
        if ($mime && str_contains($mime, 'image')) return 'image';
        if ($mime && str_contains($mime, 'video')) return 'video';
        if ($mime && str_contains($mime, 'pdf')) return 'pdf';
        return 'other';
    }

    private function normalizeTags($tags): ?array
    {
        if (is_array($tags)) { return $tags; }
        if (is_string($tags)) {
            return collect(explode(',', $tags))
                ->map(fn($t) => trim($t))
                ->filter()
                ->values()
                ->all();
        }
        return null;
    }

    private function normalizeTopics($topics): ?array
    {
        if (is_null($topics)) return null;
        if (is_array($topics)) return $topics;
        if (is_string($topics)) {
            return collect(preg_split('/[\n,]+/', $topics))
                ->map(fn($t) => trim($t))
                ->filter()
                ->values()
                ->all();
        }
        return null;
    }
}