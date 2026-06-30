<?php

namespace App\Services\Marketing;

use App\Mail\CampaignEmail;
use App\Models\Marketing\Campaign;
use App\Models\Marketing\Lead;
use Illuminate\Support\Facades\Mail;

class CampaignEmailService
{
    public function sendCampaign(Campaign $campaign, string $subject, string $htmlBody): array
    {
        $leads = $campaign->leads()->where('email', '!=', null)->get();

        $sent = 0;
        $failed = [];
        $skipped = 0;

        foreach ($leads as $lead) {
            if ($this->shouldSkipLead($lead)) {
                $skipped++;

                continue;
            }

            $mailable = new CampaignEmail(
                subject: $subject,
                htmlBody: $htmlBody,
                campaignName: $campaign->name,
                leadName: $lead->first_name.' '.$lead->last_name,
            );

            try {
                Mail::to($lead->email)->queue($mailable);
                $sent++;
                $this->logSendAttempt($campaign->id, $lead->id, 'sent');
            } catch (\Throwable $e) {
                $failed[] = $lead->email;
                $this->logSendAttempt($campaign->id, $lead->id, 'failed', $e->getMessage());
            }
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'skipped' => $skipped,
            'total_leads' => $leads->count(),
        ];
    }

    public function sendToRecipients(Campaign $campaign, string $subject, string $htmlBody, array $recipients): array
    {
        $sent = 0;
        $failed = [];
        $skipped = 0;

        foreach ($recipients as $email) {
            $mailable = new CampaignEmail(
                subject: $subject,
                htmlBody: $htmlBody,
                campaignName: $campaign->name,
            );

            try {
                Mail::to($email)->queue($mailable);
                $sent++;
            } catch (\Throwable $e) {
                $failed[] = $email;
            }
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'skipped' => $skipped,
            'total_leads' => count($recipients),
        ];
    }

    private function shouldSkipLead(Lead $lead): bool
    {
        $leadTags = $lead->tags ?? [];
        if (in_array('unsubscribed', $leadTags)) {
            return true;
        }

        return $lead->status === 'converted';
    }

    private function logSendAttempt(int $campaignId, int $leadId, string $status, ?string $error = null): void
    {
        $logPath = storage_path('logs/campaign_sends.jsonl');
        $entry = [
            'campaign_id' => $campaignId,
            'lead_id' => $leadId,
            'status' => $status,
            'error' => $error,
            'sent_at' => now()->toIso8601String(),
        ];

        file_put_contents($logPath, json_encode($entry).PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
