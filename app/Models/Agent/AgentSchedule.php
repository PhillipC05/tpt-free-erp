<?php

namespace App\Models\Agent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AgentSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_profile_id', 'skill_slug', 'cron_expression', 'input_template',
        'is_active', 'last_run_at', 'next_run_at', 'last_execution_id',
    ];

    protected $casts = [
        'input_template' => 'array',
        'is_active'      => 'boolean',
        'last_run_at'    => 'datetime',
        'next_run_at'    => 'datetime',
    ];

    public function agentProfile(): BelongsTo
    {
        return $this->belongsTo(AgentProfile::class);
    }

    public function lastExecution(): BelongsTo
    {
        return $this->belongsTo(AgentExecution::class, 'last_execution_id');
    }
}
