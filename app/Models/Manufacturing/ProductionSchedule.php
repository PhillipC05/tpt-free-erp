<?php

namespace App\Models\Manufacturing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionSchedule extends Model
{
    protected $table = 'manufacturing_production_schedules';

    protected $fillable = [
        'schedule_number', 'work_order_id', 'resource_name',
        'planned_start', 'planned_end', 'actual_start', 'actual_end',
        'planned_quantity', 'actual_quantity', 'status', 'notes',
    ];

    protected $casts = [
        'planned_start' => 'datetime',
        'planned_end' => 'datetime',
        'actual_start' => 'datetime',
        'actual_end' => 'datetime',
        'planned_quantity' => 'decimal:2',
        'actual_quantity' => 'decimal:2',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }
}
