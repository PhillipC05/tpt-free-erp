<?php

namespace App\Services\Notification;

use App\Mail\NotificationMail;
use Illuminate\Support\Facades\Mail;

class EmailDeliveryService
{
    public function sendNotification(
        string $toEmail,
        string $subject,
        string $message,
        ?string $fromName = null,
    ): bool {
        $fromName ??= config('app.name', 'TPT ERP');

        Mail::to($toEmail)->send(new NotificationMail(
            subject: $subject,
            message: $message,
            fromName: $fromName,
        ));

        return true;
    }
}
