<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CampaignEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $subject,
        public readonly string $htmlBody,
        public readonly string $campaignName,
        public readonly ?string $leadName = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subject);
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtml(),
            textString: $this->buildText(),
        );
    }

    private function buildHtml(): string
    {
        $greeting = $this->leadName ? "<p>Hello {$this->leadName},</p>" : '';

        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2 style="color: #333;">{$this->subject}</h2>
    {$greeting}
    <div style="color: #555; line-height: 1.6;">
        {$this->htmlBody}
    </div>
    <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
    <p style="color: #999; font-size: 12px;">Sent from {$this->campaignName}</p>
</body>
</html>
HTML;
    }

    private function buildText(): string
    {
        $greeting = $this->leadName ? "Hello {$this->leadName},\n\n" : '';
        $plainText = strip_tags($this->htmlBody);

        return <<<TEXT
{$this->subject}

{$greeting}{$plainText}

---
Sent from {$this->campaignName}
TEXT;
    }
}
