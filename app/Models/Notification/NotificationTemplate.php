<?php

namespace App\Models\Notification;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'notification_templates';

    protected $fillable = [
        'code', 'name', 'subject', 'body', 'html_body',
        'default_channels', 'variables', 'category', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_channels' => 'array',
            'variables' => 'array',
        ];
    }

    public function render(array $data = []): array
    {
        $subject = $this->subject;
        $body = $this->body;

        foreach ($data as $key => $value) {
            $subject = str_replace("{{{$key}}}", (string) $value, $subject);
            $body = str_replace("{{{$key}}}", (string) $value, $body);
        }

        return ['subject' => $subject, 'body' => $body];
    }
}
