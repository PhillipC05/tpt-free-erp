<?php

namespace App\Models\Quality;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityCheckItem extends Model
{
    protected $table = 'quality_check_items';

    protected $fillable = [
        'check_id', 'parameter', 'expected_value', 'actual_value', 'result', 'notes',
    ];

    public function check(): BelongsTo
    {
        return $this->belongsTo(QualityCheck::class, 'check_id');
    }
}
