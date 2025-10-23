<?php

namespace App\Http\Controllers;

use App\Models\Result;
use Illuminate\Http\Request;
use App\Models\ResultTemplate;
use App\Models\GradingSystem;
use App\Models\SubjectMark;
use App\Models\ResultCard;
use App\Models\ResultPublish;
use App\Models\ClassModel;
use App\Models\Exam;
use App\Services\ResultGenerationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ResultController extends Controller
{
    public function index()
    {
        return response()->json(Result::with('student','exam','uploadedBy')->get());
    }

    public function show($id)
    {
        return response()->json(Result::with('student','exam','uploadedBy')->findOrFail($id));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id'=>'required|exists:students,id',
            'exam_id'=>'required|exists:exams,id',
            'subject'=>'required|string',
            'marks_obtained'=>'nullable|numeric',
            'total_marks'=>'nullable|numeric',
            'grade'=>'nullable|string',
            'uploaded_by'=>'nullable|exists:users,id'
        ]);

        $result = Result::create($data);
        return response()->json($result);
    }

    public function update(Request $request, $id)
    {
        $result = Result::findOrFail($id);
        $data = $request->validate([
            'marks_obtained'=>'sometimes|numeric',
            'total_marks'=>'sometimes|numeric',
            'grade'=>'sometimes|string'
        ]);
        $result->update($data);
        return response()->json($result);
    }

    public function destroy($id)
    {
        $result = Result::findOrFail($id);
        $result->delete();
        return response()->json(['message'=>'Result deleted']);
    }

    // Result system endpoints
    public function listTemplates()
    {
        return response()->json(ResultTemplate::with('gradingSystem')->orderBy('is_active', 'desc')->orderBy('name')->get());
    }

    public function storeTemplate(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'format' => 'required|string|in:percentage,gpa,cbse,custom',
            'settings' => 'nullable|array',
            'grading_system_id' => 'nullable|exists:grading_systems,id',
            'is_active' => 'boolean'
        ]);
        $data['created_by'] = auth()->id();
        $template = ResultTemplate::updateOrCreate(['name' => $data['name']], $data);
        return response()->json(['template' => $template], 201);
    }

    public function uploadMarks(Request $request)
    {
        $payload = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'class_id' => 'required|exists:class_models,id',
            'subject_id' => 'required|exists:subjects,id',
            'total_marks' => 'nullable|numeric|min:1',
            'template_id' => 'nullable|exists:result_templates,id',
            'finalize' => 'boolean',
            'remarks' => 'nullable|string',
            'marks' => 'required|array',
            'marks.*.student_id' => 'required|exists:students,id',
            'marks.*.marks_obtained' => 'required|numeric|min:0'
        ]);

        DB::beginTransaction();
        try {
            foreach ($payload['marks'] as $item) {
                SubjectMark::updateOrCreate(
                    [
                        'student_id' => $item['student_id'],
                        'exam_id' => $payload['exam_id'],
                        'subject_id' => $payload['subject_id'],
                    ],
                    [
                        'class_id' => $payload['class_id'],
                        'marks_obtained' => $item['marks_obtained'],
                        'total_marks' => $payload['total_marks'] ?? 100,
                        'uploaded_by' => auth()->id(),
                        'template_id' => $payload['template_id'] ?? null,
                        'status' => ($payload['finalize'] ?? false) ? 'finalized' : 'draft',
                        'remarks' => $payload['remarks'] ?? null,
                    ]
                );
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Marks upload failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to upload marks'], 500);
        }

        return response()->json(['message' => 'Marks uploaded successfully']);
    }

    public function generate(Request $request, ResultGenerationService $service)
    {
        $data = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'class_id' => 'required|exists:class_models,id',
            'template_id' => 'nullable|exists:result_templates,id',
            'format' => 'nullable|string|in:percentage,gpa,cbse,custom',
            'generate_pdf' => 'boolean'
        ]);

        try {
            $cards = $service->generateForExamClass(
                (int)$data['exam_id'], (int)$data['class_id'], $data['template_id'] ?? null, $data['format'] ?? null, [
                    'generate_pdf' => $data['generate_pdf'] ?? true
                ]
            );
        } catch (\Throwable $e) {
            Log::error('Result generation failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['count' => count($cards)]);
    }

    public function publish(Request $request)
    {
        $data = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'class_id' => 'required|exists:class_models,id',
            'template_id' => 'nullable|exists:result_templates,id',
            'format' => 'nullable|string|in:percentage,gpa,cbse,custom',
            'status' => 'required|string|in:draft,published,archived'
        ]);

        $publish = ResultPublish::updateOrCreate(
            [
                'exam_id' => $data['exam_id'],
                'class_id' => $data['class_id'],
                'format' => $data['format'] ?? 'percentage',
            ],
            [
                'template_id' => $data['template_id'] ?? null,
                'status' => $data['status'],
                'published_at' => $data['status'] === 'published' ? now() : null,
                'published_by' => auth()->id(),
            ]
        );

        return response()->json(['publish' => $publish]);
    }

    public function listCards(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'class_id' => 'required|exists:class_models,id'
        ]);

        $cards = ResultCard::with(['student.user', 'template'])
            ->where('exam_id', $request->exam_id)
            ->where('class_id', $request->class_id)
            ->orderBy('position')
            ->get();

        return response()->json($cards);
    }

    public function downloadCard($cardId)
    {
        $card = ResultCard::findOrFail($cardId);
        if (!$card->pdf_path) {
            return response()->json(['message' => 'No snapshot available'], 404);
        }
        return response()->json(['url' => $card->pdf_path]);
    }
}
