<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function index()
    {
        return response()->json(Budget::all());
    }

    public function show($id)
    {
        return response()->json(Budget::findOrFail($id));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'year'=>'required|integer',
            'total_budget'=>'required|numeric',
            'spent_amount'=>'nullable|numeric'
        ]);

        $budget = Budget::create($data);
        return response()->json($budget);
    }

    public function update(Request $request, $id)
    {
        $budget = Budget::findOrFail($id);
        $data = $request->validate([
            'total_budget'=>'sometimes|numeric',
            'spent_amount'=>'sometimes|numeric'
        ]);
        $budget->update($data);
        return response()->json($budget);
    }

    public function destroy($id)
    {
        $budget = Budget::findOrFail($id);
        $budget->delete();
        return response()->json(['message'=>'Budget deleted']);
    }
}
