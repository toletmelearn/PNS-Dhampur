<?php

namespace App\Jobs;

use App\Services\AadhaarVerificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;

class BulkAadhaarVerificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var array<int> */
    protected array $studentIds;
    protected ?int $userId;

    public function __construct(array $studentIds, ?int $userId = null)
    {
        $this->studentIds = $studentIds;
        $this->userId = $userId;
    }

    public function handle(AadhaarVerificationService $aadhaarService): void
    {
        $userId = $this->userId ?? Auth::id();

        $students = Student::whereIn('id', $this->studentIds)->get(['id', 'name', 'aadhaar', 'dob']);

        foreach ($students as $student) {
            try {
                $demographic = [
                    'name' => $student->name,
                    'date_of_birth' => is_string($student->dob) ? $student->dob : optional($student->dob)->format('Y-m-d')
                ];

                $res = $aadhaarService->verifyAadhaar($student->aadhaar, $demographic);
                $ok = ($res['success'] ?? false) || ($res['status'] ?? null) === 'success';
                $score = $res['match_score'] ?? ($res['overall_match_score'] ?? null);

                DB::table('student_verifications')->insert([
                    'student_id' => $student->id,
                    'verification_type' => 'aadhaar',
                    'status' => $ok ? 'verified' : 'failed',
                    'match_score' => $score,
                    'document_type' => 'aadhar_card',
                    'verification_status' => $ok ? 'verified' : 'failed',
                    'confidence_score' => $score,
                    'verified_by' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('BulkAadhaarVerificationJob failed for student', [
                    'student_id' => $student->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Bulk Aadhaar verification job completed', [
            'student_count' => count($this->studentIds),
        ]);
    }
}
