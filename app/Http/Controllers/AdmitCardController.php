<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Student;
use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;

class AdmitCardController extends Controller
{
    public function index()
    {
        $exams = Exam::with(['class'])
            ->where('exam_date', '>=', now())
            ->orderBy('exam_date', 'asc')
            ->get();
        
        $classes = ClassModel::all();
        
        return view('admit-cards.index', compact('exams', 'classes'));
    }

    public function generate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'exam_id' => 'required|exists:exams,id',
            'class_id' => 'nullable|exists:class_models,id',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:students,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $exam = Exam::with(['class'])->findOrFail($request->exam_id);
            
            // Get students based on filters
            $studentsQuery = Student::with(['class']);
            
            if ($request->student_ids) {
                $studentsQuery->whereIn('id', $request->student_ids);
            } elseif ($request->class_id) {
                $studentsQuery->where('class_id', $request->class_id);
            } else {
                $studentsQuery->where('class_id', $exam->class_id);
            }
            
            // Only include verified students
            $students = $studentsQuery->where('verified', true)->get();
            
            if ($students->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No verified students found for the selected criteria'
                ], 404);
            }

            // Generate admit cards
            $admitCards = [];
            foreach ($students as $student) {
                $admitCards[] = $this->generateAdmitCardData($exam, $student);
            }

            return response()->json([
                'success' => true,
                'message' => 'Admit cards generated successfully',
                'data' => [
                    'exam' => $exam,
                    'admit_cards' => $admitCards,
                    'total_students' => count($admitCards)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate admit cards: ' . $e->getMessage()
            ], 500);
        }
    }

    public function downloadPdf(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'exam_id' => 'required|exists:exams,id',
            'class_id' => 'nullable|exists:class_models,id',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:students,id'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        try {
            $exam = Exam::with(['class'])->findOrFail($request->exam_id);
            
            // Get students based on filters
            $studentsQuery = Student::with(['class']);
            
            if ($request->student_ids) {
                $studentsQuery->whereIn('id', $request->student_ids);
            } elseif ($request->class_id) {
                $studentsQuery->where('class_id', $request->class_id);
            } else {
                $studentsQuery->where('class_id', $exam->class_id);
            }
            
            // Only include verified students
            $students = $studentsQuery->where('verified', true)->get();
            
            if ($students->isEmpty()) {
                return redirect()->back()->with('error', 'No verified students found for the selected criteria');
            }

            // Generate admit cards data
            $admitCards = [];
            foreach ($students as $student) {
                $admitCards[] = $this->generateAdmitCardData($exam, $student);
            }

            // Generate PDF
            $pdf = Pdf::loadView('pdfs.admit-card', [
                'exam' => $exam,
                'admitCards' => $admitCards,
                'school_name' => 'PNS Dhampur',
                'school_address' => 'Dhampur, Uttar Pradesh',
                'generated_at' => now()
            ]);

            $filename = 'admit_cards_' . $exam->subject . '_' . $exam->class->name . '_' . now()->format('Y-m-d') . '.pdf';
            
            return $pdf->download($filename);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    public function downloadSingle($examId, $studentId)
    {
        try {
            $exam = Exam::with(['class'])->findOrFail($examId);
            $student = Student::with(['class'])->findOrFail($studentId);
            
            if (!$student->verified) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student is not verified'
                ], 400);
            }

            $admitCard = $this->generateAdmitCardData($exam, $student);

            // Generate PDF for single student
            $pdf = Pdf::loadView('pdfs.admit-card-single', [
                'exam' => $exam,
                'admitCard' => $admitCard,
                'school_name' => 'PNS Dhampur',
                'school_address' => 'Dhampur, Uttar Pradesh',
                'generated_at' => now()
            ]);

            $filename = 'admit_card_' . $student->admission_no . '_' . $exam->subject . '.pdf';
            
            return $pdf->download($filename);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate admit card: ' . $e->getMessage()
            ], 500);
        }
    }

    public function preview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'exam_id' => 'required|exists:exams,id',
            'student_id' => 'required|exists:students,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $exam = Exam::with(['class'])->findOrFail($request->exam_id);
            $student = Student::with(['class'])->findOrFail($request->student_id);
            
            if (!$student->verified) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student is not verified'
                ], 400);
            }

            $admitCard = $this->generateAdmitCardData($exam, $student);

            return response()->json([
                'success' => true,
                'data' => [
                    'exam' => $exam,
                    'admit_card' => $admitCard,
                    'school_info' => [
                        'name' => 'PNS Dhampur',
                        'address' => 'Dhampur, Uttar Pradesh'
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate preview: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkGenerate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'exam_ids' => 'required|array',
            'exam_ids.*' => 'exists:exams,id',
            'class_ids' => 'nullable|array',
            'class_ids.*' => 'exists:class_models,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $exams = Exam::with(['class'])->whereIn('id', $request->exam_ids)->get();
            $allAdmitCards = [];
            
            foreach ($exams as $exam) {
                $studentsQuery = Student::with(['class'])->where('verified', true);
                
                if ($request->class_ids) {
                    $studentsQuery->whereIn('class_id', $request->class_ids);
                } else {
                    $studentsQuery->where('class_id', $exam->class_id);
                }
                
                $students = $studentsQuery->get();
                
                foreach ($students as $student) {
                    $allAdmitCards[] = [
                        'exam' => $exam,
                        'admit_card' => $this->generateAdmitCardData($exam, $student)
                    ];
                }
            }

            if (empty($allAdmitCards)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No admit cards to generate'
                ], 404);
            }

            // Generate bulk PDF
            $pdf = Pdf::loadView('pdfs.admit-cards-bulk', [
                'admitCardsData' => $allAdmitCards,
                'school_name' => 'PNS Dhampur',
                'school_address' => 'Dhampur, Uttar Pradesh',
                'generated_at' => now()
            ]);

            $filename = 'bulk_admit_cards_' . now()->format('Y-m-d_H-i-s') . '.pdf';
            
            return $pdf->download($filename);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate bulk admit cards: ' . $e->getMessage()
            ], 500);
        }
    }

    private function generateAdmitCardData($exam, $student)
    {
        return [
            'student_name' => $student->name,
            'admission_no' => $student->admission_no,
            'class' => $student->class->name . ' - ' . $student->class->section,
            'father_name' => $student->father_name,
            'mother_name' => $student->mother_name,
            'dob' => $student->dob,
            'exam_subject' => $exam->subject,
            'exam_date' => $exam->exam_date,
            'exam_time' => $exam->start_time . ' - ' . $exam->end_time,
            'exam_duration' => $exam->duration . ' minutes',
            'total_marks' => $exam->total_marks,
            'instructions' => $this->getExamInstructions(),
            'admit_card_no' => 'AC' . $exam->id . $student->id . now()->format('Ymd'),
            'generated_at' => now()->format('d/m/Y H:i:s')
        ];
    }

    private function getExamInstructions()
    {
        return [
            'Bring this admit card to the examination hall',
            'Report to the examination center 30 minutes before the exam',
            'Carry a valid photo ID proof',
            'Mobile phones and electronic devices are strictly prohibited',
            'Use only blue or black pen for writing',
            'Read all instructions carefully before attempting the paper',
            'Do not write anything on the admit card',
            'Follow all COVID-19 safety protocols if applicable'
        ];
    }

    public function getExamStudents($examId)
    {
        try {
            $exam = Exam::with(['class'])->findOrFail($examId);
            
            $students = Student::with(['class'])
                ->where('class_id', $exam->class_id)
                ->where('verified', true)
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'exam' => $exam,
                    'students' => $students,
                    'total_students' => $students->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch exam students: ' . $e->getMessage()
            ], 500);
        }
    }
}