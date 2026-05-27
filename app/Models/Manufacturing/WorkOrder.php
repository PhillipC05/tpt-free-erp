<?php

namespace App\Models\Manufacturing;

use App\Models\HR\Employee;
use App\Models\Inventory\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrder extends Model
{
    protected $table = 'manufacturing_work_orders';

    protected $fillable = [
        'wo_number', 'product_id', 'bom_id', 'planned_quantity', 'produced_quantity',
        'start_date', 'end_date', 'status', 'notes', 'assigned_to',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }
}