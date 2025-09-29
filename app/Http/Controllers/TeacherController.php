<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    public function index()
    {
        return response()->json(Teacher::with('user','classes')->get());
    }

    public function show($id)
    {
        return response()->json(Teacher::with('user','classes')->findOrFail($id));
    }

    public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string',
        'email' => 'required|email|unique:teachers',
        'document' => 'required|file|mimes:pdf,jpg,jpeg,png',
    ]);

    $docPath = $request->file('document')->store('uploads/teachers', 'public');

    $teacher = Teacher::create([
        'name' => $request->name,
        'email' => $request->email,
        'document' => $docPath,
    ]);

    return response()->json(['teacher' => $teacher]);
}


    public function update(Request $request, $id)
    {
        $teacher = Teacher::findOrFail($id);
        $data = $request->validate([
            'qualification'=>'sometimes|string',
            'experience_years'=>'sometimes|integer',
            'salary'=>'sometimes|numeric',
            'joining_date'=>'sometimes|date',
            'documents'=>'sometimes|array'
        ]);
        $teacher->update($data);
        return response()->json($teacher);
    }

    public function destroy($id)
    {
        $teacher = Teacher::findOrFail($id);
        $teacher->delete();
        return response()->json(['message'=>'Teacher deleted']);
    }
}
