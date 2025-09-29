<?php

namespace App\Http\Controllers;

use App\Models\Salary;
use Illuminate\Http\Request;

class SalaryController extends Controller
{
    public function index()
    {
        return response()->json(Salary::with('teacher')->get());
    }

    public function show($id)
    {
        return response()->json(Salary::with('teacher')->findOrFail($id));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'teacher_id'=>'required|exists:teachers,id',
            'month'=>'required|integer|min:1|max:12',
            'year'=>'required|integer',
            'basic'=>'nullable|numeric',
            'allowances'=>'nullable|array',
            'deductions'=>'nullable|array',
            'net_salary'=>'nullable|numeric',
            'paid_date'=>'nullable|date'
        ]);

        $salary = Salary::create($data);
        return response()->json($salary);
    }

    public function update(Request $request, $id)
    {
        $salary = Salary::findOrFail($id);
        $data = $request->validate([
            'basic'=>'sometimes|numeric',
            'allowances'=>'sometimes|array',
            'deductions'=>'sometimes|array',
            'net_salary'=>'sometimes|numeric',
            'paid_date'=>'sometimes|date'
        ]);
        $salary->update($data);
        return response()->json($salary);
    }

    public function destroy($id)
    {
        $salary = Salary::findOrFail($id);
        $salary->delete();
        return response()->json(['message'=>'Salary record deleted']);
    }
}
