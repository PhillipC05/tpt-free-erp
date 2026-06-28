<?php

namespace App\Models\Notification;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationWebhook extends Model
{
    use HasFactory;

    protected $table = 'notification_webhooks';

    protected $fillable = [
        'user_id', 'name', 'url', 'events', 'secret',
        'is_active', 'failure_count', 'last_triggered_at',
    ];

    protected function casts(): array
    {
        return [
            'events' => 'array',
            'last_triggered_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
