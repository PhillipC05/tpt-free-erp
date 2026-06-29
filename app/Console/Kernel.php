<?php

namespace App\Console;

use App\Console\Commands\CheckContractExpiry;
use App\Console\Commands\CleanExpiredReports;
use App\Console\Commands\NotifyContractExpiry;
use App\Console\Commands\RunAgentSchedules;
use App\Console\Commands\RunScheduledReports;
use App\Console\Commands\SeedCampaignAnalytics;
use App\Console\Commands\SyncSkills;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        CheckContractExpiry::class,
        CleanExpiredReports::class,
        NotifyContractExpiry::class,
        RunScheduledReports::class,
        SeedCampaignAnalytics::class,
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
        // Alert creators/signers about contracts expiring in 30, 7, or 1 day(s)
        $schedule->command('contracts:notify-expiry')->dailyAt('08:00');
        // Create expiry warning notifications for contracts ending within 30 days
        $schedule->command('contracts:check-expiry')->dailyAt('09:00');
        // Seed yesterday's campaign analytics rows for all active campaigns
        $schedule->command('marketing:seed-analytics')->dailyAt('01:00');
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
