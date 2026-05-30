<?php

namespace App\Services\Sales;

use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Sales\Invoice;
use App\Models\Sales\Customer;
use App\Models\Sales\CrmPipeline;
use App\Models\Inventory\Stock;
use App\Models\Inventory\StockMovement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SalesService
{
    public function createOrder(array $data): Order
    {
        $data['status'] = $data['status'] ?? 'draft';
        $data['created_by'] = $data['created_by'] ?? auth()->id();
        return Order::create($data);
    }

    public function confirmOrder(Order $order): Order
    {
        if ($order->status !== 'draft') {
            throw new \RuntimeException('Only draft orders can be confirmed');
        }

        return DB::transaction(function () use ($order) {
            $order->update(['status' => 'confirmed']);

            // Reserve stock for each item
            foreach ($order->items as $item) {
                if ($item->product_id) {
                    $stockItems = Stock::where('product_id', $item->product_id)
                        ->where('available_quantity', '>', 0)
                        ->orderBy('created_at', 'asc')
                        ->get();

                    $quantityNeeded = (float) $item->quantity;

                    foreach ($stockItems as $stock) {
                        if ($quantityNeeded <= 0) break;

                        $available = (float) $stock->available_quantity;
                        $toReserve = min($available, $quantityNeeded);

                        $stock->update([
                            'reserved_quantity' => (float) $stock->reserved_quantity + $toReserve,
                            'available_quantity' => $available - $toReserve,
                        ]);

                        $quantityNeeded -= $toReserve;
                    }
                }
            }

            return $order->fresh();
        });
    }

    public function shipOrder(Order $order): Order
    {
        if ($order->status !== 'confirmed') {
            throw new \RuntimeException('Only confirmed orders can be shipped');
        }
        $order->update(['status' => 'shipped']);
        return $order->fresh();
    }

    public function deliverOrder(Order $order): Order
    {
        if ($order->status !== 'shipped') {
            throw new \RuntimeException('Only shipped orders can be delivered');
        }

        return DB::transaction(function () use ($order) {
            $order->update(['status' => 'delivered']);

            // Reduce actual stock for delivered items
            foreach ($order->items as $item) {
                if ($item->product_id) {
                    $stocks = Stock::where('product_id', $item->product_id)
                        ->where('reserved_quantity', '>', 0)
                        ->get();

                    foreach ($stocks as $stock) {
                        $reserved = (float) $stock->reserved_quantity;
                        $quantity = (float) $item->quantity;

                        $toRelease = min($reserved, $quantity);
                        $stock->update([
                            'quantity' => (float) $stock->quantity - $toRelease,
                            'reserved_quantity' => $reserved - $toRelease,
                        ]);

                        // Record out movement
                        StockMovement::create([
                            'product_id' => $item->product_id,
                            'warehouse_id' => $stock->warehouse_id,
                            'type' => 'out',
                            'quantity' => $toRelease,
                            'unit_cost' => (float) $item->unit_price,
                            'total_cost' => $toRelease * (float) $item->unit_price,
                            'reference_type' => 'sales_order',
                            'reference_id' => $order->id,
                            'description' => "Sales order {$order->order_number} delivery",
                            'created_by' => auth()->id(),
                            'movement_date' => now(),
                        ]);
                    }
                }
            }

            return $order->fresh();
        });
    }

    public function cancelOrder(Order $order): Order
    {
        if (in_array($order->status, ['delivered', 'cancelled'])) {
            throw new \RuntimeException('Order cannot be cancelled');
        }

        return DB::transaction(function () use ($order) {
            $order->update(['status' => 'cancelled']);

            // Release reserved stock
            if (in_array($order->status, ['confirmed', 'processing', 'shipped'])) {
                foreach ($order->items as $item) {
                    if ($item->product_id) {
                        $stocks = Stock::where('product_id', $item->product_id)
                            ->where('reserved_quantity', '>', 0)
                            ->get();

                        foreach ($stocks as $stock) {
                            $reserved = (float) $stock->reserved_quantity;
                            $quantity = (float) $item->quantity;
                            $toRelease = min($reserved, $quantity);

                            $stock->update([
                                'reserved_quantity' => $reserved - $toRelease,
                                'available_quantity' => (float) $stock->available_quantity + $toRelease,
                            ]);
                        }
                    }
                }
            }

            return $order->fresh();
        });
    }

    public function createInvoiceFromOrder(Order $order): Invoice
    {
        return DB::transaction(function () use ($order) {
            $invoiceNumber = 'INV-' . strtoupper(uniqid());

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'subtotal' => $order->subtotal,
                'tax_amount' => $order->tax_amount,
                'discount_amount' => $order->discount_amount,
                'total_amount' => $order->total_amount,
                'paid_amount' => 0,
                'balance_due' => $order->total_amount,
                'status' => 'draft',
            ]);

            return $invoice;
        });
    }

    public function recordInvoicePayment(Invoice $invoice, float $amount): Invoice
    {
        if (in_array($invoice->status, ['paid', 'cancelled'])) {
            throw new \RuntimeException('Cannot record payment for this invoice');
        }

        return DB::transaction(function () use ($invoice, $amount) {
            $totalPaid = (float) $invoice->paid_amount + $amount;
            $totalAmount = (float) $invoice->total_amount;
            $newBalance = $totalAmount - $totalPaid;

            $status = $newBalance <= 0 ? 'paid' : 'partially_paid';

            $invoice->update([
                'paid_amount' => $totalPaid,
                'balance_due' => max($newBalance, 0),
                'status' => $status,
            ]);

            return $invoice->fresh();
        });
    }

    public function getSalesForecast(int $months = 3): array
    {
        $startDate = now()->startOfMonth()->subMonths(6);
        $endDate = now()->endOfMonth()->addMonths($months - 1);

        $historicalData = Order::where('status', 'delivered')
            ->where('order_date', '>=', $startDate)
            ->where('order_date', '<=', now())
            ->selectRaw('DATE_FORMAT(order_date, "%Y-%m") as month, SUM(total_amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $forecast = [];
        $averageRevenue = $historicalData->avg('total') ?: 0;
        $current = now()->startOfMonth();

        for ($i = 0; $i < $months; $i++) {
            $monthKey = $current->format('Y-m');
            $actual = isset($historicalData[$monthKey]) ? (float) $historicalData[$monthKey]['total'] : null;
            $forecast[] = [
                'month' => $monthKey,
                'actual' => $actual,
                'forecast' => $actual ?? round($averageRevenue, 2),
            ];
            $current->addMonth();
        }

        return $forecast;
    }

    public function getCrmPipelineSummary(): array
    {
        $stages = ['lead', 'qualified', 'proposal', 'negotiation', 'closed_won', 'closed_lost'];
        $summary = [];

        foreach ($stages as $stage) {
            $items = CrmPipeline::where('stage', $stage)->where('status', 'active')->get();
            $summary[] = [
                'stage' => $stage,
                'count' => $items->count(),
                'total_value' => (float) $items->sum('value'),
                'weighted_value' => $items->sum(function ($item) {
                    return (float) $item->value * $item->probability / 100;
                }),
            ];
        }

        return $summary;
    }

    public function getCustomerOrders(Customer $customer): Collection
    {
        return $customer->orders()->with('items')->orderBy('created_at', 'desc')->get();
    }

    public function getCustomerInvoices(Customer $customer): Collection
    {
        return $customer->invoices()->orderBy('created_at', 'desc')->get();
    }
}