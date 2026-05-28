<?php

namespace App\Models\Quality;

use App\Models\HR\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NonConformance extends Model
{
    protected $table = 'quality_non_conformances';

    protected $fillable = [
        'nc_number', 'check_id', 'description', 'severity', 'status',
        'root_cause', 'corrective_action', 'assigned_to',
        'target_resolution_date', 'resolved_at',
    ];

    protected $casts = [
        'target_resolution_date' => 'date',
        'resolved_at' => 'datetime',
    ];

    public function check(): BelongsTo
    {
        return $this->belongsTo(QualityCheck::class, 'check_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }
}
