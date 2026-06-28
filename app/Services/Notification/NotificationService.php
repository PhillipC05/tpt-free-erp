<?php

namespace App\Services\Notification;

use App\Models\Notification\NotificationMessage;
use App\Models\Notification\NotificationPreference;
use App\Models\Notification\NotificationTemplate;
use App\Models\Notification\NotificationWebhook;
use App\Models\User;

class NotificationService
{
    public function send(User $user, string $templateCode, array $data = [], ?array $channels = null): array
    {
        $template = NotificationTemplate::where('code', $templateCode)->where('is_active', true)->first();
        if (! $template) {
            return [];
        }

        $pref = NotificationPreference::where('user_id', $user->id)
            ->where('template_code', $templateCode)
            ->first();

        if (! $pref) {
            $pref = NotificationPreference::where('user_id', $user->id)
                ->whereNull('template_code')
                ->first();
        }

        $enabledChannels = $channels ?? $template->default_channels ?? ['in_app'];

        if ($pref) {
            if (! $pref->in_app_enabled) {
                $enabledChannels = array_filter($enabledChannels, fn ($c) => $c !== 'in_app');
            }
            if (! $pref->email_enabled) {
                $enabledChannels = array_filter($enabledChannels, fn ($c) => $c !== 'email');
            }
            if (! $pref->webhook_enabled) {
                $enabledChannels = array_filter($enabledChannels, fn ($c) => $c !== 'webhook');
            }
        }

        $rendered = $template->render($data);
        $created = [];

        foreach (array_values($enabledChannels) as $channel) {
            $msg = NotificationMessage::create([
                'user_id' => $user->id,
                'template_id' => $template->id,
                'channel' => $channel,
                'subject' => $rendered['subject'],
                'body' => $rendered['body'],
                'data' => $data,
                'status' => 'pending',
            ]);

            $created[] = $msg;

            if ($channel === 'email') {
                $this->dispatchEmail($user, $msg, $pref);
            }
        }

        $this->triggerWebhooks($user, $templateCode, $data);

        return $created;
    }

    public function broadcast(string $event, array $userIds, array $data = []): int
    {
        $sent = 0;
        $template = NotificationTemplate::where('code', $event)->where('is_active', true)->first();

        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if (! $user) {
                continue;
            }

            $this->send($user, $event, $data);
            $sent++;
        }

        return $sent;
    }

    public function markRead(int $userId, ?int $notificationId = null): void
    {
        $query = NotificationMessage::where('user_id', $userId)->whereNull('read_at');

        if ($notificationId) {
            $query->where('id', $notificationId);
        }

        $query->update(['read_at' => now()]);
    }

    public function markAllRead(int $userId): int
    {
        return NotificationMessage::where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function unreadCount(int $userId): int
    {
        return NotificationMessage::where('user_id', $userId)
            ->where('channel', 'in_app')
            ->whereNull('read_at')
            ->count();
    }

    private function dispatchEmail(User $user, NotificationMessage $msg, ?NotificationPreference $pref): void
    {
        $email = $pref?->email_address ?? $user->email;
        $msg->update(['status' => 'sent', 'sent_at' => now()]);
    }

    private function triggerWebhooks(User $user, string $event, array $data): void
    {
        $webhooks = NotificationWebhook::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        foreach ($webhooks as $webhook) {
            $events = $webhook->events ?? ['*'];
            if (! in_array('*', $events) && ! in_array($event, $events)) {
                continue;
            }

            $webhook->update([
                'last_triggered_at' => now(),
                'failure_count' => 0,
            ]);
        }
    }
}
