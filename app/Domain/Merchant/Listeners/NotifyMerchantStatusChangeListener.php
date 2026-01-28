<?php

namespace App\Domain\Merchant\Listeners;

use App\Domain\Merchant\Events\MerchantStatusChangedEvent;
use App\Domain\Identity\Models\User;
use App\Classes\MuaadhMailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Notify Merchant Status Change Listener
 *
 * Notifies merchant when their account status changes.
 *
 * Channel Independence: This listener handles status change notifications
 * for ALL channels (Web, Mobile, API, WhatsApp).
 */
class NotifyMerchantStatusChangeListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public int $backoff = 60;
    public string $queue = 'notifications';

    /**
     * Handle the event.
     */
    public function handle(MerchantStatusChangedEvent $event): void
    {
        $merchant = User::find($event->merchantId);

        if (!$merchant) {
            Log::warning('NotifyMerchantStatusChange: Merchant not found', [
                'merchant_id' => $event->merchantId,
            ]);
            return;
        }

        if (!$merchant->email) {
            Log::warning('NotifyMerchantStatusChange: Merchant email not found', [
                'merchant_id' => $event->merchantId,
            ]);
            return;
        }

        $this->sendStatusNotification($merchant, $event);

        Log::channel('domain')->info('Merchant status notification sent', [
            'event_id' => $event->eventId,
            'merchant_id' => $event->merchantId,
            'new_status' => $event->newStatus,
        ]);
    }

    /**
     * Send status change notification
     */
    protected function sendStatusNotification(User $merchant, MerchantStatusChangedEvent $event): void
    {
        // Determine email type based on status change
        $emailType = match (true) {
            $event->wasActivated() => 'merchant_approved',
            $event->wasSuspended() => 'merchant_suspended',
            $event->wasDeactivated() => 'merchant_deactivated',
            default => null,
        };

        if (!$emailType) {
            return;
        }

        $mailer = new MuaadhMailer();

        $mailer->sendAutoMail([
            'to' => $merchant->email,
            'type' => $emailType,
            'cname' => $merchant->name,
            'merchant_name' => $merchant->shop_name ?? $merchant->name,
            'reason' => $event->reason,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(MerchantStatusChangedEvent $event, \Throwable $exception): void
    {
        Log::error('Failed to send merchant status notification', [
            'merchant_id' => $event->merchantId,
            'new_status' => $event->newStatus,
            'error' => $exception->getMessage(),
        ]);
    }
}
