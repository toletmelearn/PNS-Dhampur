<?php

namespace App\Http\Controllers;

use App\Models\ExamPaper;
use App\Models\Question;
use App\Models\Subject;
use App\Models\ClassModel;
use App\Models\Exam;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ExamPaperController extends Controller
{
    public function index(Request $request)
    {
        $query = ExamPaper::with(['subject', 'class', 'exam', 'teacher', 'questions']);
        
        // Apply filters
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }
        
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }
        
        if ($request->filled('exam_id')) {
            $query->where('exam_id', $request->exam_id);
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('paper_code', 'like', "%{$search}%")
                  ->orWhereHas('subject', function($subQuery) use ($search) {
                      $subQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        // Filter by teacher if not admin
        if (!auth()->user()->hasRole('admin')) {
            $query->where('teacher_id', auth()->id());
        }
        
        $examPapers = $query->orderBy('created_at', 'desc')->paginate(15);
        
        // Get filter options
        $subjects = Subject::orderBy('name')->get();
        $classes = ClassModel::orderBy('name')->get();
        $exams = Exam::orderBy('name')->get();
        
        return view('exam-papers.index', compact('examPapers', 'subjects', 'classes', 'exams'));
    }

    public function create()
    {
        $subjects = Subject::orderBy('name')->get();
        $classes = ClassModel::orderBy('name')->get();
        $exams = Exam::where('status', 'active')->orderBy('name')->get();
        
        return view('exam-papers.create', compact('subjects', 'classes', 'exams'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'subject_id' => 'required|exists:subjects,id',
            'class_id' => 'required|exists:class_models,id',
            'exam_id' => 'required|exists:exams,id',
            'duration_minutes' => 'required|integer|min:30|max:300',
            'total_marks' => 'required|integer|min:1|max:200',
            'instructions' => 'nullable|string',
            'paper_type' => 'required|in:objective,subjective,mixed',
            'difficulty_level' => 'required|in:easy,medium,hard',
            'submission_deadline' => 'required|date|after:now',
            'questions' => 'required|array|min:1',
            'questions.*.question_text' => 'required|string',
            'questions.*.question_type' => 'required|in:mcq,short_answer,long_answer,true_false,fill_blank',
            'questions.*.marks' => 'required|integer|min:1',
            'questions.*.options' => 'nullable|array',
            'questions.*.correct_answer' => 'nullable|string',
            'questions.*.explanation' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Generate unique paper code
            $paperCode = $this->generatePaperCode($request->subject_id, $request->class_id);
            
            // Create exam paper
            $examPaper = ExamPaper::create([
                'title' => $request->title,
                'paper_code' => $paperCode,
                'subject_id' => $request->subject_id,
                'class_id' => $request->class_id,
                'exam_id' => $request->exam_id,
                'teacher_id' => auth()->id(),
                'duration_minutes' => $request->duration_minutes,
                'total_marks' => $request->total_marks,
                'instructions' => $request->instructions,
                'paper_type' => $request->paper_type,
                'difficulty_level' => $request->difficulty_level,
                'submission_deadline' => $request->submission_deadline,
                'status' => 'draft'
            ]);

            // Create questions
            foreach ($request->questions as $index => $questionData) {
                $question = Question::create([
                    'exam_paper_id' => $examPaper->id,
                    'question_text' => $questionData['question_text'],
                    'question_type' => $questionData['question_type'],
                    'marks' => $questionData['marks'],
                    'options' => isset($questionData['options']) ? json_encode($questionData['options']) : null,
                    'correct_answer' => $questionData['correct_answer'] ?? null,
                    'explanation' => $questionData['explanation'] ?? null,
                    'order_number' => $index + 1
                ]);
            }

            return redirect()->route('exam-papers.show', $examPaper)
                ->with('success', 'Exam paper created successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to create exam paper: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(ExamPaper $examPaper)
    {
        $examPaper->load(['subject', 'class', 'exam', 'teacher', 'questions' => function($query) {
            $query->orderBy('order_number');
        }]);
        
        // Check if user can view this paper
        if (!auth()->user()->hasRole('admin') && $examPaper->teacher_id !== auth()->id()) {
            abort(403, 'Unauthorized access to exam paper.');
        }
        
        return view('exam-papers.show', compact('examPaper'));
    }

    public function edit(ExamPaper $examPaper)
    {
        // Check if user can edit this paper
        if (!auth()->user()->hasRole('admin') && $examPaper->teacher_id !== auth()->id()) {
            abort(403, 'Unauthorized access to exam paper.');
        }
        
        // Can't edit if already published or submitted
        if (in_array($examPaper->status, ['published', 'submitted'])) {
            return redirect()->route('exam-papers.show', $examPaper)
                ->with('error', 'Cannot edit exam paper that is already published or submitted.');
        }
        
        $examPaper->load(['questions' => function($query) {
            $query->orderBy('order_number');
        }]);
        
        $subjects = Subject::orderBy('name')->get();
        $classes = ClassModel::orderBy('name')->get();
        $exams = Exam::where('status', 'active')->orderBy('name')->get();
        
        return view('exam-papers.edit', compact('examPaper', 'subjects', 'classes', 'exams'));
    }

    public function update(Request $request, ExamPaper $examPaper)
    {
        // Check if user can update this paper
        if (!auth()->user()->hasRole('admin') && $examPaper->teacher_id !== auth()->id()) {
            abort(403, 'Unauthorized access to exam paper.');
        }
        
        // Can't update if already published or submitted
        if (in_array($examPaper->status, ['published', 'submitted'])) {
            return redirect()->route('exam-papers.show', $examPaper)
                ->with('error', 'Cannot update exam paper that is already published or submitted.');
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'subject_id' => 'required|exists:subjects,id',
            'class_id' => 'required|exists:class_models,id',
            'exam_id' => 'required|exists:exams,id',
            'duration_minutes' => 'required|integer|min:30|max:300',
            'total_marks' => 'required|integer|min:1|max:200',
            'instructions' => 'nullable|string',
            'paper_type' => 'required|in:objective,subjective,mixed',
            'difficulty_level' => 'required|in:easy,medium,hard',
            'submission_deadline' => 'required|date|after:now',
            'questions' => 'required|array|min:1',
            'questions.*.question_text' => 'required|string',
            'questions.*.question_type' => 'required|in:mcq,short_answer,long_answer,true_false,fill_blank',
            'questions.*.marks' => 'required|integer|min:1',
            'questions.*.options' => 'nullable|array',
            'questions.*.correct_answer' => 'nullable|string',
            'questions.*.explanation' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Update exam paper
            $examPaper->update([
                'title' => $request->title,
                'subject_id' => $request->subject_id,
                'class_id' => $request->class_id,
                'exam_id' => $request->exam_id,
                'duration_minutes' => $request->duration_minutes,
                'total_marks' => $request->total_marks,
                'instructions' => $request->instructions,
                'paper_type' => $request->paper_type,
                'difficulty_level' => $request->difficulty_level,
                'submission_deadline' => $request->submission_deadline
            ]);

            // Delete existing questions and create new ones
            $examPaper->questions()->delete();
            
            foreach ($request->questions as $index => $questionData) {
                Question::create([
                    'exam_paper_id' => $examPaper->id,
                    'question_text' => $questionData['question_text'],
                    'question_type' => $questionData['question_type'],
                    'marks' => $questionData['marks'],
                    'options' => isset($questionData['options']) ? json_encode($questionData['options']) : null,
                    'correct_answer' => $questionData['correct_answer'] ?? null,
                    'explanation' => $questionData['explanation'] ?? null,
                    'order_number' => $index + 1
                ]);
            }

            return redirect()->route('exam-papers.show', $examPaper)
                ->with('success', 'Exam paper updated successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update exam paper: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(ExamPaper $examPaper)
    {
        // Check if user can delete this paper
        if (!auth()->user()->hasRole('admin') && $examPaper->teacher_id !== auth()->id()) {
            abort(403, 'Unauthorized access to exam paper.');
        }
        
        // Can't delete if already published or submitted
        if (in_array($examPaper->status, ['published', 'submitted'])) {
            return redirect()->route('exam-papers.index')
                ->with('error', 'Cannot delete exam paper that is already published or submitted.');
        }

        try {
            $examPaper->questions()->delete();
            $examPaper->delete();
            
            return redirect()->route('exam-papers.index')
                ->with('success', 'Exam paper deleted successfully!');
                
        } catch (\Exception $e) {
            return redirect()->route('exam-papers.index')
                ->with('error', 'Failed to delete exam paper: ' . $e->getMessage());
        }
    }

    public function publish(ExamPaper $examPaper)
    {
        // Check if user can publish this paper
        if (!auth()->user()->hasRole('admin') && $examPaper->teacher_id !== auth()->id()) {
            abort(403, 'Unauthorized access to exam paper.');
        }
        
        // Validate paper before publishing
        if ($examPaper->questions()->count() === 0) {
            return redirect()->back()
                ->with('error', 'Cannot publish exam paper without questions.');
        }
        
        $totalMarks = $examPaper->questions()->sum('marks');
        if ($totalMarks !== $examPaper->total_marks) {
            return redirect()->back()
                ->with('error', 'Total marks of questions (' . $totalMarks . ') does not match paper total marks (' . $examPaper->total_marks . ').');
        }

        try {
            $examPaper->update([
                'status' => 'published',
                'published_at' => now(),
                'published_by' => auth()->id()
            ]);
            
            return redirect()->route('exam-papers.show', $examPaper)
                ->with('success', 'Exam paper published successfully!');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to publish exam paper: ' . $e->getMessage());
        }
    }

    public function submit(ExamPaper $examPaper)
    {
        // Check if user can submit this paper
        if (!auth()->user()->hasRole('admin') && $examPaper->teacher_id !== auth()->id()) {
            abort(403, 'Unauthorized access to exam paper.');
        }
        
        // Can only submit draft papers
        if ($examPaper->status !== 'draft') {
            return redirect()->back()
                ->with('error', 'Can only submit draft exam papers.');
        }

        try {
            $examPaper->update([
                'status' => 'submitted',
                'submitted_at' => now()
            ]);
            
            return redirect()->route('exam-papers.show', $examPaper)
                ->with('success', 'Exam paper submitted for review successfully!');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to submit exam paper: ' . $e->getMessage());
        }
    }

    public function approve(ExamPaper $examPaper)
    {
        // Only admin can approve
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Only administrators can approve exam papers.');
        }

        try {
            $examPaper->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => auth()->id()
            ]);
            
            return redirect()->route('exam-papers.show', $examPaper)
                ->with('success', 'Exam paper approved successfully!');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to approve exam paper: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, ExamPaper $examPaper)
    {
        // Only admin can reject
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Only administrators can reject exam papers.');
        }

        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator);
        }

        try {
            $examPaper->update([
                'status' => 'rejected',
                'rejected_at' => now(),
                'rejected_by' => auth()->id(),
                'rejection_reason' => $request->rejection_reason
            ]);
            
            return redirect()->route('exam-papers.show', $examPaper)
                ->with('success', 'Exam paper rejected successfully!');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to reject exam paper: ' . $e->getMessage());
        }
    }

    public function duplicate(ExamPaper $examPaper)
    {
        // Check if user can duplicate this paper
        if (!auth()->user()->hasRole('admin') && $examPaper->teacher_id !== auth()->id()) {
            abort(403, 'Unauthorized access to exam paper.');
        }

        try {
            // Create duplicate paper
            $newPaper = $examPaper->replicate();
            $newPaper->title = $examPaper->title . ' (Copy)';
            $newPaper->paper_code = $this->generatePaperCode($examPaper->subject_id, $examPaper->class_id);
            $newPaper->status = 'draft';
            $newPaper->published_at = null;
            $newPaper->published_by = null;
            $newPaper->submitted_at = null;
            $newPaper->approved_at = null;
            $newPaper->approved_by = null;
            $newPaper->rejected_at = null;
            $newPaper->rejected_by = null;
            $newPaper->rejection_reason = null;
            $newPaper->teacher_id = auth()->id();
            $newPaper->save();
            
            // Duplicate questions
            foreach ($examPaper->questions as $question) {
                $newQuestion = $question->replicate();
                $newQuestion->exam_paper_id = $newPaper->id;
                $newQuestion->save();
            }
            
            return redirect()->route('exam-papers.edit', $newPaper)
                ->with('success', 'Exam paper duplicated successfully!');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to duplicate exam paper: ' . $e->getMessage());
        }
    }

    public function exportPdf(ExamPaper $examPaper)
    {
        // Check if user can export this paper
        if (!auth()->user()->hasRole('admin') && $examPaper->teacher_id !== auth()->id()) {
            abort(403, 'Unauthorized access to exam paper.');
        }
        
        $examPaper->load(['subject', 'class', 'exam', 'questions' => function($query) {
            $query->orderBy('order_number');
        }]);
        
        // Generate PDF (would need PDF library like DomPDF or wkhtmltopdf)
        return response()->json([
            'success' => true,
            'message' => 'PDF export functionality to be implemented',
            'data' => $examPaper
        ]);
    }

    public function getQuestionBank(Request $request)
    {
        $subjectId = $request->get('subject_id');
        $classId = $request->get('class_id');
        $questionType = $request->get('question_type');
        
        $query = Question::whereHas('examPaper', function($q) use ($subjectId, $classId) {
            if ($subjectId) {
                $q->where('subject_id', $subjectId);
            }
            if ($classId) {
                $q->where('class_id', $classId);
            }
        });
        
        if ($questionType) {
            $query->where('question_type', $questionType);
        }
        
        $questions = $query->with(['examPaper.subject', 'examPaper.class'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
            
        return response()->json([
            'success' => true,
            'data' => $questions
        ]);
    }

    private function generatePaperCode($subjectId, $classId)
    {
        $subject = Subject::find($subjectId);
        $class = ClassModel::find($classId);
        
        $subjectCode = strtoupper(substr($subject->name, 0, 3));
        $classCode = strtoupper(str_replace(' ', '', $class->name));
        $timestamp = now()->format('ymd');
        $random = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        return "{$subjectCode}-{$classCode}-{$timestamp}-{$random}";
    }
}