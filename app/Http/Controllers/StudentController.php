<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index()
    {
        return response()->json(Student::with('user','class')->get());
    }

    public function show($id)
    {
        return response()->json(Student::with('user','class')->findOrFail($id));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id'=>'nullable|exists:users,id',
            'admission_no'=>'nullable|unique:students,admission_no',
            'father_name'=>'nullable|string',
            'mother_name'=>'nullable|string',
            'dob'=>'nullable|date',
            'aadhaar'=>'nullable|string',
            'class_id'=>'nullable|exists:classes,id',
            'documents'=>'nullable|array',
            'status'=>'nullable|in:active,left,alumni'
        ]);

        $student = Student::create($data);
        return response()->json($student);
    }

    public function update(Request $request, $id)
    {
        $student = Student::findOrFail($id);
        $data = $request->validate([
            'father_name'=>'sometimes|string',
            'mother_name'=>'sometimes|string',
            'dob'=>'sometimes|date',
            'aadhaar'=>'sometimes|string',
            'class_id'=>'sometimes|exists:classes,id',
            'documents'=>'sometimes|array',
            'status'=>'sometimes|in:active,left,alumni'
        ]);
        $student->update($data);
        return response()->json($student);
    }

    public function destroy($id)
    {
        $student = Student::findOrFail($id);
        $student->delete();
        return response()->json(['message'=>'Student deleted']);
    }
}
