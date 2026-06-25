<?php

namespace App\Models\Subscription;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanChange extends Model
{
    use HasFactory;

    protected $table = 'subscription_plan_changes';

    protected $fillable = [
        'subscription_id', 'from_plan_id', 'to_plan_id', 'change_type',
        'effective_date', 'proration_amount', 'reason', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'proration_amount' => 'decimal:2',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function fromPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'from_plan_id');
    }

    public function toPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'to_plan_id');
    }
}
