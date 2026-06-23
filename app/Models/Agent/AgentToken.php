<?php

namespace App\Models\Agent;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AgentToken extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'agent_profile_id', 'user_id', 'token_hash', 'name',
        'abilities', 'allowed_skill_slugs', 'rate_limit_per_minute',
        'last_used_at', 'expires_at',
    ];

    protected $hidden = ['token_hash'];

    protected $casts = [
        'abilities'           => 'array',
        'allowed_skill_slugs' => 'array',
        'last_used_at'        => 'datetime',
        'expires_at'          => 'datetime',
    ];

    public function agentProfile(): BelongsTo
    {
        return $this->belongsTo(AgentProfile::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
