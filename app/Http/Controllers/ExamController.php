<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function index()
    {
        return response()->json(Exam::with('class','results')->get());
    }

    public function show($id)
    {
        return response()->json(Exam::with('class','results')->findOrFail($id));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'=>'required|string',
            'class_id'=>'nullable|exists:classes,id',
            'start_date'=>'nullable|date',
            'end_date'=>'nullable|date'
        ]);

        $exam = Exam::create($data);
        return response()->json($exam);
    }

    public function update(Request $request, $id)
    {
        $exam = Exam::findOrFail($id);
        $data = $request->validate([
            'name'=>'sometimes|string',
            'class_id'=>'sometimes|exists:classes,id',
            'start_date'=>'sometimes|date',
            'end_date'=>'sometimes|date'
        ]);
        $exam->update($data);
        return response()->json($exam);
    }

    public function destroy($id)
    {
        $exam = Exam::findOrFail($id);
        $exam->delete();
        return response()->json(['message'=>'Exam deleted']);
    }
}
