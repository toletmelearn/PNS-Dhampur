<?php

namespace App\Http\Controllers;

use App\Models\Syllabus;
use Illuminate\Http\Request;

class SyllabusController extends Controller
{
    public function index()
    {
        return response()->json(Syllabus::with('class','teacher')->get());
    }

    public function show($id)
    {
        return response()->json(Syllabus::with('class','teacher')->findOrFail($id));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'class_id'=>'required|exists:classes,id',
            'subject'=>'required|string',
            'teacher_id'=>'nullable|exists:teachers,id',
            'file_path'=>'nullable|string',
            'note'=>'nullable|string'
        ]);

        $syllabus = Syllabus::create($data);
        return response()->json($syllabus);
    }

    public function update(Request $request, $id)
    {
        $syllabus = Syllabus::findOrFail($id);
        $data = $request->validate([
            'subject'=>'sometimes|string',
            'teacher_id'=>'sometimes|exists:teachers,id',
            'file_path'=>'sometimes|string',
            'note'=>'sometimes|string'
        ]);
        $syllabus->update($data);
        return response()->json($syllabus);
    }

    public function destroy($id)
    {
        $syllabus = Syllabus::findOrFail($id);
        $syllabus->delete();
        return response()->json(['message'=>'Syllabus deleted']);
    }
}
