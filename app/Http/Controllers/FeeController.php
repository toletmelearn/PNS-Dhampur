<?php

namespace App\Http\Controllers;

use App\Models\Fee;
use Illuminate\Http\Request;

class FeeController extends Controller
{
    public function index()
    {
        return response()->json(Fee::with('student')->get());
    }

    public function show($id)
    {
        return response()->json(Fee::with('student')->findOrFail($id));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id'=>'required|exists:students,id',
            'amount'=>'required|numeric',
            'due_date'=>'nullable|date',
            'paid_amount'=>'nullable|numeric',
            'paid_date'=>'nullable|date',
            'status'=>'nullable|in:paid,unpaid,partial',
            'remarks'=>'nullable|string'
        ]);

        $fee = Fee::create($data);
        return response()->json($fee);
    }

    public function update(Request $request, $id)
    {
        $fee = Fee::findOrFail($id);
        $data = $request->validate([
            'amount'=>'sometimes|numeric',
            'due_date'=>'sometimes|date',
            'paid_amount'=>'sometimes|numeric',
            'paid_date'=>'sometimes|date',
            'status'=>'sometimes|in:paid,unpaid,partial',
            'remarks'=>'sometimes|string'
        ]);
        $fee->update($data);
        return response()->json($fee);
    }

    public function destroy($id)
    {
        $fee = Fee::findOrFail($id);
        $fee->delete();
        return response()->json(['message'=>'Fee record deleted']);
    }
}
