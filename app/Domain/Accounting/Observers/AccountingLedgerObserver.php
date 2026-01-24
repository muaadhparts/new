<?php

namespace App\Domain\Accounting\Observers;

use App\Domain\Accounting\Models\AccountingLedger;
use App\Domain\Accounting\Models\AccountBalance;
use Illuminate\Support\Facades\Log;

/**
 * Accounting Ledger Observer
 *
 * Handles AccountingLedger model lifecycle events.
 */
class AccountingLedgerObserver
{
    /**
     * Handle the AccountingLedger "creating" event.
     */
    public function creating(AccountingLedger $entry): void
    {
        // Get current balance
        $balance = AccountBalance::firstOrCreate(
            ['user_id' => $entry->user_id],
            ['current_balance' => 0, 'pending_balance' => 0]
        );

        $entry->balance_before = $balance->current_balance;

        // Calculate balance after
        if ($entry->type === 'credit') {
            $entry->balance_after = $entry->balance_before + $entry->amount;
        } else {
            $entry->balance_after = $entry->balance_before - $entry->amount;
        }
    }

    /**
     * Handle the AccountingLedger "created" event.
     */
    public function created(AccountingLedger $entry): void
    {
        // Update account balance
        $balance = AccountBalance::where('user_id', $entry->user_id)->first();

        if ($balance) {
            $balance->current_balance = $entry->balance_after;
            $balance->save();
        }

        Log::channel('accounting')->info('Ledger entry created', [
            'user_id' => $entry->user_id,
            'type' => $entry->type,
            'amount' => $entry->amount,
            'balance_after' => $entry->balance_after,
        ]);
    }

    /**
     * Handle the AccountingLedger "deleted" event.
     */
    public function deleted(AccountingLedger $entry): void
    {
        // Recalculate balance (should rarely happen)
        Log::channel('accounting')->warning('Ledger entry deleted', [
            'id' => $entry->id,
            'user_id' => $entry->user_id,
            'amount' => $entry->amount,
        ]);
    }
}
