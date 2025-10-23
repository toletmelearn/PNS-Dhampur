<?php

namespace App\Services;

use App\Models\ResultTemplate;
use App\Models\GradingSystem;
use App\Models\SubjectMark;
use App\Models\ResultCard;
use App\Models\ClassModel;
use App\Models\Student;
use App\Models\Exam;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class ResultGenerationService
{
    /**
     * Calculate grade using grading system.
     */
    public function calculateGrade(float $marksObtained, float $totalMarks, GradingSystem $system): array
    {
        return $system->resolveGrade($marksObtained, $totalMarks);
    }

    /**
     * Generate result cards for a class & exam using a template/format.
     */
    public function generateForExamClass(int $examId, int $classId, ?int $templateId = null, ?string $format = null, array $options = []): array
    {
        $exam = Exam::findOrFail($examId);
        $class = ClassModel::findOrFail($classId);
        $template = $templateId ? ResultTemplate::findOrFail($templateId) : ResultTemplate::where('is_active', true)->first();
        if (!$template) { throw new \RuntimeException('No active result template found'); }

        $format = $format ?: $template->format;
        $system = $template->gradingSystem ?: GradingSystem::where('is_default', true)->first();
        if (!$system) { throw new \RuntimeException('No grading system configured'); }

        $students = $class->students()->orderBy('roll_number')->get();

        $cards = [];
        foreach ($students as $student) {
            $marks = SubjectMark::with('subject')
                ->where('student_id', $student->id)
                ->where('exam_id', $exam->id)
                ->where('class_id', $class->id)
                ->get();

            if ($marks->isEmpty()) { continue; }

            $totalObtained = 0.0; $maxMarks = 0.0; $subjectsData = [];
            foreach ($marks as $m) {
                $gradeInfo = $this->calculateGrade((float)$m->marks_obtained, (float)$m->total_marks, $system);
                $subjectsData[] = [
                    'subject_id' => $m->subject_id,
                    'subject_name' => optional($m->subject)->name,
                    'marks_obtained' => (float)$m->marks_obtained,
                    'total_marks' => (float)$m->total_marks,
                    'grade' => $gradeInfo['grade'] ?? $m->grade,
                    'grade_point' => $gradeInfo['point'] ?? $m->grade_point,
                    'percentage' => $gradeInfo['percentage'] ?? null,
                ];
                $totalObtained += (float)$m->marks_obtained;
                $maxMarks += (float)$m->total_marks;
            }

            $overall = $this->calculateGrade($totalObtained, $maxMarks, $system);
            $percentage = $overall['percentage'] ?? ($maxMarks > 0 ? round(($totalObtained/$maxMarks)*100, 2) : null);

            $card = ResultCard::updateOrCreate(
                ['student_id' => $student->id, 'exam_id' => $exam->id],
                [
                    'class_id' => $class->id,
                    'template_id' => $template->id,
                    'format' => $format,
                    'total_marks' => round($totalObtained, 2),
                    'max_marks' => round($maxMarks, 2),
                    'percentage' => $percentage,
                    'grade' => $overall['grade'] ?? null,
                    'card_data' => [
                        'subjects' => $subjectsData,
                        'overall' => $overall,
                        'template_settings' => $template->settings,
                    ],
                    'generated_at' => now(),
                ]
            );

            // Optional PDF generation
            if (($options['generate_pdf'] ?? true) === true) {
                $pdfPath = $this->generatePdf($card, $class, $exam);
                if ($pdfPath) {
                    $card->pdf_path = $pdfPath; $card->save();
                }
            }

            $cards[] = $card;
        }

        // Assign ranks/positions within class based on total marks (tie-aware)
        $this->assignPositions($exam->id, $class->id);

        return $cards;
    }

    /**
     * Assign rank positions for a class & exam.
     */
    public function assignPositions(int $examId, int $classId): void
    {
        $cards = ResultCard::where('exam_id', $examId)->where('class_id', $classId)
            ->orderByDesc('total_marks')
            ->orderBy('student_id')
            ->get();

        $currentRank = 0; $prevMarks = null; $tiesCount = 0; $positionIndex = 0;
        foreach ($cards as $card) {
            $positionIndex++;
            if ($prevMarks === null || $card->total_marks < $prevMarks) {
                $currentRank = $positionIndex; $tiesCount = 1; $prevMarks = $card->total_marks;
            } else {
                // tie on same total marks => same rank
                $tiesCount++;
            }
            $card->position = $currentRank; $card->save();
        }
    }

    /**
     * Generate a PDF snapshot for a result card (fallback to HTML if PDF lib is unavailable).
     */
    public function generatePdf(ResultCard $card, ClassModel $class, Exam $exam): ?string
    {
        try {
            $viewData = [ 'card' => $card, 'class' => $class, 'exam' => $exam ];
            $html = View::make('results.card', $viewData)->render();

            // Try DomPDF if available
            if (class_exists('Barryvdh\\DomPDF\\Facade\\Pdf')) {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
                $fileName = sprintf('results/%s_%s_%s.pdf', $class->id, $exam->id, $card->student_id);
                Storage::disk('public')->put($fileName, $pdf->output());
                return Storage::disk('public')->url($fileName);
            }

            // Fallback: store HTML snapshot
            $fileName = sprintf('results/%s_%s_%s.html', $class->id, $exam->id, $card->student_id);
            Storage::disk('public')->put($fileName, $html);
            return Storage::disk('public')->url($fileName);
        } catch (\Throwable $e) {
            Log::error('Failed to generate result card snapshot', ['error' => $e->getMessage()]);
            return null;
        }
    }
}