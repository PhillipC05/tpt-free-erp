<?php

namespace App\Console\Commands;

use App\Models\Marketing\Campaign;
use App\Models\Marketing\CampaignAnalytic;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Seeds yesterday's analytic row for every active campaign that doesn't already have one.
 * Intended as a daily scheduled task — run automatically or via:
 *   php artisan marketing:seed-analytics [--date=YYYY-MM-DD]
 */
class SeedCampaignAnalytics extends Command
{
    protected $signature = 'marketing:seed-analytics {--date= : Date to seed (YYYY-MM-DD, defaults to yesterday)}';

    protected $description = 'Seed campaign analytic rows for active campaigns (runs daily)';

    public function handle(): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))->toDateString()
            : now()->subDay()->toDateString();

        $campaigns = Campaign::where('status', 'active')
            ->where('start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $date);
            })
            ->get();

        if ($campaigns->isEmpty()) {
            $this->info("No active campaigns found for {$date}.");

            return self::SUCCESS;
        }

        $seeded = 0;

        foreach ($campaigns as $campaign) {
            $exists = CampaignAnalytic::where('campaign_id', $campaign->id)
                ->where('date', $date)
                ->exists();

            if ($exists) {
                continue;
            }

            // Simulate realistic engagement metrics derived from budget
            $dailyBudget = $campaign->budget ? (float) $campaign->budget / 30 : 100;
            $impressions = (int) ($dailyBudget * random_int(80, 120));
            $clicks = (int) ($impressions * (random_int(2, 8) / 100));
            $conversions = (int) ($clicks * (random_int(5, 20) / 100));
            $cost = round($dailyBudget * (random_int(80, 100) / 100), 2);
            $revenue = round($cost * (random_int(100, 400) / 100), 2);

            CampaignAnalytic::create([
                'campaign_id' => $campaign->id,
                'date' => $date,
                'impressions' => $impressions,
                'clicks' => $clicks,
                'conversions' => $conversions,
                'cost' => $cost,
                'revenue' => $revenue,
            ]);

            $seeded++;
        }

        $this->info("Seeded analytics for {$seeded} campaign(s) on {$date}.");

        return self::SUCCESS;
    }
}
