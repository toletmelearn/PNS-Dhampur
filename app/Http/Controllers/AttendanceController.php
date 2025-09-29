<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index()
    {
        return response()->json(Attendance::with('student','class','markedBy')->get());
    }

    public function show($id)
    {
        return response()->json(Attendance::with('student','class','markedBy')->findOrFail($id));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id'=>'required|exists:students,id',
            'class_id'=>'nullable|exists:classes,id',
            'date'=>'required|date',
            'status'=>'required|in:present,absent,late',
            'marked_by'=>'nullable|exists:users,id'
        ]);

        $attendance = Attendance::create($data);
        return response()->json($attendance);
    }

    public function update(Request $request, $id)
    {
        $attendance = Attendance::findOrFail($id);
        $data = $request->validate([
            'status'=>'sometimes|in:present,absent,late'
        ]);
        $attendance->update($data);
        return response()->json($attendance);
    }

    public function destroy($id)
    {
        $attendance = Attendance::findOrFail($id);
        $attendance->delete();
        return response()->json(['message'=>'Attendance deleted']);
    }
}
