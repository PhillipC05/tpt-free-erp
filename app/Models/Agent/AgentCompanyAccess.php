<?php

namespace App\Models\Agent;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentCompanyAccess extends Model
{
    use HasFactory;

    protected $table = 'agent_company_access';

    protected $fillable = [
        'agent_profile_id', 'company_id', 'granted_by', 'access_level', 'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function agentProfile(): BelongsTo
    {
        return $this->belongsTo(AgentProfile::class);
    }

    public function granter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}
