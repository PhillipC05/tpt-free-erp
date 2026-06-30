<?php

namespace App\Models\Agent;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgentAbTest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'agent_profile_id', 'skill_slug_a', 'skill_slug_b',
        'input_data', 'status', 'winner_skill', 'created_by',
    ];

    protected $casts = [
        'input_data' => 'array',
    ];

    public function agentProfile(): BelongsTo
    {
        return $this->belongsTo(AgentProfile::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function results(): HasMany
    {
        return $this->hasMany(AgentAbTestResult::class, 'ab_test_id');
    }
}
