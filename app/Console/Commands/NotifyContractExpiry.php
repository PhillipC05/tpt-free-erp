<?php

namespace App\Console\Commands;

use App\Models\Contracts\Contract;
use App\Models\User;
use App\Notifications\ContractExpiryNotification;
use Illuminate\Console\Command;

class NotifyContractExpiry extends Command
{
    protected $signature = 'contracts:notify-expiry';
    protected $description = 'Send expiry alerts for contracts expiring in 30, 7, or 1 day(s)';

    private const ALERT_DAYS = [30, 7, 1];

    public function handle(): void
    {
        $notified = 0;

        foreach (self::ALERT_DAYS as $days) {
            $targetDate = now()->addDays($days)->toDateString();

            $contracts = Contract::whereDate('end_date', $targetDate)
                ->whereIn('status', ['active', 'signed'])
                ->with('creator')
                ->get();

            foreach ($contracts as $contract) {
                $recipients = $this->resolveRecipients($contract);

                foreach ($recipients as $user) {
                    $user->notify(new ContractExpiryNotification($contract, $days));
                    $notified++;
                }

                $this->info("Notified {$contract->contract_number} (expires in {$days}d): {$recipients->count()} recipient(s)");
            }
        }

        $this->info("Contract expiry check complete. {$notified} notification(s) sent.");
    }

    private function resolveRecipients(Contract $contract): \Illuminate\Support\Collection
    {
        $userIds = collect([$contract->created_by, $contract->signed_by])
            ->filter()
            ->unique();

        return User::whereIn('id', $userIds)->get();
    }
}
