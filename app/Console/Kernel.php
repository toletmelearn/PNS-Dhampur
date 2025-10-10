<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Backup schedules based on configuration
        $backupConfig = config('backup.schedules', []);

        // Daily full backup
        if (isset($backupConfig['daily_full']) && $backupConfig['daily_full']['enabled']) {
            $schedule->command('backup:create full')
                ->dailyAt($backupConfig['daily_full']['time'])
                ->withoutOverlapping()
                ->onOneServer()
                ->runInBackground()
                ->emailOutputOnFailure(config('backup.notifications.channels.email.recipients', []));
        }

        // Hourly database backup
        if (isset($backupConfig['hourly_database']) && $backupConfig['hourly_database']['enabled']) {
            $schedule->command('backup:create database')
                ->hourly()
                ->withoutOverlapping()
                ->onOneServer()
                ->runInBackground();
        }

        // Weekly archive backup
        if (isset($backupConfig['weekly_archive']) && $backupConfig['weekly_archive']['enabled']) {
            $schedule->command('backup:create full --compress --encrypt')
                ->weeklyOn($backupConfig['weekly_archive']['day'], $backupConfig['weekly_archive']['time'])
                ->withoutOverlapping()
                ->onOneServer()
                ->runInBackground()
                ->emailOutputOnFailure(config('backup.notifications.channels.email.recipients', []));
        }

        // Backup cleanup - daily at 3 AM
        $schedule->command('backup:cleanup')
            ->dailyAt('03:00')
            ->withoutOverlapping()
            ->onOneServer();

        // System monitoring - every 5 minutes
        $schedule->command('monitoring:check')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->onOneServer()
            ->runInBackground();

        // Health check - every hour
        $schedule->command('system:health-check')
            ->hourly()
            ->withoutOverlapping()
            ->onOneServer()
            ->runInBackground();

        // Log cleanup - daily at 2 AM
        $schedule->command('log:clear')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->onOneServer();

        // Cache optimization - daily at 1 AM
        $schedule->command('optimize:clear')
            ->dailyAt('01:00')
            ->withoutOverlapping()
            ->onOneServer();

        // Queue monitoring - every minute
        $schedule->command('queue:monitor redis --max=100')
            ->everyMinute()
            ->withoutOverlapping()
            ->onOneServer();

        // Failed jobs cleanup - daily at 4 AM
        $schedule->command('queue:flush')
            ->dailyAt('04:00')
            ->withoutOverlapping()
            ->onOneServer();

        // Security scan - daily at midnight
        $schedule->command('security:scan')
            ->dailyAt('00:00')
            ->withoutOverlapping()
            ->onOneServer()
            ->runInBackground();

        // Performance monitoring - every 10 minutes
        $schedule->command('performance:monitor')
            ->everyTenMinutes()
            ->withoutOverlapping()
            ->onOneServer()
            ->runInBackground();

        // Database optimization - weekly on Sunday at 5 AM
        $schedule->command('db:optimize')
            ->weeklyOn(0, '05:00')
            ->withoutOverlapping()
            ->onOneServer();

        // Storage cleanup - daily at 6 AM
        $schedule->command('storage:cleanup')
            ->dailyAt('06:00')
            ->withoutOverlapping()
            ->onOneServer();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
