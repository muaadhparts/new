<?php

namespace App\Domain\Shipping\Schedule;

use App\Domain\Shipping\Models\ShipmentTracking;
use App\Domain\Shipping\Notifications\ShipmentStatusNotification;
use Illuminate\Support\Facades\Log;

/**
 * Notify Delivery Delays Task
 *
 * Notifies customers about delayed shipments.
 */
class NotifyDeliveryDelaysTask
{
    /**
     * Execute the task.
     */
    public function __invoke(): void
    {
        $delayedShipments = ShipmentTracking::whereNotIn('status', ['delivered', 'failed', 'cancelled'])
            ->whereNotNull('estimated_delivery')
            ->where('estimated_delivery', '<', now())
            ->with(['merchantPurchase.purchase.user'])
            ->get();

        $notified = 0;

        foreach ($delayedShipments as $shipment) {
            $user = $shipment->merchantPurchase?->purchase?->user;

            if ($user) {
                $user->notify(new ShipmentStatusNotification(
                    $shipment,
                    'Your shipment is delayed. We apologize for the inconvenience.'
                ));
                $notified++;
            }
        }

        Log::info('Delivery delay notifications sent', [
            'delayed_shipments' => $delayedShipments->count(),
            'notifications_sent' => $notified,
        ]);
    }

    /**
     * Get the schedule frequency.
     */
    public static function frequency(): string
    {
        return 'dailyAt';
    }

    /**
     * Get the schedule time.
     */
    public static function at(): string
    {
        return '09:00';
    }
}
