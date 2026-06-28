<?php

namespace App\Models\Notification;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationMessage extends Model
{
    use HasFactory;

    protected $table = 'notification_queue';

    protected $fillable = [
        'user_id', 'template_id', 'channel', 'subject', 'body',
        'data', 'status', 'error_message', 'attempts', 'sent_at',
        'read_at', 'scheduled_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'sent_at' => 'datetime',
            'read_at' => 'datetime',
            'scheduled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_id');
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}
