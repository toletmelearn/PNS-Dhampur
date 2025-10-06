<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Check for bell notifications every minute during school hours
        $schedule->command('bell:check')
                 ->everyMinute()
                 ->between('07:00', '15:00')
                 ->weekdays();

        // Auto-assign substitute teachers every hour during school hours
        $schedule->command('substitutes:auto-assign')
                 ->hourly()
                 ->between('07:00', '15:00')
                 ->weekdays();

        // Daily auto-assignment for next day (run at 6 PM)
        $schedule->command('substitutes:auto-assign --date=' . now()->addDay()->format('Y-m-d'))
                 ->dailyAt('18:00')
                 ->weekdays();

        // Send notification reminders every hour during school hours
        $schedule->command('notifications:send-reminders')
                 ->hourly()
                 ->between('07:00', '18:00')
                 ->weekdays();

        // Process scheduled notifications every 15 minutes
        $schedule->command('notifications:send-reminders --type=scheduled')
                 ->everyFifteenMinutes();

        // Process substitute notifications every 5 minutes
        $schedule->command('substitute:process-notifications')
                 ->everyFiveMinutes();

        // Clean up expired notifications daily at midnight
        $schedule->command('notifications:send-reminders --type=cleanup')
                 ->dailyAt('00:00');

        // Check for season switching daily at 6 AM
        $schedule->command('bell:check-season-switch')
                 ->dailyAt('06:00');

        // Automated backup scheduling
        // Daily database backup at 2 AM
        $schedule->command('backup:database --compress')
                 ->dailyAt('02:00')
                 ->withoutOverlapping()
                 ->onFailure(function () {
                     \Log::error('Daily database backup failed');
                 });

        // Weekly full file backup on Sundays at 3 AM
        $schedule->command('backup:files --compress --exclude=node_modules,vendor,.git')
                 ->weeklyOn(0, '03:00')
                 ->withoutOverlapping()
                 ->onFailure(function () {
                     \Log::error('Weekly file backup failed');
                 });

        // Monthly full system export on the 1st at 4 AM
        $schedule->command('data:export --format=json --all-tables')
                 ->monthlyOn(1, '04:00')
                 ->withoutOverlapping()
                 ->onFailure(function () {
                     \Log::error('Monthly data export failed');
                 });

        // Clean up old backups daily at 5 AM (keep last 30 days)
        $schedule->call(function () {
            $backupPath = storage_path('app/backups');
            if (is_dir($backupPath)) {
                $files = glob($backupPath . '/*');
                $cutoff = now()->subDays(30)->timestamp;
                
                foreach ($files as $file) {
                    if (is_file($file) && filemtime($file) < $cutoff) {
                        unlink($file);
                    }
                }
            }
        })->dailyAt('05:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
