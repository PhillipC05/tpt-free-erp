<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $table = 'sales_invoices';

    protected $fillable = [
        'invoice_number', 'order_id', 'customer_id', 'invoice_date', 'due_date',
        'subtotal', 'tax_amount', 'total_amount', 'paid_amount', 'balance_due', 'status',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}