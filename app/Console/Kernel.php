<?php

namespace App\Console;

use App\Console\Commands\CleanExpiredReports;
use App\Console\Commands\RunScheduledReports;
use App\Console\Commands\SyncSkills;
use App\Console\Commands\RunAgentSchedules;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        CleanExpiredReports::class,
        RunScheduledReports::class,
        SyncSkills::class,
        RunAgentSchedules::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Run scheduled reports every 5 minutes
        $schedule->command('reports:run-scheduled')->everyFiveMinutes();
        // Run agent schedules every minute
        $schedule->command('agents:run-schedules')->everyMinute();
        // Clean up expired generated reports daily
        $schedule->command('reports:clean-expired')->daily();
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
