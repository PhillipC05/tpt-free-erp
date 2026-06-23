<?php

namespace App\Console\Commands;

use App\Jobs\ReportGenerationJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RunScheduledReports extends Command
{
    protected $signature = 'reports:run-scheduled';
    protected $description = 'Execute all due scheduled reports';

    public function handle(): void
    {
        $due = DB::table('scheduled_reports')
            ->where('is_active', true)
            ->where('next_run_at', '<=', now())
            ->get();

        foreach ($due as $scheduled) {
            ReportGenerationJob::dispatch(
                $scheduled->user_id,
                $scheduled->report_type,
                json_decode($scheduled->parameters, true) ?? [],
                $scheduled->format ?? 'json',
                $scheduled->delivery_email,
                $scheduled->id
            );

            $frequency = $scheduled->frequency;
            $nextRun = match ($frequency) {
                'hourly'  => now()->addHour(),
                'daily'   => now()->addDay(),
                'weekly'  => now()->addWeek(),
                'monthly' => now()->addMonth(),
                default   => now()->addDay(),
            };

            DB::table('scheduled_reports')
                ->where('id', $scheduled->id)
                ->update([
                    'last_run_at' => now(),
                    'next_run_at' => $nextRun,
                ]);

            $this->info("Dispatched report: {$scheduled->report_type} for user {$scheduled->user_id}");
        }

        $this->info("Processed {$due->count()} scheduled reports.");
    }
}
