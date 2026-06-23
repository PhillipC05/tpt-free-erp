<?php

namespace App\Models\Agent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AgentSkillAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_profile_id', 'skill_slug', 'is_enabled', 'config_overrides',
    ];

    protected $casts = [
        'is_enabled'       => 'boolean',
        'config_overrides' => 'array',
    ];

    public function agentProfile(): BelongsTo
    {
        return $this->belongsTo(AgentProfile::class);
    }
}
