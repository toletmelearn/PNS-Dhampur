<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FeeReminderService;

class SendFeeReminders extends Command
{
    protected $signature = 'fees:send-reminders {--days=3} {--overdue}';
    protected $description = 'Send SMS reminders for upcoming due fees or overdue fees';

    public function handle(): int
    {
        /** @var FeeReminderService $service */
        $service = app(FeeReminderService::class);

        $days = (int)$this->option('days');
        $isOverdue = (bool)$this->option('overdue');

        if ($isOverdue) {
            $count = $service->sendOverdueReminders($days);
            $this->info("Overdue fee reminders sent: {$count}");
        } else {
            $count = $service->sendUpcomingDueReminders($days);
            $this->info("Upcoming due fee reminders sent: {$count}");
        }

        return self::SUCCESS;
    }
}