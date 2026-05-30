<?php

namespace App\Services\Finance;

use App\Models\Finance\JournalEntry;
use App\Models\Finance\JournalEntryLine;
use App\Models\Finance\Account;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class JournalService
{
    public function createJournalEntry(array $data, array $lines): JournalEntry
    {
        return DB::transaction(function () use ($data, $lines) {
            $data['created_by'] = $data['created_by'] ?? auth()->id();
            $data['status'] = $data['status'] ?? 'draft';

            $totalDebit = 0;
            $totalCredit = 0;

            foreach ($lines as $line) {
                if ($line['type'] === 'debit') {
                    $totalDebit += (float) $line['amount'];
                } else {
                    $totalCredit += (float) $line['amount'];
                }
            }

            $data['total_debit'] = $totalDebit;
            $data['total_credit'] = $totalCredit;

            $entry = JournalEntry::create($data);

            foreach ($lines as $line) {
                $entry->lines()->create($line);
            }

            // Post entries immediately if status is 'posted'
            if ($data['status'] === 'posted') {
                $this->postJournalEntry($entry);
            }

            return $entry->load('lines');
        });
    }

    public function postJournalEntry(JournalEntry $entry): JournalEntry
    {
        if ($entry->status !== 'draft') {
            throw new \RuntimeException('Only draft journal entries can be posted');
        }

        if (abs($entry->total_debit - $entry->total_credit) > 0.01) {
            throw new \RuntimeException('Journal entry is not balanced. Debits must equal credits');
        }

        return DB::transaction(function () use ($entry) {
            $entry->update([
                'status' => 'posted',
                'approved_by' => auth()->id(),
                'posted_at' => now(),
            ]);

            // Update account balances for each line
            foreach ($entry->lines as $line) {
                $account = Account::findOrFail($line->account_id);
                $newBalance = (float) $account->current_balance;

                if ($line->type === 'debit') {
                    if (in_array($account->type, ['asset', 'expense'])) {
                        $newBalance += (float) $line->amount;
                    } else {
                        $newBalance -= (float) $line->amount;
                    }
                } else {
                    if (in_array($account->type, ['liability', 'equity', 'revenue'])) {
                        $newBalance += (float) $line->amount;
                    } else {
                        $newBalance -= (float) $line->amount;
                    }
                }

                $account->update(['current_balance' => $newBalance]);
            }

            return $entry->fresh()->load('lines');
        });
    }

    public function voidJournalEntry(JournalEntry $entry): JournalEntry
    {
        if ($entry->status !== 'posted') {
            throw new \RuntimeException('Only posted journal entries can be voided');
        }

        return DB::transaction(function () use ($entry) {
            // Reverse all account balance changes
            foreach ($entry->lines as $line) {
                $account = Account::findOrFail($line->account_id);
                $reversalType = $line->type === 'debit' ? 'credit' : 'debit';
                $newBalance = (float) $account->current_balance;

                if ($reversalType === 'debit') {
                    if (in_array($account->type, ['asset', 'expense'])) {
                        $newBalance += (float) $line->amount;
                    } else {
                        $newBalance -= (float) $line->amount;
                    }
                } else {
                    if (in_array($account->type, ['liability', 'equity', 'revenue'])) {
                        $newBalance += (float) $line->amount;
                    } else {
                        $newBalance -= (float) $line->amount;
                    }
                }

                $account->update(['current_balance' => $newBalance]);
            }

            $entry->update(['status' => 'void']);

            return $entry->fresh()->load('lines');
        });
    }

    public function getGeneralLedger(?string $startDate = null, ?string $endDate = null): Collection
    {
        $query = JournalEntry::with(['lines.account', 'creator'])
            ->orderBy('entry_date', 'desc');

        if ($startDate) {
            $query->where('entry_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('entry_date', '<=', $endDate);
        }

        return $query->get();
    }

    public function getCashFlow(?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfYear()->toDateString();
        $endDate = $endDate ?? now()->toDateString();

        $cashAccounts = Account::where('type', 'asset')
            ->whereIn('category', ['cash', 'bank'])
            ->where('is_active', true)
            ->get();

        $openingBalance = 0;
        $closingBalance = 0;
        $movements = [];

        foreach ($cashAccounts as $account) {
            $openingBalance += $account->opening_balance;
            $closingBalance += (float) $account->current_balance;
        }

        return [
            'period' => ['start' => $startDate, 'end' => $endDate],
            'opening_balance' => $openingBalance,
            'closing_balance' => $closingBalance,
            'net_change' => $closingBalance - $openingBalance,
        ];
    }
}