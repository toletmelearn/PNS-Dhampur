<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SubstituteNotificationService;

class ProcessSubstituteNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'substitute:process-notifications 
                            {--dry-run : Show what would be processed without actually sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process scheduled substitute teacher notifications (reminders, etc.)';

    protected $notificationService;

    /**
     * Create a new command instance.
     */
    public function __construct(SubstituteNotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing scheduled substitute notifications...');

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE - No notifications will be sent');
        }

        try {
            if (!$this->option('dry-run')) {
                $processedCount = $this->notificationService->processScheduledNotifications();
                
                if ($processedCount > 0) {
                    $this->info("Successfully processed {$processedCount} scheduled notifications.");
                } else {
                    $this->info('No scheduled notifications to process.');
                }
            } else {
                // In dry-run mode, just show what would be processed
                $this->showPendingNotifications();
            }

        } catch (\Exception $e) {
            $this->error('Failed to process notifications: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Show pending notifications in dry-run mode
     */
    private function showPendingNotifications()
    {
        $notifications = \App\Models\Notification::where('scheduled_for', '<=', now())
            ->whereNull('sent_at')
            ->where('type', 'LIKE', 'substitution_%')
            ->get();

        if ($notifications->isEmpty()) {
            $this->info('No pending substitute notifications found.');
            return;
        }

        $this->info("Found {$notifications->count()} pending notifications:");
        
        $headers = ['ID', 'Type', 'User', 'Title', 'Scheduled For'];
        $rows = [];

        foreach ($notifications as $notification) {
            $user = $notification->user;
            $rows[] = [
                $notification->id,
                $notification->type,
                $user ? $user->name : 'Unknown',
                $notification->title,
                $notification->scheduled_for->format('Y-m-d H:i:s')
            ];
        }

        $this->table($headers, $rows);
    }
}