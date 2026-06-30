<?php

namespace App\Models\Agent;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgentProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'description', 'agent_type', 'provider_config', 'is_active', 'created_by',
    ];

    protected $casts = [
        'provider_config' => 'array',
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(AgentToken::class);
    }

    public function skillAssignments(): HasMany
    {
        return $this->hasMany(AgentSkillAssignment::class);
    }

    public function executions(): HasMany
    {
        return $this->hasMany(AgentExecution::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(AgentSchedule::class);
    }
}
