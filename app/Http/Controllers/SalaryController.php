<?php

namespace App\Http\Controllers;

use App\Models\Salary;
use Illuminate\Http\Request;

class SalaryController extends Controller
{
    // GET /api/salaries
    public function index()
    {
        return response()->json(Salary::with('teacher')->get());
    }

    // GET /api/salaries/{id}
    public function show($id)
    {
        return response()->json(Salary::with('teacher')->findOrFail($id));
    }

    // POST /api/salaries
    public function store(Request $request)
    {
        $data = $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer',
            'basic' => 'nullable|numeric',
            'allowances' => 'nullable|array',
            'deductions' => 'nullable|array',
            'net_salary' => 'nullable|numeric',
            'paid_date' => 'nullable|date'
        ]);

        // Optional: calculate net_salary automatically if not provided
        if (!isset($data['net_salary'])) {
            $basic = $data['basic'] ?? 0;
            $allowances = array_sum($data['allowances'] ?? []);
            $deductions = array_sum($data['deductions'] ?? []);
            $data['net_salary'] = $basic + $allowances - $deductions;
        }

        $salary = Salary::create($data);
        return response()->json($salary, 201);
    }

    // PUT /api/salaries/{id}
    public function update(Request $request, $id)
    {
        $salary = Salary::findOrFail($id);

        $data = $request->validate([
            'basic' => 'sometimes|numeric',
            'allowances' => 'sometimes|array',
            'deductions' => 'sometimes|array',
            'net_salary' => 'sometimes|numeric',
            'paid_date' => 'sometimes|date'
        ]);

        // Optional: recalculate net_salary if basic, allowances, or deductions are updated
        if (isset($data['basic']) || isset($data['allowances']) || isset($data['deductions'])) {
            $basic = $data['basic'] ?? $salary->basic;
            $allowances = array_sum($data['allowances'] ?? $salary->allowances ?? []);
            $deductions = array_sum($data['deductions'] ?? $salary->deductions ?? []);
            $data['net_salary'] = $basic + $allowances - $deductions;
        }

        $salary->update($data);
        return response()->json($salary);
    }

    // DELETE /api/salaries/{id}
    public function destroy($id)
    {
        $salary = Salary::findOrFail($id);
        $salary->delete();
        return response()->json(['message' => 'Salary record deleted']);
    }

    // POST /api/salaries/{id}/pay
    public function pay(Request $request, $id)
    {
        $salary = Salary::findOrFail($id);

        if ($salary->paid_date) {
            return response()->json(['message' => 'Salary already paid'], 400);
        }

        $data = $request->validate([
            'paid_date' => 'required|date',
        ]);

        $salary->update(['paid_date' => $data['paid_date']]);

        return response()->json([
            'message' => 'Salary paid successfully',
            'salary' => $salary
        ]);
    }
}
