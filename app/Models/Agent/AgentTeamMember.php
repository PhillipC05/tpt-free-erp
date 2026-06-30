<?php

namespace App\Models\Agent;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentTeamMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id', 'agent_profile_id', 'execution_order', 'skill_slug', 'input_mapping',
    ];

    protected $casts = [
        'execution_order' => 'integer',
        'input_mapping' => 'array',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(AgentTeam::class, 'team_id');
    }

    public function agentProfile(): BelongsTo
    {
        return $this->belongsTo(AgentProfile::class);
    }
}
