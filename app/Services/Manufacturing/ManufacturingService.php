<?php

namespace App\Services\Manufacturing;

use App\Models\Inventory\Stock;
use App\Models\Inventory\StockMovement;
use App\Models\Manufacturing\Bom;
use App\Models\Manufacturing\BomComponent;
use App\Models\Manufacturing\ProductionSchedule;
use App\Models\Manufacturing\WorkOrder;
use Illuminate\Support\Facades\DB;

class ManufacturingService
{
    public function createBom(array $data): Bom
    {
        return Bom::create($data);
    }

    public function addComponent(Bom $bom, array $data): BomComponent
    {
        return $bom->components()->create($data);
    }

    public function calculateBomCost(Bom $bom): array
    {
        $bom->load('components.product');
        $materialCost = 0;

        foreach ($bom->components as $component) {
            $quantity = (float) $component->quantity;
            $waste = (float) $component->waste_percent;
            $effectiveQuantity = $quantity * (1 + $waste / 100);

            $costPrice = (float) ($component->product->cost_price ?? 0);
            $materialCost += $effectiveQuantity * $costPrice;
        }

        $laborCost = $materialCost * 0.2; // 20% labor
        $overheadCost = $materialCost * 0.1; // 10% overhead
        $totalCost = $materialCost + $laborCost + $overheadCost;

        return [
            'bom' => $bom,
            'material_cost' => round($materialCost, 2),
            'labor_cost' => round($laborCost, 2),
            'overhead_cost' => round($overheadCost, 2),
            'total_cost' => round($totalCost, 2),
            'unit_cost' => round($totalCost / max((float) $bom->quantity, 1), 2),
        ];
    }

    public function createWorkOrder(array $data): WorkOrder
    {
        $data['status'] = $data['status'] ?? 'planned';

        return WorkOrder::create($data);
    }

    public function startWorkOrder(WorkOrder $workOrder): WorkOrder
    {
        if ($workOrder->status !== 'planned') {
            throw new \RuntimeException('Only planned work orders can be started');
        }

        return DB::transaction(function () use ($workOrder) {
            $workOrder->update(['status' => 'in_progress']);

            // Consume raw materials from stock
            if ($workOrder->bom_id) {
                $bom = Bom::with('components.product')->findOrFail($workOrder->bom_id);
                $plannedQty = (float) $workOrder->planned_quantity;

                foreach ($bom->components as $component) {
                    $quantity = (float) $component->quantity * $plannedQty;
                    $productId = $component->component_product_id;

                    // Find stock to consume (FIFO)
                    $stocks = Stock::where('product_id', $productId)
                        ->where('available_quantity', '>', 0)
                        ->orderBy('created_at', 'asc')
                        ->get();

                    $needed = $quantity;
                    foreach ($stocks as $stock) {
                        if ($needed <= 0) {
                            break;
                        }
                        $available = (float) $stock->available_quantity;
                        $toConsume = min($available, $needed);

                        $stock->update([
                            'quantity' => (float) $stock->quantity - $toConsume,
                            'available_quantity' => $available - $toConsume,
                        ]);

                        StockMovement::create([
                            'product_id' => $productId,
                            'warehouse_id' => $stock->warehouse_id,
                            'type' => 'out',
                            'quantity' => $toConsume,
                            'unit_cost' => (float) $component->product->cost_price,
                            'total_cost' => $toConsume * (float) $component->product->cost_price,
                            'reference_type' => 'work_order',
                            'reference_id' => $workOrder->id,
                            'description' => "WO {$workOrder->wo_number} material consumption",
                            'created_by' => auth()->user()?->id,
                            'movement_date' => now(),
                        ]);

                        $needed -= $toConsume;
                    }
                }
            }

            return $workOrder->fresh();
        });
    }

    public function completeWorkOrder(WorkOrder $workOrder, array $data): WorkOrder
    {
        if ($workOrder->status !== 'in_progress') {
            throw new \RuntimeException('Only in-progress work orders can be completed');
        }

        return DB::transaction(function () use ($workOrder, $data) {
            $updateData = array_merge($data, [
                'status' => 'completed',
                'end_date' => $data['end_date'] ?? now()->toDateString(),
            ]);

            $workOrder->update($updateData);

            // Add finished goods to stock
            $producedQty = (float) ($data['produced_quantity'] ?? $workOrder->planned_quantity);
            if ($producedQty > 0) {
                $stock = Stock::firstOrCreate(
                    ['product_id' => $workOrder->product_id, 'warehouse_id' => 1],
                    ['quantity' => 0, 'reserved_quantity' => 0, 'available_quantity' => 0]
                );

                $stock->update([
                    'quantity' => (float) $stock->quantity + $producedQty,
                    'available_quantity' => (float) $stock->available_quantity + $producedQty,
                ]);

                StockMovement::create([
                    'product_id' => $workOrder->product_id,
                    'warehouse_id' => 1,
                    'type' => 'in',
                    'quantity' => $producedQty,
                    'unit_cost' => (float) $workOrder->product->cost_price,
                    'total_cost' => $producedQty * (float) $workOrder->product->cost_price,
                    'reference_type' => 'work_order',
                    'reference_id' => $workOrder->id,
                    'description' => "WO {$workOrder->wo_number} production output",
                    'created_by' => auth()->user()?->id,
                    'movement_date' => now(),
                ]);
            }

            return $workOrder->fresh();
        });
    }

    public function recordProduction(WorkOrder $workOrder, float $quantity): WorkOrder
    {
        $produced = (float) $workOrder->produced_quantity + $quantity;
        $workOrder->update(['produced_quantity' => $produced]);

        return $workOrder->fresh();
    }

    public function scheduleProduction(WorkOrder $workOrder, array $data): ProductionSchedule
    {
        return ProductionSchedule::create(array_merge($data, [
            'work_order_id' => $workOrder->id,
            'status' => 'planned',
        ]));
    }

    public function getWorkOrderCost(WorkOrder $workOrder): array
    {
        $workOrder->load(['product', 'bom.components.product']);

        $plannedQty = (float) $workOrder->planned_quantity;
        $producedQty = (float) $workOrder->produced_quantity;

        // Material cost from BOM
        $materialCost = 0;
        $laborCost = 0;
        $overheadCost = 0;

        if ($workOrder->bom) {
            foreach ($workOrder->bom->components as $component) {
                $compQty = (float) $component->quantity * $plannedQty;
                $costPrice = (float) ($component->product->cost_price ?? 0);
                $materialCost += $compQty * $costPrice;
            }
            $laborCost = $materialCost * 0.2;
            $overheadCost = $materialCost * 0.1;
        }

        $totalCost = $materialCost + $laborCost + $overheadCost;

        return [
            'work_order' => $workOrder,
            'planned_quantity' => $plannedQty,
            'produced_quantity' => $producedQty,
            'material_cost' => round($materialCost, 2),
            'labor_cost' => round($laborCost, 2),
            'overhead_cost' => round($overheadCost, 2),
            'total_cost' => round($totalCost, 2),
            'unit_cost' => $producedQty > 0 ? round($totalCost / $producedQty, 2) : 0,
        ];
    }
}
