<?php

namespace App\Services\Inventory;

use App\Models\Inventory\Product;
use App\Models\Inventory\Stock;
use App\Models\Inventory\StockMovement;
use App\Models\Inventory\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function createProduct(array $data): Product
    {
        return Product::create($data);
    }

    public function updateProduct(Product $product, array $data): Product
    {
        $product->update($data);

        return $product->fresh();
    }

    public function deleteProduct(Product $product): bool
    {
        if ($product->stock()->sum('quantity') > 0) {
            throw new \RuntimeException('Cannot delete product with existing stock');
        }

        return $product->delete();
    }

    public function getLowStockProducts(): Collection
    {
        return Product::with(['stock.warehouse', 'category'])
            ->where('is_active', true)
            ->get()
            ->filter(function ($product) {
                $totalStock = $product->stock->sum('quantity');

                return $totalStock <= $product->min_stock_level;
            });
    }

    public function adjustStock(int $productId, int $warehouseId, float $quantity, string $reason = '', ?string $referenceType = null, ?int $referenceId = null): StockMovement
    {
        return DB::transaction(function () use ($productId, $warehouseId, $quantity, $reason, $referenceType, $referenceId) {
            $product = Product::findOrFail($productId);
            $warehouse = Warehouse::findOrFail($warehouseId);

            $stock = Stock::firstOrCreate(
                ['product_id' => $productId, 'warehouse_id' => $warehouseId],
                ['quantity' => 0, 'reserved_quantity' => 0, 'available_quantity' => 0]
            );

            $type = $quantity > 0 ? 'in' : 'out';

            $newQuantity = (float) $stock->quantity + $quantity;
            $newAvailable = (float) $stock->available_quantity + $quantity;

            if ($newAvailable < 0) {
                throw new \RuntimeException('Insufficient stock available');
            }

            $stock->update([
                'quantity' => max($newQuantity, 0),
                'available_quantity' => max($newAvailable, 0),
            ]);

            $costPrice = (float) $product->cost_price;
            $totalCost = abs($quantity) * $costPrice;

            return StockMovement::create([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'type' => $type,
                'quantity' => abs($quantity),
                'unit_cost' => $costPrice,
                'total_cost' => $totalCost,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $reason ?: ($type === 'in' ? 'Stock adjustment IN' : 'Stock adjustment OUT'),
                'created_by' => auth()->id(),
                'movement_date' => now(),
            ]);
        });
    }

    public function transferStock(int $productId, int $fromWarehouseId, int $toWarehouseId, float $quantity, string $reason = ''): array
    {
        return DB::transaction(function () use ($productId, $fromWarehouseId, $toWarehouseId, $quantity, $reason) {
            // Remove from source
            $fromStock = Stock::where('product_id', $productId)
                ->where('warehouse_id', $fromWarehouseId)
                ->firstOrFail();

            $fromAvailable = (float) $fromStock->available_quantity;
            if ($fromAvailable < $quantity) {
                throw new \RuntimeException('Insufficient stock at source warehouse');
            }

            $fromStock->update([
                'quantity' => (float) $fromStock->quantity - $quantity,
                'available_quantity' => $fromAvailable - $quantity,
            ]);

            // Add to destination
            $toStock = Stock::firstOrCreate(
                ['product_id' => $productId, 'warehouse_id' => $toWarehouseId],
                ['quantity' => 0, 'reserved_quantity' => 0, 'available_quantity' => 0]
            );

            $toStock->update([
                'quantity' => (float) $toStock->quantity + $quantity,
                'available_quantity' => (float) $toStock->available_quantity + $quantity,
            ]);

            // Record out movement
            $outMovement = StockMovement::create([
                'product_id' => $productId,
                'warehouse_id' => $fromWarehouseId,
                'type' => 'transfer',
                'quantity' => $quantity,
                'description' => $reason ?: 'Stock transfer OUT',
                'created_by' => auth()->id(),
                'movement_date' => now(),
            ]);

            // Record in movement
            $inMovement = StockMovement::create([
                'product_id' => $productId,
                'warehouse_id' => $toWarehouseId,
                'type' => 'transfer',
                'quantity' => $quantity,
                'description' => $reason ?: 'Stock transfer IN',
                'created_by' => auth()->id(),
                'movement_date' => now(),
            ]);

            return [
                'from' => $fromStock->fresh(),
                'to' => $toStock->fresh(),
                'movements' => [$outMovement, $inMovement],
            ];
        });
    }

    public function getInventoryValuation(string $method = 'average'): array
    {
        $products = Product::with('stock.warehouse')->where('is_active', true)->get();
        $valuation = [];
        $totalValue = 0;

        foreach ($products as $product) {
            $totalStock = $product->stock->sum('quantity');
            $costPrice = (float) $product->cost_price;

            if ($method === 'fifo') {
                $value = $totalStock * $costPrice;
            } elseif ($method === 'lifo') {
                $value = $totalStock * $costPrice;
            } else {
                // Average cost
                $value = $totalStock * $costPrice;
            }

            $valuation[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'total_stock' => $totalStock,
                'unit_cost' => $costPrice,
                'total_value' => $value,
                'valuation_method' => $method,
            ];

            $totalValue += $value;
        }

        return [
            'method' => $method,
            'items' => $valuation,
            'total_value' => $totalValue,
            'total_products' => count($valuation),
        ];
    }

    public function getProductStockLevels(int $productId): array
    {
        $product = Product::with('stock.warehouse')->findOrFail($productId);
        $totalStock = $product->stock->sum('quantity');
        $totalReserved = $product->stock->sum('reserved_quantity');
        $totalAvailable = $product->stock->sum('available_quantity');

        return [
            'product' => $product,
            'total_quantity' => $totalStock,
            'total_reserved' => $totalReserved,
            'total_available' => $totalAvailable,
            'warehouse_stock' => $product->stock,
        ];
    }
}
