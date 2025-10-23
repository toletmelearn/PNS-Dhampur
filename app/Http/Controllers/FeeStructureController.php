<?php

namespace App\Http\Controllers;

use App\Models\FeeStructure;
use App\Models\FeeStructureItem;
use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeeStructureController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:super_admin,admin']);
    }

    public function index()
    {
        $structures = FeeStructure::with('classModel', 'items')->orderBy('academic_year', 'desc')->paginate(15);
        $classes = ClassModel::orderBy('name')->get();
        return view('finance.fees.structures.index', compact('structures', 'classes'));
    }

    public function create()
    {
        $classes = ClassModel::orderBy('name')->get();
        return view('finance.fees.structures.create', compact('classes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_model_id' => 'required|exists:class_models,id',
            'name' => 'required|string|max:255',
            'academic_year' => 'required|string|max:20',
            'description' => 'nullable|string',
            'items' => 'array',
            'items.*.item_name' => 'required_with:items|string|max:255',
            'items.*.amount' => 'required_with:items|numeric|min:0',
            'items.*.frequency' => 'required_with:items|in:monthly,annual,one_time',
            'items.*.due_day' => 'nullable|integer|min:1|max:31',
        ]);

        DB::transaction(function () use ($validated) {
            $structure = FeeStructure::create([
                'class_model_id' => $validated['class_model_id'],
                'name' => $validated['name'],
                'academic_year' => $validated['academic_year'],
                'description' => $validated['description'] ?? null,
                'is_active' => true,
            ]);

            foreach (($validated['items'] ?? []) as $idx => $item) {
                FeeStructureItem::create([
                    'fee_structure_id' => $structure->id,
                    'item_name' => $item['item_name'],
                    'amount' => $item['amount'],
                    'frequency' => $item['frequency'],
                    'due_day' => $item['due_day'] ?? null,
                    'position' => $idx,
                    'is_active' => true,
                ]);
            }
        });

        return redirect()->route('fee-structures.index')->with('success', 'Fee structure created');
    }

    public function edit(FeeStructure $feeStructure)
    {
        $classes = ClassModel::orderBy('name')->get();
        $feeStructure->load('items');
        return view('finance.fees.structures.edit', compact('feeStructure', 'classes'));
    }

    public function update(Request $request, FeeStructure $feeStructure)
    {
        $validated = $request->validate([
            'class_model_id' => 'required|exists:class_models,id',
            'name' => 'required|string|max:255',
            'academic_year' => 'required|string|max:20',
            'items' => 'array',
            'items.*.id' => 'nullable|exists:fee_structure_items,id',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.amount' => 'required|numeric|min:0',
            'items.*.frequency' => 'required|in:monthly,annual,one_time',
            'items.*.due_day' => 'nullable|integer|min:1|max:31',
        ]);

        DB::transaction(function () use ($validated, $feeStructure) {
            $feeStructure->update([
                'class_model_id' => $validated['class_model_id'],
                'name' => $validated['name'],
                'academic_year' => $validated['academic_year'],
                'description' => request('description'),
            ]);

            // Sync items (simple replace strategy)
            $feeStructure->items()->delete();
            foreach (($validated['items'] ?? []) as $idx => $item) {
                FeeStructureItem::create([
                    'fee_structure_id' => $feeStructure->id,
                    'item_name' => $item['item_name'],
                    'amount' => $item['amount'],
                    'frequency' => $item['frequency'],
                    'due_day' => $item['due_day'] ?? null,
                    'position' => $idx,
                    'is_active' => true,
                ]);
            }
        });

        return redirect()->route('fee-structures.index')->with('success', 'Fee structure updated');
    }

    public function destroy(FeeStructure $feeStructure)
    {
        $feeStructure->delete();
        return redirect()->route('fee-structures.index')->with('success', 'Fee structure deleted');
    }
}