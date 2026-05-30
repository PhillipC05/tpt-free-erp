<?php

namespace App\Services\Procurement;

use App\Models\Procurement\PurchaseOrder;
use App\Models\Procurement\POItem;
use App\Models\Procurement\Requisition;
use App\Models\Procurement\Vendor;
use App\Models\Inventory\Stock;
use App\Models\Inventory\StockMovement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProcurementService
{
    public function createPurchaseOrder(array $data): PurchaseOrder
    {
        $data['status'] = $data['status'] ?? 'draft';
        $data['created_by'] = $data['created_by'] ?? auth()->id();
        return PurchaseOrder::create($data);
    }

    public function sendPurchaseOrder(PurchaseOrder $po): PurchaseOrder
    {
        if ($po->status !== 'draft') {
            throw new \RuntimeException('Only draft purchase orders can be sent');
        }
        $po->update(['status' => 'sent']);
        return $po->fresh();
    }

    public function confirmPurchaseOrder(PurchaseOrder $po): PurchaseOrder
    {
        if ($po->status !== 'sent') {
            throw new \RuntimeException('Only sent purchase orders can be confirmed');
        }
        $po->update([
            'status' => 'confirmed',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
        return $po->fresh();
    }

    public function receivePurchaseOrder(PurchaseOrder $po, ?array $items = null): PurchaseOrder
    {
        if ($po->status !== 'confirmed') {
            throw new \RuntimeException('Only confirmed purchase orders can be received');
        }

        return DB::transaction(function () use ($po, $items) {
            $po->update(['status' => 'received']);

            // Update stock for each item
            $poItems = $items ? POItem::whereIn('id', $items)->get() : $po->items;

            foreach ($poItems as $item) {
                if ($item->product_id) {
                    // Default warehouse (could be configured per PO)
                    $warehouseId = 1;

                    $stock = Stock::firstOrCreate(
                        ['product_id' => $item->product_id, 'warehouse_id' => $warehouseId],
                        ['quantity' => 0, 'reserved_quantity' => 0, 'available_quantity' => 0]
                    );

                    $quantity = (float) $item->quantity;
                    $stock->update([
                        'quantity' => (float) $stock->quantity + $quantity,
                        'available_quantity' => (float) $stock->available_quantity + $quantity,
                    ]);

                    $item->update(['received_quantity' => $quantity]);

                    StockMovement::create([
                        'product_id' => $item->product_id,
                        'warehouse_id' => $warehouseId,
                        'type' => 'in',
                        'quantity' => $quantity,
                        'unit_cost' => (float) $item->unit_price,
                        'total_cost' => $quantity * (float) $item->unit_price,
                        'reference_type' => 'purchase_order',
                        'reference_id' => $po->id,
                        'description' => "PO {$po->po_number} receipt",
                        'created_by' => auth()->id(),
                        'movement_date' => now(),
                    ]);
                }
            }

            return $po->fresh();
        });
    }

    public function approvePurchaseOrder(PurchaseOrder $po): PurchaseOrder
    {
        if ($po->status !== 'sent') {
            throw new \RuntimeException('Only sent purchase orders can be approved');
        }
        $po->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
        return $po->fresh();
    }

    public function cancelPurchaseOrder(PurchaseOrder $po): PurchaseOrder
    {
        if (in_array($po->status, ['received', 'cancelled'])) {
            throw new \RuntimeException('Purchase order cannot be cancelled');
        }
        $po->update(['status' => 'cancelled']);
        return $po->fresh();
    }

    public function createRequisition(array $data): Requisition
    {
        $data['status'] = $data['status'] ?? 'pending';
        return Requisition::create($data);
    }

    public function approveRequisition(Requisition $requisition): Requisition
    {
        if ($requisition->status !== 'pending') {
            throw new \RuntimeException('Only pending requisitions can be approved');
        }
        $requisition->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
        return $requisition->fresh();
    }

    public function rejectRequisition(Requisition $requisition, string $reason): Requisition
    {
        if ($requisition->status !== 'pending') {
            throw new \RuntimeException('Only pending requisitions can be rejected');
        }
        $requisition->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);
        return $requisition->fresh();
    }

    public function getVendorPurchaseOrders(Vendor $vendor): Collection
    {
        return $vendor->purchaseOrders()->with('items')->orderBy('created_at', 'desc')->get();
    }

    public function getVendorPerformance(Vendor $vendor): array
    {
        $orders = $vendor->purchaseOrders()->whereIn('status', ['received', 'cancelled'])->get();

        return [
            'vendor' => $vendor,
            'total_orders' => $orders->count(),
            'total_received' => $orders->where('status', 'received')->count(),
            'total_cancelled' => $orders->where('status', 'cancelled')->count(),
            'total_amount' => (float) $orders->sum('total_amount'),
        ];
    }
}