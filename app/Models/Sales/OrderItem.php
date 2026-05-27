<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $table = 'sales_order_items';

    protected $fillable = [
        'order_id', 'product_id', 'description', 'quantity', 'unit_price',
        'discount_percent', 'tax_percent', 'line_total',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(\App\Models\Inventory\Product::class);
    }
}