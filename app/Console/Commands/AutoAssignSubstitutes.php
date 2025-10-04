<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TeacherSubstitution;
use App\Services\AutoTeacherAssignmentService;
use App\Services\ConflictResolutionService;
use Carbon\Carbon;

class AutoAssignSubstitutes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'substitutes:auto-assign 
                           {--date= : Specific date to process (Y-m-d format)}
                           {--emergency : Only process emergency requests}
                           {--dry-run : Show what would be assigned without actually assigning}
                           {--priority= : Priority criteria (subject_expertise, availability, workload, performance)}
                           {--resolve-conflicts : Also run conflict resolution}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically assign substitute teachers to pending substitution requests and resolve conflicts';

    protected AutoTeacherAssignmentService $assignmentService;
    protected ConflictResolutionService $conflictService;

    public function __construct(
        AutoTeacherAssignmentService $assignmentService,
        ConflictResolutionService $conflictService
    ) {
        parent::__construct();
        $this->assignmentService = $assignmentService;
        $this->conflictService = $conflictService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::today();
        $emergencyOnly = $this->option('emergency');
        $dryRun = $this->option('dry-run');
        $priority = $this->option('priority') ?? 'subject_expertise';
        $resolveConflicts = $this->option('resolve-conflicts');

        $this->info("Starting automatic substitute assignment for {$date->format('Y-m-d')}");
        
        if ($emergencyOnly) {
            $this->info("Processing emergency requests only");
        }
        
        if ($dryRun) {
            $this->warn("DRY RUN MODE - No actual assignments will be made");
        }

        // Step 1: Resolve conflicts if requested
        if ($resolveConflicts) {
            $this->info('Resolving conflicts for multiple absences...');
            $conflictResults = $this->conflictService->resolveMultipleAbsenceConflicts($date);
            $this->displayConflictResults($conflictResults);
        }

        // Step 2: Auto-assign substitutes using the new service
        $this->info('Running automatic substitute assignment...');
        $assignmentResults = $this->assignmentService->autoAssignSubstitutes($priority, $dryRun);
        $this->displayAssignmentResults($assignmentResults);

        // Step 3: Show statistics
        if (!$dryRun) {
            $this->info('Generating assignment statistics...');
            $stats = $this->assignmentService->getAssignmentStats($date, $date);
            $this->displayStatistics($stats);
        }

        $this->info('Automatic substitute assignment completed.');
        
        return Command::SUCCESS;
    }

    /**
     * Display conflict resolution results
     */
    private function displayConflictResults(array $results): void
    {
        $this->newLine();
        $this->info('=== CONFLICT RESOLUTION RESULTS ===');
        
        $this->table([
            'Metric', 'Value'
        ], [
            ['Date', $results['date']],
            ['Total Conflicts', $results['total_conflicts']],
            ['Resolved Conflicts', $results['resolved_conflicts']],
            ['Unresolved Conflicts', $results['unresolved_conflicts']],
            ['Strategies Used', implode(', ', $results['strategies_used'])]
        ]);

        if (!empty($results['conflicts'])) {
            $this->newLine();
            $this->info('Conflict Details:');
            
            foreach ($results['conflicts'] as $conflict) {
                $status = $conflict['resolution']['success'] ? '✅ RESOLVED' : '❌ UNRESOLVED';
                $this->line("Group {$conflict['group_id']}: {$conflict['requests_count']} requests - {$status}");
                
                if ($conflict['resolution']['success']) {
                    $strategies = implode(', ', $conflict['resolution']['strategies_used']);
                    $this->line("  Strategies: {$strategies}");
                }
            }
        }
    }

    /**
     * Display assignment results
     */
    private function displayAssignmentResults(array $results): void
    {
        $this->newLine();
        $this->info('=== ASSIGNMENT RESULTS ===');
        
        $this->table([
            'Metric', 'Value'
        ], [
            ['Assigned', $results['assigned']],
            ['Failed', $results['failed']],
            ['Conflicts Resolved', $results['conflicts_resolved']],
            ['Success Rate', $results['assigned'] + $results['failed'] > 0 
                ? round(($results['assigned'] / ($results['assigned'] + $results['failed'])) * 100, 2) . '%' 
                : '0%']
        ]);

        // Show successful assignments
        if (!empty($results['assignments'])) {
            $this->newLine();
            $this->info('Successful Assignments:');
            
            $assignmentTable = [];
            foreach ($results['assignments'] as $assignment) {
                $assignmentTable[] = [
                    $assignment['request_id'],
                    $assignment['substitute_teacher_name'] ?? 'N/A',
                    $assignment['assignment_score'] ?? 'N/A',
                    $assignment['dry_run'] ? 'DRY RUN' : 'ASSIGNED'
                ];
            }
            
            $this->table([
                'Request ID', 'Substitute Teacher', 'Score', 'Status'
            ], $assignmentTable);
        }

        // Show failures
        if (!empty($results['failures'])) {
            $this->newLine();
            $this->error('Failed Assignments:');
            
            $failureTable = [];
            foreach ($results['failures'] as $failure) {
                $failureTable[] = [
                    $failure['request_id'],
                    $failure['reason']
                ];
            }
            
            $this->table([
                'Request ID', 'Reason'
            ], $failureTable);
        }
    }

    /**
     * Display assignment statistics
     */
    private function displayStatistics(array $stats): void
    {
        $this->newLine();
        $this->info('=== ASSIGNMENT STATISTICS ===');
        
        $this->table([
            'Metric', 'Value'
        ], [
            ['Total Requests', $stats['total_requests']],
            ['Auto Assigned', $stats['auto_assigned']],
            ['Manual Assigned', $stats['manual_assigned']],
            ['Pending', $stats['pending']],
            ['Completed', $stats['completed']],
            ['Cancelled', $stats['cancelled']],
            ['Emergency Requests', $stats['emergency_requests']],
            ['Average Assignment Time (minutes)', round($stats['average_assignment_time'] ?? 0, 2)],
            ['Success Rate', round($stats['success_rate'], 2) . '%']
        ]);
    }

    /**
     * Process pending substitution requests
     */
    private function processPendingRequests($date, $emergencyOnly, $dryRun, $resolveConflicts)
    {
        $query = TeacherSubstitution::where('status', 'pending')
                                   ->where('date', $date);

        if ($emergencyOnly) {
            $query->where('is_emergency', true);
        } else {
            // Skip emergency requests in normal auto-assignment
            $query->where('is_emergency', false);
        }

        $pendingRequests = $query->orderBy('priority', 'desc')
                                ->orderBy('start_time')
                                ->get();

        if ($pendingRequests->isEmpty()) {
            $this->info('No pending substitution requests found for the specified criteria.');
            return Command::SUCCESS;
        }

        $this->info("Found {$pendingRequests->count()} pending requests to process");

        $assigned = 0;
        $failed = [];

        $this->withProgressBar($pendingRequests, function ($substitution) use (&$assigned, &$failed, $dryRun) {
            $availableTeachers = TeacherSubstitution::findAvailableSubstitutes(
                $substitution->date,
                $substitution->start_time,
                $substitution->end_time,
                $substitution->subject
            );

            if ($availableTeachers->isEmpty()) {
                $failed[] = [
                    'id' => $substitution->id,
                    'reason' => 'No available substitute teachers found',
                    'details' => [
                        'absent_teacher' => $substitution->absentTeacher->user->name,
                        'class' => $substitution->class->name,
                        'time' => "{$substitution->start_time} - {$substitution->end_time}",
                        'subject' => $substitution->subject,
                    ]
                ];
                return;
            }

            // Select the best substitute teacher
            $bestSubstitute = $this->selectBestSubstitute($availableTeachers, $substitution);

            if (!$dryRun) {
                $success = TeacherSubstitution::autoAssignSubstitute($substitution->id);
                if ($success) {
                    $assigned++;
                } else {
                    $failed[] = [
                        'id' => $substitution->id,
                        'reason' => 'Auto-assignment failed',
                        'details' => [
                            'absent_teacher' => $substitution->absentTeacher->user->name,
                            'class' => $substitution->class->name,
                            'time' => "{$substitution->start_time} - {$substitution->end_time}",
                        ]
                    ];
                }
            } else {
                // Dry run - just count what would be assigned
                $assigned++;
                $this->line("\nWould assign: {$bestSubstitute->user->name} -> {$substitution->absentTeacher->user->name}'s class");
            }
        });

        $this->newLine(2);

        // Display results
        if ($dryRun) {
            $this->info("DRY RUN RESULTS:");
            $this->info("Would assign: {$assigned} substitutions");
        } else {
            $this->info("AUTO-ASSIGNMENT COMPLETED:");
            $this->info("Successfully assigned: {$assigned} substitutions");
        }

        if (!empty($failed)) {
            $this->warn("Failed to assign: " . count($failed) . " substitutions");
            
            if ($this->option('verbose')) {
                $this->newLine();
                $this->error("Failed assignments:");
                
                foreach ($failed as $failure) {
                    $this->line("ID: {$failure['id']} - {$failure['reason']}");
                    if (isset($failure['details'])) {
                        $this->line("  Teacher: {$failure['details']['absent_teacher']}");
                        $this->line("  Class: {$failure['details']['class']}");
                        $this->line("  Time: {$failure['details']['time']}");
                        if (isset($failure['details']['subject'])) {
                            $this->line("  Subject: {$failure['details']['subject']}");
                        }
                    }
                    $this->newLine();
                }
            }
        }

        // Send notifications for failed assignments
        if (!$dryRun && !empty($failed)) {
            $this->sendFailureNotifications($failed, $date);
        }

        return Command::SUCCESS;
    }

    /**
     * Select the best substitute teacher from available options
     */
    private function selectBestSubstitute($availableTeachers, $substitution)
    {
        // Scoring criteria:
        // 1. Subject expertise match
        // 2. Lower current substitution load
        // 3. Higher experience
        
        $scored = $availableTeachers->map(function ($teacher) use ($substitution) {
            $score = 0;
            
            // Check current substitution load for the day
            $currentLoad = $teacher->substitutionAssignments()
                                  ->where('date', $substitution->date)
                                  ->whereIn('status', ['assigned', 'completed'])
                                  ->count();
            
            // Get availability record for subject expertise
            $availability = $teacher->availability()
                                   ->where('date', $substitution->date)
                                   ->first();
            
            // Subject expertise bonus
            if ($availability && $availability->subject_expertise) {
                if (in_array($substitution->subject, $availability->subject_expertise)) {
                    $score += 50;
                }
            }
            
            // Lower load bonus (inverse scoring)
            $maxLoad = $availability->max_substitutions_per_day ?? 3;
            $loadScore = max(0, ($maxLoad - $currentLoad) * 10);
            $score += $loadScore;
            
            // Experience bonus
            $score += min($teacher->experience_years ?? 0, 20); // Cap at 20 years
            
            return [
                'teacher' => $teacher,
                'score' => $score,
                'current_load' => $currentLoad,
                'max_load' => $maxLoad,
            ];
        });
        
        // Sort by score (highest first)
        $best = $scored->sortByDesc('score')->first();
        
        return $best['teacher'];
    }

    /**
     * Send notifications for failed assignments
     */
    private function sendFailureNotifications(array $failed, Carbon $date): void
    {
        // Log failed assignments
        $logFile = storage_path('logs/substitution_failures.log');
        $logEntry = [
            'date' => $date->format('Y-m-d'),
            'timestamp' => now()->toISOString(),
            'failed_count' => count($failed),
            'failures' => $failed,
        ];
        
        file_put_contents($logFile, json_encode($logEntry, JSON_PRETTY_PRINT) . "\n", FILE_APPEND | LOCK_EX);
        
        $this->info("Failed assignments logged to: {$logFile}");
        
        // Here you could add email notifications, Slack notifications, etc.
        // For now, we'll just create a simple notification file
        $notificationFile = storage_path('app/substitution_alerts.json');
        $alerts = [];
        
        if (file_exists($notificationFile)) {
            $alerts = json_decode(file_get_contents($notificationFile), true) ?? [];
        }
        
        $alerts[] = [
            'type' => 'failed_auto_assignment',
            'date' => $date->format('Y-m-d'),
            'count' => count($failed),
            'created_at' => now()->toISOString(),
            'message' => "Failed to auto-assign " . count($failed) . " substitution requests for {$date->format('Y-m-d')}. Manual intervention required.",
        ];
        
        file_put_contents($notificationFile, json_encode($alerts, JSON_PRETTY_PRINT));
        
        $this->warn("Alert created for manual review of failed assignments");
    }
}