<?php

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractMilestone extends Model
{
    protected $table = 'contract_milestones';

    protected $fillable = [
        'contract_id', 'title', 'description', 'due_date', 'payment_amount', 'is_completed', 'completed_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'payment_amount' => 'decimal:2',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}
