<?php

namespace App\Http\Controllers;

use App\Models\SRRegister;
use App\Models\Student;
use App\Models\StudentHistory;
use App\Models\PromotionRecord;
use App\Models\TransferCertificate;
use App\Models\StatisticalReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SRRegisterApiController extends Controller
{
    public function index(Request $request)
    {
        $query = SRRegister::query()->with(['student:id,name,admission_no,class_id', 'class:id,name', 'subject:id,name', 'teacher:id,name']);

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->integer('class_id'));
        }
        if ($request->filled('academic_year')) {
            $query->where('academic_year', $request->string('academic_year'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('student_id')) {
            $query->where('student_id', $request->integer('student_id'));
        }

        $perPage = min(max((int)$request->get('per_page', 25), 1), 100);
        return response()->json($query->paginate($perPage));
    }

    public function search(Request $request)
    {
        $q = $request->string('q');
        $results = SRRegister::query()
            ->with(['student:id,name,admission_no', 'class:id,name'])
            ->when($q, function ($query) use ($q) {
                $query->whereHas('student', function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('admission_no', 'like', "%{$q}%");
                })
                ->orWhereHas('class', function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%");
                });
            })
            ->limit(50)
            ->get();

        return response()->json(['data' => $results]);
    }

    public function studentProfile(int $studentId)
    {
        $student = Student::with(['class:id,name', 'srRegisters' => function ($q) {
            $q->orderByDesc('updated_at');
        }])->findOrFail($studentId);

        return response()->json(['data' => $student]);
    }

    public function histories(int $studentId)
    {
        $data = StudentHistory::with(['class:id,name'])
            ->where('student_id', $studentId)
            ->orderByDesc('academic_year')
            ->get();
        return response()->json(['data' => $data]);
    }

    public function storeHistory(Request $request, int $studentId)
    {
        $validated = $request->validate([
            'academic_year' => 'required|string',
            'class_id' => 'nullable|exists:class_models,id',
            'section' => 'nullable|string',
            'status' => 'required|string',
            'history_data' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        $validated['student_id'] = $studentId;
        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        $history = StudentHistory::create($validated);
        return response()->json(['data' => $history], 201);
    }

    public function promotions(int $studentId)
    {
        $data = PromotionRecord::with(['promotedBy:id,name'])
            ->where('student_id', $studentId)
            ->orderByDesc('promotion_date')
            ->get();
        return response()->json(['data' => $data]);
    }

    public function recordPromotion(Request $request, int $studentId)
    {
        $validated = $request->validate([
            'from_class' => 'nullable|exists:class_models,id',
            'to_class' => 'nullable|exists:class_models,id',
            'academic_year' => 'required|string',
            'promotion_date' => 'nullable|date',
            'remarks' => 'nullable|string',
            'status' => 'nullable|string|in:recorded,approved',
        ]);

        $validated['student_id'] = $studentId;
        $validated['promoted_by'] = Auth::id();

        $promotion = PromotionRecord::create($validated);
        return response()->json(['data' => $promotion], 201);
    }

    public function transfers(int $studentId)
    {
        $data = TransferCertificate::with(['approvedBy:id,name'])
            ->where('student_id', $studentId)
            ->orderByDesc('issue_date')
            ->get();
        return response()->json(['data' => $data]);
    }

    public function issueTC(Request $request, int $studentId)
    {
        $validated = $request->validate([
            'tc_number' => 'required|string|unique:transfer_certificates,tc_number',
            'issue_date' => 'nullable|date',
            'reason' => 'nullable|string',
            'from_school' => 'nullable|string',
            'to_school' => 'nullable|string',
            'status' => 'nullable|string|in:issued,revoked',
            'file_path' => 'nullable|string',
            'meta' => 'nullable|array',
        ]);

        $validated['student_id'] = $studentId;
        $validated['approved_by'] = Auth::id();

        $tc = TransferCertificate::create($validated);
        return response()->json(['data' => $tc], 201);
    }

    public function stats(Request $request)
    {
        $validated = $request->validate([
            'context' => 'required|string',
            'parameters' => 'nullable|array',
            'cache_key' => 'nullable|string',
        ]);

        $context = $validated['context'];
        $params = $validated['parameters'] ?? [];

        $metrics = $this->computeMetrics($context, $params);

        $report = StatisticalReport::create([
            'context' => $context,
            'parameters' => $params,
            'metrics' => $metrics,
            'generated_at' => now(),
            'generated_by' => Auth::id(),
            'cache_key' => $validated['cache_key'] ?? null,
        ]);

        return response()->json(['data' => $report]);
    }

    protected function computeMetrics(string $context, array $params): array
    {
        switch ($context) {
            case 'class_wise':
                return SRRegister::selectRaw('class_id, COUNT(*) as total')
                    ->groupBy('class_id')
                    ->get()
                    ->map(fn($r) => ['class_id' => $r->class_id, 'total' => (int)$r->total])
                    ->toArray();
            case 'status_breakdown':
                return SRRegister::selectRaw('status, COUNT(*) as total')
                    ->groupBy('status')
                    ->get()
                    ->map(fn($r) => ['status' => $r->status, 'total' => (int)$r->total])
                    ->toArray();
            case 'admission_year':
                return SRRegister::selectRaw('academic_year, COUNT(*) as total')
                    ->groupBy('academic_year')
                    ->get()
                    ->map(fn($r) => ['academic_year' => $r->academic_year, 'total' => (int)$r->total])
                    ->toArray();
            default:
                return [];
        }
    }
}
