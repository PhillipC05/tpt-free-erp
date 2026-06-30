<?php

namespace App\Models\Agent;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_profile_id', 'skill_slug', 'triggered_by', 'trigger_type',
        'input', 'output', 'status', 'tokens_used', 'model_used',
        'duration_ms', 'error_message',
    ];

    protected $casts = [
        'input' => 'array',
        'output' => 'array',
    ];

    public function agentProfile(): BelongsTo
    {
        return $this->belongsTo(AgentProfile::class);
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}
