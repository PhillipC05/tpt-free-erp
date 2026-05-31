<?php

namespace App\Models\Quality;

use App\Models\HR\Employee;
use App\Models\Inventory\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QualityCheck extends Model
{
    use HasFactory;
    protected $table = 'quality_checks';

    protected $fillable = [
        'check_code', 'product_id', 'reference_type', 'reference_id',
        'type', 'result', 'notes', 'inspected_by', 'inspected_at',
    ];

    protected $casts = [
        'inspected_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'inspected_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QualityCheckItem::class, 'check_id');
    }

    public function nonConformances(): HasMany
    {
        return $this->hasMany(NonConformance::class, 'check_id');
    }
}
