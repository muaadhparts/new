<?php

namespace App\Domain\Commerce\Listeners;

use App\Domain\Commerce\Events\PurchaseStatusChangedEvent;
use App\Domain\Commerce\Models\Purchase;
use App\Classes\MuaadhMailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Notify Purchase Status Change Listener
 *
 * Notifies customer when purchase status changes.
 *
 * Channel Independence: This listener handles status change notifications
 * for ALL channels (Web, Mobile, API, WhatsApp).
 */
class NotifyPurchaseStatusChangeListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public int $backoff = 60;
    public string $queue = 'notifications';

    /**
     * Statuses that warrant customer notification
     */
    protected array $notifiableStatuses = [
        'confirmed',
        'processing',
        'shipped',
        'delivered',
        'cancelled',
        'refunded',
    ];

    /**
     * Handle the event.
     */
    public function handle(PurchaseStatusChangedEvent $event): void
    {
        // Only notify for significant status changes
        if (!in_array($event->newStatus, $this->notifiableStatuses)) {
            return;
        }

        $purchase = Purchase::find($event->purchaseId);

        if (!$purchase) {
            Log::warning('NotifyPurchaseStatusChange: Purchase not found', [
                'purchase_id' => $event->purchaseId,
            ]);
            return;
        }

        $customerEmail = $purchase->customer_email;

        if (!$customerEmail) {
            Log::warning('NotifyPurchaseStatusChange: Customer email not found', [
                'purchase_id' => $event->purchaseId,
            ]);
            return;
        }

        $this->sendStatusNotification($purchase, $event);

        Log::channel('domain')->info('Purchase status notification sent', [
            'event_id' => $event->eventId,
            'purchase_id' => $event->purchaseId,
            'new_status' => $event->newStatus,
        ]);
    }

    /**
     * Send status change notification
     */
    protected function sendStatusNotification(Purchase $purchase, PurchaseStatusChangedEvent $event): void
    {
        $mailer = new MuaadhMailer();

        $mailer->sendAutoPurchaseMail([
            'order_id' => $purchase->id,
            'purchase_number' => $purchase->purchase_number,
            'customer_name' => $purchase->customer_name,
            'customer_email' => $purchase->customer_email,
            'type' => 'purchase_status_update',
            'previous_status' => $event->previousStatus,
            'new_status' => $event->newStatus,
            'status_label' => $this->getStatusLabel($event->newStatus),
        ], $purchase->customer_email);
    }

    /**
     * Get human-readable status label
     */
    protected function getStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => __('Pending'),
            'confirmed' => __('Confirmed'),
            'processing' => __('Processing'),
            'shipped' => __('Shipped'),
            'delivered' => __('Delivered'),
            'cancelled' => __('Cancelled'),
            'refunded' => __('Refunded'),
            default => $status,
        };
    }

    /**
     * Handle a job failure.
     */
    public function failed(PurchaseStatusChangedEvent $event, \Throwable $exception): void
    {
        Log::error('Failed to send purchase status notification', [
            'purchase_id' => $event->purchaseId,
            'new_status' => $event->newStatus,
            'error' => $exception->getMessage(),
        ]);
    }
}
