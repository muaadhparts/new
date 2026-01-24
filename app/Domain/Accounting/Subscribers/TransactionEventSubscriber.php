<?php

namespace App\Domain\Accounting\Subscribers;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

/**
 * Transaction Event Subscriber
 *
 * Handles all transaction-related events in one place.
 */
class TransactionEventSubscriber
{
    /**
     * Handle transaction recorded events.
     */
    public function handleTransactionRecorded($event): void
    {
        Log::channel('accounting')->info('Transaction recorded', [
            'ledger_id' => $event->ledger->id ?? null,
            'type' => $event->ledger->type ?? null,
            'amount' => $event->ledger->amount ?? null,
        ]);
    }

    /**
     * Handle balance updated events.
     */
    public function handleBalanceUpdated($event): void
    {
        Log::channel('accounting')->info('Balance updated', [
            'merchant_id' => $event->merchantId ?? null,
            'old_balance' => $event->oldBalance ?? null,
            'new_balance' => $event->newBalance ?? null,
        ]);
    }

    /**
     * Handle commission calculated events.
     */
    public function handleCommissionCalculated($event): void
    {
        Log::channel('accounting')->info('Commission calculated', [
            'order_id' => $event->orderId ?? null,
            'commission' => $event->commission ?? null,
        ]);
    }

    /**
     * Handle settlement processed events.
     */
    public function handleSettlementProcessed($event): void
    {
        Log::channel('accounting')->info('Settlement processed', [
            'batch_id' => $event->batchId ?? null,
            'total_amount' => $event->totalAmount ?? null,
            'merchants_count' => $event->merchantsCount ?? null,
        ]);
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            'App\Domain\Accounting\Events\TransactionRecorded' => 'handleTransactionRecorded',
            'App\Domain\Accounting\Events\BalanceUpdated' => 'handleBalanceUpdated',
            'App\Domain\Accounting\Events\CommissionCalculated' => 'handleCommissionCalculated',
            'App\Domain\Accounting\Events\SettlementProcessed' => 'handleSettlementProcessed',
        ];
    }
}
