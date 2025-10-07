<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ClassModel;
use Illuminate\Http\Request;
use App\Helpers\SecurityHelper;

class ExamController extends Controller
{
    public function index(Request $request)
    {
        // Handle web requests
        if (!$request->expectsJson()) {
            return view('academic.exams.index');
        }

        // Handle API requests with filtering
        $query = Exam::with('class');

        // Apply filters
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', SecurityHelper::buildLikePattern($request->search));
        }

        $exams = $query->orderBy('date', 'desc')->get();
        return response()->json($exams);
    }

    public function show($id)
    {
        $exam = Exam::with('class')->findOrFail($id);
        return response()->json($exam);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'class_id' => 'required|exists:class_models,id',
            'date' => 'required|date',
            'time' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'nullable|in:scheduled,ongoing,completed,cancelled'
        ]);

        // Set default status if not provided
        if (!isset($data['status'])) {
            $data['status'] = 'scheduled';
        }

        $exam = Exam::create($data);
        $exam->load('class');

        if ($request->expectsJson()) {
            return response()->json($exam, 201);
        }

        return redirect()->route('exams.index')->with('success', 'Exam scheduled successfully!');
    }

    public function update(Request $request, $id)
    {
        $exam = Exam::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'class_id' => 'sometimes|required|exists:class_models,id',
            'date' => 'sometimes|required|date',
            'time' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'nullable|in:scheduled,ongoing,completed,cancelled'
        ]);

        $exam->update($data);
        $exam->load('class');

        if ($request->expectsJson()) {
            return response()->json($exam);
        }

        return redirect()->route('exams.index')->with('success', 'Exam updated successfully!');
    }

    public function destroy($id)
    {
        $exam = Exam::findOrFail($id);
        $exam->delete();
        
        return response()->json(['message' => 'Exam deleted successfully']);
    }

    /**
     * Get classes for dropdown
     */
    public function getClasses()
    {
        $classes = ClassModel::select('id', 'name', 'section')->get();
        return response()->json($classes);
    }
}
