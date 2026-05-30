<?php

namespace App\Services\Finance;

use App\Models\Finance\TaxRate;
use Illuminate\Support\Collection;

class TaxService
{
    public function createTaxRate(array $data): TaxRate
    {
        return TaxRate::create($data);
    }

    public function updateTaxRate(TaxRate $taxRate, array $data): TaxRate
    {
        $taxRate->update($data);
        return $taxRate->fresh();
    }

    public function getActiveTaxRates(): Collection
    {
        return TaxRate::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>=', now()->toDateString());
            })
            ->orderBy('name')
            ->get();
    }

    public function computeTax(string $taxCode, float $amount): float
    {
        $taxRate = TaxRate::where('code', $taxCode)
            ->where('is_active', true)
            ->first();

        if (!$taxRate) {
            throw new \RuntimeException("Tax rate '{$taxCode}' not found or inactive");
        }

        return $taxRate->computeFor($amount);
    }

    public function computeTaxById(int $taxRateId, float $amount): float
    {
        $taxRate = TaxRate::findOrFail($taxRateId);
        return $taxRate->computeFor($amount);
    }
}