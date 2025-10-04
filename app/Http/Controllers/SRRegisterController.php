<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ClassModel;
use App\Models\SRRegister;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SRRegisterController extends Controller
{
    public function index(Request $request)
    {
        $query = SRRegister::with(['student', 'class', 'subject', 'teacher', 'updatedBy']);
        
        // Apply filters
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }
        
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }
        
        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }
        
        if ($request->filled('academic_year')) {
            $query->where('academic_year', $request->academic_year);
        }
        
        if ($request->filled('term')) {
            $query->where('term', $request->term);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('student', function($studentQuery) use ($search) {
                    $studentQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('admission_number', 'like', "%{$search}%");
                })
                ->orWhereHas('subject', function($subjectQuery) use ($search) {
                    $subjectQuery->where('name', 'like', "%{$search}%");
                });
            });
        }
        
        // Filter by class teacher permissions
        if (!auth()->user()->hasRole('admin')) {
            $teacherClasses = auth()->user()->teacher->classes ?? collect();
            if ($teacherClasses->isNotEmpty()) {
                $query->whereIn('class_id', $teacherClasses->pluck('id'));
            } else {
                // If teacher has no assigned classes, show empty results
                $query->where('id', 0);
            }
        }
        
        $srRegisters = $query->orderBy('created_at', 'desc')->paginate(20);
        
        // Get filter options
        $classes = ClassModel::orderBy('name')->get();
        $subjects = Subject::orderBy('name')->get();
        $students = Student::with(['classModel', 'user'])->orderBy('name')->get();
        $academicYears = SRRegister::distinct()->pluck('academic_year')->sort()->values();
        
        return view('sr-register.index', compact('srRegisters', 'classes', 'subjects', 'students', 'academicYears'));
    }

    public function create(Request $request)
    {
        $classes = ClassModel::orderBy('name')->get();
        $subjects = Subject::orderBy('name')->get();
        
        // Pre-select class and subject if provided
        $selectedClass = $request->get('class_id');
        $selectedSubject = $request->get('subject_id');
        
        $students = collect();
        if ($selectedClass) {
            $students = Student::with(['classModel', 'user'])->where('class_id', $selectedClass)->orderBy('name')->get();
        }
        
        return view('sr-register.create', compact('classes', 'subjects', 'students', 'selectedClass', 'selectedSubject'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'class_id' => 'required|exists:class_models,id',
            'subject_id' => 'required|exists:subjects,id',
            'academic_year' => 'required|string|max:20',
            'term' => 'required|in:1,2,3,annual',
            'records' => 'required|array|min:1',
            'records.*.student_id' => 'required|exists:students,id',
            'records.*.attendance_percentage' => 'nullable|numeric|min:0|max:100',
            'records.*.theory_marks' => 'nullable|numeric|min:0|max:100',
            'records.*.practical_marks' => 'nullable|numeric|min:0|max:100',
            'records.*.internal_assessment' => 'nullable|numeric|min:0|max:100',
            'records.*.project_marks' => 'nullable|numeric|min:0|max:100',
            'records.*.total_marks' => 'nullable|numeric|min:0|max:500',
            'records.*.grade' => 'nullable|string|max:5',
            'records.*.remarks' => 'nullable|string|max:500',
            'records.*.conduct_grade' => 'nullable|in:A,B,C,D,E',
            'records.*.discipline_remarks' => 'nullable|string|max:500',
            'records.*.co_curricular_activities' => 'nullable|string|max:1000',
            'records.*.sports_achievements' => 'nullable|string|max:1000',
            'records.*.special_achievements' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            foreach ($request->records as $recordData) {
                // Check if record already exists
                $existingRecord = SRRegister::where([
                    'student_id' => $recordData['student_id'],
                    'class_id' => $request->class_id,
                    'subject_id' => $request->subject_id,
                    'academic_year' => $request->academic_year,
                    'term' => $request->term
                ])->first();

                if ($existingRecord) {
                    // Update existing record
                    $existingRecord->update([
                        'attendance_percentage' => $recordData['attendance_percentage'] ?? null,
                        'theory_marks' => $recordData['theory_marks'] ?? null,
                        'practical_marks' => $recordData['practical_marks'] ?? null,
                        'internal_assessment' => $recordData['internal_assessment'] ?? null,
                        'project_marks' => $recordData['project_marks'] ?? null,
                        'total_marks' => $recordData['total_marks'] ?? null,
                        'grade' => $recordData['grade'] ?? null,
                        'remarks' => $recordData['remarks'] ?? null,
                        'conduct_grade' => $recordData['conduct_grade'] ?? null,
                        'discipline_remarks' => $recordData['discipline_remarks'] ?? null,
                        'co_curricular_activities' => $recordData['co_curricular_activities'] ?? null,
                        'sports_achievements' => $recordData['sports_achievements'] ?? null,
                        'special_achievements' => $recordData['special_achievements'] ?? null,
                        'teacher_id' => auth()->id(),
                        'updated_by' => auth()->id(),
                        'last_updated_at' => now()
                    ]);
                } else {
                    // Create new record
                    SRRegister::create([
                        'student_id' => $recordData['student_id'],
                        'class_id' => $request->class_id,
                        'subject_id' => $request->subject_id,
                        'academic_year' => $request->academic_year,
                        'term' => $request->term,
                        'attendance_percentage' => $recordData['attendance_percentage'] ?? null,
                        'theory_marks' => $recordData['theory_marks'] ?? null,
                        'practical_marks' => $recordData['practical_marks'] ?? null,
                        'internal_assessment' => $recordData['internal_assessment'] ?? null,
                        'project_marks' => $recordData['project_marks'] ?? null,
                        'total_marks' => $recordData['total_marks'] ?? null,
                        'grade' => $recordData['grade'] ?? null,
                        'remarks' => $recordData['remarks'] ?? null,
                        'conduct_grade' => $recordData['conduct_grade'] ?? null,
                        'discipline_remarks' => $recordData['discipline_remarks'] ?? null,
                        'co_curricular_activities' => $recordData['co_curricular_activities'] ?? null,
                        'sports_achievements' => $recordData['sports_achievements'] ?? null,
                        'special_achievements' => $recordData['special_achievements'] ?? null,
                        'teacher_id' => auth()->id(),
                        'updated_by' => auth()->id(),
                        'last_updated_at' => now()
                    ]);
                }
            }

            return redirect()->route('sr-register.index')
                ->with('success', 'SR Register records saved successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to save SR Register records: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(SRRegister $srRegister)
    {
        $srRegister->load(['student', 'class', 'subject', 'teacher', 'updatedBy']);
        
        // Check permissions
        if (!$this->canViewRecord($srRegister)) {
            abort(403, 'Unauthorized access to SR Register record.');
        }
        
        // Get audit trail
        $auditTrail = $this->getAuditTrail($srRegister);
        
        return view('sr-register.show', compact('srRegister', 'auditTrail'));
    }

    public function edit(SRRegister $srRegister)
    {
        // Check permissions
        if (!$this->canEditRecord($srRegister)) {
            abort(403, 'Unauthorized access to edit SR Register record.');
        }
        
        $srRegister->load(['student', 'class', 'subject']);
        
        $classes = ClassModel::orderBy('name')->get();
        $subjects = Subject::orderBy('name')->get();
        
        return view('sr-register.edit', compact('srRegister', 'classes', 'subjects'));
    }

    public function update(Request $request, SRRegister $srRegister)
    {
        // Check permissions
        if (!$this->canEditRecord($srRegister)) {
            abort(403, 'Unauthorized access to edit SR Register record.');
        }

        $validator = Validator::make($request->all(), [
            'attendance_percentage' => 'nullable|numeric|min:0|max:100',
            'theory_marks' => 'nullable|numeric|min:0|max:100',
            'practical_marks' => 'nullable|numeric|min:0|max:100',
            'internal_assessment' => 'nullable|numeric|min:0|max:100',
            'project_marks' => 'nullable|numeric|min:0|max:100',
            'total_marks' => 'nullable|numeric|min:0|max:500',
            'grade' => 'nullable|string|max:5',
            'remarks' => 'nullable|string|max:500',
            'conduct_grade' => 'nullable|in:A,B,C,D,E',
            'discipline_remarks' => 'nullable|string|max:500',
            'co_curricular_activities' => 'nullable|string|max:1000',
            'sports_achievements' => 'nullable|string|max:1000',
            'special_achievements' => 'nullable|string|max:1000',
            'correction_reason' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Store original data for audit trail
            $originalData = $srRegister->toArray();
            
            // Update record
            $srRegister->update([
                'attendance_percentage' => $request->attendance_percentage,
                'theory_marks' => $request->theory_marks,
                'practical_marks' => $request->practical_marks,
                'internal_assessment' => $request->internal_assessment,
                'project_marks' => $request->project_marks,
                'total_marks' => $request->total_marks,
                'grade' => $request->grade,
                'remarks' => $request->remarks,
                'conduct_grade' => $request->conduct_grade,
                'discipline_remarks' => $request->discipline_remarks,
                'co_curricular_activities' => $request->co_curricular_activities,
                'sports_achievements' => $request->sports_achievements,
                'special_achievements' => $request->special_achievements,
                'updated_by' => auth()->id(),
                'last_updated_at' => now()
            ]);
            
            // Create audit trail entry
            $this->createAuditTrail($srRegister, $originalData, $request->correction_reason);

            return redirect()->route('sr-register.show', $srRegister)
                ->with('success', 'SR Register record updated successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update SR Register record: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(SRRegister $srRegister)
    {
        // Only admin can delete
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Only administrators can delete SR Register records.');
        }

        try {
            $srRegister->delete();
            
            return redirect()->route('sr-register.index')
                ->with('success', 'SR Register record deleted successfully!');
                
        } catch (\Exception $e) {
            return redirect()->route('sr-register.index')
                ->with('error', 'Failed to delete SR Register record: ' . $e->getMessage());
        }
    }

    public function bulkEntry(Request $request)
    {
        $classes = ClassModel::orderBy('name')->get();
        $subjects = Subject::orderBy('name')->get();
        
        $selectedClass = $request->get('class_id');
        $selectedSubject = $request->get('subject_id');
        $academicYear = $request->get('academic_year', date('Y'));
        $term = $request->get('term', '1');
        
        $students = collect();
        $existingRecords = collect();
        
        if ($selectedClass && $selectedSubject) {
            $students = Student::where('class_id', $selectedClass)
                ->orderBy('name')
                ->get();
                
            // Get existing records for this class/subject/term
            $existingRecords = SRRegister::where([
                'class_id' => $selectedClass,
                'subject_id' => $selectedSubject,
                'academic_year' => $academicYear,
                'term' => $term
            ])->get()->keyBy('student_id');
        }
        
        return view('sr-register.bulk-entry', compact(
            'classes', 'subjects', 'students', 'existingRecords',
            'selectedClass', 'selectedSubject', 'academicYear', 'term'
        ));
    }

    public function getStudentsByClass(Request $request)
    {
        $classId = $request->get('class_id');
        
        if (!$classId) {
            return response()->json(['students' => []]);
        }
        
        $students = Student::where('class_id', $classId)
            ->orderBy('name')
            ->get(['id', 'name', 'admission_number']);
            
        return response()->json(['students' => $students]);
    }

    public function exportReport(Request $request)
    {
        $classId = $request->get('class_id');
        $subjectId = $request->get('subject_id');
        $academicYear = $request->get('academic_year');
        $term = $request->get('term');
        
        if (!$classId || !$academicYear || !$term) {
            return redirect()->back()
                ->with('error', 'Please select class, academic year, and term for export.');
        }
        
        $query = SRRegister::with(['student', 'class', 'subject'])
            ->where('class_id', $classId)
            ->where('academic_year', $academicYear)
            ->where('term', $term);
            
        if ($subjectId) {
            $query->where('subject_id', $subjectId);
        }
        
        $records = $query->orderBy('student.name')->get();
        
        // Generate CSV or PDF export
        return response()->json([
            'success' => true,
            'message' => 'Export functionality to be implemented',
            'data' => $records->count() . ' records found'
        ]);
    }

    public function studentProfile(Student $student)
    {
        $student->load(['class', 'srRegisters.subject', 'srRegisters.teacher']);
        
        // Check permissions
        if (!$this->canViewStudentProfile($student)) {
            abort(403, 'Unauthorized access to student profile.');
        }
        
        // Group records by academic year and term
        $recordsByYear = $student->srRegisters
            ->groupBy('academic_year')
            ->map(function($yearRecords) {
                return $yearRecords->groupBy('term');
            });
        
        return view('sr-register.student-profile', compact('student', 'recordsByYear'));
    }

    public function classReport(Request $request)
    {
        $classId = $request->get('class_id');
        $academicYear = $request->get('academic_year', date('Y'));
        $term = $request->get('term', '1');
        
        if (!$classId) {
            $classes = ClassModel::orderBy('name')->get();
            return view('sr-register.class-report', compact('classes'));
        }
        
        $class = ClassModel::findOrFail($classId);
        
        // Get all students in the class
        $students = Student::where('class_id', $classId)
            ->with(['srRegisters' => function($query) use ($academicYear, $term) {
                $query->where('academic_year', $academicYear)
                      ->where('term', $term)
                      ->with('subject');
            }])
            ->orderBy('name')
            ->get();
        
        // Get all subjects for this class
        $subjects = Subject::whereHas('srRegisters', function($query) use ($classId, $academicYear, $term) {
            $query->where('class_id', $classId)
                  ->where('academic_year', $academicYear)
                  ->where('term', $term);
        })->orderBy('name')->get();
        
        $classes = ClassModel::orderBy('name')->get();
        
        return view('sr-register.class-report', compact(
            'class', 'students', 'subjects', 'classes', 
            'classId', 'academicYear', 'term'
        ));
    }

    // Private helper methods
    private function canViewRecord(SRRegister $record)
    {
        if (auth()->user()->hasRole('admin')) {
            return true;
        }
        
        // Class teachers can view records of their assigned classes
        $teacherClasses = auth()->user()->teacher->classes ?? collect();
        return $teacherClasses->contains('id', $record->class_id);
    }

    private function canEditRecord(SRRegister $record)
    {
        if (auth()->user()->hasRole('admin')) {
            return true;
        }
        
        // Class teachers can edit records of their assigned classes
        $teacherClasses = auth()->user()->teacher->classes ?? collect();
        return $teacherClasses->contains('id', $record->class_id);
    }

    private function canViewStudentProfile(Student $student)
    {
        if (auth()->user()->hasRole('admin')) {
            return true;
        }
        
        // Class teachers can view profiles of students in their assigned classes
        $teacherClasses = auth()->user()->teacher->classes ?? collect();
        return $teacherClasses->contains('id', $student->class_id);
    }

    private function createAuditTrail(SRRegister $record, array $originalData, string $reason)
    {
        $changes = [];
        $currentData = $record->toArray();
        
        foreach ($currentData as $field => $newValue) {
            $oldValue = $originalData[$field] ?? null;
            if ($oldValue != $newValue && !in_array($field, ['updated_at', 'updated_by', 'last_updated_at'])) {
                $changes[$field] = [
                    'old' => $oldValue,
                    'new' => $newValue
                ];
            }
        }
        
        if (!empty($changes)) {
            // Store audit trail (would need to create SRRegisterAudit model)
            // For now, we'll log it
            \Log::info('SR Register Updated', [
                'record_id' => $record->id,
                'user_id' => auth()->id(),
                'reason' => $reason,
                'changes' => $changes,
                'timestamp' => now()
            ]);
        }
    }

    private function getAuditTrail(SRRegister $record)
    {
        // This would fetch from SRRegisterAudit model
        // For now, return empty collection
        return collect();
    }
}