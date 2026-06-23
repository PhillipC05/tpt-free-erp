<?php

namespace App\Models\Marketing;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'marketing_campaigns';

    protected $fillable = [
        'name', 'code', 'type', 'status', 'budget', 'actual_spend',
        'start_date', 'end_date', 'target_audience', 'goals', 'created_by',
    ];

    protected $casts = [
        'target_audience' => 'array',
        'goals' => 'array',
        'budget' => 'decimal:2',
        'actual_spend' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'campaign_id');
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(CampaignAnalytic::class, 'campaign_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
