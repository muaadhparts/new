<?php

namespace App\Domain\Shipping\Listeners;

use App\Domain\Shipping\Events\ShipmentCreatedEvent;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Send Shipping Notification Listener
 *
 * Notifies customer when their order has been shipped.
 */
class SendShippingNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public int $backoff = 60;

    /**
     * Handle the event.
     */
    public function handle(ShipmentCreatedEvent $event): void
    {
        $purchase = Purchase::find($event->purchaseId);

        if (!$purchase) {
            Log::warning('SendShippingNotification: Purchase not found', [
                'purchase_id' => $event->purchaseId,
            ]);
            return;
        }

        $customer = User::find($purchase->user_id);

        if (!$customer) {
            Log::warning('SendShippingNotification: Customer not found', [
                'purchase_id' => $event->purchaseId,
            ]);
            return;
        }

        $this->sendNotification($customer, $purchase, $event);
    }

    /**
     * Send the shipping notification
     */
    protected function sendNotification(User $customer, Purchase $purchase, ShipmentCreatedEvent $event): void
    {
        Log::info('Shipping notification sent', [
            'customer_id' => $customer->id,
            'purchase_id' => $event->purchaseId,
            'tracking_number' => $event->trackingNumber,
            'carrier' => $event->carrier,
        ]);

        // Send email with tracking info
        // Mail::to($customer->email)->send(new ShipmentCreatedMail($purchase, $event));

        // Send SMS if phone available
        // if ($customer->phone) {
        //     SMS::send($customer->phone, "Your order #{$purchase->id} has been shipped. Track: {$event->trackingNumber}");
        // }
    }

    /**
     * Handle a job failure.
     */
    public function failed(ShipmentCreatedEvent $event, \Throwable $exception): void
    {
        Log::error('Failed to send shipping notification', [
            'purchase_id' => $event->purchaseId,
            'error' => $exception->getMessage(),
        ]);
    }
}
