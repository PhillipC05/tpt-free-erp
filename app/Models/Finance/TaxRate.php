<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    protected $table = 'finance_tax_rates';

    protected $fillable = [
        'code', 'name', 'rate', 'type', 'description',
        'is_active', 'effective_date', 'expiry_date',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'is_active' => 'boolean',
        'effective_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function computeFor(float $amount): float
    {
        if ($this->type === 'fixed') {
            return (float) $this->rate;
        }

        return round($amount * (float) $this->rate / 100, 2);
    }
}
