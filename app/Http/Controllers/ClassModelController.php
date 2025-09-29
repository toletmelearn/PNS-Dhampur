<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use Illuminate\Http\Request;

class ClassModelController extends Controller
{
    public function index()
    {
        return response()->json(ClassModel::with('teacher','students','exams')->get());
    }

    public function show($id)
    {
        return response()->json(ClassModel::with('teacher','students','exams')->findOrFail($id));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'=>'required|string',
            'section'=>'nullable|string',
            'class_teacher_id'=>'nullable|exists:teachers,id'
        ]);

        $class = ClassModel::create($data);
        return response()->json($class);
    }

    public function update(Request $request, $id)
    {
        $class = ClassModel::findOrFail($id);
        $data = $request->validate([
            'name'=>'sometimes|string',
            'section'=>'sometimes|string',
            'class_teacher_id'=>'sometimes|exists:teachers,id'
        ]);
        $class->update($data);
        return response()->json($class);
    }

    public function destroy($id)
    {
        $class = ClassModel::findOrFail($id);
        $class->delete();
        return response()->json(['message'=>'Class deleted']);
    }
}
