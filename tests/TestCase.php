<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string',
        'father_name' => 'required|string',
        'mother_name' => 'required|string',
        'dob' => 'required|date',
        'class_id' => 'required|integer',
        'birth_cert' => 'required|file|mimes:pdf,jpg,jpeg,png',
        'aadhaar_file' => 'required|file|mimes:pdf,jpg,jpeg,png',
    ]);

    $birthPath = $request->file('birth_cert')->store('uploads/students', 'public');
    $aadhaarPath = $request->file('aadhaar_file')->store('uploads/students', 'public');

    $student = Student::create([
        'name' => $request->name,
        'father_name' => $request->father_name,
        'mother_name' => $request->mother_name,
        'dob' => $request->dob,
        'class_id' => $request->class_id,
        'birth_cert' => $birthPath,
        'aadhaar_file' => $aadhaarPath,
        'verified' => false,
    ]);

    return response()->json(['student' => $student]);
}

public function verify(Request $request, $id)
{
    $student = Student::findOrFail($id);

    $verifiedData = $request->input('verified_data');

    // Simple check (name + father + DOB) – can add OCR later
    $student->verified = (
        $student->name === $verifiedData['name'] &&
        $student->father_name === $verifiedData['father_name'] &&
        $student->dob === $verifiedData['dob']
    );

    $student->save();

    return response()->json(['student' => $student]);
}

