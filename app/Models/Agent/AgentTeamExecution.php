<?php

namespace App\Models\Agent;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentTeamExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id', 'triggered_by', 'status', 'output', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'output' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(AgentTeam::class, 'team_id');
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function stepResults(): HasMany
    {
        return $this->hasMany(AgentTeamStepResult::class, 'team_execution_id');
    }
}
