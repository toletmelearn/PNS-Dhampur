<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Exam;
use App\Models\AdmitCard;
use App\Services\AdmitCardGenerationService;
use Illuminate\Support\Facades\Log;

class AutoGenerateAdmitCards extends Command
{
    protected $signature = 'admit:auto-generate {--days=3} {--exam_id=} {--class_id=} {--dry-run}';

    protected $description = 'Automatically generate admit cards for upcoming exams';

    protected AdmitCardGenerationService $service;

    public function __construct(AdmitCardGenerationService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function handle(): int
    {
        $days = (int)($this->option('days') ?? 3);
        $examId = $this->option('exam_id');
        $classId = $this->option('class_id');
        $dryRun = (bool)$this->option('dry-run');

        $this->info("Starting auto admit generation: days={$days}, exam_id=" . ($examId ?? 'ALL') . ", class_id=" . ($classId ?? 'AUTO') . ", dry_run=" . ($dryRun ? 'yes' : 'no'));

        $examsQuery = Exam::query()
            ->whereDate('exam_date', '>=', now())
            ->whereDate('exam_date', '<=', now()->addDays($days));
        if ($examId) {
            $examsQuery->where('id', $examId);
        }
        $exams = $examsQuery->orderBy('exam_date')->get();

        if ($exams->isEmpty()) {
            $this->warn('No upcoming exams found in the specified window.');
            return self::SUCCESS;
        }

        foreach ($exams as $exam) {
            try {
                $existingCount = AdmitCard::where('exam_id', $exam->id)
                    ->when($classId, fn($q) => $q->where('class_id', $classId))
                    ->count();
                $this->line("Exam #{$exam->id} on {$exam->exam_date}: existing admit cards = {$existingCount}");

                if ($dryRun) {
                    $this->line('Dry-run mode: skipping generation.');
                    continue;
                }

                $result = $this->service->generateAdmitCards($exam->id, $classId, []);
                if (($result['success'] ?? false) === true) {
                    $count = $result['generated'] ?? 0;
                    $this->info("Generated {$count} admit cards for exam #{$exam->id}.");
                } else {
                    $this->error("Failed generating for exam #{$exam->id}: " . ($result['message'] ?? 'Unknown error'));
                }
            } catch (\Throwable $e) {
                Log::error('Auto admit generation failed', [
                    'exam_id' => $exam->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("Exception during generation for exam #{$exam->id}: {$e->getMessage()}");
            }
        }

        $this->info('Auto admit generation completed.');
        return self::SUCCESS;
    }
}