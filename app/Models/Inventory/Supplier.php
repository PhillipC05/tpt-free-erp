<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $table = 'inventory_suppliers';

    protected $fillable = [
        'code', 'name', 'contact_person', 'email', 'phone', 'address',
        'city', 'country', 'tax_number', 'payment_terms', 'lead_time_days',
        'minimum_order_value', 'status', 'current_balance',
    ];

    protected $casts = [
        'minimum_order_value' => 'decimal:2',
        'current_balance' => 'decimal:2',
    ];
}
