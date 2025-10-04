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
