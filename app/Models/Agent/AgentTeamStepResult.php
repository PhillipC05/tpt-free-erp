<?php

namespace App\Models\Agent;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentTeamStepResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_execution_id', 'agent_profile_id', 'skill_slug', 'execution_id',
        'input', 'output', 'status', 'duration_ms', 'step_order',
    ];

    protected $casts = [
        'input' => 'array',
        'output' => 'array',
        'duration_ms' => 'integer',
        'step_order' => 'integer',
    ];

    public function teamExecution(): BelongsTo
    {
        return $this->belongsTo(AgentTeamExecution::class, 'team_execution_id');
    }

    public function agentProfile(): BelongsTo
    {
        return $this->belongsTo(AgentProfile::class);
    }

    public function execution(): BelongsTo
    {
        return $this->belongsTo(AgentExecution::class);
    }
}
