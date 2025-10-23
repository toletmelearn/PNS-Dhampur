<?php

namespace App\Http\Controllers;

use App\Models\AdmitCard;
use App\Models\AdmitTemplate;
use App\Models\Exam;
use App\Models\Student;
use App\Models\AdmitVerificationLog;
use App\Services\AdmitCardGenerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;

class AdmitApiController extends Controller
{
    protected AdmitCardGenerationService $service;

    public function __construct(AdmitCardGenerationService $service)
    {
        $this->service = $service;
    }

    public function templates()
    {
        $templates = AdmitTemplate::where('is_active', true)->get();
        return response()->json(['success' => true, 'data' => $templates]);
    }

    public function allocate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'exam_id' => 'required|exists:exams,id',
            'class_id' => 'nullable|exists:class_models,id',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:students,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $exam = Exam::findOrFail($request->exam_id);
        $studentsQuery = Student::where('verified', true);
        if ($request->student_ids) {
            $studentsQuery->whereIn('id', $request->student_ids);
        } elseif ($request->class_id) {
            $studentsQuery->where('class_id', $request->class_id);
        } else {
            $studentsQuery->where('class_id', $exam->class_id);
        }
        $students = $studentsQuery->orderBy('roll_number')->get();

        $map = $this->service->ensureSeatAllocations($exam, $students);
        return response()->json(['success' => true, 'allocated' => count($map)]);
    }

    public function generate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'exam_id' => 'required|exists:exams,id',
            'class_id' => 'nullable|exists:class_models,id',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:students,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $result = $this->service->generateAdmitCards(
            $request->exam_id,
            $request->class_id,
            $request->student_ids ?? []
        );

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    public function list(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'exam_id' => 'required|exists:exams,id',
            'class_id' => 'nullable|exists:class_models,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $query = AdmitCard::with(['student', 'exam'])
            ->where('exam_id', $request->exam_id);
        if ($request->class_id) {
            $query->where('class_id', $request->class_id);
        }
        $cards = $query->orderBy('id', 'desc')->paginate(50);
        return response()->json(['success' => true, 'data' => $cards]);
    }

    public function download($id)
    {
        $card = AdmitCard::findOrFail($id);
        if (!$card->pdf_path || !Storage::disk('public')->exists($card->pdf_path)) {
            abort(404, 'PDF not found');
        }
        return Storage::disk('public')->download($card->pdf_path);
    }

    public function bulkDownload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'exam_id' => 'required|exists:exams,id',
            'class_id' => 'nullable|exists:class_models,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $cards = AdmitCard::with(['student', 'exam'])
            ->where('exam_id', $request->exam_id)
            ->when($request->class_id, fn($q) => $q->where('class_id', $request->class_id))
            ->orderBy('id')
            ->get();

        if ($cards->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No admit cards found'], 404);
        }

        $admitCardsData = [];
        foreach ($cards as $card) {
            $admitCardsData[] = [
                'admit_card_no' => $card->admit_card_no,
                'student_name' => $card->student->name ?? ($card->student->first_name . ' ' . $card->student->last_name),
                'admission_no' => $card->student->admission_number ?? $card->student->admission_no ?? 'N/A',
                'class' => $card->classModel?->name,
                'father_name' => $card->student->father_name,
                'mother_name' => $card->student->mother_name,
                'dob' => $card->student->date_of_birth ?? $card->student->dob,
                'exam_subject' => $card->exam->subject,
                'exam_date' => $card->exam->exam_date,
                'exam_time' => ($card->exam->start_time ?? '') . ' - ' . ($card->exam->end_time ?? ''),
                'exam_duration' => ($card->exam->duration ?? '') . ' minutes',
                'total_marks' => $card->exam->total_marks,
                'instructions' => $this->getExamInstructions(),
            ];
        }

        $pdf = Pdf::loadView('pdfs.admit-cards-bulk', [
            'admitCardsData' => $admitCardsData,
            'school_name' => config('app.name'),
            'school_address' => '',
            'generated_at' => now(),
        ]);

        $filename = 'bulk_admit_cards_' . now()->format('Y-m-d_H-i-s') . '.pdf';
        return $pdf->download($filename);
    }

    public function verify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'admit_card_no' => 'nullable|string',
            'qr_data' => 'nullable|array',
            'method' => 'nullable|in:qr,barcode,manual',
            'location' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $method = $request->input('method', 'qr');
        $payload = $request->input('qr_data');
        $admitCard = null;

        if ($payload && isset($payload['admit_card_no'])) {
            $admitCard = AdmitCard::where('admit_card_no', $payload['admit_card_no'])->first();
        } elseif ($request->admit_card_no) {
            $admitCard = AdmitCard::where('admit_card_no', $request->admit_card_no)->first();
        }

        if (!$admitCard) {
            return response()->json(['success' => false, 'message' => 'Admit card not found'], 404);
        }

        $log = AdmitVerificationLog::create([
            'admit_card_id' => $admitCard->id,
            'exam_id' => $admitCard->exam_id,
            'student_id' => $admitCard->student_id,
            'method' => $method,
            'success' => true,
            'scanned_at' => now(),
            'verified_by' => auth()->id(),
            'location' => $request->location,
            'payload' => $payload,
            'notes' => null,
        ]);

        return response()->json(['success' => true, 'data' => $log]);
    }

    private function getExamInstructions(): array
    {
        return [
            'Bring this admit card to the examination hall',
            'Report to the examination center 30 minutes before the exam',
            'Carry a valid photo ID proof',
            'Mobile phones and electronic devices are strictly prohibited',
            'Use only blue or black pen for writing',
        ];
    }
}