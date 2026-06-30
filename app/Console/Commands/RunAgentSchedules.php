<?php

namespace App\Console\Commands;

use App\Jobs\AgentSkillJob;
use App\Models\Agent\AgentSchedule;
use Illuminate\Console\Command;

class RunAgentSchedules extends Command
{
    protected $signature = 'agents:run-schedules';

    protected $description = 'Execute all due agent skill schedules';

    public function handle(): void
    {
        $due = AgentSchedule::with('agentProfile')
            ->where('is_active', true)
            ->where('next_run_at', '<=', now())
            ->get();

        foreach ($due as $schedule) {
            if (! $schedule->agentProfile || ! $schedule->agentProfile->is_active) {
                continue;
            }

            AgentSkillJob::dispatch(
                $schedule->agent_profile_id,
                $schedule->skill_slug,
                $schedule->input_template ?? [],
                null,
                'scheduled'
            );

            // Parse next run from cron expression (simplified: just add 1 hour for now)
            $schedule->update([
                'last_run_at' => now(),
                'next_run_at' => now()->addHour(),
            ]);

            $this->info("Dispatched agent skill: {$schedule->skill_slug} for agent {$schedule->agent_profile_id}");
        }

        $this->info("Processed {$due->count()} agent schedules.");
    }
}
