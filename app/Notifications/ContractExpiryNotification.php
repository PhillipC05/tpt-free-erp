<?php

namespace App\Notifications;

use App\Models\Contracts\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractExpiryNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Contract $contract,
        public readonly int $daysUntilExpiry
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Contract Expiring in {$this->daysUntilExpiry} Day(s): {$this->contract->title}")
            ->line("The contract \"{$this->contract->title}\" ({$this->contract->contract_number}) expires in {$this->daysUntilExpiry} day(s).")
            ->line('End date: '.$this->contract->end_date->toFormattedDateString())
            ->action('View Contract', url("/contracts/{$this->contract->id}"))
            ->line('Please take action before the contract expires.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'contract_expiry',
            'contract_id' => $this->contract->id,
            'contract_number' => $this->contract->contract_number,
            'title' => $this->contract->title,
            'days_until_expiry' => $this->daysUntilExpiry,
            'end_date' => $this->contract->end_date->toDateString(),
        ];
    }
}
