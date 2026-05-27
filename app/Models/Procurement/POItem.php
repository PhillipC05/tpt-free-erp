<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class POItem extends Model
{
    protected $table = 'procurement_po_items';

    protected $fillable = [
        'purchase_order_id', 'product_id', 'description', 'quantity',
        'received_quantity', 'unit_price', 'line_total',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(\App\Models\Inventory\Product::class);
    }
}