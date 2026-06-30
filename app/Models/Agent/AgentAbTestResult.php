<?php

namespace App\Models\Agent;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentAbTestResult extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'ab_test_id', 'skill_slug', 'execution_id', 'output',
        'tokens_used', 'duration_ms', 'quality_score', 'created_at',
    ];

    protected $casts = [
        'output' => 'array',
        'quality_score' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function abTest(): BelongsTo
    {
        return $this->belongsTo(AgentAbTest::class, 'ab_test_id');
    }

    public function execution(): BelongsTo
    {
        return $this->belongsTo(AgentExecution::class);
    }
}
