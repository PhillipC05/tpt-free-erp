<?php

namespace App\Models\Agent;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentCostRecord extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'agent_profile_id', 'skill_slug', 'model_used',
        'tokens_input', 'tokens_output', 'estimated_cost',
        'currency', 'recorded_at', 'date_bucket', 'created_at',
    ];

    protected $casts = [
        'estimated_cost' => 'decimal:6',
        'recorded_at' => 'datetime',
        'date_bucket' => 'date',
        'created_at' => 'datetime',
    ];

    public function agentProfile(): BelongsTo
    {
        return $this->belongsTo(AgentProfile::class);
    }
}
