<?php

namespace App\Http\Controllers;

use App\Models\StudentFee;
use App\Models\Student;
use App\Models\FeeStructure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentFeeController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    public function index(Request $request)
    {
        $query = StudentFee::with('student', 'structure');
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        if ($request->filled('academic_year')) {
            $query->where('academic_year', $request->get('academic_year'));
        }
        $fees = $query->orderBy('due_date')->paginate(15);
        return view('finance.fees.student.index', compact('fees'));
    }

    public function assign(Request $request, Student $student)
    {
        $structures = FeeStructure::where('is_active', true)->with('items')->get();
        return view('finance.fees.student.assign', compact('student', 'structures'));
    }

    public function storeAssignment(Request $request, Student $student)
    {
        $validated = $request->validate([
            'fee_structure_id' => 'required|exists:fee_structures,id',
            'academic_year' => 'required|string|max:20',
        ]);

        $structure = FeeStructure::with('items')->findOrFail($validated['fee_structure_id']);

        DB::transaction(function () use ($student, $structure, $validated) {
            foreach ($structure->items as $idx => $item) {
                StudentFee::create([
                    'student_id' => $student->id,
                    'fee_structure_id' => $structure->id,
                    'installment_no' => $idx + 1,
                    'item_name' => $item->item_name,
                    'amount' => $item->amount,
                    'due_date' => now()->setDay($item->due_day ?? 1),
                    'status' => 'pending',
                    'late_fee' => 0,
                    'discount' => 0,
                    'paid_amount' => 0,
                    'academic_year' => $validated['academic_year'],
                ]);
            }
        });

        return redirect()->route('student-fees.index')->with('success', 'Fees assigned to student');
    }

    public function show(StudentFee $studentFee)
    {
        $this->authorize('view', $studentFee);
        $studentFee->load('student');
        return view('finance.fees.student.show', compact('studentFee'));
    }
}