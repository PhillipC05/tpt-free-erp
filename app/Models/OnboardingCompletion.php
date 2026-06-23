<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingCompletion extends Model
{
    protected $fillable = [
        'user_id',
        'industry_key',
        'completed_at',
        'skipped_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'skipped_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
