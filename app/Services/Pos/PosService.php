<?php

namespace App\Services\Pos;

use App\Models\Inventory\StockMovement;
use App\Models\Pos\Payment;
use App\Models\Pos\Transaction;
use Illuminate\Support\Facades\DB;

class PosService
{
    public function processCheckout(Transaction $transaction, array $payments, int $userId): Transaction
    {
        return DB::transaction(function () use ($transaction, $payments, $userId) {
            foreach ($payments as $paymentData) {
                Payment::create([
                    'transaction_id' => $transaction->id,
                    'method' => $paymentData['method'],
                    'amount' => $paymentData['amount'],
                    'reference' => $paymentData['reference'] ?? null,
                    'received_by' => $userId,
                ]);
            }

            $transaction->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $this->deductStock($transaction);

            return $transaction->fresh(['items', 'payments', 'terminal', 'customer']);
        });
    }

    public function voidTransaction(Transaction $transaction, string $reason, int $userId): Transaction
    {
        return DB::transaction(function () use ($transaction, $reason) {
            $transaction->update([
                'status' => 'voided',
                'notes' => ($transaction->notes ? $transaction->notes."\n" : '')."VOIDED: {$reason}",
            ]);

            return $transaction->fresh();
        });
    }

    public function refundTransaction(Transaction $transaction, array $refundData, int $userId): Transaction
    {
        return DB::transaction(function () use ($transaction, $refundData, $userId) {
            $totalRefunded = Payment::where('transaction_id', $transaction->id)
                ->where('method', '!=', 'refund')
                ->sum('amount');

            if ($refundData['amount'] > $totalRefunded) {
                throw new \InvalidArgumentException('Refund amount exceeds original payment amount');
            }

            Payment::create([
                'transaction_id' => $transaction->id,
                'method' => 'other',
                'amount' => -$refundData['amount'],
                'reference' => 'REFUND: '.($refundData['reason'] ?? ''),
                'received_by' => $userId,
            ]);

            $transaction->update([
                'status' => 'refunded',
                'notes' => ($transaction->notes ? $transaction->notes."\n" : '')."REFUNDED: {$refundData['reason']}",
            ]);

            return $transaction->fresh(['items', 'payments']);
        });
    }

    private function deductStock(Transaction $transaction): void
    {
        $items = $transaction->items()->whereNotNull('product_id')->get();

        foreach ($items as $item) {
            StockMovement::create([
                'product_id' => $item->product_id,
                'warehouse_id' => $transaction->terminal->warehouse_id,
                'type' => 'out',
                'quantity' => $item->quantity,
                'unit_cost' => $item->unit_price,
                'total_cost' => $item->line_total,
                'reference_type' => 'pos_transaction',
                'reference_id' => $transaction->id,
                'description' => "POS Sale #{$transaction->transaction_number}",
                'created_by' => $transaction->created_by,
                'movement_date' => now(),
            ]);
        }
    }
}
