<?php

namespace App\Models\Sales;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmPipeline extends Model
{
    use SoftDeletes;

    protected $table = 'sales_crm_pipelines';

    protected $fillable = [
        'code', 'name', 'customer_id', 'contact_name', 'contact_email', 'contact_phone',
        'stage', 'value', 'probability', 'expected_close_date', 'actual_close_date',
        'notes', 'assigned_to', 'status',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'expected_close_date' => 'date',
        'actual_close_date' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function getWeightedValueAttribute(): float
    {
        return round((float) $this->value * $this->probability / 100, 2);
    }
}
