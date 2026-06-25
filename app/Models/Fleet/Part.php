<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Part extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'fleet_parts';

    protected $fillable = [
        'part_number', 'name', 'description', 'category_id', 'manufacturer',
        'supplier', 'unit', 'unit_cost', 'sell_price', 'quantity_on_hand',
        'reorder_level', 'reorder_quantity', 'bin_location',
        'compatible_vehicles', 'is_active',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(PartCategory::class, 'category_id');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(PartUsage::class, 'part_id');
    }

    public function isLowStock(): bool
    {
        return $this->quantity_on_hand <= $this->reorder_level;
    }
}
