<?php

namespace App\Http\Controllers;

use App\Models\Result;
use Illuminate\Http\Request;

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
}
