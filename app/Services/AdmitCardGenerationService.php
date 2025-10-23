<?php

namespace App\Services;

use App\Models\AdmitCard;
use App\Models\AdmitTemplate;
use App\Models\Exam;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\ExamSeatAllocation;
use App\Models\AdmitVerificationLog;
use App\Models\School;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AdmitCardGenerationService
{
    /**
     * Generate admit cards for an exam, optionally filtered by class or student IDs.
     * Returns array with summary and paths.
     */
    public function generateAdmitCards(int $examId, ?int $classId = null, array $studentIds = []): array
    {
        $exam = Exam::with(['class'])->findOrFail($examId);

        $studentsQuery = Student::with(['class'])
            ->where('verified', true);

        if (!empty($studentIds)) {
            $studentsQuery->whereIn('id', $studentIds);
        } elseif ($classId) {
            $studentsQuery->where('class_id', $classId);
        } else {
            $studentsQuery->where('class_id', $exam->class_id);
        }

        $students = $studentsQuery->orderBy('roll_number')->get();

        if ($students->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No verified students for selection',
                'generated' => 0,
                'paths' => []
            ];
        }

        DB::beginTransaction();

        try {
            $paths = [];
            $template = AdmitTemplate::where('is_active', true)->first();

            // Ensure seat allocations exist
            $allocations = $this->ensureSeatAllocations($exam, $students);

            foreach ($students as $student) {
                $allocation = $allocations[$student->id] ?? null;

                $admitCard = AdmitCard::firstOrCreate(
                    ['exam_id' => $exam->id, 'student_id' => $student->id],
                    [
                        'class_id' => $student->class_id,
                        'template_id' => $template?->id,
                        'seat_allocation_id' => $allocation?->id,
                        'admit_card_no' => $this->generateAdmitCardNo($exam, $student),
                        'is_published' => false,
                        'generated_at' => now(),
                    ]
                );

                // Build QR payload and image
                $qrPayload = $this->buildQrPayload($exam, $student, $admitCard, $allocation);
                $qrDataUri = $this->generateQrDataUri($qrPayload);

                if ($qrDataUri) {
                    $admitCard->qr_code = $qrDataUri;
                } else {
                    $admitCard->qr_code = json_encode($qrPayload);
                }

                // Optional barcode string (can be rendered by client/JS or a package later)
                $admitCard->barcode = $admitCard->admit_card_no;

                // Render PDF snapshot
                $cardData = $this->buildCardData($exam, $student, $admitCard, $allocation);
                $schoolInfo = $this->getSchoolInfo();
                $pdf = Pdf::loadView('pdfs.admit-card-single', [
                    'admitCard' => $cardData,
                    'exam' => $exam,
                    'school_info' => $schoolInfo,
                    'school_name' => $schoolInfo['name'] ?? '',
                    'school_address' => $schoolInfo['address'] ?? '',
                    'generated_at' => now(),
                ])->setPaper($template->format ?? 'A4');

                $path = 'admit-cards/' . $admitCard->admit_card_no . '.pdf';
                Storage::disk('public')->put($path, $pdf->output());
                $admitCard->pdf_path = $path;
                $admitCard->html_snapshot = null; // Optional future: store HTML snapshot
                $admitCard->save();

                $paths[] = Storage::disk('public')->path($path);
                AdmitVerificationLog::create([
                    'admit_card_id' => $admitCard->id,
                    'exam_id' => $admitCard->exam_id,
                    'student_id' => $admitCard->student_id,
                    'method' => 'manual',
                    'success' => true,
                    'scanned_at' => now(),
                    'verified_by' => auth()->id() ?? null,
                    'location' => 'system',
                    'payload' => ['action' => 'generated', 'path' => $path],
                    'notes' => 'Admit card generated and PDF stored.',
                ]);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Admit cards generated',
                'generated' => count($paths),
                'paths' => $paths,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Admit card generation failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Generation failed: ' . $e->getMessage(),
                'generated' => 0,
                'paths' => [],
            ];
        }
    }

    /**
     * Ensure seat allocations exist for each student, return map student_id => ExamSeatAllocation
     */
    public function ensureSeatAllocations(Exam $exam, $students): array
    {
        $map = [];
        $centerName = $this->getDefaultCenterName();
        $roomNo = $this->getDefaultRoomNo();

        $counter = 1;
        foreach ($students as $student) {
            $allocation = ExamSeatAllocation::firstOrCreate(
                ['exam_id' => $exam->id, 'student_id' => $student->id],
                [
                    'class_id' => $student->class_id,
                    'center_name' => $centerName,
                    'room_no' => $roomNo,
                    'seat_number' => 'S' . str_pad((string)$counter, 3, '0', STR_PAD_LEFT),
                    'roll_number' => $student->roll_number,
                ]
            );
            $map[$student->id] = $allocation;
            $counter++;
        }

        return $map;
    }

    /**
     * Build admit card data for blade view.
     */
    private function buildCardData(Exam $exam, Student $student, AdmitCard $admitCard, ?ExamSeatAllocation $allocation): array
    {
        $classStr = $student->class?->name;
        if (isset($student->class?->section)) {
            $classStr .= ' - ' . $student->class->section;
        }

        return [
            'student_name' => $student->name ?? ($student->first_name . ' ' . $student->last_name),
            'admission_no' => $student->admission_number ?? $student->admission_no ?? 'N/A',
            'class' => $classStr,
            'father_name' => $student->father_name ?? 'N/A',
            'mother_name' => $student->mother_name ?? 'N/A',
            'dob' => $student->date_of_birth ?? $student->dob ?? null,
            'exam_subject' => $exam->subject,
            'exam_date' => $exam->exam_date,
            'exam_time' => ($exam->start_time ?? '') . ' - ' . ($exam->end_time ?? ''),
            'exam_duration' => ($exam->duration ?? '') . ' minutes',
            'total_marks' => $exam->total_marks,
            'instructions' => $this->getExamInstructions(),
            'admit_card_no' => $admitCard->admit_card_no,
            'generated_at' => now()->format('d/m/Y H:i:s'),
            'qr_code' => $admitCard->qr_code,
            'barcode' => $admitCard->barcode,
            'photo_url' => $this->getStudentPhotoUrl($student),
            'center_name' => $allocation?->center_name,
            'room_no' => $allocation?->room_no,
            'seat_number' => $allocation?->seat_number,
            'roll_number' => $allocation?->roll_number,
        ];
    }

    private function getSchoolInfo(): array
    {
        $school = School::first();
        return [
            'name' => $school?->name ?? config('app.name'),
            'address' => $school?->address ?? '',
        ];
    }

    private function getDefaultCenterName(): string
    {
        $school = School::first();
        return $school?->name ?? 'Main Campus';
    }

    private function getDefaultRoomNo(): string
    {
        return 'Room-1';
    }

    private function getStudentPhotoUrl(Student $student): ?string
    {
        if ($student->photo) {
            return asset('storage/' . $student->photo);
        }
        return null;
    }

    private function generateAdmitCardNo(Exam $exam, Student $student): string
    {
        return 'AC-' . $exam->id . '-' . str_pad((string)$student->id, 6, '0', STR_PAD_LEFT) . '-' . now()->format('Ymd');
    }

    private function buildQrPayload(Exam $exam, Student $student, AdmitCard $admitCard, ?ExamSeatAllocation $allocation): array
    {
        return [
            'type' => 'admit_card',
            'admit_card_no' => $admitCard->admit_card_no,
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'class_id' => $student->class_id,
            'roll_number' => $allocation?->roll_number,
            'seat_number' => $allocation?->seat_number,
            'center_name' => $allocation?->center_name,
            'room_no' => $allocation?->room_no,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    private function generateQrDataUri(array $payload): ?string
    {
        try {
            $content = json_encode($payload, JSON_UNESCAPED_SLASHES);

            if (class_exists('SimpleSoftwareIO\\QrCode\\Facades\\QrCode')) {
                $png = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(180)->generate($content);
                return 'data:image/png;base64,' . base64_encode($png);
            }

            if (class_exists('\\BaconQrCode\\Writer')) {
                $renderer = new \BaconQrCode\Renderer\ImageRenderer(
                    new \BaconQrCode\Renderer\RendererStyle\RendererStyle(180),
                    new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
                );
                $writer = new \BaconQrCode\Writer($renderer);
                $svg = $writer->writeString($content);
                return 'data:image/svg+xml;base64,' . base64_encode($svg);
            }
        } catch (\Throwable $e) {
            Log::warning('QR generation failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    private function getExamInstructions(): array
    {
        return [
            'Bring this admit card to the examination hall',
            'Report to the examination center 30 minutes before the exam',
            'Carry a valid photo ID proof',
            'Mobile phones and electronic devices are strictly prohibited',
            'Use only blue or black pen for writing',
            'Read all instructions carefully before attempting the paper',
            'Do not write anything on the admit card',
            'Follow all safety protocols if applicable',
        ];
    }
}