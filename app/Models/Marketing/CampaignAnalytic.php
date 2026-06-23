<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignAnalytic extends Model
{
    protected $table = 'marketing_campaign_analytics';

    protected $fillable = [
        'campaign_id', 'date', 'impressions', 'clicks', 'conversions', 'cost', 'revenue',
    ];

    protected $casts = [
        'date' => 'date',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'conversions' => 'integer',
        'cost' => 'decimal:2',
        'revenue' => 'decimal:2',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
