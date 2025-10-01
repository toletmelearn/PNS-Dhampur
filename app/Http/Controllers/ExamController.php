<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function index()
    {
        return response()->json(Exam::with('class')->get());
    }

    public function show($id)
    {
        return response()->json(Exam::with('class')->findOrFail($id));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'class_id' => 'required|exists:class_models,id',
            'date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        $exam = Exam::create($data);
        return response()->json($exam, 201);
    }

    public function update(Request $request, $id)
    {
        $exam = Exam::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'class_id' => 'sometimes|required|exists:class_models,id',
            'date' => 'sometimes|required|date',
            'description' => 'nullable|string',
        ]);

        $exam->update($data);
        return response()->json($exam);
    }

    public function destroy($id)
    {
        $exam = Exam::findOrFail($id);
        $exam->delete();
        return response()->json(['message' => 'Exam deleted']);
    }
}
