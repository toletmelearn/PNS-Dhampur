<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\Subject;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    public function assignSubjects(Request $request, Teacher $teacher)
    {
        $validated = $request->validate([
            'subjects' => 'required|array',
            'subjects.*' => 'exists:subjects,id'
        ]);

        // Assign subjects by setting teacher_id for each subject
        Subject::whereIn('id', $validated['subjects'])->update(['teacher_id' => $teacher->id]);

        return response()->json([
            'message' => 'Subjects assigned successfully',
            'teacher_id' => $teacher->id,
            'assigned_subjects' => $validated['subjects']
        ]);
    }
}
