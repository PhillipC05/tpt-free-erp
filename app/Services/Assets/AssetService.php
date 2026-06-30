<?php

namespace App\Services\Assets;

use App\Models\Assets\Asset;
use App\Models\Assets\MaintenanceRecord;
use Illuminate\Support\Collection;

class AssetService
{
    public function createAsset(array $data): Asset
    {
        $data['status'] = $data['status'] ?? 'active';

        return Asset::create($data);
    }

    public function updateAsset(Asset $asset, array $data): Asset
    {
        $asset->update($data);

        return $asset->fresh();
    }

    public function calculateDepreciation(Asset $asset): float
    {
        $purchaseCost = (float) $asset->purchase_cost;
        $salvageValue = (float) $asset->salvage_value;
        $usefulLife = (int) $asset->useful_life_years;

        if ($usefulLife <= 0) {
            throw new \RuntimeException('Asset useful life must be greater than 0');
        }

        switch ($asset->depreciation_method) {
            case 'straight_line':
                $annualDepreciation = ($purchaseCost - $salvageValue) / $usefulLife;
                break;

            case 'declining_balance':
                $rate = 2 / $usefulLife; // Double declining
                $bookValue = (float) $asset->current_value;
                $annualDepreciation = $bookValue * $rate;

                // Don't depreciate below salvage value
                if ($bookValue - $annualDepreciation < $salvageValue) {
                    $annualDepreciation = $bookValue - $salvageValue;
                }
                break;

            case 'sum_of_years':
                $remainingLife = $usefulLife - (now()->diffInYears($asset->purchase_date));
                $remainingLife = max(1, $remainingLife);
                $sumOfYears = $usefulLife * ($usefulLife + 1) / 2;
                $annualDepreciation = ($purchaseCost - $salvageValue) * ($remainingLife / $sumOfYears);
                break;

            default:
                $annualDepreciation = ($purchaseCost - $salvageValue) / $usefulLife;
        }

        $newValue = max((float) $asset->current_value - $annualDepreciation, $salvageValue);
        $asset->update(['current_value' => $newValue]);

        return $annualDepreciation;
    }

    public function scheduleMaintenance(Asset $asset, array $data): MaintenanceRecord
    {
        return $asset->maintenanceRecords()->create($data);
    }

    public function completeMaintenance(MaintenanceRecord $record, array $data): MaintenanceRecord
    {
        $record->update(array_merge($data, [
            'status' => 'completed',
            'completed_date' => now()->toDateString(),
        ]));

        return $record->fresh();
    }

    public function getMaintenanceHistory(Asset $asset): Collection
    {
        return $asset->maintenanceRecords()->orderBy('scheduled_date', 'desc')->get();
    }

    public function getAssetSummary(): array
    {
        $totalCost = Asset::sum('purchase_cost');
        $currentValue = Asset::sum('current_value');
        $totalDepreciation = $totalCost - $currentValue;

        return [
            'total_assets' => Asset::count(),
            'total_cost' => (float) $totalCost,
            'current_value' => (float) $currentValue,
            'total_depreciation' => (float) $totalDepreciation,
            'active_assets' => Asset::whereIn('status', ['active', 'in_use'])->count(),
            'under_maintenance' => Asset::where('status', 'under_maintenance')->count(),
            'retired' => Asset::whereIn('status', ['retired', 'disposed'])->count(),
        ];
    }
}
