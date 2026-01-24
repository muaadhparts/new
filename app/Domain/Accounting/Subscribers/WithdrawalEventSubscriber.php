<?php

namespace App\Domain\Accounting\Subscribers;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

/**
 * Withdrawal Event Subscriber
 *
 * Handles all withdrawal-related events in one place.
 */
class WithdrawalEventSubscriber
{
    /**
     * Handle withdrawal requested events.
     */
    public function handleWithdrawalRequested($event): void
    {
        Log::channel('accounting')->info('Withdrawal requested', [
            'withdraw_id' => $event->withdraw->id ?? null,
            'merchant_id' => $event->withdraw->merchant_id ?? null,
            'amount' => $event->withdraw->amount ?? null,
        ]);
    }

    /**
     * Handle withdrawal approved events.
     */
    public function handleWithdrawalApproved($event): void
    {
        Log::channel('accounting')->info('Withdrawal approved', [
            'withdraw_id' => $event->withdraw->id ?? null,
            'approved_by' => $event->approvedBy ?? null,
        ]);
    }

    /**
     * Handle withdrawal rejected events.
     */
    public function handleWithdrawalRejected($event): void
    {
        Log::channel('accounting')->warning('Withdrawal rejected', [
            'withdraw_id' => $event->withdraw->id ?? null,
            'reason' => $event->reason ?? null,
        ]);
    }

    /**
     * Handle withdrawal completed events.
     */
    public function handleWithdrawalCompleted($event): void
    {
        Log::channel('accounting')->info('Withdrawal completed', [
            'withdraw_id' => $event->withdraw->id ?? null,
            'transaction_reference' => $event->transactionReference ?? null,
        ]);
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            'App\Domain\Accounting\Events\WithdrawalRequested' => 'handleWithdrawalRequested',
            'App\Domain\Accounting\Events\WithdrawalApproved' => 'handleWithdrawalApproved',
            'App\Domain\Accounting\Events\WithdrawalRejected' => 'handleWithdrawalRejected',
            'App\Domain\Accounting\Events\WithdrawalCompleted' => 'handleWithdrawalCompleted',
        ];
    }
}
