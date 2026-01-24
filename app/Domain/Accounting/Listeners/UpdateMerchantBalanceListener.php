<?php

namespace App\Domain\Accounting\Listeners;

use App\Domain\Accounting\Models\AccountBalance;
use App\Domain\Accounting\Models\AccountingLedger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Update Merchant Balance Listener
 *
 * Updates merchant balance when transactions occur.
 */
class UpdateMerchantBalanceListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public int $backoff = 30;

    /**
     * Handle the event.
     */
    public function handle($event): void
    {
        $merchantId = $event->merchantId ?? null;
        $amount = $event->amount ?? 0;
        $type = $event->type ?? 'credit';

        if (!$merchantId || !$amount) {
            return;
        }

        DB::transaction(function () use ($merchantId, $amount, $type, $event) {
            $balance = AccountBalance::firstOrCreate(
                ['user_id' => $merchantId],
                ['current_balance' => 0, 'pending_balance' => 0]
            );

            $previousBalance = $balance->current_balance;

            if ($type === 'credit') {
                $balance->current_balance += $amount;
            } else {
                $balance->current_balance -= $amount;
            }

            $balance->save();

            // Log the transaction
            AccountingLedger::create([
                'user_id' => $merchantId,
                'type' => $type,
                'amount' => $amount,
                'balance_before' => $previousBalance,
                'balance_after' => $balance->current_balance,
                'reference' => $event->reference ?? null,
                'description' => $event->description ?? null,
            ]);

            Log::info('Merchant balance updated', [
                'merchant_id' => $merchantId,
                'type' => $type,
                'amount' => $amount,
                'new_balance' => $balance->current_balance,
            ]);
        });
    }

    /**
     * Handle a job failure.
     */
    public function failed($event, \Throwable $exception): void
    {
        Log::error('Failed to update merchant balance', [
            'merchant_id' => $event->merchantId ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
