<?php

namespace App\Domain\Accounting\Listeners;

use App\Domain\Accounting\Notifications\WithdrawRequestedNotification;
use App\Domain\Accounting\Notifications\WithdrawApprovedNotification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Send Withdraw Notification Listener
 *
 * Sends notifications for withdrawal status changes.
 */
class SendWithdrawNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    /**
     * Handle the event.
     */
    public function handle($event): void
    {
        $withdraw = $event->withdraw ?? null;
        $status = $event->status ?? 'pending';

        if (!$withdraw) {
            return;
        }

        $merchant = User::find($withdraw->user_id);

        if (!$merchant) {
            Log::warning('Withdraw notification: Merchant not found', [
                'withdraw_id' => $withdraw->id,
            ]);
            return;
        }

        $this->sendNotification($merchant, $withdraw, $status);
    }

    /**
     * Send appropriate notification based on status.
     */
    protected function sendNotification(User $merchant, $withdraw, string $status): void
    {
        switch ($status) {
            case 'pending':
                $merchant->notify(new WithdrawRequestedNotification($withdraw));
                break;

            case 'approved':
                $merchant->notify(new WithdrawApprovedNotification($withdraw));
                break;

            default:
                Log::info('Withdraw status notification', [
                    'withdraw_id' => $withdraw->id,
                    'status' => $status,
                ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed($event, \Throwable $exception): void
    {
        Log::error('Failed to send withdraw notification', [
            'error' => $exception->getMessage(),
        ]);
    }
}
