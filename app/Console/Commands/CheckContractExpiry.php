<?php

namespace App\Console\Commands;

use App\Models\Contracts\Contract;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckContractExpiry extends Command
{
    protected $signature = 'contracts:check-expiry';

    protected $description = 'Create notifications for active contracts expiring within 30 days';

    public function handle(): int
    {
        $contracts = Contract::where('status', 'active')
            ->whereBetween('end_date', [now()->toDateString(), now()->addDays(30)->toDateString()])
            ->get();

        $created = 0;

        foreach ($contracts as $contract) {
            $daysUntilExpiry = (int) now()->diffInDays($contract->end_date, false);

            if ($daysUntilExpiry < 0) {
                continue;
            }

            $alreadyNotified = DB::table('notifications')
                ->where('type', 'contract_expiry_warning')
                ->where('created_at', '>=', now()->startOfDay())
                ->where('created_at', '<', now()->addDay()->startOfDay())
                ->whereJsonContains('data', ['contract_id' => $contract->id])
                ->exists();

            if ($alreadyNotified) {
                continue;
            }

            DB::table('notifications')->insert([
                'id' => Str::uuid(),
                'type' => 'contract_expiry_warning',
                'notifiable_type' => App\Models\User::class,
                'notifiable_id' => $contract->created_by,
                'data' => json_encode([
                    'contract_id' => $contract->id,
                    'title' => $contract->title,
                    'days_until_expiry' => $daysUntilExpiry,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $created++;
            $this->info("Notification created for contract \"{$contract->title}\" (expires in {$daysUntilExpiry} days)");
        }

        $this->info("Contract expiry check complete. {$created} notification(s) created.");

        return 0;
    }
}
