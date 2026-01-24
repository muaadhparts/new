<?php

namespace App\Domain\Accounting\Observers;

use App\Domain\Accounting\Models\Withdraw;
use App\Domain\Accounting\Models\AccountBalance;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Withdraw Observer
 *
 * Handles Withdraw model lifecycle events.
 */
class WithdrawObserver
{
    /**
     * Handle the Withdraw "creating" event.
     */
    public function creating(Withdraw $withdraw): void
    {
        // Generate reference number if not set
        if (empty($withdraw->reference)) {
            $withdraw->reference = $this->generateReference();
        }

        // Set default status
        if (empty($withdraw->status)) {
            $withdraw->status = 'pending';
        }
    }

    /**
     * Handle the Withdraw "created" event.
     */
    public function created(Withdraw $withdraw): void
    {
        // Update pending balance
        $balance = AccountBalance::where('user_id', $withdraw->user_id)->first();

        if ($balance) {
            $balance->pending_balance += $withdraw->amount;
            $balance->save();
        }

        Log::channel('accounting')->info('Withdrawal request created', [
            'reference' => $withdraw->reference,
            'user_id' => $withdraw->user_id,
            'amount' => $withdraw->amount,
        ]);
    }

    /**
     * Handle the Withdraw "updating" event.
     */
    public function updating(Withdraw $withdraw): void
    {
        if ($withdraw->isDirty('status')) {
            $withdraw->processed_at = now();
        }
    }

    /**
     * Handle the Withdraw "updated" event.
     */
    public function updated(Withdraw $withdraw): void
    {
        if ($withdraw->wasChanged('status')) {
            $balance = AccountBalance::where('user_id', $withdraw->user_id)->first();

            if ($balance) {
                // Remove from pending
                $balance->pending_balance -= $withdraw->amount;

                // If approved, deduct from current balance
                if ($withdraw->status === 'approved') {
                    $balance->current_balance -= $withdraw->amount;
                }

                $balance->save();
            }

            Log::channel('accounting')->info('Withdrawal status updated', [
                'reference' => $withdraw->reference,
                'status' => $withdraw->status,
            ]);
        }
    }

    /**
     * Generate unique reference number.
     */
    protected function generateReference(): string
    {
        $prefix = 'WD';
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(6));

        return "{$prefix}-{$date}-{$random}";
    }
}
